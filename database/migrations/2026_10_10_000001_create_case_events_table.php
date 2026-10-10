<?php

use App\Models\Counseling;
use App\Models\Sharing;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak penanganan sebuah kasus, dari curhat dikirim sampai kasus ditutup.
     *
     * Append-only: tidak ada softDeletes dan tidak ada jalur kode yang
     * meng-update atau menghapus baris di sini. Tabel ini adalah satu-satunya
     * tempat urutan kejadian tersimpan — kolom status di tabel lain ditimpa
     * di tempat dan tidak menyisakan jejak.
     */
    public function up(): void
    {
        Schema::create('case_events', function (Blueprint $table) {
            $table->id();

            // Head entity. Nullable karena konseling yang bersumber dari laporan
            // bisa tidak punya curhat; baris seperti itu dicari lewat counseling_id.
            $table->foreignIdFor(Sharing::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Counseling::class)->nullable()->constrained()->nullOnDelete();

            // Nilai CaseEventType. String, bukan enum MySQL, supaya menambah
            // jenis kejadian tidak butuh ALTER TABLE.
            $table->string('event');

            // Null berarti dipicu sistem (job, scheduler), bukan oleh pengguna.
            $table->foreignIdFor(User::class, 'actor_id')->nullable()->constrained('users')->nullOnDelete();

            // Snapshot peran aktor saat kejadian, karena users.role bisa berubah.
            $table->string('actor_role')->nullable();

            // Waktu kejadian menurut domain, terpisah dari created_at (waktu perekaman).
            $table->dateTime('occurred_at');

            // Metadata terstruktur saja. TIDAK BOLEH memuat transkrip curhat
            // maupun catatan klinis — lihat CaseTimelineVisibility.
            $table->json('payload')->nullable();

            $table->timestamps();

            $table->index(['sharing_id', 'occurred_at']);
            $table->index(['counseling_id', 'occurred_at']);
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_events');
    }
};
