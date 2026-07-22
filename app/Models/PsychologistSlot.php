<?php

namespace App\Models;

use App\Enums\SlotStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PsychologistSlot extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $table = 'psychologist_slots';

    public function psychologistProfile()
    {
        return $this->belongsTo(PsychologistProfile::class, 'psychologist_id');
    }

    /**
     * Scope: only slots that are still available (safe to delete).
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', SlotStatus::AVAILABLE->value);
    }

    /**
     * Scope: slots in the future (slot_date >= today).
     */
    public function scopeUpcoming($query)
    {
        return $query->whereDate('slot_date', '>=', now()->toDateString());
    }

    public function bookingSchedule()
    {
        return $this->hasOne(BookingSchedule::class, 'slot_id');
    }

    protected function casts(): array
    {
        return [
            'status'           => SlotStatus::class,
            'slot_date'        => 'date',
            'slot_start_time'  => 'datetime:H:i',
            'slot_end_time'    => 'datetime:H:i',
        ];
    }
}
