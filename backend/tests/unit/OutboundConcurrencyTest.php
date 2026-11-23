<?php

require_once __DIR__ . '/../_support/EnsuresOutboundIdRange.php';

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;

final class OutboundConcurrencyTest extends CIUnitTestCase
{
    use EnsuresOutboundIdRange;

    private const EXPECTED_DATABASE = 'psswms_testing';

    protected $db;
    protected ?int $productId = null;
    protected ?int $batchId = null;
    protected ?int $headerId = null;
    protected ?int $userId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = Database::connect('default');
        $this->ensureOutboundIdRange($this->db);
        $active = $this->db->query('SELECT DATABASE() AS db_name')->getRowArray();
        $this->assertSame(self::EXPECTED_DATABASE, $active['db_name'] ?? null, 'Concurrency test harus memakai database testing.');

        $this->db->table('users')->insert([
            'name'       => 'User Uji Concurrency',
            'username'   => 'conc_' . strtoupper(bin2hex(random_bytes(5))),
            'password'   => password_hash('rahasia', PASSWORD_DEFAULT),
            'role'       => 'ADMIN',
            'status'     => 'AKTIF',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->userId = (int)$this->db->insertID();

        $this->db->table('products')->insert([
            'sku_code'      => 'TEST-CONC-' . strtoupper(bin2hex(random_bytes(5))),
            'name'          => 'Produk Uji Concurrency',
            'unit'          => 'PCS',
            'minimum_stock' => 0,
            'status'        => 'AKTIF',
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
        $this->productId = (int)$this->db->insertID();

        $this->db->table('product_batches')->insert([
            'id_product'   => $this->productId,
            'batch_number' => 'BATCH-CONC-' . strtoupper(bin2hex(random_bytes(4))),
            'quantity'     => 5,
            'created_at'   => date('Y-m-d H:i:s'),
            'updated_at'   => date('Y-m-d H:i:s'),
        ]);
        $this->batchId = (int)$this->db->insertID();

        $this->db->table('outbound_headers')->insert([
            'delivery_number' => 'OUT-CONC-' . strtoupper(bin2hex(random_bytes(4))),
            'delivery_date'   => date('Y-m-d'),
            'status'          => 'DRAFT',
            'created_by'      => $this->userId,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        $this->headerId = (int)$this->db->insertID();

        $this->db->table('outbound_details')->insert([
            'id_outbound' => $this->headerId,
            'id_batch'    => $this->batchId,
            'quantity'    => 3,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    protected function tearDown(): void
    {
        $active = $this->db->query('SELECT DATABASE() AS db_name')->getRowArray();

        if (($active['db_name'] ?? null) === self::EXPECTED_DATABASE) {
            if ($this->headerId !== null) {
                $this->db->table('outbound_details')->where('id_outbound', $this->headerId)->delete();
                $this->db->table('outbound_headers')->where('id_outbound', $this->headerId)->delete();
            }

            if ($this->batchId !== null) {
                $this->db->table('product_batches')->where('id_batch', $this->batchId)->delete();
            }

            if ($this->productId !== null) {
                $this->db->table('products')->where('id_product', $this->productId)->delete();
            }

            if ($this->userId !== null) {
                $this->db->table('users')->where('id_user', $this->userId)->delete();
            }
        }

        parent::tearDown();
    }

    public function testConcurrentCompleteDecrementsStockOnce(): void
    {
        $childCode = <<<'PHP'
use App\Services\OutboundService;
use Config\Database;
require 'system/Test/bootstrap.php';
$id = (string) $argv[1];
$userId = (string) $argv[2];
$startFile = $argv[3];
while (! file_exists($startFile)) {
    usleep(1000);
}
$db = Database::connect('default');
$active = $db->query('SELECT DATABASE() AS db_name')->getRowArray();
if (($active['db_name'] ?? null) !== 'psswms_testing') {
    echo json_encode(['status' => false, 'code' => 500, 'message' => 'Child process bukan database testing']);
    exit(1);
}
$service = new OutboundService($db);
echo json_encode($service->complete($id, ['id' => (int) $userId, 'role' => 'ADMIN']));
PHP;

        $startFile = (string)tempnam(sys_get_temp_dir(), 'outbound_start_');
        unlink($startFile);

        $backendPath = realpath(__DIR__ . '/../..');
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $processes = [];
        for ($i = 0; $i < 2; $i++) {
            $process = proc_open(
                [PHP_BINARY, '-r', $childCode, (string)$this->headerId, (string)$this->userId, $startFile],
                $descriptors,
                $pipes,
                $backendPath
            );
            $this->assertIsResource($process);
            $processes[] = ['handle' => $process, 'pipes' => $pipes];
        }

        touch($startFile);

        $results = [];
        foreach ($processes as $process) {
            fclose($process['pipes'][0]);
            $stdout = stream_get_contents($process['pipes'][1]);
            $stderr = stream_get_contents($process['pipes'][2]);
            fclose($process['pipes'][1]);
            fclose($process['pipes'][2]);
            $exitCode = proc_close($process['handle']);

            $decoded = json_decode(trim((string)$stdout), true);
            $this->assertIsArray($decoded, 'Child stdout: ' . $stdout . ' stderr: ' . $stderr . ' exit: ' . $exitCode);
            $results[] = $decoded;
        }

        unlink($startFile);

        $successes = array_values(array_filter($results, static fn($result) => $result['status'] === true));
        $conflicts = array_values(array_filter($results, static fn($result) => $result['status'] === false && $result['code'] === 409));

        $this->assertCount(1, $successes);
        $this->assertCount(1, $conflicts);

        $stock = $this->db->table('product_batches')
            ->select('quantity')
            ->where('id_batch', $this->batchId)
            ->get()
            ->getRowArray();
        $this->assertSame(2, (int)$stock['quantity']);

        $header = $this->db->table('outbound_headers')
            ->select('status')
            ->where('id_outbound', $this->headerId)
            ->get()
            ->getRowArray();
        $this->assertSame('COMPLETED', $header['status']);
    }
}
