<?php

namespace App\Actions\Psychologist;

use App\Enums\SlotStatus;
use App\Models\PsychologistSlot;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DeletePsychologistSlotAction
{
    public function handle(PsychologistSlot $slot): void
    {
        // Safe delete validation
        if ($slot->status !== SlotStatus::AVAILABLE) {
            throw new UnprocessableEntityHttpException('Hanya slot dengan status tersedia yang dapat dihapus.');
        }

        $slot->delete();
    }
}
