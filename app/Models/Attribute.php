<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'type', 'is_filterable', 'display_order'])]
class Attribute extends Model
{
    protected function casts(): array
    {
        return ['is_filterable' => 'boolean'];
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('display_order');
    }
}
