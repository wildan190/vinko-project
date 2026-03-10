<?php

namespace App\Imports;

use App\Models\ProductExport;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductExportImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new ProductExport([
            'sku_platform'          => $row['sku_platform'] ?? null,
            'quantity'              => $row['jumlah_barang'] ?? 1,
            'order_number'          => $row['no_pesanan'] ?? null,
            'tracking_number'       => $row['nomor_resi'] ?? null,
            'product_id'            => $row['id_produk'] ?? null,
            'sku_id'                => $row['id_sku'] ?? null,
            'product_specification' => $row['spesifikasi_produk'] ?? null,
            'product_image_url'     => $row['tautan_gambar_produk'] ?? null,
            'image_path'            => $row['gambar_produk'] ?? null,
        ]);
    }
}
