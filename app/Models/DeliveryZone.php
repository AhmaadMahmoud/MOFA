<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name_en', 'name_ar', 'fee', 'is_pickup', 'is_active', 'sort_order'])]
class DeliveryZone extends Model
{
    use HasFactory, HasLocalizedAttributes;

    protected function casts(): array
    {
        return ['fee' => 'integer', 'is_pickup' => 'boolean', 'is_active' => 'boolean'];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('id');
    }

    public function feeLabel(): string
    {
        return $this->fee > 0 ? Money::format($this->fee) : __('Free');
    }
}
