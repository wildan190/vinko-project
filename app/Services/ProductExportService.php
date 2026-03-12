<?php

namespace App\Services;

use App\Models\ProductExport;
use App\Imports\ProductExportImport;
use App\Jobs\MergeProductImageJob;
use App\Jobs\ProcessImageUploadJob;
use App\Jobs\ImportProductRowJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use ZipArchive;

class ProductExportService
{
    public function importFromExcel($file)
    {
        $data = Excel::toArray(new ProductExportImport, $file);
        $rows = $data[0] ?? [];

        if (empty($rows)) {
            return ['error' => 'The uploaded file is empty.'];
        }

        $jobs = [];
        foreach ($rows as $row) {
            $jobs[] = new ImportProductRowJob($row);
        }

        $batch = Bus::batch($jobs)->name('Excel Data Import')->dispatch();

        return [
            'success' => true,
            'batchId' => $batch->id,
            'total' => count($rows)
        ];
    }

    public function processSingleImage(ProductExport $product, $file)
    {
        $this->deleteProductImages($product);

        $path = $file->store('product_images', 'public');
        
        try {
            $manager = new ImageManager(new ImagickDriver());
            $thumbnail = $manager->read(Storage::disk('public')->path($path));
            $thumbnail->scale(width: 200);
            
            if (!Storage::disk('public')->exists('thumbnails')) {
                Storage::disk('public')->makeDirectory('thumbnails');
            }
            
            $thumbnailPath = 'thumbnails/' . basename($path);
            $thumbnail->toPng()->save(Storage::disk('public')->path($thumbnailPath));
        } catch (\Exception $e) {
            // Log or handle error
        }

        $product->update([
            'image_path' => $path,
            'merged_image' => null
        ]);

        return $product;
    }

    public function startMergeProcess($selectedIds = [])
    {
        $query = ProductExport::whereNotNull('image_path')
            ->where('image_path', 'not like', '=_xlfn%');

        if (!empty($selectedIds)) {
            $query->whereIn('id', $selectedIds);
        } else {
            $query->whereNull('merged_image');
        }

        $products = $query->get()->filter(function ($product) {
            return Storage::disk('public')->exists($product->image_path);
        });

        if ($products->isEmpty()) {
            $message = !empty($selectedIds) 
                ? 'Selected products have no valid images or images have not been uploaded.' 
                : 'No new products with valid uploaded images found to merge.';
            return ['error' => $message];
        }

        $jobs = $products->map(function ($product) {
            return new MergeProductImageJob($product);
        });

        $batch = Bus::batch($jobs)->name('Merge Product Images')->dispatch();

        return [
            'batchId' => $batch->id,
            'total' => $products->count()
        ];
    }

    public function deleteProductImages(ProductExport $product)
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
            Storage::disk('public')->delete('thumbnails/' . basename($product->image_path));
        }
        if ($product->merged_image) {
            Storage::disk('public')->delete($product->merged_image);
            Storage::disk('public')->delete('thumbnails/merged/' . basename($product->merged_image));
        }
    }

    public function bulkDelete($ids)
    {
        if (empty($ids)) {
            return ['error' => 'No items selected.'];
        }

        $products = ProductExport::whereIn('id', $ids)->get();
        foreach ($products as $product) {
            $this->deleteProductImages($product);
            $product->delete();
        }

        return ['success' => true, 'message' => count($ids) . ' items deleted successfully.'];
    }

    public function generateMergedZip($selectedIds = null)
    {
        $query = ProductExport::whereNotNull('merged_image');
        
        if (!empty($selectedIds)) {
            if (is_string($selectedIds)) {
                $selectedIds = explode(',', $selectedIds);
            }
            $query->whereIn('id', $selectedIds);
        }

        $products = $query->get();

        if ($products->isEmpty()) {
            return ['error' => 'No merged images found to download.'];
        }

        $zip = new ZipArchive();
        $zipFileName = 'merged_images_' . now()->format('Ymd_His') . '.zip';
        $tempFile = tempnam(sys_get_temp_dir(), 'zip');

        if ($zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($products as $product) {
                if (Storage::disk('public')->exists($product->merged_image)) {
                    $filePath = Storage::disk('public')->path($product->merged_image);
                    $fileNameInZip = $product->order_number . '_' . basename($product->merged_image);
                    $zip->addFile($filePath, $fileNameInZip);
                }
            }
            $zip->close();
        }

        return [
            'file' => $tempFile,
            'name' => $zipFileName
        ];
    }

    public function transformProduct(ProductExport $product)
    {
        $hasValidImage = $product->image_path && 
                         !str_starts_with($product->image_path, '=_xlfn') && 
                         Storage::disk('public')->exists($product->image_path);
        
        $thumbnailPath = $product->image_path ? 'thumbnails/' . basename($product->image_path) : null;
        $displayPath = ($thumbnailPath && Storage::disk('public')->exists($thumbnailPath)) ? $thumbnailPath : $product->image_path;
        
        $mergedThumbnailPath = $product->merged_image ? 'thumbnails/merged/' . basename($product->merged_image) : null;
        $displayMergedPath = ($mergedThumbnailPath && Storage::disk('public')->exists($mergedThumbnailPath)) ? $mergedThumbnailPath : $product->merged_image;

        return [
            'id' => $product->id,
            'order_number' => $product->order_number,
            'sku_platform' => $product->sku_platform,
            'product_specification' => $product->product_specification,
            'product_id' => $product->product_id,
            'sku_id' => $product->sku_id,
            'product_image_url' => $product->product_image_url,
            'quantity' => $product->quantity,
            'tracking_number' => $product->tracking_number,
            'created_at_formatted' => $product->created_at->format('Y-m-d H:i'),
            'created_at' => $product->created_at->toIso8601String(),
            'image_url' => $hasValidImage ? Storage::url($displayPath) : null,
            'merged_image_url' => $product->merged_image ? Storage::url($product->merged_image) : null,
            'merged_thumbnail_url' => $product->merged_image ? Storage::url($displayMergedPath) : null,
            'has_valid_image' => $hasValidImage,
            'is_merged' => (bool)$product->merged_image,
            'upload_url' => route('product-export.upload', $product),
            'delete_url' => route('product-export.destroy', $product),
        ];
    }
}
