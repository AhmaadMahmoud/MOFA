<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** One sitting on the site by an anonymous visitor (see App\Analytics\Tracker). */
#[Fillable(['visitor_id', 'started_at', 'last_seen_at', 'landing_path', 'referrer_host', 'source', 'utm_campaign', 'device', 'browser', 'os', 'locale', 'is_returning', 'page_views', 'order_id'])]
class Visit extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'is_returning' => 'boolean',
            'page_views' => 'integer',
        ];
    }

    public function pageViews(): HasMany
    {
        return $this->hasMany(PageView::class)->orderBy('viewed_at')->orderBy('id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(VisitEvent::class)->orderBy('created_at')->orderBy('id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
