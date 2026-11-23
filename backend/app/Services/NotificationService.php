<?php

namespace App\Services;

use App\Models\NotificationModel;
use Config\Database;

class NotificationService
{
    private const TYPE_LOW_STOCK = 'low_stock';
    private const TYPE_OUT_OF_STOCK = 'out_of_stock';
    private const TYPE_NEAR_EXPIRY = 'near_expiry';
    private const TYPE_EXPIRED = 'expired';
    private const TYPE_PENDING_APPROVAL = 'pending_approval';
    private const TYPE_STOCK_ADJUSTMENT = 'stock_adjustment';
    private const TYPE_OPNAME_SELISIH = 'opname_selisih';
    private const TYPE_INBOUND_COMPLETED = 'inbound_completed';
    private const TYPE_OUTBOUND_COMPLETED = 'outbound_completed';
    private const TYPE_USER_ACTIVITY = 'user_activity';

    private const TYPES = [
        self::TYPE_OUT_OF_STOCK       => 'critical',
        self::TYPE_EXPIRED            => 'critical',
        self::TYPE_PENDING_APPROVAL   => 'critical',
        self::TYPE_LOW_STOCK          => 'high',
        self::TYPE_NEAR_EXPIRY        => 'high',
        self::TYPE_STOCK_ADJUSTMENT   => 'high',
        self::TYPE_OPNAME_SELISIH     => 'low',
        self::TYPE_INBOUND_COMPLETED  => 'low',
        self::TYPE_OUTBOUND_COMPLETED => 'low',
        self::TYPE_USER_ACTIVITY      => 'low',
    ];

    private const SUMMARY_TYPES = [
        self::TYPE_LOW_STOCK,
        self::TYPE_OUT_OF_STOCK,
        self::TYPE_NEAR_EXPIRY,
        self::TYPE_EXPIRED,
        self::TYPE_PENDING_APPROVAL,
    ];

