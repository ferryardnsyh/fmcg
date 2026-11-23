<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Master\MasterDataService;

class MasterController extends BaseController
{
    protected MasterDataService $masterDataService;

    public function __construct()
    {
        $this->masterDataService = new MasterDataService();
    }

    public function categories()
    {
        $data = $this->masterDataService->categories();

        return $this->response->setJSON(['status' => true, 'data' => $data]);
    }

    public function suppliers()
    {
        $data = $this->masterDataService->suppliers();

        return $this->response->setJSON(['status' => true, 'data' => $data]);
    }

    public function rackLocations()
    {
        $data = $this->masterDataService->rackLocations();

        return $this->response->setJSON(['status' => true, 'data' => $data]);
    }
}
