<?php

namespace App\Http\Controllers;

use App\Models\ProductExport;
use App\Imports\ProductExportImport;
use App\Exports\ProductExportExport;
use App\Jobs\MergeProductImageJob;
use App\Jobs\ProcessImageUploadJob;
use App\Jobs\ImportProductRowJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;

class ProductExportController extends Controller
{
    public function index()
    {
        $products = ProductExport::orderBy('order_number', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(20); // Increased pagination for better grouping view
        return view('pages.product-export.index', compact('products'), ['title' => 'Product Export Management']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv'
        ]);

        $data = Excel::toArray(new ProductExportImport, $request->file('file'));
        $rows = $data[0] ?? [];

        if (empty($rows)) {
            return response()->json(['error' => 'The uploaded file is empty.'], 400);
        }

        $jobs = [];
        foreach ($rows as $row) {
            $jobs[] = new ImportProductRowJob($row);
        }

        $batch = Bus::batch($jobs)->name('Excel Data Import')->dispatch();

        return response()->json([
            'success' => true,
            'batchId' => $batch->id,
            'total' => count($rows)
        ]);
    }

    public function export()
    {
        return Excel::download(new ProductExportExport, 'product_exports.xlsx');
    }

    public function exportTemplate()
    {
        // Export an empty collection to just get the headings
        return Excel::download(new class extends ProductExportExport {
            public function collection() { return collect(); }
        }, 'product_export_template.xlsx');
    }

    public function uploadImage(Request $request, ProductExport $product)
    {
        $request->validate([
            'image' => 'required|image|max:512000', 
        ]);

        $this->processSingleImage($product, $request->file('image'));

        return response()->json(['success' => true]);
    }

    public function bulkUpload(Request $request)
    {
        $request->validate([
            'images.*' => 'required|image|max:512000',
        ]);

        $files = $request->file('images');
        $successCount = 0;
        $jobs = [];

        foreach ($files as $file) {
            $originalName = $file->getClientOriginalName();
            $sku = pathinfo($originalName, PATHINFO_FILENAME);

            $product = ProductExport::where('sku_platform', $sku)
                ->orWhere('order_number', $sku)
                ->first();

            if ($product) {
                // Store file temporarily to be processed by job
                $path = $file->store('temp_uploads', 'public');
                $jobs[] = new ProcessImageUploadJob($product, $path);
                $successCount++;
            }
        }

        if (empty($jobs)) {
            return response()->json(['error' => 'No matching products found for these images.'], 400);
        }

        $batch = Bus::batch($jobs)->name('Bulk Image Upload')->dispatch();

        return response()->json([
            'success' => true,
            'batchId' => $batch->id,
            'message' => "Starting background processing for $successCount images."
        ]);
    }

    private function processSingleImage($product, $file)
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
            Storage::disk('public')->delete('thumbnails/' . basename($product->image_path));
        }

        $path = $file->store('product_images', 'public');
        
        try {
            $manager = new ImageManager(new GdDriver());
            $thumbnail = $manager->read($file);
            $thumbnail->scale(width: 200);
            
            if (!Storage::disk('public')->exists('thumbnails')) {
                Storage::disk('public')->makeDirectory('thumbnails');
            }
            
            $thumbnailPath = 'thumbnails/' . basename($path);
            $thumbnail->toPng()->save(Storage::disk('public')->path($thumbnailPath));
        } catch (\Exception $e) {
            // Log or handle error
        }

        $product->update(['image_path' => $path]);
    }

    public function processMerge()
    {
        $products = ProductExport::whereNotNull('image_path')->get();

        if ($products->isEmpty()) {
            return response()->json(['error' => 'No products with images found.'], 400);
        }

        $jobs = $products->map(function ($product) {
            return new MergeProductImageJob($product);
        });

        $batch = Bus::batch($jobs)->name('Merge Product Images')->dispatch();

        return response()->json([
            'batchId' => $batch->id,
            'total' => $products->count()
        ]);
    }

    public function getBatchStatus($batchId)
    {
        return Bus::findBatch($batchId);
    }

    public function destroy(ProductExport $product)
    {
        $this->deleteProductImages($product);
        $product->delete();

        return back()->with('success', 'Record and all associated images deleted successfully.');
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['error' => 'No items selected.'], 400);
        }

        $products = ProductExport::whereIn('id', $ids)->get();
        foreach ($products as $product) {
            $this->deleteProductImages($product);
            $product->delete();
        }

        return response()->json(['success' => true, 'message' => count($ids) . ' items deleted successfully.']);
    }

    private function deleteProductImages(ProductExport $product)
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
            Storage::disk('public')->delete('thumbnails/' . basename($product->image_path));
        }
        if ($product->merged_image) {
            Storage::disk('public')->delete($product->merged_image);
        }
    }
}
