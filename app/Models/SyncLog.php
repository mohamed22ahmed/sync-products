<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $fillable = [
        'sync_type',
        'status',
        'total_products_fetched',
        'products_created',
        'products_updated',
        'products_skipped',
        'products_failed',
        'total_batches',
        'batch_id',
        'error_message',
        'sync_options',
        'started_at',
        'completed_at',
        'duration_seconds'
    ];

    protected $casts = [
        'sync_options' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('started_at', '>=', now()->subDays($days));
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('sync_type', $type);
    }

    public function getDurationFormattedAttribute()
    {
        if (!$this->duration_seconds) {
            return 'N/A';
        }

        $hours = intval($this->duration_seconds / 3600);
        $minutes = intval(($this->duration_seconds % 3600) / 60);
        $seconds = $this->duration_seconds % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getSuccessRateAttribute()
    {
        $total = $this->products_created + $this->products_updated;
        $failed = $this->products_failed;
        
        if ($total + $failed === 0) {
            return 0;
        }

        return round(($total / ($total + $failed)) * 100, 2);
    }
}
