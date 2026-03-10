<?php

namespace App\Exports;

use App\Models\ProductExport;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductExportExport implements FromCollection, WithHeadings, WithMapping
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ProductExport::all();
    }

    public function headings(): array
    {
        return [
            'Gambar Produk',
            'SKU Platform',
            'Jumlah Barang',
            'No. Pesanan',
            'Nomor Resi',
            'ID Produk',
            'ID SKU',
            'Spesifikasi Produk',
            'Tautan Gambar Produk',
        ];
    }

    public function map($product): array
    {
        return [
            $product->image_path,
            $product->sku_platform,
            $product->quantity,
            $product->order_number,
            $product->tracking_number,
            $product->product_id,
            $product->sku_id,
            $product->product_specification,
            $product->product_image_url,
        ];
    }
}
