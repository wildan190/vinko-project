<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_exports', function (Blueprint $table) {
            $table->id();
            $table->string('image_path')->nullable(); // Gambar Produk
            $table->string('sku_platform')->nullable(); // SKU Platform
            $table->integer('quantity')->default(1); // Jumlah Barang
            $table->string('order_number')->nullable(); // No. Pesanan
            $table->string('tracking_number')->nullable(); // Nomor Resi
            $table->string('product_id')->nullable(); // ID Produk
            $table->string('sku_id')->nullable(); // ID SKU
            $table->text('product_specification')->nullable(); // Spesifikasi Produk
            $table->text('product_image_url')->nullable(); // Tautan Gambar Produk
            $table->string('merged_image')->nullable(); // Merged image with QR
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_exports');
    }
};
