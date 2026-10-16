<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add priority, result, and extend source_type on counselings
        Schema::table('counselings', function (Blueprint $table) {
            if (!Schema::hasColumn('counselings', 'priority')) {
                $table->enum('priority', ['tinggi', 'sedang', 'rendah'])->default('rendah')->after('status');
            }
            if (!Schema::hasColumn('counselings', 'result')) {
                $table->text('result')->nullable()->after('notes');
            }
        });

        // Extend source_type enum to include 'student'
        DB::statement("ALTER TABLE counselings MODIFY COLUMN source_type ENUM('regular', 'nlp_incident', 'student') NOT NULL DEFAULT 'student'");

        // 2. Migrate existing records from reports to counselings
        if (Schema::hasTable('reports')) {
            $reports = DB::table('reports')->get();
            foreach ($reports as $report) {
                $status = match ($report->status) {
                    'Diselesaikan' => 'selesai',
                    'Dibatalkan' => 'dibatalkan',
                    'Jadwal Ditolak Siswa' => 'ditolak',
                    'Konseling Dijadwalkan' => 'dijadwalkan',
                    default => 'menunggu',
                };

                $scheduledAt = null;
                if (!empty($report->date) && !empty($report->time)) {
                    $scheduledAt = $report->date . ' ' . $report->time;
                }

                $existing = DB::table('counselings')->where('report_id', $report->id)->first();
                if ($existing) {
                    DB::table('counselings')->where('id', $existing->id)->update([
                        'source_type' => 'student',
                        'priority' => $report->priority ?? 'rendah',
                        'result' => $report->result ?? $existing->result,
                    ]);
                } else {
                    DB::table('counselings')->insert([
                        'student_id' => $report->user_id,
                        'counselor_id' => $report->counselor_id,
                        'room' => $report->room,
                        'notes' => $report->notes,
                        'reason' => $report->topic,
                        'result' => $report->result,
                        'priority' => $report->priority ?? 'rendah',
                        'scheduled_at' => $scheduledAt,
                        'type' => 'internal',
                        'source_type' => 'student',
                        'status' => $status,
                        'cutdown_at' => $report->cutdown_for_report ?? null,
                        'created_at' => $report->created_at,
                        'updated_at' => $report->updated_at,
                        'deleted_at' => $report->deleted_at ?? null,
                    ]);
                }
            }

            // 3. Drop report_id foreign key & column from counselings
            if (Schema::hasColumn('counselings', 'report_id')) {
                Schema::table('counselings', function (Blueprint $table) {
                    $table->dropForeign(['report_id']);
                    $table->dropColumn('report_id');
                });
            }

            // 4. Drop reports table
            Schema::dropIfExists('reports');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-create reports table
        if (!Schema::hasTable('reports')) {
            Schema::create('reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('counselor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('topic');
                $table->string('room');
                $table->date('date');
                $table->string('time');
                $table->string('status')->default('Belum Ditinjau');
                $table->enum('priority', ['tinggi', 'rendah'])->default('rendah');
                $table->text('notes')->nullable();
                $table->text('result')->nullable();
                $table->dateTime('cutdown_for_report')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        Schema::table('counselings', function (Blueprint $table) {
            if (!Schema::hasColumn('counselings', 'report_id')) {
                $table->foreignId('report_id')->nullable()->after('source_type')->constrained('reports')->nullOnDelete();
            }
            if (Schema::hasColumn('counselings', 'priority')) {
                $table->dropColumn('priority');
            }
            if (Schema::hasColumn('counselings', 'result')) {
                $table->dropColumn('result');
            }
        });

        DB::statement("ALTER TABLE counselings MODIFY COLUMN source_type ENUM('regular', 'nlp_incident') NOT NULL DEFAULT 'regular'");
    }
};
