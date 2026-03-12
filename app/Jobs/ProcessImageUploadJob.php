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

class ProcessImageUploadJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600;
    protected $product;
    protected $tempPath;

    /**
     * Create a new job instance.
     */
    public function __construct(ProductExport $product, string $tempPath)
    {
        $this->product = $product;
        $this->tempPath = $tempPath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        ini_set("memory_limit", "-1");
        set_time_limit(600);

        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        if (!Storage::disk('public')->exists($this->tempPath)) {
            return;
        }

        // Process single image logic
        if ($this->product->image_path) {
            Storage::disk('public')->delete($this->product->image_path);
            Storage::disk('public')->delete('thumbnails/' . basename($this->product->image_path));
        }
        
        // Also cleanup merged images if any
        if ($this->product->merged_image) {
            Storage::disk('public')->delete($this->product->merged_image);
            Storage::disk('public')->delete('thumbnails/merged/' . basename($this->product->merged_image));
        }

        $fileName = basename($this->tempPath);
        $finalPath = 'product_images/' . $fileName;
        
        // Move from temp to product_images
        Storage::disk('public')->move($this->tempPath, $finalPath);
        
        try {
            $manager = new ImageManager(new ImagickDriver());
            // Using path() instead of get() for memory efficiency with large files
            $thumbnail = $manager->read(Storage::disk('public')->path($finalPath));
            $thumbnail->scale(width: 200);
            
            if (!Storage::disk('public')->exists('thumbnails')) {
                Storage::disk('public')->makeDirectory('thumbnails');
            }
            
            $thumbnailPath = 'thumbnails/' . $fileName;
            $thumbnail->toPng()->save(Storage::disk('public')->path($thumbnailPath));
        } catch (\Exception $e) {
            // Error handling
        }

        $this->product->update([
            'image_path' => $finalPath,
            'merged_image' => null // Reset merged status
        ]);
    }
}
