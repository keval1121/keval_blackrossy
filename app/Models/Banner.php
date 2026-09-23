<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'title', 'subtitle', 'button_text', 'button_url', 'desktop_image',
    'mobile_image', 'starts_at', 'ends_at', 'is_active', 'display_order',
])]
class Banner extends Model
{
    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $builder) {
                $builder->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $builder) {
                $builder->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('display_order');
    }

    public function desktopUrl(): string
    {
        return asset('storage/'.$this->desktop_image);
    }

    public function mobileUrl(): string
    {
        return $this->mobile_image ? asset('storage/'.$this->mobile_image) : $this->desktopUrl();
    }
}
