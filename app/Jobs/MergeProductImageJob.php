<?php

namespace App\Jobs;

use App\Models\ProductExport;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MergeProductImageJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $product;

    /**
     * Create a new job instance.
     */
    public function __construct(ProductExport $product)
    {
        $this->product = $product;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Increase memory limit for very large images
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        // Ensure we handle both batched and non-batched jobs
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        // Revert to GD Driver as Imagick is not available
        $manager = new ImageManager(new GdDriver());

        // Header Dimensions (9cm x 2.5cm) @ 300 DPI
        // 9cm = 1062px, 2.5cm = 295px
        $headerWidth = 1062;
        $headerHeight = 295;

        // Create white header canvas
        $header = $manager->create($headerWidth, $headerHeight)->fill('ffffff');

        // Add QR Code (right side)
        $qrContent = $this->product->tracking_number ?? $this->product->order_number ?? 'N/A';
        // Simplified QR Generation to avoid Imagick backend issues
        $qrCodeImage = QrCode::format('png')
            ->size(200)
            ->margin(1)
            ->generate($qrContent);
        
        // Decode PNG string explicitly to avoid DecoderException
        $qr = $manager->read((string) $qrCodeImage);
        $header->place($qr, 'right', 20);

        // Add Product Image (small, next to QR)
        if ($this->product->image_path && Storage::disk('public')->exists($this->product->image_path)) {
            $smallImg = $manager->read(Storage::disk('public')->path($this->product->image_path));
            $smallImg->scale(height: 200);
            $header->place($smallImg, 'right', 240);
        }

        // Font file
        $fontFile = '/System/Library/Fonts/Supplemental/Arial.ttf';
        $fontExists = file_exists($fontFile);

        // Add Text Info
        $header->text("NO. PESANAN: " . $this->product->order_number, 20, 40, function($font) use ($fontExists, $fontFile) {
            if ($fontExists) $font->file($fontFile);
            $font->size(24);
            $font->color('000000');
        });
        $header->text("SPESIFIKASI: " . $this->product->product_specification, 20, 80, function($font) use ($fontExists, $fontFile) {
            if ($fontExists) $font->file($fontFile);
            $font->size(24);
            $font->color('000000');
        });
        $header->text("SKU PLATFORM: " . $this->product->sku_platform, 20, 120, function($font) use ($fontExists, $fontFile) {
            if ($fontExists) $font->file($fontFile);
            $font->size(24);
            $font->color('000000');
        });
        $header->text("ID PRODUK: " . $this->product->product_id, 20, 160, function($font) use ($fontExists, $fontFile) {
            if ($fontExists) $font->file($fontFile);
            $font->size(24);
            $font->color('000000');
        });
        $header->text("ID SKU: " . $this->product->sku_id, 20, 200, function($font) use ($fontExists, $fontFile) {
            if ($fontExists) $font->file($fontFile);
            $font->size(24);
            $font->color('000000');
        });
        $header->text("Qty: " . $this->product->quantity, 20, 260, function($font) use ($fontExists, $fontFile) {
            if ($fontExists) $font->file($fontFile);
            $font->size(40);
            $font->color('000000');
        });

        // Load Main Product Image
        if ($this->product->image_path && Storage::disk('public')->exists($this->product->image_path)) {
            $mainImg = $manager->read(Storage::disk('public')->path($this->product->image_path));
            // Limit main image size to 2000px width for performance while keeping quality
            if ($mainImg->width() > 2000) {
                $mainImg->scale(width: 2000);
            }
        } else {
            // Placeholder if image not found
            $mainImg = $manager->create(1062, 1062)->fill('eeeeee');
        }

        // Combine Header and Main Image
        $finalWidth = max($mainImg->width(), $headerWidth);
        $finalHeight = $mainImg->height() + $headerHeight;

        $canvas = $manager->create($finalWidth, $finalHeight)->fill('ffffff');
        // Place information header on the top-right
        $canvas->place($header, 'top-right');
        // Place main product image below the header
        $canvas->place($mainImg, 'top-left', 0, $headerHeight);

        // Save Merged Image
        $fileName = 'merged_' . $this->product->id . '_' . time() . '.png';
        $savePath = 'merged_images/' . $fileName;
        
        if (!Storage::disk('public')->exists('merged_images')) {
            Storage::disk('public')->makeDirectory('merged_images');
        }

        $canvas->toPng()->save(Storage::disk('public')->path($savePath));

        $this->product->update(['merged_image' => $savePath]);
    }
}
