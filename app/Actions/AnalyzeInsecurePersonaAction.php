<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;

class AnalyzeInsecurePersonaAction
{
    public function __construct(private CallGeminiAction $gemini) {}

    public function handle(array $answers): object
    {
        $key = implode('', $answers);
        $ans = DB::table('ai_generated')->where('key', $key)->first();

        if (!$ans || $ans->answer == null) {
            $pertanyaan = 'Bagian 1: Kekuatan Super (Strength-Spotter) 
                            N
                            o Pertanyaan Opsi Jawaban (Pilih yang paling mendekati) 
                            1 Di waktu luang, aku paling 
                            suka... A. Membaca/menonton hal baru yang menarik 
                            (Pembelajar);B. Menyendiri untuk menggambar/menulis/bermain 
                            musik (Kreator);C. Ngobrol atau berkumpul dengan teman (Sosialis);D. Merapikan kamar atau merencanakan jadwal 
                            (Perencana) 
                            2 Ketika menghadapi masalah 
                            yang sulit, aku...A. Mencari banyak referensi dan tutorial (Pemecah 
                            Masalah);B. Memikirkan solusi yang tidak biasa (Inovator);C. Langsung menelepon teman untuk minta saran 
                            (Kolaborator);D. Membuat daftar langkah-langkah untuk 
                            menyelesaikannya (Strategis) 
                            3 Aku merasa paling bangga 
                            jika...A. Berhasil memahami konsep yang rumit (Pemikir 
                            Mendalam);B. Karyaku (gambar, tulisan, proyek) dipuji orang 
                            (Seniman);C. Aku bisa membantu teman yang kesusahan 
                            (Penolong) ;D. Semua tugas terselesaikan tepat waktu dengan 
                            rapi (Teliti) 
                            4 Dalam kerja kelompok, peran 
                            alamiku adalah...A. Si pencari fakta dan data (Peneliti);B. Si pencetus ide gila (Idea Generator);C. Si perekat tim yang menjaga suasana (Mediator);D. Si pengatur waktu dan pembagi tugas (Manajer 
                            Proyek) 
                            5 Kekuatan terbesarku adalah... A. Rasa ingin tahuku yang tinggi (Curiosity);B. Imajinasiku yang liar (Imagination);C. Kemampuanku memahami perasaan orang;D. Konsistensi dan ketekunanku (Perseverance) ';
            $carahitung = '"Kode Jawaban","Nama","""Kekuatan Super""","Deskripsi & Penyampaian Hasil (Untuk Siswa)"
                            "A.","Pembelajar/Pemikir","The Sage (Sang Bijak)","Kekuatan Supermu adalah Rasa Ingin Tahu! Otakmu adalah senjatanya. Kamu adalah pembelajar sejati yang bersemangat saat menemukan hal baru dan memahami bagaimana sesuatu bekerja. Dunia need people like you!"
                            "B.","Kreator/Inovator","The Artisan (Sang Perajin)","Kekuatan Supermu adalah Kreativitas! Kamu punya kemampuan untuk melihat dunia dengan cara yang berbeda dan menciptakan sesuatu dari imajinasimu. Ide-idemu adalah sumber dayamu yang berharga."
                            "C.","Sosialis/Kolaborator","The Guardian (Sang Penjaga)","Kekuatan Supermu adalah Empati dan Kerja Sama! Kamu adalah ahli dalam memahami perasaan orang dan menyatukan tim. Kehangatan dan dukunganmu membuat orang lain merasa kuat."
                            "D.","Perencana/Strategis","The Architect (Sang Arsitek)","Kekuatan Supermu adalah Ketelitian dan Organisasi! Kamu hebat dalam merencanakan, menyusun strategi, dan memastikan semuanya berjalan lancar. Kamu adalah orang yang membuat rencana besar menjadi kenyataan."';
            $jawab = json_encode($answers);
            $perintah = "saya punya daftar pertanyaan ini $pertanyaan. dengan cara penghitungan $carahitung. Hasilkan jenis kepribadian siswa dengan jawaban no. 1-5 $jawab ini berdasarkan cara hitung tersebut. output dalam bentuk JSON (parsed) berikut (hanya contoh, berikan berdasarkan analisamu sendiri) ```json { \"main_archtype\": \"nama persona utama yang paling dominan\", \"secondary_archtype\": \"nama persona kedua yang melengkapi atau null\", \"archtype_character\": \"tipe karakter persona. contoh : Mental Baja hanya satu tipe\", \"archtype_habits\": \"kebiasaan persona contoh: Kebebasan dan Kreasi hanya 2 tipe dengan dan bukan &\", \"archtype_description\": \"deskripsi persona\", \"archtype_power\": \"kekuatan persona contoh: Rasa ingin tahu yang tak terkalahkan berbentuk deskriptif maksimal 8 kata\" } ``` tanpa tambahan apapun dengan bahasa antara teman-teman bagi siswa SMA namun jangan terlalu panjang";
            
            $hasil = $this->gemini->handle($perintah, 600);
            $hasil = str_replace(['```', 'json'], '', $hasil);
            DB::table('ai_generated')->updateOrInsert(
                ['key' => $key],
                ['answer' => $hasil, 'updated_at' => now()]
            );
        } else {
            $hasil = $ans->answer;
        }

        return json_decode($hasil);
    }
}
