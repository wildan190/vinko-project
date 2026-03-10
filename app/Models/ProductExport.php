<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductExport extends Model
{
    protected $fillable = [
        'image_path',
        'sku_platform',
        'quantity',
        'order_number',
        'tracking_number',
        'product_id',
        'sku_id',
        'product_specification',
        'product_image_url',
        'merged_image',
    ];
}
