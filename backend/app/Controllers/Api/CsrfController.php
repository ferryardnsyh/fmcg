<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class CsrfController extends BaseController
{
    public function index()
    {
        $security = service('security');

        return $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setJSON([
                'status' => true,
                'data'   => [
                    'csrf_hash'  => $security->getHash(),
                    'token_name' => $security->getTokenName(),
                    'header_name' => $security->getHeaderName(),
                ],
            ]);
    }
}
