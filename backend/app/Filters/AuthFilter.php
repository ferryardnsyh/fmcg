<?php

namespace App\Filters;

use App\Auth\AuthContext;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;
use Config\Database;

class AuthFilter implements FilterInterface
{
    protected $db;

    protected AuthContext $context;

    public function __construct($db = null, ?AuthContext $context = null)
    {
        $this->db      = $db ?? Database::connect('default');
        $this->context = $context ?? new AuthContext($this->db);
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $response = service('response');

        if (!session()->get('logged_in')) {
            return $this->fail($response, 401, 'Unauthorized. Silakan login terlebih dahulu.');
        }

        $user   = $this->context->current();
        $reason = $this->context->failureCode();

        if ($reason === 'invalid_id' || $reason === 'not_found') {
            session()->destroy();

            return $this->fail($response, 401, 'Unauthorized. Silakan login terlebih dahulu.');
        }

        if ($reason === 'inactive') {
            session()->destroy();

            return $this->fail($response, 401, 'Akun sudah dinonaktifkan. Silakan hubungi administrator.');
        }

        if ($user === null) {
            return $this->fail($response, 401, 'Unauthorized. Silakan login terlebih dahulu.');
        }

        if (!empty($arguments) && !$this->context->allows($arguments)) {
            return $this->fail($response, 403, 'Forbidden. Anda tidak memiliki akses ke resource ini.');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }

    private function fail(ResponseInterface $response, int $code, string $message): ResponseInterface
    {
        $response->setStatusCode($code);
        $response->setJSON([
            'status'  => false,
            'message' => $message,
        ]);

        return $response;
    }
}
