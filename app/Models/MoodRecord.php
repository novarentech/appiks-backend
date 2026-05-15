<?php

namespace App\Models;

use App\Enums\MoodStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MoodRecord extends Model
{
    /** @use HasFactory<\Database\Factories\MoodRecordFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['id', 'user_id', 'created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Filter berdasarkan user_id
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Filter hanya mood hari ini
     */
    public function scopeToday($query)
    {
        return $query->whereDate('recorded', Carbon::today());
    }

    /**
     * Filter berdasarkan bulan dan tahun
     */
    public function scopeInMonth($query, int $month, int $year)
    {
        return $query->whereMonth('recorded', $month)->whereYear('recorded', $year);
    }

    /**
     * Filter dalam rentang minggu ini (Senin–Minggu)
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('recorded', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }
}
