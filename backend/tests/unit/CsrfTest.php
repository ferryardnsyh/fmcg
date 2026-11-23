<?php

use CodeIgniter\Filters\CSRF;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;
use Config\Filters;
use Config\Security;
use Config\Services;

final class CsrfTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $db;
    protected string $tokenName;
    protected string $headerName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = Database::connect('default');
        $this->db->transBegin();
        $security = config(Security::class);
        $this->tokenName  = $security->tokenName;
        $this->headerName = $security->headerName;
        session()->destroy();
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

    private function createUser(string $role): int
    {
        $this->db->table('users')->insert([
            'name'     => 'User Uji CSRF',
            'username' => 'csrf_' . bin2hex(random_bytes(5)),
            'password' => password_hash('rahasia', PASSWORD_DEFAULT),
            'role'     => $role,
            'status'   => 'AKTIF',
        ]);

        return (int)$this->db->insertID();
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

    private function tokenEndpoint(array $session = []): array
    {
        Services::resetSingle('security');
        $response = $this->withHeaders([])->withBody('')->withSession($session)->get('api/csrf-token');
        $response->assertStatus(200);
        $body = json_decode((string)$response->getJSON(), true);
        $session[$this->tokenName] = $body['data']['csrf_hash'];

        return $session;
    }

    private function request(
        string $method,
        string $path,
        array $session = [],
        array $headers = [],
        ?array $params = null,
        ?string $body = null
    ) {
        Services::resetSingle('security');

        return $this->withHeaders($headers)
            ->withBody($body ?? '')
            ->withSession($session)
            ->call($method, $path, $params);
    }

    private function assertCsrfRejected(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Mutasi tanpa token CSRF harus ditolak.');
        } catch (SecurityException $exception) {
            $this->assertSame(403, $exception->getCode());
        }
    }

    private function withToken(
        string $method,
        string $path,
        array $session,
        ?string $token = null,
        ?array $params = null
    ) {
        $token = $token ?? $session[$this->tokenName];

        return $this->request($method, $path, $session, [$this->headerName => $token], $params);
    }

    private function safeRoutes(): void
    {
        $this->withRoutes([
            ['GET', 'csrf-safe', static fn () => service('response')->setJSON(['status' => true])],
            ['HEAD', 'csrf-safe-head', static fn () => service('response')->setJSON(['status' => true])],
            ['OPTIONS', 'csrf-safe-options', static fn () => service('response')->setStatusCode(204)],
        ]);
    }

    private function mutationRoutes(bool $auth = false): void
    {
        $options = $auth ? ['filter' => 'auth'] : [];
        $routes  = [];

        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $routes[] = [
                $method,
                'csrf-mutate-' . strtolower($method),
                static fn () => service('response')->setJSON([
                    'status' => true,
                    'method' => service('request')->getMethod(),
                ]),
                $options,
            ];
        }

        $this->withRoutes($routes);
    }

    public function test01TokenEndpointReturnsBuiltInToken(): void
    {
        $response = $this->request('GET', 'api/csrf-token');
        $response->assertStatus(200);
        $response->assertHeader('Cache-Control');
        $body = json_decode((string)$response->getJSON(), true);

        $this->assertTrue($body['status']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/i', $body['data']['csrf_hash']);
        $this->assertSame($this->tokenName, $body['data']['token_name']);
        $this->assertSame($this->headerName, $body['data']['header_name']);
        $this->assertArrayNotHasKey('id_user', $body['data']);
        $this->assertArrayNotHasKey('role', $body['data']);
    }

    public function test02BuiltInCsrfFilterIsConfigured(): void
    {
        $security = config(Security::class);
        $filters  = config(Filters::class);

        $this->assertSame('session', $security->csrfProtection);
        $this->assertTrue($security->regenerate);
        $this->assertSame(CSRF::class, $filters->aliases['csrf']);
        $this->assertContains('csrf', $filters->globals['before']);
    }

    public function test03GetHeadAndOptionsAreNotBlocked(): void
    {
        $this->safeRoutes();

        $this->request('GET', 'csrf-safe')->assertStatus(200);
        $this->request('HEAD', 'csrf-safe-head')->assertStatus(200);
        $this->request('OPTIONS', 'csrf-safe-options')->assertStatus(204);
    }

    public function test04PostWithoutTokenRejected(): void
    {
        $this->mutationRoutes();

        $this->assertCsrfRejected(fn () => $this->request(
            'POST',
            'csrf-mutate-post',
            [],
            [],
            ['name' => 'test']
        ));
    }

    public function test05PutPatchDeleteWithoutTokenRejected(): void
    {
        $this->mutationRoutes();

        foreach (['PUT', 'PATCH', 'DELETE'] as $method) {
            $this->assertCsrfRejected(fn () => $this->request(
                $method,
                'csrf-mutate-' . strtolower($method)
            ));
        }
    }

    public function test06InvalidTokenRejected(): void
    {
        $this->mutationRoutes();
        $session = $this->tokenEndpoint();

        $this->assertCsrfRejected(fn () => $this->withToken(
            'POST',
            'csrf-mutate-post',
            $session,
            str_repeat('0', 32)
        ));
    }

    public function test07TokenFromDifferentSessionRejected(): void
    {
        $this->mutationRoutes();
        $first  = $this->tokenEndpoint();
        $second = $this->tokenEndpoint();
        $this->assertNotSame($first[$this->tokenName], $second[$this->tokenName]);

        $this->assertCsrfRejected(fn () => $this->withToken(
            'POST',
            'csrf-mutate-post',
            $first,
            $second[$this->tokenName]
        ));
    }

    public function test08QueryTokenNotAccepted(): void
    {
        $this->mutationRoutes();
        $session = $this->tokenEndpoint();
        $path    = 'csrf-mutate-post?' . $this->tokenName . '=' . $session[$this->tokenName];

        $this->assertCsrfRejected(fn () => $this->request('POST', $path, $session));
    }

    public function test09UserIdentityPayloadCannotCarryToken(): void
    {
        $this->mutationRoutes();
        $session = $this->tokenEndpoint();

        $this->assertCsrfRejected(fn () => $this->request(
            'POST',
            'csrf-mutate-post',
            $session,
            [],
            ['id_user' => $session[$this->tokenName]]
        ));
    }

    public function test10ContentTypeChangeWithoutTokenRejected(): void
    {
        $this->mutationRoutes();
        $session = $this->tokenEndpoint();

        $this->assertCsrfRejected(fn () => $this->request(
            'POST',
            'csrf-mutate-post',
            $session,
            ['Content-Type' => 'text/plain'],
            null,
            'id_user=1'
        ));
    }

    public function test11ValidHeaderTokenAcceptedForAllMutations(): void
    {
        $this->mutationRoutes();

        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $session = $this->tokenEndpoint();
            $path    = 'csrf-mutate-' . strtolower($method);
            $response = $this->withToken($method, $path, $session);
            $response->assertStatus(200);
            $body = json_decode((string)$response->getJSON(), true);
            $this->assertSame($method, $body['method']);
        }
    }

    public function test12TokenRegeneratesAndCanBeRefreshed(): void
    {
        $this->mutationRoutes();
        $session = $this->tokenEndpoint();
        $tokenA  = $session[$this->tokenName];

        $response = $this->withToken('POST', 'csrf-mutate-post', $session);
        $response->assertStatus(200);

        $tokenB = service('security')->getHash();
        $this->assertNotSame($tokenA, $tokenB);

        $session[$this->tokenName] = $tokenB;
        $refreshed = $this->tokenEndpoint($session);
        $this->assertSame($tokenB, $refreshed[$this->tokenName]);
    }

    public function test13JsonBodyTokenAcceptedAndRemoved(): void
    {
        $this->withRoutes([
            ['POST', 'csrf-body', static function () {
                return service('response')->setJSON([
                    'status' => true,
                    'data'   => service('request')->getJSON(true),
                ]);
            }],
        ]);
        $session = $this->tokenEndpoint();

        $response = $this->request(
            'POST',
            'csrf-body',
            $session,
            ['Content-Type' => 'application/json'],
            null,
            json_encode([
                'name'             => 'test',
                $this->tokenName   => $session[$this->tokenName],
            ])
        );

        $response->assertStatus(200);
        $body = json_decode((string)$response->getJSON(), true);
        $this->assertSame('test', $body['data']['name']);
        $this->assertArrayNotHasKey($this->tokenName, $body['data']);
    }

    public function test14ProtectedMutationWorksWithValidToken(): void
    {
        $userId  = $this->createUser('ADMIN');
        $session = $this->tokenEndpoint($this->sessionFor($userId, 'ADMIN'));
        $this->mutationRoutes(true);

        $response = $this->withToken('POST', 'csrf-mutate-post', $session);

        $response->assertStatus(200);
    }

    public function test15LoggedOutSessionCannotReuseProtectedMutation(): void
    {
        $session = $this->tokenEndpoint();
        $this->mutationRoutes(true);

        $response = $this->withToken('POST', 'csrf-mutate-post', $session);

        $response->assertStatus(401);
    }

    public function test16PreflightAllowsCsrfHeader(): void
    {
        $response = $this->request(
            'OPTIONS',
            'api/csrf-token',
            [],
            [
                'Origin'                         => 'http://localhost:5176',
                'Access-Control-Request-Method'  => 'POST',
                'Access-Control-Request-Headers' => 'content-type, x-csrf-token',
            ]
        );

        $response->assertStatus(204);
        $response->assertHeader(
            'Access-Control-Allow-Headers',
            'Origin, X-Requested-With, Content-Type, Accept, Authorization, X-CSRF-TOKEN'
        );
    }
}
