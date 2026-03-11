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
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MergeProductImageJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $tries = 5;
    public $timeout = 1200;
    protected $product;

    public function __construct(ProductExport $p) {
        $this->product = $p;
    }

    public function handle(): void {
        ini_set("memory_limit", "-1");
        set_time_limit(1200);

        if ($this->batch() && $this->batch()->cancelled()) return;

        $manager = new ImageManager(new ImagickDriver());

        if ($this->product->image_path && Storage::disk("public")->exists($this->product->image_path)) {
            $mainImg = $manager->read(Storage::disk("public")->path($this->product->image_path));
        } else {
            $mainImg = $manager->create(1181, 1181)->fill("eeeeee");
        }

        $pxPerCm = 118.11;
        $scaleFactor = max(1, $mainImg->width() / round(28 * $pxPerCm));
        
        $boxW = round(9 * $pxPerCm * $scaleFactor);
        $boxH = round(2.5 * $pxPerCm * $scaleFactor);
        $headH = round(3 * $pxPerCm * $scaleFactor);

        $info = $manager->create($boxW, $boxH)->fill("ffffff");
        
        $qrData = QrCode::format("png")
            ->size(round(240 * $scaleFactor))
            ->margin(1)
            ->generate($this->product->tracking_number ?? "N/A");
            
        $info->place($manager->read((string)$qrData), "right", round(10 * $scaleFactor));

        $font = public_path('fonts/Roboto-Bold.ttf');
        if (!file_exists($font)) {
            $font = "/usr/share/fonts/TTF/DejaVuSans.ttf";
        }
        $fEx = file_exists($font);
        $pL = round(15 * $scaleFactor);

        // Text details
        $info->text("NO. PESANAN: " . $this->product->order_number, $pL, round(35 * $scaleFactor), function($f) use ($fEx, $font, $scaleFactor) {
            if($fEx) $f->file($font);
            $f->size(round(22 * $scaleFactor));
            $f->color("000000");
        });

        $info->text("SKU: " . $this->product->sku_platform, $pL, round(70 * $scaleFactor), function($f) use ($fEx, $font, $scaleFactor) {
            if($fEx) $f->file($font);
            $f->size(round(22 * $scaleFactor));
            $f->color("000000");
        });

        $spec = strlen($this->product->product_specification) > 45 
            ? substr($this->product->product_specification, 0, 42) . "..." 
            : $this->product->product_specification;
            
        $info->text("SPEC: " . $spec, $pL, round(105 * $scaleFactor), function($f) use ($fEx, $font, $scaleFactor) {
            if($fEx) $f->file($font);
            $f->size(round(18 * $scaleFactor));
            $f->color("000000");
        });

        $info->text("Qty: " . $this->product->quantity, $pL, round(200 * $scaleFactor), function($f) use ($fEx, $font, $scaleFactor) {
            if($fEx) $f->file($font);
            $f->size(round(45 * $scaleFactor));
            $f->color("000000");
        });

        // Merge canvas
        $canvas = $manager->create($mainImg->width(), $mainImg->height() + $headH)->fill("ffffff");
        
        $paddingRight = round(0.25 * $pxPerCm * $scaleFactor);
        $paddingTop = round(0.25 * $pxPerCm * $scaleFactor);
        
        $canvas->place($info, "top-right", $paddingRight, $paddingTop)
               ->place($mainImg, "top-left", 0, $headH);

        // Delete old merged image and thumbnail if exists
        if ($this->product->merged_image) {
            Storage::disk('public')->delete($this->product->merged_image);
            Storage::disk('public')->delete('thumbnails/merged/' . basename($this->product->merged_image));
        }

        $path = "merged_images/merged_" . $this->product->id . "_" . time() . ".png";
        if (!Storage::disk("public")->exists("merged_images")) {
            Storage::disk("public")->makeDirectory("merged_images");
        }

        // Save original high-res image
        $canvas->toPng()->save(Storage::disk("public")->path($path));

        // Create Thumbnail for UI performance
        try {
            $thumbnail = $manager->read(Storage::disk('public')->path($path));
            $thumbnail->scale(width: 200);
            
            $thumbnailDir = 'thumbnails/merged';
            if (!Storage::disk('public')->exists($thumbnailDir)) {
                Storage::disk('public')->makeDirectory($thumbnailDir);
            }
            
            $thumbnailPath = $thumbnailDir . '/' . basename($path);
            $thumbnail->toPng()->save(Storage::disk('public')->path($thumbnailPath));
        } catch (\Exception $e) {
            // Silently fail thumbnail creation
        }

        $this->product->update(["merged_image" => $path]);
    }
}
