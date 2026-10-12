<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponder;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
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
     * @queryParam unread boolean Filter status terbaca (true untuk belum dibaca, false untuk sudah dibaca). Example: true
     * @queryParam class string Filter jenis notifikasi (misal: RedZoneAlertNotification). Example: RedZoneAlertNotification
     * @queryParam per_page int Jumlah notifikasi per halaman jika menggunakan pagination. Default: 15. Example: 15
     * @queryParam page int Halaman pagination. Example: 1
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
    #[QueryParameter('class', description: 'Filter jenis notifikasi (misal: RedZoneAlertNotification).', type: 'string')]
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

        $this->applyClassFilter($query, $request);

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
     * @queryParam class string Filter jenis notifikasi (misal: RedZoneAlertNotification). Example: RedZoneAlertNotification
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
    #[QueryParameter('class', description: 'Filter jenis notifikasi (misal: RedZoneAlertNotification).', type: 'string')]
    public function unreadCount(Request $request): JsonResponse
    {
        $query = auth()->user()->unreadNotifications();
        $this->applyClassFilter($query, $request);
        $count = $query->count();

        return $this->success(['unread_count' => $count], 'Jumlah notifikasi belum dibaca berhasil diambil.');
    }

    /**
     * Filter query notifikasi berdasarkan parameter class.
     */
    protected function applyClassFilter($query, Request $request): void
    {
        if ($request->filled('class')) {
            $rawClass = $request->input('class');
            $classes = is_array($rawClass) ? $rawClass : explode(',', (string) $rawClass);

            $query->where(function ($q) use ($classes) {
                foreach ($classes as $c) {
                    $c = trim($c);
                    if ($c === '') {
                        continue;
                    }
                    $q->orWhere('type', $c)
                      ->orWhere('type', 'App\\Notifications\\' . $c)
                      ->orWhere('type', 'like', '%\\' . $c);
                }
            });
        }
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
