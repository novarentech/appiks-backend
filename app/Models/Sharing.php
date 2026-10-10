<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sharing extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $hidden = ['updated_at'];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'status' => ReportStatus::class,
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Satu curhat bisa menghasilkan lebih dari satu sesi konseling — tidak ada
     * unique index pada counselings.sharing_id dan tidak ada guard di
     * CreateCounselingRequest. Relasi tunggal ini dipertahankan karena dipakai
     * sebagai objek di banyak endpoint, tapi sekarang deterministik: yang
     * terbaru, bukan baris mana saja yang dikembalikan MySQL lebih dulu.
     */
    public function counseling()
    {
        return $this->hasOne(Counseling::class)->latestOfMany();
    }

    /**
     * Seluruh sesi konseling yang berasal dari curhat ini.
     */
    public function counselings()
    {
        return $this->hasMany(Counseling::class);
    }

    /**
     * Jejak penanganan kasus ini, terurut.
     */
    public function caseEvents()
    {
        return $this->hasMany(CaseEvent::class)->orderBy('occurred_at')->orderBy('id');
    }

    /**
     * Get the sharing's NLP analysis.
     */
    public function nlp(): MorphOne
    {
        return $this->morphOne(NlpAnalysis::class, 'nlpable');
    }
}
