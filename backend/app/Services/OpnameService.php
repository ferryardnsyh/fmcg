<?php

namespace App\Services;

use App\Services\Concerns\ManagesOpnameWorkflows;
use App\Services\Concerns\ServiceResults;
use App\Services\Concerns\ValidatesServiceActors;
use App\Support\WmsPolicy;
use App\Validation\MasterInput;
use Config\Database;

class OpnameService
{
    use ServiceResults;
    use ValidatesServiceActors;
    use ManagesOpnameWorkflows;

    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function createSession(array $data, ?array $actor = null): array
    {
        $error = $this->rejectAbsentActor($actor);
        if ($error !== null) {
            return $error;
        }

        if (empty($data['opname_date'])) {
            return $this->makeFailureResult('Tanggal opname wajib diisi');
        }

        if (!MasterInput::isValidIsoDate($data['opname_date'])) {
            return $this->makeFailureResult('Tanggal opname harus berformat YYYY-MM-DD');
        }

        return $this->runServiceTransaction(
            fn () => $this->insertOpnameSession($data, $actor),
            'OpnameService::createSession',
            'Gagal membuat sesi opname',
            null
        );
    }

    public function getAllSessions(): array
    {
        $sessions = $this->db
            ->table('stock_opname_sessions sos')
            ->select('
                sos.*,
                u.name AS created_by_name
            ')
            ->join('users u', 'u.id_user = sos.created_by', 'left')
            ->orderBy('sos.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return [
            'status' => true,
            'data' => $sessions
        ];
    }

    public function getSessionById(int $id): array
    {
        $session = $this->db
            ->table('stock_opname_sessions sos')
            ->select('
                sos.*,
                u.name AS created_by_name
            ')
            ->join('users u', 'u.id_user = sos.created_by', 'left')
            ->where('sos.id_opname', $id)
            ->get()
            ->getRowArray();

        if (!$session) {
            return $this->makeFailureResult('Sesi opname tidak ditemukan');
        }

        $details = $this->db
            ->table('stock_opname_details sod')
            ->select('
                sod.*,
                p.name AS product_name,
                p.sku_code,
                pb.batch_number
            ')
            ->join('stock_opname_sessions sos', 'sod.id_opname = sos.id_opname', 'inner')
            ->join('product_batches pb', 'pb.id_batch = sod.id_batch', 'left')
            ->join('products p', 'p.id_product = pb.id_product', 'left')
            ->where('sod.id_opname', $session['id_opname'])
            ->get()
            ->getResultArray();

        $session['details'] = $details;

        return [
            'status' => true,
            'data' => $session
        ];
    }

    public function addDetail(array $data): array
    {
        if (empty($data['id_opname']) || empty($data['id_batch'])) {
            return $this->makeFailureResult('Opname ID dan Batch ID wajib diisi');
        }

        $this->db->transBegin();

        try {
            [$session, $error] = $this->lockOpnameSession(
                $data['id_opname'],
                'Hanya sesi dengan status DRAFT yang bisa ditambahkan detail'
            );
            if ($error !== null) {
                return $error;
            }

            [$batch, $error] = $this->lockOpnameBatch($data['id_batch']);
            if ($error !== null) {
                return $error;
            }

            return $this->saveOpnameDetail($data, $batch, $session);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'OpnameService::addDetail: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());

            return $this->makeFailureResult('Gagal menyimpan detail opname');
        }
    }

    public function completeSession(int $id): array
    {
        $this->db->transBegin();

        try {
            [$session, $details, $error] = $this->lockOpnameCompletion($id);
            if ($error !== null) {
                return $error;
            }

            return $this->applyOpnameCompletion($id, $session, $details);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'OpnameService::completeSession: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());

            return $this->makeFailureResult('Gagal menyelesaikan opname');
        }
    }

    public function deleteSession(int $id): array
    {
        $session = $this->db->table('stock_opname_sessions')
            ->where('id_opname', $id)
            ->get()
            ->getRowArray();

        if (!$session) {
            return $this->makeFailureResult('Sesi opname tidak ditemukan');
        }

        if ($session['status'] === WmsPolicy::STATUS_COMPLETED) {
            return $this->makeFailureResult('Sesi opname yang sudah selesai tidak bisa dihapus');
        }

        return $this->runServiceTransaction(function () use ($id) {
            $this->db->table('stock_opname_details')->where('id_opname', $id)->delete();
            $this->db->table('stock_opname_sessions')->where('id_opname', $id)->delete();

            return $this->makeSuccessResult('Sesi opname berhasil dihapus');
        }, 'OpnameService::deleteSession', 'Gagal menghapus sesi opname', null);
    }
}
