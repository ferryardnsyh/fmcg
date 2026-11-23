<?php

namespace App\Services\Concerns;

use RuntimeException;
use Throwable;

trait ServiceResults
{
    private function makeSuccessResult(string $message, ?int $code = null, ?array $data = null): array
    {
        $result = [
            'status'  => true,
            'message' => $message,
        ];

        if ($code !== null) {
            $result['code'] = $code;
        }

        if ($data !== null) {
            $result['data'] = $data;
        }

        return $result;
    }

    private function makeFailureResult(string $message, ?int $code = null): array
    {
        $result = [
            'status'  => false,
            'message' => $message,
        ];

        if ($code !== null) {
            $result['code'] = $code;
        }

        return $result;
    }

    private function runServiceTransaction(
        callable $operation,
        string $logContext,
        string $failureMessage,
        ?int $failureCode = 500,
        bool $verifyCommit = false
    ): array {
        $this->db->transBegin();

        try {
            $result = $operation();

            if (is_array($result) && ($result['status'] ?? null) === false) {
                $this->db->transRollback();

                return $result;
            }

            if ($verifyCommit) {
                if (!$this->db->transCommit()) {
                    throw new RuntimeException('Gagal menyimpan transaksi');
                }
            } else {
                $this->db->transCommit();
            }

            return $result;
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', $logContext . ': ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());

            return $this->makeFailureResult($failureMessage, $failureCode);
        }
    }
}
