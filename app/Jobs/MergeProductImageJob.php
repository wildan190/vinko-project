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

        // Load Main Product Image first to get its dimensions
        if ($this->product->image_path && Storage::disk('public')->exists($this->product->image_path)) {
            $mainImg = $manager->read(Storage::disk('public')->path($this->product->image_path));
        } else {
            // Placeholder if image not found (10cm x 10cm @ 300 DPI)
            $mainImg = $manager->create(1181, 1181)->fill('eeeeee');
        }

        $imgWidth = $mainImg->width();
        $imgHeight = $mainImg->height();

        // 300 DPI conversion: 1 cm = 118.11 pixels
        $pxPerCm = 118.11;

        // Requirement: Barcode box is fix 9cm x 2.5cm
        $boxWidth = round(9 * $pxPerCm);   // ~1063px
        $boxHeight = round(2.5 * $pxPerCm); // ~295px

        // Requirement: Output height increases by ~3cm (e.g. 50cm -> 53cm)
        // We will use 3cm as the total header area height
        $headerAreaHeight = round(3 * $pxPerCm); // ~354px

        // Create the Information Box (The "Barcode Box")
        $infoBox = $manager->create($boxWidth, $boxHeight)->fill('ffffff');
        
        // Add Border to Info Box
        $infoBox->drawRectangle(0, 0, function($draw) use ($boxWidth, $boxHeight) {
            $draw->size($boxWidth, $boxHeight);
            $draw->border('000000', 2);
        });

        // Add QR Code (inside info box, right side)
        $qrContent = $this->product->tracking_number ?? $this->product->order_number ?? 'N/A';
        $qrCodeImage = QrCode::format('png')
            ->size(240)
            ->margin(1)
            ->generate($qrContent);
        
        $qr = $manager->read((string) $qrCodeImage);
        $infoBox->place($qr, 'right', 10);

        // Font file
        $fontFile = '/System/Library/Fonts/Supplemental/Arial.ttf';
        $fontExists = file_exists($fontFile);

        // Add Text Info to Info Box (left side)
        $infoBox->text("NO. PESANAN: " . $this->product->order_number, 15, 35, function($font) use ($fontExists, $fontFile) {
            if ($fontExists) $font->file($fontFile);
            $font->size(22);
            $font->color('000000');
        });
        $infoBox->text("SKU: " . $this->product->sku_platform, 15, 70, function($font) use ($fontExists, $fontFile) {
            if ($fontExists) $font->file($fontFile);
            $font->size(22);
            $font->color('000000');
        });
        
        // Wrap specification text if too long
        $spec = $this->product->product_specification;
        if (strlen($spec) > 45) $spec = substr($spec, 0, 42) . '...';
        
        $infoBox->text("SPEC: " . $spec, 15, 105, function($font) use ($fontExists, $fontFile) {
            if ($fontExists) $font->file($fontFile);
            $font->size(18);
            $font->color('000000');
        });

        $infoBox->text("ID: " . $this->product->product_id, 15, 140, function($font) use ($fontExists, $fontFile) {
            if ($fontExists) $font->file($fontFile);
            $font->size(18);
            $font->color('000000');
        });

        $infoBox->text("Qty: " . $this->product->quantity, 15, 200, function($font) use ($fontExists, $fontFile) {
            if ($fontExists) $font->file($fontFile);
            $font->size(45);
            $font->color('000000');
        });

        // Create Final Canvas
        // Width stays the same as product image, height increases by 3cm
        $finalWidth = $imgWidth;
        $finalHeight = $imgHeight + $headerAreaHeight;

        $canvas = $manager->create($finalWidth, $finalHeight)->fill('ffffff');

        // Place Info Box in the header area
        // If image is wider than 9cm, we can center it or put it on the left
        // Based on "output jadi 28x53", we'll center the 9cm box in the 28cm header
        $canvas->place($infoBox, 'top-center', 0, round(0.25 * $pxPerCm)); // 0.25cm padding from top

        // Place Main Product Image below the header area
        $canvas->place($mainImg, 'top-left', 0, $headerAreaHeight);

        // Save Merged Image
        $fileName = 'merged_' . $this->product->id . '_' . time() . '.png';
        $savePath = 'merged_images/' . $fileName;
        
        if (!Storage::disk('public')->exists('merged_images')) {
            Storage::disk('public')->makeDirectory('merged_images');
        }

        $canvas->toPng()->save(Storage::disk('public')->path($savePath));

        // Create Thumbnail for Merged Image (Small thumbnail for UI performance)
        try {
            $thumbnail = $manager->read(Storage::disk('public')->path($savePath));
            $thumbnail->scale(width: 200);
            
            $thumbnailDir = 'thumbnails/merged';
            if (!Storage::disk('public')->exists($thumbnailDir)) {
                Storage::disk('public')->makeDirectory($thumbnailDir);
            }
            
            $thumbnailPath = $thumbnailDir . '/' . $fileName;
            $thumbnail->toPng()->save(Storage::disk('public')->path($thumbnailPath));
        } catch (\Exception $e) {
            // Silently fail thumbnail creation
        }

        $this->product->update(['merged_image' => $savePath]);
    }
}
