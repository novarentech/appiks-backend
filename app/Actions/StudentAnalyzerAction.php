<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;

class StudentAnalyzerAction
{
    public function __construct(private CallGeminiAction $gemini) {}

    public function analyzeInsecureQuiz(): string
    {
        $archtype_data = [
            'SAGE' => [
                'archtype' => 'the sage',
                'arctype_char' => 'mental baja',
                'arctype_habits' => 'kebebasan dan kreasi',
                'arctype_description' => 'Kekuatan Supermu adalah Rasa Ingin Tahu! Otakmu adalah senjatanya. Kamu adalah pembelajar sejati yang bersemangat saat menemukan hal baru dan memahami bagaimana sesuatu bekerja. Dunia need people like you!',
                'archtype_power' => 'Rasa Ingin Tahu yang Tak Terkalahkan',
            ],
            'ARTISAN' => [
                'archtype' => 'the artisan',
                'arctype_char' => 'mental baja',
                'arctype_habits' => 'kebebasan dan kreasi',
                'arctype_description' => 'Kekuatan Supermu adalah Kreativitas! Kamu punya kemampuan untuk melihat dunia dengan cara yang berbeda dan menciptakan sesuatu dari imajinasimu. Ide-idemu adalah sumber dayamu yang berharga.',
                'archtype_power' => 'Kreativitas yang Tak Terbatas',
            ],
            'GUARDIAN' => [
                'archtype' => 'the guardian',
                'arctype_char' => 'mental baja',
                'arctype_habits' => 'kebebasan dan kreasi',
                'arctype_description' => 'Kekuatan Supermu adalah Empati dan Kerja Sama! Kamu adalah ahli dalam memahami perasaan orang dan menyatukan tim. Kehangatan dan dukunganmu membuat orang lain merasa kuat.',
                'archtype_power' => 'Empati dan Kerja Sama yang Menyatukan',
            ],
            'ARCHITECT' => [
                'archtype' => 'the architect',
                'arctype_char' => 'mental baja',
                'arctype_habits' => 'kebebasan dan kreasi',
                'arctype_description' => 'Kekuatan Supermu adalah Ketelitian dan Organisasi! Kamu hebat dalam merencanakan, menyusun strategi, dan memastikan semuanya berjalan lancar. Kamu adalah orang yang membuat rencana besar menjadi kenyataan.',
                'archtype_power' => 'Ketelitian dan Organisasi yang Sempurna',
            ],
        ];

        $learning_mode_data = [
            'VISUAL' => [
                'style' => 'visual',
                'desc' => 'Kamu jago banget kalau belajar pakai mind map dan gambar!',
            ],
            'AUDITORI' => [
                'style' => 'auditori',
                'desc' => 'Kamu jago banget kalau belajar pakai dengerin penjelasan dan diskusi!',
            ],
            'KINESTETIK' => [
                'style' => 'kinestetik',
                'desc' => 'Kamu jago banget kalau belajar pakai praktik langsung dan gerak!',
            ],
        ];

        $mindset_data = [
            'GROWTH' => 'Kamu punya motivasi internal dan resilience yang keren!',
            'FIXED' => 'Coba tantang dirimu sedikit demi sedikit, kamu pasti bisa!',
        ];

        $archetypes = array_keys($archtype_data);
        $learning_modes = array_keys($learning_mode_data);
        $mindsets = array_keys($mindset_data);

        $archetype_result = $archetypes[array_rand($archetypes)];
        $learning_mode_result = $learning_modes[array_rand($learning_modes)];
        $mindset_result = $mindsets[array_rand($mindsets)];

        $hardcoded_profile = $archtype_data[$archetype_result];
        $hardcoded_profile['learning_mode'] = $learning_mode_data[$learning_mode_result];
        $hardcoded_profile['fuel'] = $mindset_data[$mindset_result];

        $prompt_template = "Anda adalah generator pesan motivasi persona siswa. Siswa ini memiliki profil:
- Archtype: {$hardcoded_profile['archtype']} (Kekuatan: {$hardcoded_profile['archtype_power']})
- Mental: {$hardcoded_profile['arctype_char']}
- Gaya Belajar: {$hardcoded_profile['learning_mode']['style']} ({$hardcoded_profile['learning_mode']['desc']})

Buatkan 3 bagian teks yang unik dan sangat personal dalam format JSON (JANGAN gunakan Markdown atau pemformatan lain di output utama Anda, hanya JSON murni):
{
  \"personal_message\": \"Pesan motivasi yang mengikat Archtype dan Karakter Mental dalam 2-3 kalimat.\",
  \"mission_first\": \"Misi tantangan yang mengikat Kekuatan Super dan Gaya Belajar. Harus berupa tindakan belajar konkret dalam 1 kalimat.\",
  \"mission_second\": \"Misi tantangan yang mengasah Mental Baja/Resiliensi saat menghadapi kegagalan, dalam 1-2 kalimat.\"
}";

        $result = $this->gemini->handle($prompt_template, 6000);
        $result = str_replace(['```', 'json'], '', $result);
        $ai_results = json_decode($result, true);

        $final_result = [
            'archtype' => $hardcoded_profile['archtype'],
            'arctype_char' => $hardcoded_profile['arctype_char'],
            'arctype_habits' => $hardcoded_profile['arctype_habits'],
            'arctype_description' => $hardcoded_profile['arctype_description'],
            'archtype_power' => $hardcoded_profile['archtype_power'],
            'learning_mode' => $hardcoded_profile['learning_mode'],
            'fuel' => $hardcoded_profile['fuel'],
            'personal_message' => $ai_results['personal_message'] ?? 'Kamu punya potensi luar biasa untuk terus berkembang!',
            'mission_first' => $ai_results['mission_first'] ?? 'Cobalah buat mind map sederhana untuk bab tersulit hari ini.',
            'mission_second' => $ai_results['mission_second'] ?? 'Jika gagal, tuliskan 3 hal yang bisa kamu perbaiki besok.',
        ];

        return json_encode($final_result);
    }
}
