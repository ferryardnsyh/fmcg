<?php

require_once __DIR__ . '/../_support/EnsuresOutboundIdRange.php';

use App\Services\DashboardService;
use App\Services\LaporanService;
use App\Services\OutboundService;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class LaporanBarangKeluarTest extends CIUnitTestCase
{
    use EnsuresOutboundIdRange;

    protected $db;
    protected LaporanService $laporan;
    protected DashboardService $dashboard;
    protected OutboundService $outbound;
    protected int $productId;
    protected int $batchId;
    protected int $userId;
    protected string $productName;
    protected array $completed;
    protected array $draft;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = Database::connect('default');
        $this->ensureOutboundIdRange($this->db);
        $this->db->transBegin();
        $this->laporan = $this->serviceWithDb(LaporanService::class);
        $this->dashboard = $this->serviceWithDb(DashboardService::class);
        $this->outbound = $this->serviceWithDb(OutboundService::class);

        $this->db->table('users')->insert([
            'name'       => 'User Uji Laporan',
            'username'   => 'lap3_' . strtoupper(bin2hex(random_bytes(5))),
            'password'   => password_hash('rahasia', PASSWORD_DEFAULT),
            'role'       => 'ADMIN',
            'status'     => 'AKTIF',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->userId = (int)$this->db->insertID();

        $this->productName = 'Produk Uji Laporan 3 ' . bin2hex(random_bytes(3));
        $this->db->table('products')->insert([
            'sku_code'      => 'TEST-LAP3-' . strtoupper(bin2hex(random_bytes(5))),
            'name'          => $this->productName,
            'unit'          => 'PCS',
            'minimum_stock' => 0,
            'status'        => 'AKTIF',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $this->productId = (int)$this->db->insertID();

        $this->db->table('product_batches')->insert([
            'id_product'   => $this->productId,
            'batch_number' => 'BATCH-LAP3-' . strtoupper(bin2hex(random_bytes(4))),
            'quantity'     => 20000,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
        $this->batchId = (int)$this->db->insertID();

        $this->completed = $this->createHeader('COMPLETED', date('Y-m-d'), 9999);
        $this->draft = $this->createHeader('DRAFT', date('Y-m-d'), 5);
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

    private function serviceWithDb(string $class)
    {
        $reflection = new \ReflectionClass($class);
        $instance = $reflection->newInstanceWithoutConstructor();
        $property = $reflection->getProperty('db');
        $property->setAccessible(true);
        $property->setValue($instance, $this->db);

        return $instance;
    }

    private function createHeader(string $status, string $date, int $quantity): array
    {
        $deliveryNumber = 'LAP3-' . strtoupper(bin2hex(random_bytes(5)));

        $this->db->table('outbound')->insert([
            'delivery_number' => $deliveryNumber,
            'delivery_date'   => $date,
            'status'          => $status,
            'created_by'      => $this->userId,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        $id = (int)$this->db->insertID();

        $this->db->table('outbound_details')->insert([
            'id_outbound' => $id,
            'id_batch'    => $this->batchId,
            'quantity'    => $quantity,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return ['id' => $id, 'delivery_number' => $deliveryNumber];
    }

    private function report(?string $from = null, ?string $to = null): array
    {
        return $this->laporan->getLaporanBarangKeluar($from, $to);
    }

    public function test01LaporanHanyaMenghitungCompleted(): void
    {
        $result = $this->report(date('Y-m-d'), date('Y-m-d'));

        $this->assertTrue($result['status']);
        $this->assertCount(1, $result['data']['headers']);
        $this->assertSame($this->completed['delivery_number'], $result['data']['headers'][0]['delivery_number']);
        $this->assertSame('COMPLETED', $result['data']['headers'][0]['status']);
        $this->assertSame(9999, (int)$result['data']['headers'][0]['total_quantity']);
    }

    public function test02RekapPerTransaksiTidakMemasukkanDraft(): void
    {
        $result = $this->report(date('Y-m-d'), date('Y-m-d'));
        $numbers = array_column($result['data']['headers'], 'delivery_number');

        $this->assertContains($this->completed['delivery_number'], $numbers);
        $this->assertNotContains($this->draft['delivery_number'], $numbers);
    }

    public function test03QuantityDraftTidakMasukTotal(): void
    {
        $result = $this->report(date('Y-m-d'), date('Y-m-d'));

        $this->assertSame(9999, (int)$result['data']['grand_total_quantity']);
        $this->assertNotSame(10004, (int)$result['data']['grand_total_quantity']);
    }

    public function test04FilterTanggalMasihBerfungsi(): void
    {
        $old = $this->createHeader('COMPLETED', '2099-01-01', 3);

        $result = $this->report('2099-01-01', '2099-01-31');
        $numbers = array_column($result['data']['headers'], 'delivery_number');

        $this->assertSame([$old['delivery_number']], $numbers);
        $this->assertSame(3, (int)$result['data']['grand_total_quantity']);
        $this->assertSame('2099-01-01', $result['data']['date_from']);
        $this->assertSame('2099-01-31', $result['data']['date_to']);
    }

    public function test05ExportPdfBarangKeluarMemakaiDataCompleted(): void
    {
        $result = $this->laporan->exportPdf('barang_keluar', [
            'date_from' => date('Y-m-d'),
            'date_to'   => date('Y-m-d'),
        ]);
        $reportData = $result['data']['report_data'];
        $numbers = array_column($reportData['headers'], 'delivery_number');

        $this->assertTrue($result['status']);
        $this->assertSame('Laporan Barang Keluar', $result['data']['title']);
        $this->assertSame([$this->completed['delivery_number']], $numbers);
        $this->assertSame(9999, (int)$reportData['grand_total_quantity']);
    }

    public function test06DashboardTetapMenghitungCompletedSaja(): void
    {
        $summary = $this->dashboard->summary();
        $chart = $this->dashboard->getChartData();
        $top = $this->dashboard->getTopOutboundProducts();

        $this->assertSame(1, (int)$summary['data']['total_barang_keluar_hari_ini']);

        $label = date('d M');
        $index = array_search($label, $chart['data']['labels'], true);
        $this->assertNotFalse($index);
        $this->assertSame(1, (int)$chart['data']['outbound'][$index]);

        $productDetails = array_values(array_filter(
            $top['data']['details'],
            fn($item) => $item['product_name'] === $this->productName
        ));
        $this->assertCount(1, $productDetails);
        $this->assertSame(9999, (int)$productDetails[0]['total_quantity']);
    }

    public function test07RiwayatOutboundMasihMenampilkanDraftDanCompleted(): void
    {
        $all = $this->outbound->getAll();
        $statuses = array_column($all['data'], 'status');

        $this->assertContains('DRAFT', $statuses);
        $this->assertContains('COMPLETED', $statuses);

        $draft = $this->outbound->getById((string)$this->draft['id']);
        $completed = $this->outbound->getById((string)$this->completed['id']);

        $this->assertSame('DRAFT', $draft['data']['status']);
        $this->assertSame('COMPLETED', $completed['data']['status']);
    }
}
