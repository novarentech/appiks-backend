<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Report extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function counselor()
    {
        return $this->belongsTo(User::class, 'counselor_id', 'id');
    }

    public function counselings()
    {
        return $this->hasMany(Counseling::class, 'report_id');
    }

    public function scopeFilterByRoom(Builder $query, ?string $room = null): Builder
    {
        if (empty($room)) {
            return $query;
        }

        $room = trim($room);
        $driver = DB::connection()->getDriverName();
        $concatLevelName = $driver === 'sqlite' ? "level || ' ' || name" : "CONCAT(level, ' ', name)";
        $concatNameLevel = $driver === 'sqlite' ? "name || ' ' || level" : "CONCAT(name, ' ', level)";

        return $query->whereHas('user.room', function (Builder $q) use ($room, $concatLevelName, $concatNameLevel) {
            $q->where(function (Builder $sq) use ($room, $concatLevelName, $concatNameLevel) {
                $sq->whereRaw("{$concatNameLevel} LIKE ?", ["%{$room}%"])
                    ->orWhereRaw("{$concatLevelName} LIKE ?", ["%{$room}%"])
                    ->orWhere('name', 'like', "%{$room}%")
                    ->orWhere('level', 'like', "%{$room}%");
            });
        });
    }

    public function scopeFilterByStatus(Builder $query, ?string $status): Builder
    {
        if (empty($status)) {
            return $query;
        }

        $statusLower = strtolower(trim($status));
        $enumCase = match ($statusLower) {
            'belum ditinjau', 'belum_ditinjau', 'menunggu tinjauan', 'menunggu_tinjauan', 'pending' => ReportStatus::MENUNGGU_TINJAUAN,
            'sedang ditangani', 'sedang_ditangani', 'ditinjau' => ReportStatus::DITINJAU,
            'sudah ditanggapi', 'sudah_ditanggapi', 'ditanggapi' => ReportStatus::DITANGGAPI,
            'belum ditanggapi', 'belum_ditanggapi', 'menunggu tanggapan', 'menunggu_tanggapan' => ReportStatus::MENUNGGU_TANGGAPAN,
            'menunggu persetujuan siswa', 'menunggu_persetujuan_siswa', 'menunggu persetujuan', 'menunggu_persetujuan' => ReportStatus::MENUNGGU_PERSETUJUAN,
            'konseling dijadwalkan', 'konseling_dijadwalkan', 'dijadwalkan' => ReportStatus::DIJADWALKAN,
            'diselesaikan', 'selesai' => ReportStatus::SELESAI,
            'dibatalkan' => ReportStatus::DIBATALKAN,
            'jadwal ditolak siswa', 'jadwal_ditolak_siswa', 'ditolak' => ReportStatus::DITOLAK,
            'bukan urgent', 'bukan_urgent' => ReportStatus::BUKAN_URGENT,
            'menunggu diskusi', 'menunggu_diskusi' => ReportStatus::MENUNGGU_DISKUSI,
            default => ReportStatus::tryFrom($status),
        };

        return $query->where('status', $enumCase ? $enumCase->value : $status);
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
            $q->where('topic', 'like', "%{$term}%")
                ->orWhereHas('user', function (Builder $uq) use ($term) {
                    $uq->where('name', 'like', "%{$term}%")
                        ->orWhere('identifier', 'like', "%{$term}%");
                });
        });
    }
}
