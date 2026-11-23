<?php

namespace App\Controllers\Api;

use App\Services\OutboundActorResolver;
use App\Services\OutboundService;
use Config\Database;

class OutboundController extends ApiController
{
    protected OutboundService $outboundService;
    protected OutboundActorResolver $actorResolver;

    public function __construct()
    {
        $db = Database::connect('default');
        $this->outboundService = new OutboundService($db);
        $this->actorResolver = new OutboundActorResolver($db);
    }

    private function respond(array $result)
    {
        return $this->respondServiceResultWithCode($result, 200, 400, true);
    }

    private function requireActor(): array
    {
        return $this->actorResolver->resolve();
    }

    public function index()
    {
        try {
            $actorResult = $this->requireActor();
            if (!$actorResult['status']) {
                return $this->respond($actorResult);
            }

            return $this->respond($this->outboundService->getAll());
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data outbound');
        }
    }

    public function show($id = null)
    {
        try {
            $actorResult = $this->requireActor();
            if (!$actorResult['status']) {
                return $this->respond($actorResult);
            }

            return $this->respond($this->outboundService->getById($id));
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Gagal memuat data outbound');
        }
    }

    public function store()
    {
        try {
            $actorResult = $this->requireActor();
            if (!$actorResult['status']) {
                return $this->respond($actorResult);
            }

            $data = $this->request->getJSON(true) ?? $this->request->getPost();

            return $this->respond($this->outboundService->create($data ?? [], $actorResult['actor']));
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }

    public function update($id = null)
    {
        try {
            $actorResult = $this->requireActor();
            if (!$actorResult['status']) {
                return $this->respond($actorResult);
            }

            $data = $this->request->getJSON(true) ?? $this->request->getPost();

            return $this->respond($this->outboundService->update($id, $data ?? [], $actorResult['actor']));
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }

    public function delete($id = null)
    {
        try {
            $actorResult = $this->requireActor();
            if (!$actorResult['status']) {
                return $this->respond($actorResult);
            }

            return $this->respond($this->outboundService->delete($id, $actorResult['actor']));
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }

    public function complete($id = null)
    {
        try {
            $actorResult = $this->requireActor();
            if (!$actorResult['status']) {
                return $this->respond($actorResult);
            }

            return $this->respond($this->outboundService->complete($id, $actorResult['actor']));
        } catch (\Throwable $e) {
            return $this->respondException($e, 'Terjadi kesalahan pada server');
        }
    }
}
