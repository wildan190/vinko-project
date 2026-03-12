<?php

namespace App\Http\Controllers;

use App\Models\ProductExport;
use App\Services\ProductExportService;
use App\Http\Requests\ProductExport\ImportRequest;
use App\Http\Requests\ProductExport\UploadImageRequest;
use App\Http\Requests\ProductExport\BulkDeleteRequest;
use App\Http\Requests\ProductExport\ProcessMergeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductExportExport;

class ProductExportController extends Controller
{
    protected $service;

    public function __construct(ProductExportService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $products = ProductExport::orderBy('order_number', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(20);
        return view('pages.product-export.index', compact('products'), ['title' => 'Product Export Management']);
    }

    public function import(ImportRequest $request)
    {
        $result = $this->service->importFromExcel($request->file('file'));

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 400);
        }

        return response()->json($result);
    }

    public function export()
    {
        return Excel::download(new ProductExportExport, 'product_exports.xlsx');
    }

    public function exportTemplate()
    {
        return Excel::download(new class extends ProductExportExport {
            public function collection() { return collect(); }
        }, 'product_export_template.xlsx');
    }

    public function uploadImage(UploadImageRequest $request, ProductExport $product)
    {
        $product = $this->service->processSingleImage($product, $request->file('image'));
        return response()->json([
            'success' => true,
            'product' => $this->service->transformProduct($product)
        ]);
    }

    public function processMerge(ProcessMergeRequest $request)
    {
        $result = $this->service->startMergeProcess($request->input('ids', []));

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 400);
        }

        return response()->json($result);
    }

    public function getBatchStatus($batchId)
    {
        return Bus::findBatch($batchId);
    }

    public function data(Request $request)
    {
        $products = ProductExport::orderBy('order_number', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(20);

        $products->getCollection()->transform(fn($product) => $this->service->transformProduct($product));

        return response()->json($products);
    }

    public function destroy(ProductExport $product)
    {
        $this->service->deleteProductImages($product);
        $product->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Record deleted successfully.']);
        }

        return back()->with('success', 'Record and all associated images deleted successfully.');
    }

    public function bulkDelete(BulkDeleteRequest $request)
    {
        $result = $this->service->bulkDelete($request->input('ids', []));

        if (isset($result['error'])) {
            return response()->json(['error' => $result['error']], 400);
        }

        return response()->json($result);
    }

    public function downloadMerged(Request $request)
    {
        $result = $this->service->generateMergedZip($request->input('ids'));

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        return response()->download($result['file'], $result['name'])->deleteFileAfterSend(true);
    }
}
