<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $guarded = ['id'];
    protected $casts   = ['temporary_data' => 'array', 'product' => 'array', 'payment' => 'array'];
}
