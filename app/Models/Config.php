<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    protected $guarded = ['id'];
    protected $casts   = ['order' => 'array', 'bot' => 'array', 'captions' => 'array', 'payments' => 'array'];
}
