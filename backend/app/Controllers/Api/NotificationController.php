<?php

namespace App\Controllers\Api;

use App\Services\NotificationService;

class NotificationController extends ApiController
{
    protected NotificationService $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    public function index()
    {
        try {
            $params = $this->request->getGet();
            $params['user_role'] = service('authContext')->role();
            $result = $this->notificationService->getAll($params);
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat notifikasi');
        }
    }

    public function unread()
    {
        try {
            $result = $this->notificationService->getUnread(service('authContext')->role());
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat notifikasi');
        }
    }

    public function markRead($id = null)
    {
        try {
            $result = $this->notificationService->markAsRead($id, service('authContext')->role());
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal menandai notifikasi');
        }
    }

    public function markAllRead()
    {
        try {
            $result = $this->notificationService->markAllAsRead(service('authContext')->role());
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal menandai semua notifikasi');
        }
    }

    public function generate()
    {
        try {
            if (!in_array(service('authContext')->role(), ['SUPERADMIN', 'ADMIN'])) {
                return $this->respondError(
                    'Forbidden. Hanya ADMIN/SUPERADMIN yang dapat menghasilkan notifikasi.',
                    403
                );
            }

            $result = $this->notificationService->generateAllNotifications();
            return $this->respondServiceResult($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal menghasilkan notifikasi');
        }
    }

    public function summary()
    {
        try {
            $result = $this->notificationService->getNotificationSummary(service('authContext')->role());
            return $this->respondSuccessData($result);
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat ringkasan notifikasi');
        }
    }
}
