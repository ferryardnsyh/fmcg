<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    protected function isAdmin(): bool
    {
        return service('authContext')->allows(['admin']);
    }

    protected function denyIfNotAdmin()
    {
        if (!$this->isAdmin()) {
            $allowedOrigins = ['http://localhost:5173', 'http://localhost:5174', 'http://localhost:5176'];
            $origin = service('request')->getHeaderLine('Origin');
            $allowOrigin = in_array($origin, $allowedOrigins, true) ? $origin : $allowedOrigins[0];
            $this->response
                ->setStatusCode(403)
                ->setHeader('Access-Control-Allow-Origin', $allowOrigin)
                ->setHeader('Access-Control-Allow-Credentials', 'true')
                ->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
                ->setHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization, X-CSRF-TOKEN')
                ->setHeader('Access-Control-Max-Age', '7200')
                ->setJSON([
                    'status' => false,
                    'message' => 'Forbidden. Hanya ADMIN dan SUPERADMIN yang dapat melakukan operasi ini.'
                ])
                ->send();
            exit;
        }
    }

    protected function sanitizeString(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        $value = strip_tags($value);
        $value = trim($value);
        return $value === '' ? null : $value;
    }

    protected function sanitize(array $data, array $fields): array
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $this->sanitizeString((string)$data[$field]);
            }
        }
        return $data;
    }
}
