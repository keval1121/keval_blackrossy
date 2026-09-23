<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['order_id', 'name', 'mobile', 'address', 'area', 'city', 'state', 'pincode'])]
class OrderAddress extends Model
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function formatted(): string
    {
        return collect([$this->address, $this->area, $this->city, $this->state, $this->pincode])
            ->filter()
            ->implode(', ');
    }
}
