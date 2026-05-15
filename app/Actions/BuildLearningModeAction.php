<?php

namespace App\Actions;

class BuildLearningModeAction
{
    public function handle(array $answers): array
    {
        $hitungan = array_count_values($answers);
        $jumlah = ['A' => $hitungan['A'] ?? 0, 'B' => $hitungan['B'] ?? 0, 'C' => $hitungan['C'] ?? 0];
        $maksimum = max($jumlah);
        $peta_gaya = ['A' => 'Auditori', 'B' => 'Visual', 'C' => 'Kinestetik'];

        $peta_deskripsi = [
            'A' => 'Gunakan kekuatanmu! Rekam penjelasan guru, bacakan catatanmu dengan suara lantang, diskusikan materi dengan teman, dan dengankan podcast edukasi.',
            'B' => 'Jadikan catatanmu berwarna! Gunakan stabilo, buat diagram, mind map, grafik, atau tonton video penjelasan animasi untuk membantumu memahami. ',
            'C' => 'Jangan hanya duduk! Belajar sambil berjalan, gunakan benda nyata untuk simulasi (contoh: buah untuk matematika), atau praktikkan langsung konsep yang dipelajari. ',
        ];

        foreach ($jumlah as $kode => $count) {
            if ($count === $maksimum) {
                $name = $peta_gaya[$kode];
                $desc = $peta_deskripsi[$kode];
            }
        }

        return ['mode' => $name, 'style' => $desc];
    }
}
