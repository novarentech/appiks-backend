<?php

namespace App\Actions;

use Illuminate\Support\Facades\DB;

class GenerateMissionAction
{
    public function __construct(private CallGeminiAction $gemini) {}

    public function handleFuel(array $answers): string
    {
        $key = implode('', $answers);
        $ans = DB::table('ai_generated')->where('key', $key)->first();

        if (!$ans || $ans->answer == null) {
            $sembilan = [
                'B' => 'Kamu punya motivasi internal yang sangat kuat! Ini keren sekali karena bahan bakarmu datang dari dalam dirimu sendiri. Terus pertahankan rasa ingin tahu itu.',
                'D' => 'Kamu punya motivasi internal yang sangat kuat! Ini keren sekali karena bahan bakarmu datang dari dalam dirimu sendiri. Terus pertahankan rasa ingin tahu itu.',
                'A' => 'Kamu termotivasi oleh tujuan dan pengakuan eksternal. Tidak ada salahnya, tapi coba latih juga untuk menemukan kesenangan dalam proses belajarnya sendiri, bukan hanya hasilnya.',
                'C' => 'Kamu termotivasi oleh tujuan dan pengakuan eksternal. Tidak ada salahnya, tapi coba latih juga untuk menemukan kesenangan dalam proses belajarnya sendiri, bukan hanya hasilnya.',
            ];
            $sepuluh = [
                'A' => 'Luar biasa! Kamu memiliki Mental Baja. Kegagalan bagimu bukanlah akhir, tapi batu loncatan. Ini adalah kekuatan super terhebat yang bisa dimiliki seorang pelajar. ',
                'B' => 'Wajar sekali kadang merasa demikian. Ingat, tantangan adalah kesempatan untuk tumbuh. Coba ubah pertanyaannya dari "Mengapa aku gagal?" menjadi "Apa yang bisa pelajari dari ini?". ',
                'C' => 'Luar biasa! Kamu memiliki Mental Baja. Kegagalan bagimu bukanlah akhir, tapi batu loncatan. Ini adalah kekuatan super terhebat yang bisa dimiliki seorang pelajar. ',
                'D' => 'Wajar sekali kadang merasa demikian. Ingat, tantangan adalah kesempatan untuk tumbuh. Coba ubah pertanyaannya dari "Mengapa aku gagal?" menjadi "Apa yang bisa pelajari dari ini?". ',
            ];
            $ds = $sembilan[$answers[0]];
            $da = $sepuluh[$answers[1]];
            
            $prompt = "Kombinasikan dua motivasi ini menjadi satu teks apresiasi singkat untuk siswa SMA: 1) $ds, 2) $da. Outputkan teks saja maksimal 10 kata.";
            $hasil = $this->gemini->handle($prompt, 50);
            
            $hasilArr = ['text' => $hasil];
            DB::table('ai_generated')->updateOrInsert(
                ['key' => $key],
                ['answer' => json_encode($hasilArr), 'updated_at' => now()]
            );
            return $hasil;
        }

        return json_decode($ans->answer)->text;
    }

    public function handleMission(array $payload): object
    {
        $payloadJson = json_encode($payload);
        $hasil = $this->gemini->handle("Jadikan menjadi 2 misi mingguan bagi siswa berdasarkan persona ini $payloadJson. outputkan dalam bentuk json```{first:{title:string,text:string},second:{title:string,text:string}}```. tiap misi maksimal 3 kalimat yang tiap kalimat maksimal 20 kata", 250);
        $hasil = str_replace(['```', 'json'], '', $hasil);

        return json_decode($hasil);
    }
}
