<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponder;

    /**
     * Get user notifications
     *
     * Mengambil daftar notifikasi untuk pengguna yang sedang diautentikasi (semua role).
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: array<array{
     *     id: string,
     *     type: string,
     *     data: array<string, mixed>,
     *     read_at: string|null,
     *     created_at: string
     *   }>
     * }
     */
    #[Group('Notifications')]
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $query = $user->notifications()->latest();

        if ($request->has('unread') && $request->input('unread') !== null && $request->input('unread') !== '') {
            $isUnread = filter_var($request->input('unread'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isUnread === true) {
                $query->whereNull('read_at');
            } elseif ($isUnread === false) {
                $query->whereNotNull('read_at');
            }
        }

        $format = fn($n) => [
            'id'         => $n->id,
            'type'       => class_basename($n->type),
            'data'       => $n->data,
            'read_at'    => $n->read_at,
            'created_at' => $n->created_at,
        ];

        if ($request->has('page')) {
            $paginated = $query->paginate($request->input('per_page', 15));
            $paginated->getCollection()->transform($format);

            return $this->success($paginated, 'Notifikasi berhasil diambil.');
        }

        $notifications = $query->get()->map($format);

        return $this->success($notifications, 'Notifikasi berhasil diambil.');
    }

    /**
     * Get unread notifications count
     *
     * Mendapatkan jumlah notifikasi yang belum dibaca untuk pengguna yang sedang login.
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: array{
     *     unread_count: int
     *   }
     * }
     */
    #[Group('Notifications')]
    public function unreadCount(): JsonResponse
    {
        $count = auth()->user()->unreadNotifications()->count();

        return $this->success(['unread_count' => $count], 'Jumlah notifikasi belum dibaca berhasil diambil.');
    }

    /**
     * Mark a notification as read
     *
     * Menandai satu notifikasi milik pengguna yang sedang login sebagai telah dibaca.
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: null
     * }
     */
    #[Group('Notifications')]
    public function markAsRead($id): JsonResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return $this->success(null, 'Notifikasi berhasil ditandai telah dibaca.');
    }

    /**
     * Mark all notifications as read
     *
     * Menandai semua notifikasi belum dibaca milik pengguna sebagai telah dibaca.
     *
     * @response array{
     *   success: true,
     *   message: string,
     *   data: null
     * }
     */
    #[Group('Notifications')]
    public function markAllAsRead(): JsonResponse
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->success(null, 'Semua notifikasi berhasil ditandai telah dibaca.');
    }
}
