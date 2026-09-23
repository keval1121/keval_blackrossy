<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'title', 'subtitle', 'button_text', 'button_url', 'image', 'is_enabled', 'display_order', 'config'])]
class HomepageSection extends Model
{
    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'config' => 'array',
        ];
    }

    public static function keyed(): \Illuminate\Support\Collection
    {
        return static::query()->orderBy('display_order')->get()->keyBy('key');
    }
}
