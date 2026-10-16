<?php

namespace App\Models;

use App\Enums\CounselingResolution;
use App\Enums\CounselingStatus;
use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;


class Counseling extends Model
{
    use SoftDeletes;

    protected $guarded = [];
    protected $hidden = ['deleted_at'];
    protected $table = 'counselings';

    public function student(){
        return $this->belongsTo(User::class,'student_id');
    }
    
    public function counselor(){
        return $this->belongsTo(User::class,'counselor_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'student_id');
    }


    public function logs(){
        return $this->hasMany(CounselingLog::class, 'counseling_id');
    }

    public function consents(){
        return $this->hasMany(CounselingConsent::class, 'counseling_id');
    }

    public function latestConsent(){
        return $this->hasOne(CounselingConsent::class, 'counseling_id')->latestOfMany();
    }

    public function clinicalSummary(){
        return $this->hasOne(ClinicalSummary::class, 'counseling_id');
    }

    public function psychologist(){
        return $this->belongsTo(User::class, 'psychologist_id');
    }

    public function sharing(){
        return $this->belongsTo(Sharing::class, 'sharing_id');
    }

    public function bookingSchedule()
    {
        return $this->hasMany(BookingSchedule::class, 'counseling_id');
    }

    public function latestBookingSchedule()
    {
        return $this->hasOne(BookingSchedule::class, 'counseling_id')->latestOfMany();
    }

    protected function casts(){
        return [
            'resolution' => CounselingResolution::class,
            'status' => CounselingStatus::class,
            'scheduled_at' => 'datetime',
            'cutdown_at' => 'datetime',
        ];
    }

    public function getTopicAttribute(): ?string
    {
        return $this->attributes['reason'] ?? null;
    }

    public function getDateAttribute(): ?string
    {
        return $this->scheduled_at ? $this->scheduled_at->toDateString() : null;
    }

    public function getTimeAttribute(): ?string
    {
        return $this->scheduled_at ? $this->scheduled_at->format('H:i') : null;
    }

    public function getUserIdAttribute(): ?int
    {
        return $this->attributes['student_id'] ?? null;
    }

    public function setUserIdAttribute($value): void
    {
        $this->attributes['student_id'] = $value;
    }

    public function setTopicAttribute($value): void
    {
        $this->attributes['reason'] = $value;
    }

    public function setDateAttribute($value): void
    {
        if (empty($value)) {
            return;
        }
        $existingTime = $this->scheduled_at ? $this->scheduled_at->format('H:i') : '00:00';
        $this->attributes['scheduled_at'] = $value . ' ' . $existingTime;
    }

    public function setTimeAttribute($value): void
    {
        if (empty($value)) {
            return;
        }
        $existingDate = $this->scheduled_at ? $this->scheduled_at->toDateString() : now()->toDateString();
        $this->attributes['scheduled_at'] = $existingDate . ' ' . $value;
    }

    public function setReportIdAttribute($value): void
    {
        // Ignored as report_id is consolidated
    }

    public function setStatusAttribute($value): void
    {
        if ($value instanceof ReportStatus) {
            $value = $value->value;
        }

        $mapped = match ($value) {
            'Diselesaikan' => CounselingStatus::SELESAI->value,
            'Dibatalkan' => CounselingStatus::DIBATALKAN->value,
            'Jadwal Ditolak Siswa' => CounselingStatus::DITOLAK->value,
            'Konseling Dijadwalkan' => CounselingStatus::DIJADWALKAN->value,
            'Belum Ditinjau', 'Sedang Ditangani', 'Sudah Ditanggapi', 'Belum Ditanggapi', 'Menunggu Persetujuan Siswa' => CounselingStatus::MENUNGGU->value,
            default => $value instanceof CounselingStatus ? $value->value : $value,
        };

        $this->attributes['status'] = $mapped;
    }

    public function scopeFilterByRoom(Builder $query, ?string $room = null): Builder
    {
        if (empty($room)) {
            return $query;
        }

        $cleanRoom = trim(preg_replace('/\s+/', ' ', $room));
        $driver = DB::connection()->getDriverName();
        $concatLevelName = $driver === 'sqlite' ? "level || ' ' || name" : "CONCAT(level, ' ', name)";
        $concatNameLevel = $driver === 'sqlite' ? "name || ' ' || level" : "CONCAT(name, ' ', level)";

        return $query->whereHas('student.room', function (Builder $q) use ($cleanRoom, $concatLevelName, $concatNameLevel) {
            $q->where(function (Builder $sq) use ($cleanRoom, $concatLevelName, $concatNameLevel) {
                $sq->whereRaw("{$concatLevelName} LIKE ?", ["%{$cleanRoom}%"])
                    ->orWhereRaw("{$concatNameLevel} LIKE ?", ["%{$cleanRoom}%"])
                    ->orWhere('name', 'like', "%{$cleanRoom}%")
                    ->orWhere('level', 'like', "%{$cleanRoom}%");
            });
        });
    }

    public function scopeFilterByStatus(Builder $query, ?string $status = null): Builder
    {
        if (empty($status)) {
            return $query;
        }

        $statusLower = strtolower(trim($status));
        $mapped = match ($statusLower) {
            'belum ditinjau', 'belum_ditinjau', 'menunggu tinjauan', 'menunggu_tinjauan', 'pending', 'menunggu' => CounselingStatus::MENUNGGU->value,
            'menunggu jadwal', 'menunggu_jadwal' => CounselingStatus::MENUNGGU_JADWAL->value,
            'menunggu konfirmasi', 'menunggu_konfirmasi' => CounselingStatus::MENUNGGU_KONFIRMASI->value,
            'sedang ditangani', 'sedang_ditangani', 'ditinjau' => CounselingStatus::MENUNGGU->value,
            'konseling dijadwalkan', 'konseling_dijadwalkan', 'dijadwalkan' => CounselingStatus::DIJADWALKAN->value,
            'dijadwal ulang', 'dijadwal_ulang' => CounselingStatus::DIJADWAL_ULANG->value,
            'diselesaikan', 'selesai' => CounselingStatus::SELESAI->value,
            'dibatalkan' => CounselingStatus::DIBATALKAN->value,
            'jadwal ditolak siswa', 'jadwal_ditolak_siswa', 'ditolak' => CounselingStatus::DITOLAK->value,
            'menunggu diskusi', 'menunggu_diskusi' => CounselingStatus::MENUNGGU_DISKUSI->value,
            default => CounselingStatus::tryFrom($statusLower)?->value ?? $statusLower,
        };

        return $query->where('status', $mapped);
    }

    public function scopeFilterBySource(Builder $query, ?string $source = null): Builder
    {
        if (empty($source)) {
            return $query;
        }

        $sourceLower = strtolower(trim($source));
        if ($sourceLower === 'all') {
            return $query;
        }

        if (in_array($sourceLower, ['student', 'regular', 'nlp_incident'], true)) {
            return $query->where('source_type', $sourceLower);
        }

        return $query;
    }

    public function scopeFilterByPriority(Builder $query, ?string $priority): Builder
    {
        if (empty($priority)) {
            return $query;
        }

        return $query->where('priority', strtolower(trim($priority)));
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        $term = trim($search);

        return $query->where(function (Builder $q) use ($term) {
            $q->where('reason', 'like', "%{$term}%")
                ->orWhereHas('student', function (Builder $uq) use ($term) {
                    $uq->where('name', 'like', "%{$term}%")
                        ->orWhere('identifier', 'like', "%{$term}%");
                });
        });
    }
}

