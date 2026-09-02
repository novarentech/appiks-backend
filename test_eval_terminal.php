<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Traits\InteractsWithGemini;

$runner = new class {
    use InteractsWithGemini;
};

// System Instruction aktual dari GenerateGeminiReferralSummaryJob
$systemInstruction = "Anda adalah asisten AI terintegrasi di APPIKS, sebuah platform kesehatan mental sekolah.\n\n"
. "Tugas Anda adalah menghasilkan \"Ringkasan Naratif Rujukan\" untuk Psikolog Mitra berdasarkan data rujukan siswa.\n\n"
. "SCOPE CONSENT KATEGORI DATA:\n"
. "- Riwayat Mood (mood_history)\n"
. "- Curhat Siswa (sharing_history)\n"
. "- Catatan Konseling BK (assesment_logs)\n\n"
. "ATURAN 1 (PENGGUNAAN DATA DAN EVALUASI CONSENT SCOPE):\n"
. "- Evaluasi ketersediaan key pada JSON data mentah yang diberikan.\n"
. "- Jika key kategori data ADA dalam data mentah (CONSENT = TRUE), rangkum data tersebut ke dalam narasi secara profesional.\n"
. "- Jika key kategori data TIDAK ADA dalam data mentah (CONSENT = FALSE), DILARANG mengarang, menebak, atau menyebutkan isi data tersebut.\n\n"
. "ATURAN 2 (PENYEBUTAN DATA YANG TIDAK DIBAGIKAN):\n"
. "Secara eksplisit sebutkan kategori data (dari 3 scope: Riwayat Mood, Curhat Siswa, Catatan Konseling BK) yang tidak diizinkan atau tidak dibagikan oleh siswa jika key-nya tidak ada pada data mentah.\n\n"
. "ATURAN 3 (DATA MASKING PADA RED ZONE):\n"
. "Jika mengutip isi Curhat Red Zone, ganti kata-kata sensitif terkait bunuh diri, self-harm, kekerasan, atau nama spesifik orang lain dengan frasa \"[kata kunci disamarkan]\".\n\n"
. "ATURAN 4 (DISCLAIMER WAJIB):\n"
. "DILARANG melakukan diagnosis psikologis. Setiap ringkasan WAJIB memiliki dua bagian dalam output: paragraf pertama berisi ringkasan naratif hasil analisis, kemudian paragraf kedua HANYA berisi disclaimer berikut: \"Catatan: Ringkasan ini dibuat secara otomatis oleh sistem AI APPIKS untuk membantu rujukan dan bukan merupakan diagnosis psikologis resmi.\" WAJIB terdapat tepat satu baris kosong (1 paragraf baru) sebelum kata \"Catatan:\". Jangan menggabungkan disclaimer dengan paragraf ringkasan utama.\n\n"
. "ATURAN 5 (FORMAT OUTPUT):\n"
. "Hasilkan output hanya dalam 1 paragraf yang mengalir secara natural dan profesional. Jangan gunakan bullet points. Mulai selalu dengan format: \"Siswa kelas [Tingkat], dirujuk Guru BK dengan tingkat keparahan [Tingkat Keparahan].\"";

