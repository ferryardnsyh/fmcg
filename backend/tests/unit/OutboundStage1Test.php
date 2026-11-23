<?php

require_once __DIR__ . '/../_support/EnsuresOutboundIdRange.php';

use App\Services\InboundService;
use App\Services\OutboundService;
use App\Services\ProductService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class OutboundStage1Test extends CIUnitTestCase
{
    use EnsuresOutboundIdRange;

    protected $db;
    protected OutboundService $service;
    protected int $productId;
    protected int $batchId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = Database::connect('default');
        $this->ensureOutboundIdRange($this->db);
        $this->db->transBegin();
        $this->service = new OutboundService($this->db);
        $this->db->table('users')->insert([
            'id_user'    => 2,
            'name'       => 'Admin Uji Tahap 1',
            'username'   => 'admin_st1',
            'password'   => password_hash('rahasia', PASSWORD_DEFAULT),
            'role'       => 'ADMIN',
            'status'     => 'AKTIF',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->productId = $this->createProduct();
        $this->batchId = $this->createBatch($this->productId, 100);
    }

    protected function tearDown(): void
    {
        for ($i = 0; $i < 5; $i++) {
            if (!$this->db->transRollback()) {
                break;
            }
        }

        parent::tearDown();
    }

    private function createProduct(): int
    {
        $this->db->table('products')->insert([
            'sku_code'    => 'TEST-OUT-' . strtoupper(bin2hex(random_bytes(5))),
            'name'        => 'Produk Uji Outbound',
            'unit'        => 'PCS',
            'minimum_stock' => 0,
            'status'      => 'AKTIF',
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return (int)$this->db->insertID();
    }

    private function createBatch(int $productId, int $quantity): int
    {
        $this->db->table('product_batches')->insert([
            'id_product'   => $productId,
            'batch_number' => 'BATCH-TEST-' . strtoupper(bin2hex(random_bytes(4))),
            'quantity'     => $quantity,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);

        return (int)$this->db->insertID();
    }

    private function detail(int $batchId, int $quantity, ?int $productId = null): array
    {
        return [
            'id_product' => $productId ?? $this->productId,
            'id_batch'   => $batchId,
            'quantity'   => $quantity,
        ];
    }

    private function actor(): array
    {
        return ['id' => 2, 'role' => 'ADMIN'];
    }

    private function createDraft(array $details): int
    {
        $result = $this->service->create([
            'delivery_date' => date('Y-m-d'),
            'details'       => $details,
        ], $this->actor());

        $this->assertTrue($result['status'], $result['message']);

        return (int)$result['data']['id_outbound'];
    }

    private function stock(int $batchId): int
    {
        $row = $this->db->table('product_batches')
            ->select('quantity')
            ->where('id_batch', $batchId)
            ->get()
            ->getRowArray();

        return (int)$row['quantity'];
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

    private function serviceWithDb(string $class)
    {
        $reflection = new \ReflectionClass($class);
        $instance = $reflection->newInstanceWithoutConstructor();
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        $property->setValue($instance, $this->db);

        return $instance;
    }

    public function test01CreateDraftSucceedsWithoutStockChange(): void
    {
        $before = $this->stock($this->batchId);

        $result = $this->service->create([
            'delivery_date' => date('Y-m-d'),
            'details'       => [$this->detail($this->batchId, 2)],
        ], $this->actor());

        $this->assertTrue($result['status']);
        $this->assertSame(201, $result['code']);
        $this->assertSame($before, $this->stock($this->batchId));

        $header = $this->header((int)$result['data']['id_outbound']);
        $this->assertSame('DRAFT', $header['status']);
    }

    public function test02CreateRejectsEmptyDetails(): void
    {
        $result = $this->service->create([
            'delivery_date' => date('Y-m-d'),
            'details'       => [],
        ], $this->actor());

        $this->assertFalse($result['status']);
        $this->assertSame(400, $result['code']);
    }

    public function test03CreateRejectsZeroQuantity(): void
    {
        $result = $this->service->create([
            'delivery_date' => date('Y-m-d'),
            'details'       => [$this->detail($this->batchId, 0)],
        ], $this->actor());

        $this->assertFalse($result['status']);
        $this->assertSame(400, $result['code']);
    }

    public function test04CreateRejectsNegativeQuantity(): void
    {
        $result = $this->service->create([
            'delivery_date' => date('Y-m-d'),
            'details'       => [$this->detail($this->batchId, -1)],
        ], $this->actor());

        $this->assertFalse($result['status']);
        $this->assertSame(400, $result['code']);
    }

    public function test05CreateRejectsFractionalQuantity(): void
    {
        $result = $this->service->create([
            'delivery_date' => date('Y-m-d'),
            'details'       => [[
                'id_product' => $this->productId,
                'id_batch'   => $this->batchId,
                'quantity'   => 1.5,
            ]],
        ], $this->actor());

        $this->assertFalse($result['status']);
        $this->assertSame(400, $result['code']);
    }

    public function test06CreateRejectsInvalidQuantityString(): void
    {
        $result = $this->service->create([
            'delivery_date' => date('Y-m-d'),
            'details'       => [[
                'id_product' => $this->productId,
                'id_batch'   => $this->batchId,
                'quantity'   => 'abc',
            ]],
        ], $this->actor());

        $this->assertFalse($result['status']);
        $this->assertSame(400, $result['code']);
    }

    public function test07CreateRejectsBatchProductMismatch(): void
    {
        $otherProductId = $this->createProduct();

        $result = $this->service->create([
            'delivery_date' => date('Y-m-d'),
            'details'       => [$this->detail($this->batchId, 1, $otherProductId)],
        ], $this->actor());

        $this->assertFalse($result['status']);
        $this->assertSame(400, $result['code']);
    }

    public function test08UpdateDraftSucceedsWithoutStockChange(): void
    {
        $id = $this->createDraft([$this->detail($this->batchId, 2)]);
        $before = $this->stock($this->batchId);

        $result = $this->service->update((string)$id, [
            'notes'   => 'Diubah',
            'details' => [$this->detail($this->batchId, 4)],
        ], $this->actor());

        $this->assertTrue($result['status']);
        $this->assertSame($before, $this->stock($this->batchId));

        $quantity = $this->db->table('outbound_details')
            ->select('quantity')
            ->where('id_outbound', $id)
            ->get()
            ->getRowArray();
        $this->assertSame(4, (int)$quantity['quantity']);
    }

    public function test09UpdateRejectsEmptyDetails(): void
    {
        $id = $this->createDraft([$this->detail($this->batchId, 2)]);
        $before = $this->stock($this->batchId);

        $result = $this->service->update((string)$id, ['details' => []], $this->actor());

        $this->assertFalse($result['status']);
        $this->assertSame(400, $result['code']);
        $this->assertSame('DRAFT', $this->header($id)['status']);
        $this->assertSame($before, $this->stock($this->batchId));
    }

    public function test10UpdateCompletedRejected(): void
    {
        $id = $this->createDraft([$this->detail($this->batchId, 2)]);
        $this->assertTrue($this->service->complete((string)$id, $this->actor())['status']);
        $afterComplete = $this->stock($this->batchId);

        $result = $this->service->update((string)$id, ['notes' => 'Diubah'], $this->actor());

        $this->assertFalse($result['status']);
        $this->assertSame(409, $result['code']);
        $this->assertSame('COMPLETED', $this->header($id)['status']);
        $this->assertSame($afterComplete, $this->stock($this->batchId));
    }

    public function test11DeleteDraftSucceedsWithoutStockChange(): void
    {
        $id = $this->createDraft([$this->detail($this->batchId, 2)]);
        $before = $this->stock($this->batchId);

        $result = $this->service->delete((string)$id, $this->actor());

        $this->assertTrue($result['status']);
        $this->assertSame($before, $this->stock($this->batchId));
        $this->assertSame(0, $this->db->table('outbound_headers')->where('id_outbound', $id)->countAllResults());
        $this->assertSame(0, $this->db->table('outbound_details')->where('id_outbound', $id)->countAllResults());
    }

    public function test12DeleteCompletedRejected(): void
    {
        $id = $this->createDraft([$this->detail($this->batchId, 2)]);
        $this->assertTrue($this->service->complete((string)$id, $this->actor())['status']);
        $afterComplete = $this->stock($this->batchId);

        $result = $this->service->delete((string)$id, $this->actor());

        $this->assertFalse($result['status']);
        $this->assertSame(409, $result['code']);
        $this->assertSame($afterComplete, $this->stock($this->batchId));
    }

    public function test13CompleteDraftReducesStockOnce(): void
    {
        $id = $this->createDraft([$this->detail($this->batchId, 3)]);
        $before = $this->stock($this->batchId);

        $result = $this->service->complete((string)$id, $this->actor());

        $this->assertTrue($result['status']);
        $this->assertSame($before - 3, $this->stock($this->batchId));
        $this->assertSame('COMPLETED', $this->header($id)['status']);
    }

    public function test14CompleteAgainRejectedWithoutStockChange(): void
    {
        $id = $this->createDraft([$this->detail($this->batchId, 3)]);
        $this->assertTrue($this->service->complete((string)$id, $this->actor())['status']);
        $afterFirst = $this->stock($this->batchId);

        $result = $this->service->complete((string)$id, $this->actor());

        $this->assertFalse($result['status']);
        $this->assertSame(409, $result['code']);
        $this->assertSame($afterFirst, $this->stock($this->batchId));
    }

    public function test15CompleteRejectsEmptyStoredDetails(): void
    {
        $this->db->table('outbound_headers')->insert([
            'delivery_number' => 'TEST-EMPTY-' . bin2hex(random_bytes(3)),
            'delivery_date'   => date('Y-m-d'),
            'status'          => 'DRAFT',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        $id = (int)$this->db->insertID();

        $result = $this->service->complete((string)$id, $this->actor());

        $this->assertFalse($result['status']);
        $this->assertSame(400, $result['code']);
        $this->assertSame('DRAFT', $this->header($id)['status']);
    }

    public function test16CompleteInsufficientStockKeepsDraftAndStock(): void
    {
        $this->db->table('product_batches')->where('id_batch', $this->batchId)->update(['quantity' => 2]);
        $id = $this->createDraft([$this->detail($this->batchId, 3)]);

        $result = $this->service->complete((string)$id, $this->actor());

        $this->assertFalse($result['status']);
        $this->assertSame(409, $result['code']);
        $this->assertSame('DRAFT', $this->header($id)['status']);
        $this->assertSame(2, $this->stock($this->batchId));
    }

    public function test17CompleteRollsBackAllBatchesWhenOneDetailFails(): void
    {
        $secondBatchId = $this->createBatch($this->productId, 10);
        $id = $this->createDraft([
            $this->detail($this->batchId, 1),
            $this->detail($secondBatchId, 1),
        ]);
        $firstBefore = $this->stock($this->batchId);
        $this->db->table('product_batches')->where('id_batch', $secondBatchId)->update(['quantity' => 0]);

        $result = $this->service->complete((string)$id, $this->actor());

        $this->assertFalse($result['status']);
        $this->assertSame(409, $result['code']);
        $this->assertSame($firstBefore, $this->stock($this->batchId));
        $this->assertSame(0, $this->stock($secondBatchId));
        $this->assertSame('DRAFT', $this->header($id)['status']);
    }

    public function test18CompleteSumsDuplicateBatchDetails(): void
    {
        $id = $this->createDraft([
            $this->detail($this->batchId, 2),
            $this->detail($this->batchId, 3),
        ]);
        $before = $this->stock($this->batchId);

        $result = $this->service->complete((string)$id, $this->actor());

        $this->assertTrue($result['status']);
        $this->assertSame($before - 5, $this->stock($this->batchId));
        $this->assertSame('COMPLETED', $this->header($id)['status']);
    }

    public function test19TwoDraftsCannotOverconsumeSameBatch(): void
    {
        $this->db->table('product_batches')->where('id_batch', $this->batchId)->update(['quantity' => 10]);
        $firstId = $this->createDraft([$this->detail($this->batchId, 6)]);
        $secondId = $this->createDraft([$this->detail($this->batchId, 6)]);

        $first = $this->service->complete((string)$firstId, $this->actor());
        $second = $this->service->complete((string)$secondId, $this->actor());

        $this->assertTrue($first['status']);
        $this->assertFalse($second['status']);
        $this->assertSame(409, $second['code']);
        $this->assertSame(4, $this->stock($this->batchId));
        $this->assertSame('COMPLETED', $this->header($firstId)['status']);
        $this->assertSame('DRAFT', $this->header($secondId)['status']);
    }

    public function test20FrontendUsesActualApiFields(): void
    {
        $path = ROOTPATH . '../frontend/src/pages/BarangKeluar/BarangKeluar.vue';
        $source = (string)file_get_contents($path);

        $this->assertStringContainsString(':key="item.id_outbound"', $source);
        $this->assertStringContainsString(':key="p.id_product"', $source);
        $this->assertStringContainsString(':value="p.id_product"', $source);
        $this->assertStringContainsString(':key="b.id_batch"', $source);
        $this->assertStringContainsString(':value="b.id_batch"', $source);
        $this->assertStringContainsString('b.batch_number', $source);
        $this->assertStringContainsString('id_product: d.id_product', $source);
        $this->assertStringContainsString('id_batch: d.id_batch', $source);
        $this->assertStringContainsString('outboundService.complete(item.id_outbound)', $source);
        $this->assertStringContainsString('outboundService.delete(item.id_outbound)', $source);
    }

    public function test21InboundAndMasterDataSmoke(): void
    {
        $inbound = $this->serviceWithDb(InboundService::class);
        $products = $this->serviceWithDb(ProductService::class);

        $this->assertTrue($inbound->getAll()['status']);
        $this->assertTrue($products->getProducts()['status']);
    }
}
