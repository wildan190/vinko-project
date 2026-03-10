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

class ProcessImageUploadJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        $fileName = basename($this->tempPath);
        $finalPath = 'product_images/' . $fileName;
        
        // Move from temp to product_images
        Storage::disk('public')->move($this->tempPath, $finalPath);
        
        try {
            $manager = new ImageManager(new GdDriver());
            $imageContent = Storage::disk('public')->get($finalPath);
            $thumbnail = $manager->read($imageContent);
            $thumbnail->scale(width: 200);
            
            if (!Storage::disk('public')->exists('thumbnails')) {
                Storage::disk('public')->makeDirectory('thumbnails');
            }
            
            $thumbnailPath = 'thumbnails/' . $fileName;
            $thumbnail->toPng()->save(Storage::disk('public')->path($thumbnailPath));
        } catch (\Exception $e) {
            // Error handling
        }

        $this->product->update(['image_path' => $finalPath]);
    }
}
