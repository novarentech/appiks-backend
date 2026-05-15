<?php

namespace App\Data;

class MoodResponses
{
    public static function get(string $status): string
    {
        $responses = [
            'Aman' => [
                'Terima kasih sudah berbagi mood hari ini, semoga harimu semakin menyenangkan 🌸',
                'Senang mendengar kamu merasa baik, terus jaga semangat positifmu ya ✨',
                'Kamu luar biasa! Semoga energi positifmu menular ke sekitarmu 💡',
                'Hebat, kamu sudah meluangkan waktu mengenali perasaanmu 🙌',
                'Terus pertahankan mood baikmu, tapi ingat tidak apa-apa kalau suatu saat merasa berbeda 🌈',
                'Bagus sekali! Kamu sudah menjaga diri dengan baik 🤍',
                'Hari yang indah dimulai dari perasaan yang baik, semoga harimu penuh kebahagiaan 🌞',
                'Kamu keren! Terus semangat untuk menjadi versi terbaik dari dirimu 💪',
                'Terima kasih sudah jujur pada perasaanmu, itu tanda kamu peduli pada dirimu sendiri 🌼',
                'Semoga rasa baikmu hari ini membawa kebaikan juga untuk orang-orang di sekitarmu 💖',
            ],
            'Tidak Aman' => [
                'Terima kasih sudah jujur membagikan perasaanmu, ingat kamu tidak sendirian 🤗',
                'Wajar kok merasa seperti itu, semoga segera ada hal baik yang membuatmu tenang 🌿',
                'Perasaanmu penting. Jika ingin bercerita, ada teman atau guru yang siap mendengarkan 🤍',
                'Kamu sudah hebat bisa mengenali perasaan ini, itu langkah awal untuk menjadi lebih kuat 💪',
                'Tidak apa-apa merasa sedih atau marah, itu manusiawi. Yang penting jangan memendam sendiri 🌧️',
                'Hari ini mungkin berat, tapi percayalah kamu bisa melewati ini 🌟',
                'Perasaanmu valid. Jangan takut untuk mencari dukungan bila diperlukan 🫂',
                'Kamu tidak harus selalu kuat sendirian, ada banyak orang yang peduli padamu 💌',
                'Terima kasih sudah mau berbagi. Semoga esok lebih baik untukmu 🌅',
                'Kamu berharga, apa pun mood-mu hari ini. Jangan lupa istirahat dan jaga dirimu 🌷',
            ],
        ];

        return $responses[$status][array_rand($responses[$status])];
    }
}
