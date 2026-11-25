<?php

require_once __DIR__ . '/../_support/EnsuresOutboundIdRange.php';

use App\Services\OutboundActorResolver;
use App\Services\OutboundService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class OutboundStage2Test extends CIUnitTestCase
{
    use FeatureTestTrait;
    use EnsuresOutboundIdRange;

    protected $db;
    protected OutboundService $service;
    protected OutboundActorResolver $resolver;
    protected int $productId;
    protected int $batchId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = Database::connect('default');
        $this->ensureOutboundIdRange($this->db);
        $this->db->transBegin();
        $this->service = new OutboundService($this->db);
        $this->resolver = new OutboundActorResolver($this->db);
        $this->db->table('users')->insert([
            'id_user'    => 2,
            'name'       => 'Admin Uji Tahap 2',
            'username'   => 'admin_st2',
            'password'   => password_hash('rahasia', PASSWORD_DEFAULT),
            'role'       => 'ADMIN',
            'status'     => 'AKTIF',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->table('users')->insert([
            'id_user'    => 3,
            'name'       => 'Gudang Uji Tahap 2',
            'username'   => 'gudang_st2',
            'password'   => password_hash('rahasia', PASSWORD_DEFAULT),
            'role'       => 'GUDANG',
            'status'     => 'AKTIF',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->productId = $this->createProduct();
        $this->batchId = $this->createBatch(100);
    }

    protected function tearDown(): void
    {
        for ($i = 0; $i < 5; $i++) {
            if (!$this->db->transRollback()) {
                break;
            }
        }

        session()->destroy();

        parent::tearDown();
    }

    private function createProduct(): int
    {
        $this->db->table('products')->insert([
            'sku_code'      => 'TEST-ST2-' . strtoupper(bin2hex(random_bytes(5))),
            'name'          => 'Produk Uji Tahap 2',
            'unit'          => 'PCS',
            'minimum_stock' => 0,
            'status'        => 'AKTIF',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return (int)$this->db->insertID();
    }

    private function createBatch(int $quantity): int
    {
        $this->db->table('product_batches')->insert([
            'id_product'   => $this->productId,
            'batch_number' => 'BATCH-ST2-' . strtoupper(bin2hex(random_bytes(4))),
            'quantity'     => $quantity,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        return (int)$this->db->insertID();
    }

    private function createLegacyFixture(): void
    {
        $legacyIds = [10, 11, 28];

        $existing = $this->db->table('outbound_headers')
            ->whereIn('id_outbound', $legacyIds)
            ->countAllResults();
        if ($existing > 0) {
            throw new \RuntimeException('Fixture legacy sudah ada; pengujian dihentikan.');
        }

        $this->db->table('products')->insert([
            'sku_code'      => 'LEGACY-PROD-001',
            'name'          => 'Produk Legacy Sintetis',
            'unit'          => 'PCS',
            'minimum_stock' => 0,
            'status'        => 'AKTIF',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $productId = (int)$this->db->insertID();

        $this->db->table('product_batches')->insert([
            'id_product'   => $productId,
            'batch_number' => 'LEGACY-BATCH-001',
            'quantity'     => 10,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
        $batchId = (int)$this->db->insertID();

        foreach ($legacyIds as $id) {
            $this->db->table('outbound_headers')->insert([
                'id_outbound'     => $id,
                'delivery_number' => 'LEGACY-OUT-' . str_pad((string)$id, 5, '0', STR_PAD_LEFT),
                'delivery_date'   => '2026-01-10',
                'notes'           => 'FIXTURE LEGACY SINTETIS',
                'status'          => 'DRAFT',
                'created_by'      => 2,
                'created_at'      => date('Y-m-d H:i:s'),
            ]);

            $this->db->table('outbound_details')->insert([
                'id_outbound' => $id,
                'id_batch'    => $batchId,
                'quantity'    => 1,
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function createUser(string $role, string $status = 'AKTIF'): int
    {
        $this->db->table('users')->insert([
            'name'     => 'User Uji Tahap 2',
            'username' => 'st2_' . bin2hex(random_bytes(5)),
            'password' => password_hash('rahasia', PASSWORD_DEFAULT),
            'role'     => $role,
            'status'   => $status,
        ]);

        return (int)$this->db->insertID();
    }

    private function admin(): array
    {
        return ['id' => 2, 'role' => 'ADMIN'];
    }

    private function gudang(): array
    {
        return ['id' => 3, 'role' => 'GUDANG'];
    }

    private function detail(?int $batchId = null, $quantity = 1, ?int $productId = null): array
    {
        return [
            'id_product' => $productId ?? $this->productId,
            'id_batch'   => $batchId ?? $this->batchId,
            'quantity'   => $quantity,
        ];
    }

    private function payload(array $details): array
    {
        return [
            'delivery_date' => date('Y-m-d'),
            'details'       => $details,
        ];
    }

    private function createDraft(array $actor, array $details): int
    {
        $result = $this->service->create($this->payload($details), $actor);
        $this->assertTrue($result['status'], $result['message']);

        return (int)$result['data']['id_outbound'];
    }

    private function sessionFor(int $id, string $role): array
    {
        return [
            'logged_in' => true,
            'id_user'   => $id,
            'name'      => 'User Uji',
            'username'  => 'user_uji',
            'role'      => $role,
        ];
    }

    private function csrfSession(array $session): array
    {
        $response = $this->withSession($session)->get('api/csrf-token');
        $response->assertStatus(200);
        $body = json_decode((string)$response->getJSON(), true);
        $session[config('Security')->tokenName] = $body['data']['csrf_hash'];

        return $session;
    }

    private function header(int $id): array
    {
        $row = $this->db->table('outbound_headers')
            ->where('id_outbound', $id)
            ->get()
            ->getRowArray();

        $this->assertNotNull($row);

        return $row;
    }

    private function stock(): int
    {
        $row = $this->db->table('product_batches')
            ->where('id_batch', $this->batchId)
            ->get()
            ->getRowArray();

        return (int)$row['quantity'];
    }

    private function countHeaders(): int
    {
        return $this->db->table('outbound_headers')->countAllResults();
    }

    private function countDetails(): int
    {
        return $this->db->table('outbound_details')->countAllResults();
    }

    public function test01ResolverUsesAuthenticatedSessionAndDatabaseRole(): void
    {
        session()->set($this->sessionFor(3, 'VIEWER'));

        $result = $this->resolver->resolve();

        $this->assertTrue($result['status']);
        $this->assertSame(3, $result['actor']['id']);
        $this->assertSame('GUDANG', $result['actor']['role']);
    }

    public function test02CreateUsesActorIdAndIgnoresPayloadCreatedBy(): void
    {
        $headersBefore = $this->countHeaders();
        $stockBefore = $this->stock();
        $data = $this->payload([$this->detail(null, 2)]);
        $data['created_by'] = 999;

        $result = $this->service->create($data, $this->gudang());

        $this->assertTrue($result['status']);
        $header = $this->header((int)$result['data']['id_outbound']);
        $this->assertSame(3, (int)$header['created_by']);
        $this->assertSame('DRAFT', $header['status']);
        $this->assertSame($headersBefore + 1, $this->countHeaders());
        $this->assertSame($stockBefore, $this->stock());
    }

    public function test03CreateWithoutActorRejected(): void
    {
        $headersBefore = $this->countHeaders();
        $detailsBefore = $this->countDetails();
        $stockBefore = $this->stock();

        $result = $this->service->create($this->payload([$this->detail()]));

        $this->assertFalse($result['status']);
        $this->assertSame(401, $result['code']);
        $this->assertSame($headersBefore, $this->countHeaders());
        $this->assertSame($detailsBefore, $this->countDetails());
        $this->assertSame($stockBefore, $this->stock());
    }

    public function test04UnauthorizedRoleRejected(): void
    {
        $headersBefore = $this->countHeaders();

        $result = $this->service->create($this->payload([$this->detail()]), [
            'id'   => 3,
            'role' => 'VIEWER',
        ]);

        $this->assertFalse($result['status']);
        $this->assertSame(403, $result['code']);
        $this->assertSame($headersBefore, $this->countHeaders());
    }

    public function test05InvalidDateRejected(): void
    {
        $headersBefore = $this->countHeaders();

        $result = $this->service->create([
            'delivery_date' => '2026-99-99',
            'details'       => [$this->detail()],
        ], $this->admin());

        $this->assertFalse($result['status']);
        $this->assertSame(400, $result['code']);
        $this->assertSame($headersBefore, $this->countHeaders());
    }

    public function test06InvalidProductRejected(): void
    {
        $result = $this->service->create($this->payload([
            $this->detail(null, 1, 999999),
        ]), $this->admin());

        $this->assertFalse($result['status']);
        $this->assertSame(400, $result['code']);
    }

    public function test07InvalidBatchRejected(): void
    {
        $result = $this->service->create($this->payload([
            $this->detail(999999, 1),
        ]), $this->admin());

        $this->assertFalse($result['status']);
        $this->assertSame(400, $result['code']);
    }

    public function test08FailedDetailValidationLeavesNoPartialTransaction(): void
    {
        $headersBefore = $this->countHeaders();
        $detailsBefore = $this->countDetails();
        $stockBefore = $this->stock();

        $result = $this->service->create($this->payload([
            $this->detail(null, 1),
            $this->detail(null, 0),
        ]), $this->admin());

        $this->assertFalse($result['status']);
        $this->assertSame(400, $result['code']);
        $this->assertSame($headersBefore, $this->countHeaders());
        $this->assertSame($detailsBefore, $this->countDetails());
        $this->assertSame($stockBefore, $this->stock());
    }

    public function test09GudangCannotModifyOtherUsersDraft(): void
    {
        $id = $this->createDraft($this->admin(), [$this->detail(null, 2)]);
        $stockBefore = $this->stock();
        $before = $this->header($id);

        $update = $this->service->update((string)$id, ['notes' => 'Dicoba'], $this->gudang());
        $delete = $this->service->delete((string)$id, $this->gudang());
        $complete = $this->service->complete((string)$id, $this->gudang());

        $this->assertFalse($update['status']);
        $this->assertSame(403, $update['code']);
        $this->assertFalse($delete['status']);
        $this->assertSame(403, $delete['code']);
        $this->assertFalse($complete['status']);
        $this->assertSame(403, $complete['code']);
        $this->assertSame($before, $this->header($id));
        $this->assertSame($stockBefore, $this->stock());
    }

    public function test10GudangCanModifyOwnDraft(): void
    {
        $id = $this->createDraft($this->gudang(), [$this->detail(null, 2)]);

        $update = $this->service->update((string)$id, ['notes' => 'Milik sendiri'], $this->gudang());
        $complete = $this->service->complete((string)$id, $this->gudang());

        $this->assertTrue($update['status']);
        $this->assertTrue($complete['status']);
        $this->assertSame('COMPLETED', $this->header($id)['status']);
    }

    public function test11AdminCanModifyOtherUsersDraft(): void
    {
        $id = $this->createDraft($this->gudang(), [$this->detail(null, 2)]);

        $result = $this->service->update((string)$id, ['notes' => 'Ditinjau admin'], $this->admin());

        $this->assertTrue($result['status']);
        $this->assertSame('Ditinjau admin', $this->header($id)['notes']);
    }

    public function test12DraftTransactionsWithLegacyIdsRemainEditable(): void
    {
        $this->createLegacyFixture();

        foreach ([10, 11, 28] as $id) {
            $detailsBefore = $this->db->table('outbound_details')
                ->where('id_outbound', $id)
                ->countAllResults();

            $update = $this->service->update((string)$id, ['notes' => 'Uji'], $this->admin());
            $header = $this->header($id);

            $this->assertTrue($update['status']);
            $this->assertSame(200, $update['code']);
            $this->assertSame('Uji', $header['notes']);
            $this->assertSame('DRAFT', $header['status']);
            $this->assertSame($detailsBefore, $this->db->table('outbound_details')
                ->where('id_outbound', $id)
                ->countAllResults());
        }
    }

    public function test13CompletedTransactionCannotBeEditedOrDeleted(): void
    {
        $id = $this->createDraft($this->admin(), [$this->detail(null, 2)]);
        $this->service->complete((string)$id, $this->admin());
        $stockBefore = $this->stock();

        $update = $this->service->update((string)$id, ['notes' => 'Diubah'], $this->admin());
        $delete = $this->service->delete((string)$id, $this->admin());

        $this->assertSame(409, $update['code']);
        $this->assertSame(409, $delete['code']);
        $this->assertSame('COMPLETED', $this->header($id)['status']);
        $this->assertSame($stockBefore, $this->stock());
    }

    public function test14OversellingProtectionStillWorksWithActor(): void
    {
        $this->db->table('product_batches')->where('id_batch', $this->batchId)->update(['quantity' => 2]);
        $id = $this->createDraft($this->admin(), [$this->detail(null, 3)]);

        $result = $this->service->complete((string)$id, $this->admin());

        $this->assertFalse($result['status']);
        $this->assertSame(409, $result['code']);
        $this->assertSame('DRAFT', $this->header($id)['status']);
        $this->assertSame(2, $this->stock());
    }

    public function test15UnauthenticatedHttpRequestRejected(): void
    {
        $session = $this->csrfSession([]);
        $response = $this->withSession($session)
            ->withHeaders([config('Security')->headerName => $session[config('Security')->tokenName]])
            ->post('api/outbound', $this->payload([$this->detail()]));

        $response->assertStatus(401);
        $response->assertJSONFragment(['status' => false]);
    }

    public function test16AuthenticatedHttpRequestStoresSessionCreatedBy(): void
    {
        $headersBefore = $this->countHeaders();
        $data = $this->payload([$this->detail(null, 1)]);
        $data['created_by'] = 999;

        $session = $this->csrfSession($this->sessionFor(3, 'GUDANG'));
        $response = $this->withSession($session)
            ->withHeaders([config('Security')->headerName => $session[config('Security')->tokenName]])
            ->post('api/outbound', $data);

        $response->assertStatus(201);
        $body = json_decode((string)$response->getJSON(), true);
        $this->assertSame($headersBefore + 1, $this->countHeaders());
        $this->assertSame(3, (int)$this->header((int)$body['data']['id_outbound'])['created_by']);
    }

    public function test17UnauthorizedRoleHttpRequestRejected(): void
    {
        $viewerId = $this->createUser('VIEWER');
        $headersBefore = $this->countHeaders();

        $session = $this->csrfSession($this->sessionFor($viewerId, 'VIEWER'));
        $response = $this->withSession($session)
            ->withHeaders([config('Security')->headerName => $session[config('Security')->tokenName]])
            ->post('api/outbound', $this->payload([$this->detail()]));

        $response->assertStatus(403);
        $this->assertSame($headersBefore, $this->countHeaders());
    }
}