// 5 Kombinasi Payload Terstruktur Baru
$combos = [
    'Kombinasi 1: All Scopes Granted - Red Zone (Krisis Akut)' => [
        'referral_id' => 'ref_a8f3e2d1',
        'generated_at' => date('c'),
        'anonymous_student_id' => 'stu_h7k2m4n9',
        'student_grade' => '11',
        'referral_severity' => 'tinggi',
        'consent_scope' => ['mood_history_30d', 'journal_excerpts', 'bk_assessment_notes'],
        'not_shared_categories' => [],
        'mood_distribution_30d' => ['Gembira' => 1, 'Netral' => 3, 'Sedih' => 11, 'Marah' => 7],
        'tidak_aman_streak_max' => 6,
        'tidak_aman_streak_current' => 4,
        'red_zone_count_30d' => 3,
        'yellow_zone_count_30d' => 5,
        'total_sharings_30d' => 12,
        'journal_excerpts' => [
            [
                'date' => date('Y-m-d', strtotime('-2 days')),
                'masked_text' => 'Aku merasa capek banget dan sempat berfikir ingin [MASKED:keyword] aja daripada pusing...',
                'zone' => 'Red'
            ],
            [
                'date' => date('Y-m-d', strtotime('-5 days')),
                'masked_text' => 'Semua terasa [MASKED:keyword] dan hampa, tidak tahu harus ke mana lagi.',
                'zone' => 'Red'
            ]
        ],
        'bk_assessment_notes' => 'Siswa mengalami tekanan psikologis berat pasca konflik keluarga. Menunjukkan gejala depresi sedang dan butuh rujukan profesional.',
        'active_intervention_history' => [
            ['date' => date('Y-m-d', strtotime('-10 days')), 'intervention_type' => 'Konseling Guru BK (OFFLINE)', 'outcome_summary' => 'Perlu Rujukan Professional: Siswa mengungkapkan kecemasan berlebih.'],
            ['date' => date('Y-m-d', strtotime('-2 days')), 'intervention_type' => 'Konseling Guru BK (OFFLINE)', 'outcome_summary' => 'Perlu Rujukan Professional: Ditemukan indikasi pikiran destruktif.']
        ]
    ],

    'Kombinasi 2: All Scopes Granted - Yellow Zone (Prioritas Sedang)' => [
        'referral_id' => 'ref_b9x4y1z2',
        'generated_at' => date('c'),
        'anonymous_student_id' => 'stu_p3r7t9w1',
        'student_grade' => '12',
        'referral_severity' => 'sedang',
        'consent_scope' => ['mood_history_30d', 'journal_excerpts', 'bk_assessment_notes'],
        'not_shared_categories' => [],
        'mood_distribution_30d' => ['Gembira' => 5, 'Netral' => 12, 'Sedih' => 6, 'Marah' => 2],
        'tidak_aman_streak_max' => 3,
        'tidak_aman_streak_current' => 1,
        'red_zone_count_30d' => 0,
        'yellow_zone_count_30d' => 4,
        'total_sharings_30d' => 8,
        'journal_excerpts' => [
            [
                'date' => date('Y-m-d', strtotime('-3 days')),
                'masked_text' => 'Merasa [MASKED:keyword] saat berbicara pilihan jurusan ujian dengan orang tua.',
                'zone' => 'Yellow'
            ]
        ],
        'bk_assessment_notes' => 'Konseling seputar ekspektasi orang tua. Perlu bimbingan asertif.',
        'active_intervention_history' => [
            ['date' => date('Y-m-d', strtotime('-4 days')), 'intervention_type' => 'Konseling Guru BK (ONLINE)', 'outcome_summary' => 'Bukan Kondisi Prioritas (Yellow Zone): Siswa berjanji mencoba diskusi ulang.']
        ]
    ],

    'Kombinasi 3: Partial Scope (Hanya Mood & Curhat Siswa / BK Not Shared)' => [
        'referral_id' => 'ref_c1d2e3f4',
        'generated_at' => date('c'),
        'anonymous_student_id' => 'stu_k9l8m7n6',
        'student_grade' => '10',
        'referral_severity' => 'sedang',
        'consent_scope' => ['mood_history_30d', 'journal_excerpts'],
        'not_shared_categories' => ['Catatan Konseling BK (bk_assessment_notes)'],
        'mood_distribution_30d' => ['Gembira' => 8, 'Netral' => 10, 'Sedih' => 4, 'Marah' => 1],
        'tidak_aman_streak_max' => 2,
        'tidak_aman_streak_current' => 0,
        'red_zone_count_30d' => 0,
        'yellow_zone_count_30d' => 2,
        'total_sharings_30d' => 5,
        'journal_excerpts' => [
            [
                'date' => date('Y-m-d', strtotime('-4 days')),
                'masked_text' => 'Sulit fokus belajar karena sering merasa cemas jelang evaluasi.',
                'zone' => 'Yellow'
            ]
        ]
    ],

    'Kombinasi 4: Single Scope (Hanya Catatan BK / Mood & Curhat Not Shared)' => [
        'referral_id' => 'ref_d5e6f7g8',
        'generated_at' => date('c'),
        'anonymous_student_id' => 'stu_q1w2e3r4',
        'student_grade' => '12',
        'referral_severity' => 'tinggi',
        'consent_scope' => ['bk_assessment_notes'],
        'not_shared_categories' => ['Riwayat Mood (mood_history)', 'Curhat Siswa (journal_excerpts)'],
        'bk_assessment_notes' => 'Siswa dirujuk karena kelelahan emosional menetap pasca kehilangan anggota keluarga.',
        'active_intervention_history' => [
            ['date' => date('Y-m-d', strtotime('-1 days')), 'intervention_type' => 'Konseling Guru BK (OFFLINE)', 'outcome_summary' => 'Perlu Rujukan Professional: Direkomendasikan terapi ke psikolog.']
        ]
    ],

    'Kombinasi 5: No Scopes Granted (Consent Kosong / Semua Scope Not Shared)' => [
        'referral_id' => 'ref_e9f0g1h2',
        'generated_at' => date('c'),
        'anonymous_student_id' => 'stu_z9y8x7w6',
        'student_grade' => '10',
        'referral_severity' => 'tinggi',
        'consent_scope' => [],
        'not_shared_categories' => [
            'Riwayat Mood (mood_history)',
            'Curhat Siswa (journal_excerpts)',
            'Catatan Konseling BK (bk_assessment_notes)'
        ]
    ]
];

echo "========================================================================\n";
echo "       TESTING EVALUASI 5 KOMBINASI AI REFERRAL SUMMARY (APPIKS)\n";
echo "========================================================================\n\n";

$i = 1;
foreach ($combos as $title => $payload) {
    echo "------------------------------------------------------------------------\n";
    echo " [TEST {$i}] {$title}\n";
    echo "------------------------------------------------------------------------\n";
    echo "INPUT JSON PAYLOAD:\n";
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

    echo "MEMANGGIL AI GEMINI...\n";
    $promptText = "Berikut adalah data mentah:\n" . json_encode($payload);
    $result = $runner->generateClinicalSummary($promptText, $systemInstruction);

    echo "OUTPUT AI GEMINI:\n";
    echo ($result ?? '[ERROR/NULL RESPONSE]') . "\n\n";
    $i++;
}

echo "========================================================================\n";
echo "                      EVALUASI SELESAI\n";
echo "========================================================================\n";
