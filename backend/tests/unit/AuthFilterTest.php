<?php

use App\Filters\AuthFilter;
use App\Models\UserModel;
use App\Services\AuthService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Database;

final class AuthFilterTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = Database::connect('default');
        $this->db->transBegin();
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

    private function createUser(string $role = 'ADMIN', string $status = 'AKTIF'): array
    {
        $user = [
            'name'     => 'User Uji Auth',
            'username' => 'auth_' . bin2hex(random_bytes(5)),
            'password' => 'rahasia123',
            'role'     => $role,
            'status'   => $status,
        ];

        $this->db->table('users')->insert([
            'name'     => $user['name'],
            'username' => $user['username'],
            'password' => password_hash($user['password'], PASSWORD_DEFAULT),
            'role'     => $user['role'],
            'status'   => $user['status'],
        ]);

        $user['id_user'] = (int)$this->db->insertID();

        return $user;
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

    private function makeAuthService(): AuthService
    {
        $userModel = new UserModel($this->db);

        $serviceProperty = new ReflectionClass(AuthService::class);
        $service = $serviceProperty->newInstanceWithoutConstructor();

        $userModelProperty = $serviceProperty->getProperty('userModel');
        $userModelProperty->setAccessible(true);
        $userModelProperty->setValue($service, $userModel);

        return $service;
    }

    private function invokeFilter(array $arguments = [])
    {
        $filter = new AuthFilter($this->db);

        return $filter->before(service('request'), $arguments);
    }

    private function assertFilterResponse($result, int $expectedCode, string $expectedMessage): void
    {
        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertSame($expectedCode, $result->getStatusCode());

        $body = json_decode((string)$result->getJSON(), true);
        $this->assertIsArray($body);
        $this->assertFalse($body['status']);
        $this->assertSame($expectedMessage, $body['message']);
    }

    public function test01SessionWithoutIdUserIsRejected(): void
    {
        session()->set([
            'logged_in' => true,
            'role'      => 'ADMIN',
        ]);

        $result = $this->invokeFilter();

        $this->assertFilterResponse(
            $result,
            401,
            'Unauthorized. Silakan login terlebih dahulu.'
        );
    }

    public function test02LegacyIdSessionKeyIsNotTrusted(): void
    {
        $user = $this->createUser();

        session()->set([
            'logged_in' => true,
            'id'        => $user['id_user'],
            'role'      => 'ADMIN',
        ]);

        $result = $this->invokeFilter();

        $this->assertFilterResponse(
            $result,
            401,
            'Unauthorized. Silakan login terlebih dahulu.'
        );
    }

    public function test03InvalidIdUserIsRejected(): void
    {
        session()->set([
            'logged_in' => true,
            'id_user'   => 'bukan-id',
            'role'      => 'ADMIN',
        ]);

        $result = $this->invokeFilter();

        $this->assertFilterResponse(
            $result,
            401,
            'Unauthorized. Silakan login terlebih dahulu.'
        );
    }

    public function test04UnknownUserIsRejected(): void
    {
        session()->set($this->sessionFor(999999, 'ADMIN'));

        $result = $this->invokeFilter();

        $this->assertFilterResponse(
            $result,
            401,
            'Unauthorized. Silakan login terlebih dahulu.'
        );
    }

    public function test05InactiveUserIsRejected(): void
    {
        $user = $this->createUser('ADMIN', 'NONAKTIF');
        session()->set($this->sessionFor($user['id_user'], 'ADMIN'));

        $result = $this->invokeFilter();

        $this->assertFilterResponse(
            $result,
            401,
            'Akun sudah dinonaktifkan. Silakan hubungi administrator.'
        );
    }

    public function test06ActiveUserWithValidSessionIsAllowed(): void
    {
        $user = $this->createUser('GUDANG');
        session()->set($this->sessionFor($user['id_user'], 'GUDANG'));

        $this->assertNull($this->invokeFilter());
    }

    public function test07SessionUsesIdUserKeyFromLogin(): void
    {
        $user = $this->createUser('ADMIN');
        $service = $this->makeAuthService();

        $login = $service->login($user['username'], $user['password']);

        $this->assertTrue($login['status']);
        $this->assertSame($user['id_user'], (int)session('id_user'));
        $this->assertNull(session('id'));
        $this->assertNull($this->invokeFilter());
    }

    public function test08LoginSessionAndLogoutRemainFunctional(): void
    {
        $user = $this->createUser('ADMIN');
        $service = $this->makeAuthService();

        $login = $service->login($user['username'], $user['password']);
        $this->assertTrue($login['status']);

        $me = $service->me();
        $this->assertTrue($me['status']);
        $this->assertSame($user['id_user'], (int)$me['data']['id']);

        $logout = $service->logout();
        $this->assertTrue($logout['status']);

        $this->resetServices();

        $this->assertFalse($service->me()['status']);
    }

    public function test09PublicRootEndpointRemainsAccessibleWithoutSession(): void
    {
        $response = $this->withSession([])->get('api/');

        $response->assertStatus(200);
        $body = json_decode((string)$response->getJSON(), true);
        $this->assertTrue($body['status']);
    }

    public function test10ProtectedEndpointRejectsRequestWithoutAuthentication(): void
    {
        $response = $this->withSession([])->get('api/me');

        $response->assertStatus(401);
        $body = json_decode((string)$response->getJSON(), true);
        $this->assertFalse($body['status']);
    }

    public function test11RequestSuppliedIdentityIsNotTrusted(): void
    {
        $user = $this->createUser('ADMIN');

        $response = $this->withSession([])
            ->withHeaders(['X-User-Id' => (string)$user['id_user']])
            ->get('api/me?id_user=' . $user['id_user']);

        $response->assertStatus(401);
        $body = json_decode((string)$response->getJSON(), true);
        $this->assertFalse($body['status']);
    }

    public function test12ConfiguredRolesCanAccessGeneralProtectedRoutes(): void
    {
        foreach (['SUPERADMIN', 'ADMIN', 'GUDANG'] as $role) {
            $user = $this->createUser($role);
            session()->set($this->sessionFor($user['id_user'], $role));

            $this->assertNull($this->invokeFilter());
        }
    }

    public function test13AdminRoleHierarchyRemainsUnchanged(): void
    {
        foreach (['SUPERADMIN', 'ADMIN'] as $role) {
            $user = $this->createUser($role);
            session()->set($this->sessionFor($user['id_user'], $role));

            $this->assertNull($this->invokeFilter(['admin']));
        }

        $user = $this->createUser('GUDANG');
        session()->set($this->sessionFor($user['id_user'], 'GUDANG'));

        $this->assertFilterResponse(
            $this->invokeFilter(['admin']),
            403,
            'Forbidden. Anda tidak memiliki akses ke resource ini.'
        );
    }
}
