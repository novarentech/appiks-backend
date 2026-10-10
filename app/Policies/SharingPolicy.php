<?php

namespace App\Policies;

use App\Enums\ReportStatus;
use App\Enums\UserRole;
use App\Models\Sharing;
use App\Models\User;
use App\Support\CaseTimelineVisibility;

class SharingPolicy
{
    public function view(User $user, Sharing $sharing): bool
    {
        return $sharing->user_id == $user->id || $sharing->user->counselor_id == $user->id;
    }

    public function falsePositive(User $user, Sharing $sharing): bool
    {
        return $sharing->user->counselor_id == $user->id && $sharing->status == ReportStatus::MENUNGGU_TINJAUAN;
    }

    public function create(User $user): bool
    {
        return $user->role == UserRole::STUDENT->value;
    }

    public function update(User $user, Sharing $sharing): bool
    {
        return $user->role == UserRole::COUNSELOR->value && $user->id == $sharing->user->counselor_id;
    }

    /**
     * Siapa boleh melihat jejak penanganan sebuah curhat.
     *
     * Saat ini hanya Kepala Sekolah, atas permintaan pemilik produk. Struktur
     * match di bawah sengaja memuat seluruh peran supaya membuka akses nanti
     * cukup satu baris — ganti `false` dengan kondisi yang dikomentari, lalu
     * tambahkan peran itu di CaseTimelineVisibility::RULES.
     */
    public function viewTimeline(User $user, Sharing $sharing): bool
    {
        if (! CaseTimelineVisibility::isAllowed($user->role)) {
            return false;
        }

        return match ($user->role) {
            UserRole::HEADTEACHER->value => $user->school_id !== null
                && $user->school_id == $sharing->user?->school_id,

            // Rencana saat akses dibuka nanti:
            // UserRole::STUDENT->value   => $sharing->user_id == $user->id,
            // UserRole::COUNSELOR->value => $sharing->user?->counselor_id == $user->id,
            // UserRole::SUPER->value     => true,
            default => false,
        };
    }

    public function viewGraph(User $authUser): bool
    {
        return $authUser->role === UserRole::COUNSELOR->value
            || $authUser->role === UserRole::SUPER->value;
    }

    public function viewStudentSharing(User $authUser, User $targetUser): bool
    {
        return $authUser->role === UserRole::SUPER->value
            && $targetUser->role === UserRole::STUDENT->value;
    }
}
