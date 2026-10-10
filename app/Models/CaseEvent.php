<?php

namespace App\Models;

use App\Enums\CaseEventType;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu kejadian dalam jejak penanganan sebuah kasus.
 *
 * Append-only. Tidak ada SoftDeletes, dan tidak ada jalur kode yang
 * meng-update atau menghapus baris di tabel ini.
 */
class CaseEvent extends Model
{
    protected $table = 'case_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'event'       => CaseEventType::class,
            'payload'     => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function sharing()
    {
        return $this->belongsTo(Sharing::class);
    }

    public function counseling()
    {
        return $this->belongsTo(Counseling::class);
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Urutan kronologis. `id` sebagai pemecah seri karena beberapa kejadian
     * terjadi dalam satu request dan bisa punya occurred_at identik.
     */
    public function scopeChronological($query)
    {
        return $query->orderBy('occurred_at')->orderBy('id');
    }
}
