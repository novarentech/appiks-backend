<?php

namespace Database\Factories;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sharing>
 */
class SharingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Weight distribution: new/in-progress sharings are most common
        $status = fake()->randomElement([
            ReportStatus::MENUNGGU_TINJAUAN->value,  // Belum Ditinjau
            ReportStatus::MENUNGGU_TINJAUAN->value,  // (weighted x2 — most frequent)
            ReportStatus::DITINJAU->value,            // Sedang Ditangani
            ReportStatus::DITINJAU->value,            // (weighted x2)
            ReportStatus::MENUNGGU_TANGGAPAN->value, // Belum Ditanggapi
            ReportStatus::DIJADWALKAN->value,         // Konseling Dijadwalkan
            ReportStatus::SELESAI->value,             // Diselesaikan
            ReportStatus::DIBATALKAN->value,          // Dibatalkan
        ]);

        $hasReply = in_array($status, [
            ReportStatus::DITINJAU->value,
            ReportStatus::DIJADWALKAN->value,
            ReportStatus::SELESAI->value,
        ]);

        return [
            'user_id'    => fake()->randomNumber(),
            'title'      => fake()->sentence(),
            'description' => fake()->text(),
            'reply'      => $hasReply ? fake()->text() : null,
            'replied_at' => $hasReply ? fake()->date() : null,
            'replied_by' => $hasReply ? fake()->name() : null,
            'priority'   => fake()->randomElement(['tinggi', 'rendah']),
            'status'     => $status,
            'created_at' => now(),
        ];
    }
}
