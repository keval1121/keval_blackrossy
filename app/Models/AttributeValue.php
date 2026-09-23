<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attribute_id', 'value', 'slug', 'hex_color', 'display_order'])]
class AttributeValue extends Model
{
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }
}
