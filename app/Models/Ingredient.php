<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedAttributes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name_en', 'name_ar'])]
class Ingredient extends Model
{
    use HasFactory, HasLocalizedAttributes;

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class)->using(ItemIngredient::class)->withPivot(['quantity', 'unit', 'sort_order']);
    }
}
