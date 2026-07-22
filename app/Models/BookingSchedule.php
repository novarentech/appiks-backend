<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $table = 'booking_schedules';

    public function counseling()
    {
        return $this->belongsTo(Counseling::class, 'counseling_id');
    }

    public function slot()
    {
        return $this->belongsTo(PsychologistSlot::class, 'slot_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Bookings awaiting psychologist confirmation.
     */
    public function scopePending($query)
    {
        return $query->where('status', BookingStatus::PENDING->value);
    }

    /**
     * Bookings past their 24-hour SLA window — candidates for auto-expiry.
     */
    public function scopeExpired($query)
    {
        return $query->where('status', BookingStatus::PENDING->value)
                     ->where('deadline_at', '<=', now());
    }

    protected function casts(): array
    {
        return [
            'status'      => BookingStatus::class,
            'deadline_at' => 'datetime',
        ];
    }
}