    protected $db;
    protected NotificationModel $notificationModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->notificationModel = new NotificationModel();
    }

    private function priorityForType(string $type): string
    {
        return self::TYPES[$type] ?? 'low';
    }

    private function notificationReferenceQuery(string $type, ?string $referenceType = null)
    {
        $query = $this->db->table('notifications')
            ->select('reference_id')
            ->where('type', $type)
            ->where('reference_id IS NOT NULL');

        if ($referenceType !== null) {
            $query->where('reference_type', $referenceType);
        }

        return $query;
    }

    public function getAll(array $params = []): array
    {
        $builder = $this->db->table('notifications');

        $allowedFilters = ['type', 'priority', 'is_read'];
        foreach ($allowedFilters as $f) {
            if (array_key_exists($f, $params) && $params[$f] !== '' && $params[$f] !== null) {
                $builder->where($f, $params[$f]);
            }
        }

        if (!empty($params['search'])) {
            $s = $params['search'];
            $builder->groupStart()
                ->like('title', $s)
                ->orLike('message', $s)
                ->groupEnd();
        }

        $role = $params['user_role'] ?? null;
        if ($role && $role !== 'SUPERADMIN') {
            $builder->groupStart()
                ->where('user_role IS NULL')
                ->orWhere('user_role', $role)
                ->groupEnd();
        }

        $page = max(1, (int)($params['page'] ?? 1));
        $limit = max(1, min(100, (int)($params['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $total = $builder->countAllResults(false);

        $data = $builder
            ->orderBy('priority', 'ASC')
            ->orderBy('created_at', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();

        return [
            'status' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => (int) $total,
                'pages' => (int) ceil($total / $limit),
            ]
        ];
    }

    public function getUnread(?string $userRole = null): array
    {
        $builder = $this->db->table('notifications')
            ->where('is_read', 0);

        if ($userRole && $userRole !== 'SUPERADMIN') {
            $builder->groupStart()
                ->where('user_role IS NULL')
                ->orWhere('user_role', $userRole)
                ->groupEnd();
        }

        $total = $builder->countAllResults(false);

        $data = $builder
            ->orderBy('priority', 'ASC')
            ->orderBy('created_at', 'DESC')
            ->limit(20)
            ->get()
            ->getResultArray();

        return ['status' => true, 'data' => $data, 'total' => (int) $total];
    }

    public function markAsRead(string $id, ?string $userRole = null): array
    {
        $find = $this->db->table('notifications')
            ->select('id, is_read')
            ->where('id', $id);

        if ($userRole && $userRole !== 'SUPERADMIN') {
            $find->groupStart()
                ->where('user_role IS NULL')
                ->orWhere('user_role', $userRole)
                ->groupEnd();
        }

        $notification = $find->get()->getRowArray();

        if (!$notification) {
            return [
                'status'  => false,
                'message' => 'Notifikasi tidak ditemukan'
            ];
        }

        if ((int) $notification['is_read'] === 1) {
            return [
                'status'  => true,
                'message' => 'Notifikasi ditandai sudah dibaca'
            ];
        }

        $update = $this->db->table('notifications')
            ->where('id', $id)
            ->where('is_read', 0);

        if ($userRole && $userRole !== 'SUPERADMIN') {
            $update->groupStart()
                ->where('user_role IS NULL')
                ->orWhere('user_role', $userRole)
                ->groupEnd();
        }

        if (!$update->update([
            'is_read'    => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ])) {
            return [
                'status'  => false,
                'message' => 'Gagal menandai notifikasi'
            ];
        }

        return [
            'status'  => true,
            'message' => 'Notifikasi ditandai sudah dibaca'
        ];
    }

    public function markAllAsRead(?string $userRole = null): array
    {
        $builder = $this->db->table('notifications')
            ->where('is_read', 0);

        if ($userRole && $userRole !== 'SUPERADMIN') {
            $builder->groupStart()
                ->where('user_role IS NULL')
                ->orWhere('user_role', $userRole)
                ->groupEnd();
        }

        $builder->update(['is_read' => 1, 'updated_at' => date('Y-m-d H:i:s')]);

        return ['status' => true, 'message' => 'Semua notifikasi ditandai sudah dibaca'];
    }

    public function createNotification(
        string $type,
        string $title,
        ?string $message = null,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $userRole = null
    ): array {
        $data = [
            'type'           => $type,
            'priority'       => $this->priorityForType($type),
            'title'          => $title,
            'message'        => $message,
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'user_role'      => $userRole,
            'is_read'        => 0,
            'created_at'     => date('Y-m-d H:i:s'),
        ];

        $inserted = $this->notificationModel->insert($data);

        if (!$inserted) {
            return ['status' => false, 'message' => 'Gagal membuat notifikasi'];
        }

        return ['status' => true, 'message' => 'Notifikasi berhasil dibuat', 'data' => ['id' => $inserted]];
    }

    // ─────────────────────────────────────────────
    //  GENERATORS
    // ─────────────────────────────────────────────

    public function generateAllNotifications(): array
    {
        $count = 0;
        $count += $this->generateLowStockNotifications();
        $count += $this->generateOutOfStockNotifications();
        $count += $this->generateNearExpiryNotifications();
        $count += $this->generateExpiredNotifications();
        $count += $this->generatePendingApprovalNotifications();
        $count += $this->generatePendingAdjustmentNotifications();

        return [
            'status' => true,
            'message' => "Berhasil menghasilkan $count notifikasi baru",
            'data' => ['generated' => $count]
        ];
    }

    // ⚠️ LOW STOCK — stok <= minimum_stock (termasuk stok 0)
    public function generateLowStockNotifications(): int
    {
        $products = $this->db
            ->table('products p')
            ->select('
                p.id_product, p.sku_code, p.name, p.minimum_stock,
                COALESCE(SUM(pb.quantity), 0) AS total_available
            ')
            ->join('product_batches pb', 'pb.id_product = p.id_product', 'left')
            ->where('p.status', 'AKTIF')
            ->whereNotIn('p.id_product', $this->notificationReferenceQuery(self::TYPE_LOW_STOCK))
            ->groupBy('p.id_product')
            ->having('total_available <= p.minimum_stock')
            ->orderBy('total_available', 'ASC')
            ->get()
            ->getResultArray();

        $count = 0;
        foreach ($products as $p) {
            $this->createNotification(
                self::TYPE_LOW_STOCK,
                '⚠️ Stok Menipis: ' . $p['name'],
                "Produk: {$p['name']} ({$p['sku_code']})\n" .
                "Stok Saat Ini: {$p['total_available']}\n" .
                "Minimum Stok: {$p['minimum_stock']}\n" .
                "Silakan lakukan restock segera.",
                'product',
                $p['id_product']
            );
            $count++;
        }
        return $count;
    }

    // ❌ OUT OF STOCK — stok = 0
    public function generateOutOfStockNotifications(): int
    {
        $products = $this->db
            ->table('products p')
            ->select('
                p.id_product, p.sku_code, p.name,
                COALESCE(SUM(pb.quantity), 0) AS total_available
            ')
            ->join('product_batches pb', 'pb.id_product = p.id_product', 'left')
            ->where('p.status', 'AKTIF')
            ->whereNotIn('p.id_product', $this->notificationReferenceQuery(self::TYPE_OUT_OF_STOCK))
            ->groupBy('p.id_product')
            ->having('total_available', 0)
            ->get()
            ->getResultArray();

        $count = 0;
        foreach ($products as $p) {
            $this->createNotification(
                self::TYPE_OUT_OF_STOCK,
                '❌ Stok Habis: ' . $p['name'],
                "Produk: {$p['name']} ({$p['sku_code']})\n" .
                "Stok telah mencapai nol.\n" .
                "Segera lakukan pengadaan.",
                'product',
                $p['id_product']
            );
            $count++;
        }
        return $count;
    }

    // ⏳ NEAR EXPIRY — dalam 30 hari
    public function generateNearExpiryNotifications(): int
    {
        $today = date('Y-m-d');
        $thirtyDays = date('Y-m-d', strtotime('+30 days'));

        $batches = $this->db
            ->table('product_batches pb')
            ->select('pb.id_batch, pb.batch_number, pb.expiry_date, pb.quantity, p.name AS product_name, p.sku_code')
            ->join('products p', 'p.id_product = pb.id_product', 'left')
            ->where('pb.expiry_date >=', $today)
            ->where('pb.expiry_date <=', $thirtyDays)
            ->where('pb.quantity >', 0)
            ->whereNotIn('pb.id_batch', $this->notificationReferenceQuery(self::TYPE_NEAR_EXPIRY))
            ->orderBy('pb.expiry_date', 'ASC')
            ->get()
            ->getResultArray();

        $count = 0;
        foreach ($batches as $b) {
            $daysLeft = (int)((strtotime($b['expiry_date']) - strtotime($today)) / 86400);
            $threshold = $daysLeft <= 1 ? '1 Hari' : ($daysLeft <= 3 ? '3 Hari' : ($daysLeft <= 7 ? '7 Hari' : ($daysLeft <= 14 ? '14 Hari' : '30 Hari')));

            $this->createNotification(
                self::TYPE_NEAR_EXPIRY,
                "⏳ Akan Kadaluarsa: {$b['product_name']} ({$threshold})",
                "Produk: {$b['product_name']} ({$b['sku_code']})\n" .
                "Batch: {$b['batch_number']}\n" .
                "Kadaluarsa: {$b['expiry_date']}\n" .
                "Sisa: {$daysLeft} Hari\n" .
                "Stok: {$b['quantity']}",
                'batch',
                $b['id_batch']
            );
            $count++;
        }
        return $count;
    }

    // ❌ EXPIRED — expiry_date < today
    public function generateExpiredNotifications(): int
    {
        $today = date('Y-m-d');

        $batches = $this->db
            ->table('product_batches pb')
            ->select('pb.id_batch, pb.batch_number, pb.expiry_date, pb.quantity, p.name AS product_name, p.sku_code')
            ->join('products p', 'p.id_product = pb.id_product', 'left')
            ->where('pb.expiry_date <', $today)
            ->where('pb.quantity >', 0)
            ->whereNotIn('pb.id_batch', $this->notificationReferenceQuery(self::TYPE_EXPIRED))
            ->orderBy('pb.expiry_date', 'DESC')
            ->get()
            ->getResultArray();

        $count = 0;
        foreach ($batches as $b) {
            $this->createNotification(
                self::TYPE_EXPIRED,
                '❌ Produk Kadaluarsa: ' . $b['product_name'],
                "Produk: {$b['product_name']} ({$b['sku_code']})\n" .
                "Batch: {$b['batch_number']}\n" .
                "Kadaluarsa: {$b['expiry_date']}\n" .
                "Status: KADALUARSA - Segera tarik dari rak!",
                'batch',
                $b['id_batch']
            );
            $count++;
        }
        return $count;
    }

    // 📋 PENDING APPROVAL — inbound DRAFT
    public function generatePendingApprovalNotifications(): int
    {
        $inbounds = $this->db
            ->table('inbound_headers ih')
            ->select('ih.id_inbound, ih.reception_number, ih.reception_date, ih.created_at, s.name AS supplier_name')
            ->join('suppliers s', 's.id_supplier = ih.id_supplier', 'left')
            ->where('ih.status', 'DRAFT')
            ->whereNotIn('ih.id_inbound', $this->notificationReferenceQuery(self::TYPE_PENDING_APPROVAL, 'inbound'))
            ->orderBy('ih.created_at', 'DESC')
            ->get()
            ->getResultArray();

        $count = 0;
        foreach ($inbounds as $inb) {
            $this->createNotification(
                self::TYPE_PENDING_APPROVAL,
                '📥 Inbound Butuh Approval: ' . $inb['reception_number'],
                "No. Penerimaan: {$inb['reception_number']}\n" .
                "Supplier: {$inb['supplier_name']}\n" .
                "Tanggal: {$inb['reception_date']}\n" .
                "Menunggu persetujuan.",
                'inbound',
                $inb['id_inbound'],
                'ADMIN'
            );
            $count++;
        }
        return $count;
    }

    // 📥 INBOUND COMPLETED
    public function createInboundCompletedNotification(string $id, string $receptionNumber, ?string $supplierName, int $itemCount): array
    {
        $this->deleteByReference('inbound', $id);

        return $this->createNotification(
            self::TYPE_INBOUND_COMPLETED,
            "📥 Barang Masuk: {$receptionNumber}",
            "No. Penerimaan: {$receptionNumber}\n" .
            "Supplier: {$supplierName}\n" .
            "Item: {$itemCount} Produk\n" .
            "Berhasil diterima.",
            'inbound',
            $id
        );
    }

    // 📤 OUTBOUND COMPLETED
    public function createOutboundCompletedNotification(string $id, string $deliveryNumber, int $itemCount): array
    {
        return $this->createNotification(
            self::TYPE_OUTBOUND_COMPLETED,
            "📤 Barang Keluar: {$deliveryNumber}",
            "No. Pengiriman: {$deliveryNumber}\n" .
            "Item: {$itemCount} Produk\n" .
            "Stok berhasil diperbarui.",
            'outbound',
            $id
        );
    }

    // 📋 STOCK OPNAME SELISIH
    public function createOpnameDifferenceNotification(string $sessionId, string $sessionNumber, int $totalDifference, int $productCount): array
    {
        $existing = $this->db->table('notifications')
            ->select('id')
            ->where('type', self::TYPE_OPNAME_SELISIH)
            ->where('reference_id', $sessionId)
            ->limit(1)
            ->get()
            ->getRow();

        if ($existing !== null) {
            $this->db->table('notifications')
                ->where('type', self::TYPE_OPNAME_SELISIH)
                ->where('reference_id', $sessionId)
                ->update(['is_read' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
            return ['status' => true, 'message' => 'Notifikasi diperbarui'];
        }

        $sign = $totalDifference >= 0 ? '+' : '';
        return $this->createNotification(
            self::TYPE_OPNAME_SELISIH,
            "📋 Opname Selesai: {$sessionNumber}",
            "Sesi: {$sessionNumber}\n" .
            "Produk dengan Selisih: {$productCount}\n" .
            "Total Selisih: {$sign}{$totalDifference}\n" .
            "Adjustment diperlukan.",
            'opname_session',
            $sessionId
        );
    }

    // ⚠️ ADJUSTMENT PENDING APPROVAL
    public function generatePendingAdjustmentNotifications(): int
    {
        $adjustments = $this->db
            ->table('stock_adjustments sa')
            ->select('sa.id_adjustment, sa.type, sa.quantity, sa.reason, sa.created_at, pb.id_product, p.name AS product_name')
            ->join('product_batches pb', 'pb.id_batch = sa.id_batch', 'left')
            ->join('products p', 'p.id_product = pb.id_product', 'left')
            ->where('sa.status', 'PENDING')
            ->whereNotIn('sa.id_adjustment', $this->notificationReferenceQuery(self::TYPE_PENDING_APPROVAL, 'adjustment'))
            ->orderBy('sa.created_at', 'DESC')
            ->get()
            ->getResultArray();

        $count = 0;
        foreach ($adjustments as $adj) {
            $this->createNotification(
                self::TYPE_PENDING_APPROVAL,
                '🔄 Adjustment Butuh Approval: ' . ($adj['product_name'] ?? 'Produk'),
                "Produk: " . ($adj['product_name'] ?? '-') . "\n" .
                "Tipe: {$adj['type']}\n" .
                "Jumlah: {$adj['quantity']}\n" .
                "Alasan: {$adj['reason']}\n" .
                "Menunggu persetujuan.",
                'adjustment',
                (string) $adj['id_adjustment'],
                'ADMIN'
            );
            $count++;
        }
        return $count;
    }

    public function deleteByReference(string $referenceType, string $referenceId): void
    {
        $this->db->table('notifications')
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->delete();
    }

    // ─────────────────────────────────────────────
    //  DASHBOARD SUMMARY
    // ─────────────────────────────────────────────
    public function getNotificationSummary(?string $userRole = null): array
    {
        $builder = $this->db
            ->table('notifications')
            ->select('type, COUNT(*) AS total')
            ->where('is_read', 0);

        if ($userRole && $userRole !== 'SUPERADMIN') {
            $builder->groupStart()
                ->where('user_role IS NULL')
                ->orWhere('user_role', $userRole)
                ->groupEnd();
        }

        $counts = $builder
            ->groupBy('type')
            ->get()
            ->getResultArray();

        $summary = array_fill_keys(self::SUMMARY_TYPES, 0);

        $total = 0;
        foreach ($counts as $c) {
            if (isset($summary[$c['type']])) {
                $summary[$c['type']] = (int) $c['total'];
            }
            $total += (int) $c['total'];
        }

        $summary['total'] = $total;

        return $summary;
    }
}
