<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $guarded = ['id'];
    public $casts      = [
        'url' => 'array',
    ];

    public static function search($search = null)
    {
        $src = trim($search);
        return empty($src) ? static::query()
            : static::query()->where('name', 'like', '%' . $src . '%')
            ->orWhere('api_key', 'like', '%' . $src . '%')
            ->orWhere('type_api', 'like', '%' . $src . '%')
            ->orWhere('status', 'like', '%' . $src . '%');
    }
}
