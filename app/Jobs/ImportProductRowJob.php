<?php

namespace App\Jobs;

use App\Models\ProductExport;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportProductRowJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $row;

    /**
     * Create a new job instance.
     */
    public function __construct(array $row)
    {
        $this->row = $row;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        ProductExport::create([
            'sku_platform'          => $this->row['sku_platform'] ?? null,
            'quantity'              => $this->row['jumlah_barang'] ?? 1,
            'order_number'          => $this->row['no_pesanan'] ?? null,
            'tracking_number'       => $this->row['nomor_resi'] ?? null,
            'product_id'            => $this->row['id_produk'] ?? null,
            'sku_id'                => $this->row['id_sku'] ?? null,
            'product_specification' => $this->row['spesifikasi_produk'] ?? null,
            'product_image_url'     => $this->row['tautan_gambar_produk'] ?? null,
            'image_path'            => $this->row['gambar_produk'] ?? null,
        ]);
    }
}
