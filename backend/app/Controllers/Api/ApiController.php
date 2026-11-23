<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

abstract class ApiController extends BaseController
{
    protected function respondServiceResult(array $result, int $successCode = 200, int $failureCode = 400): ResponseInterface
    {
        return $this->response
            ->setStatusCode($result['status'] ? $successCode : $failureCode)
            ->setJSON($result);
    }

    protected function respondServiceResultWithCode(array $result, int $successCode = 200, int $failureCode = 400, bool $removeCode = false): ResponseInterface
    {
        if (empty($result['status']) && !isset($result['code'])) {
            $result['code'] = $failureCode;
        }

        $code = (int) ($result['code'] ?? $successCode);

        if ($removeCode) {
            unset($result['code']);
        }

        return $this->response->setStatusCode($code)->setJSON($result);
    }

    protected function respondSuccessData(array $data, int $statusCode = 200): ResponseInterface
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'status' => true,
                'data'   => $data,
            ]);
    }

    protected function respondError(string $message, int $statusCode = 500): ResponseInterface
    {
        return $this->response
            ->setStatusCode($statusCode)
            ->setJSON([
                'status'  => false,
                'message' => $message,
            ]);
    }

    protected function respondException(Throwable $exception, string $message, int $statusCode = 500): ResponseInterface
    {
        $this->logger->error(
            'API exception: {class} at {file}:{line} - {message}',
            [
                'class'   => $exception::class,
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
                'message' => $exception->getMessage(),
            ]
        );

        return $this->respondError($message, $statusCode);
    }
}
