<?php

use App\Services\Concerns\ServiceResults;
use App\Services\Concerns\ValidatesServiceActors;
use App\Support\ValueRules;
use App\Support\WmsPolicy;
use CodeIgniter\Test\CIUnitTestCase;

final class ServiceRefactorParityTest extends CIUnitTestCase
{
    public function testValueRulesMatchThePreviousNormalizers(): void
    {
        $legacyPositiveId = static function ($value): ?int {
            if (is_int($value)) {
                return $value > 0 ? $value : null;
            }

            if ($value === null || (!is_string($value) && !is_numeric($value))) {
                return null;
            }

            $value = trim((string) $value);
            if ($value === '' || preg_match('/^\d+$/', $value) !== 1) {
                return null;
            }

            $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            return $id === false ? null : $id;
        };

        $legacyPositiveInt = static function ($value, int $max = PHP_INT_MAX): ?int {
            if (is_int($value)) {
                return $value > 0 && $value <= $max ? $value : null;
            }

            if (!is_string($value)) {
                return null;
            }

            $value = trim($value);
            if ($value === '' || !ctype_digit($value)) {
                return null;
            }

            $number = filter_var($value, FILTER_VALIDATE_INT, [
                'options' => [
                    'min_range' => 1,
                    'max_range' => $max,
                ],
            ]);

            return $number === false ? null : $number;
        };

        $values = [0, 1, -1, '1', ' 2 ', '01', 'abc', '1e3', 2.0, '2.5', true, false, null, [], '9999999999999999999999', '<b>5</b>'];

        foreach ($values as $value) {
            $this->assertSame($legacyPositiveId($value), ValueRules::positiveId($value));
            $this->assertSame($legacyPositiveInt($value), ValueRules::positiveInt($value));
        }

        $dates = ['2026-11-24', ' 2026-11-24 ', '2026-02-30', '2026-1-24', 20261124, null];

        foreach ($dates as $date) {
            $expected = is_string($date)
                && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts) === 1
                && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);

            $this->assertSame($expected, ValueRules::isValidIsoDate($date));
        }
    }

    public function testActorFailureArraysKeepTheirHistoricKeyOrder(): void
    {
        $subject = new class {
            use ValidatesServiceActors;

            public function inbound(?array $actor): array
            {
                return $this->validateServiceActor($actor, WmsPolicy::MESSAGE_FORBIDDEN_INBOUND, true, 'legacyInboundId');
            }

            public function outbound(?array $actor): array
            {
                return $this->validateServiceActor($actor, WmsPolicy::MESSAGE_FORBIDDEN_OUTBOUND, false, 'legacyOutboundId');
            }

            private function legacyInboundId($value): ?int
            {
                if (is_int($value)) {
                    return $value > 0 ? $value : null;
                }

                if ($value === null || (!is_string($value) && !is_numeric($value))) {
                    return null;
                }

                $value = trim((string) $value);
                if ($value === '' || preg_match('/^\d+$/', $value) !== 1) {
                    return null;
                }

                $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

                return $id === false ? null : $id;
            }

            private function legacyOutboundId($value): ?int
            {
                if (is_int($value)) {
                    return $value > 0 ? $value : null;
                }

                if (!is_string($value)) {
                    return null;
                }

                $value = trim($value);
                if ($value === '' || !ctype_digit($value)) {
                    return null;
                }

                $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

                return $number === false ? null : $number;
            }
        };

        $this->assertSame(
            ['status' => false, 'code' => 401, 'message' => 'Unauthorized. Silakan login terlebih dahulu.'],
            $subject->inbound(null)[1]
        );
        $this->assertSame(
            ['status' => false, 'code' => 403, 'message' => 'Forbidden. Anda tidak memiliki akses ke transaksi inbound.'],
            $subject->inbound(['id' => 1, 'role' => 'VIEWER'])[1]
        );
        $this->assertSame(
            ['status' => false, 'message' => 'Unauthorized. Silakan login terlebih dahulu.', 'code' => 401],
            $subject->outbound(null)[1]
        );
        $this->assertSame(
            ['status' => false, 'message' => 'Forbidden. Anda tidak memiliki akses ke transaksi outbound.', 'code' => 403],
            $subject->outbound(['id' => 1, 'role' => 'VIEWER'])[1]
        );
        $this->assertSame(
            [['id' => 3, 'role' => 'GUDANG'], null],
            $subject->outbound(['id' => '3', 'role' => ' gudang '])
        );
    }

    public function testServiceResultBuildersKeepTheirHistoricShapes(): void
    {
        $subject = new class {
            use ServiceResults;

            public function success(string $message, ?int $code, ?array $data): array
            {
                return $this->makeSuccessResult($message, $code, $data);
            }

            public function failure(string $message, ?int $code): array
            {
                return $this->makeFailureResult($message, $code);
            }

        };

        $this->assertSame(
            ['status' => true, 'message' => 'OK', 'code' => 201, 'data' => ['id' => 1]],
            $subject->success('OK', 201, ['id' => 1])
        );
        $this->assertSame(
            ['status' => true, 'message' => 'OK', 'data' => ['id' => 1]],
            $subject->success('OK', null, ['id' => 1])
        );
        $this->assertSame(['status' => false, 'message' => 'NO'], $subject->failure('NO', null));
        $this->assertSame(['status' => false, 'message' => 'NO', 'code' => 409], $subject->failure('NO', 409));
    }

    public function testSharedPolicyConstantsMatchTheLegacyLiterals(): void
    {
        $this->assertSame(['SUPERADMIN', 'ADMIN', 'GUDANG'], WmsPolicy::ALLOWED_ROLES);
        $this->assertSame(['SUPERADMIN', 'ADMIN'], WmsPolicy::ADMIN_ROLES);
        $this->assertSame('AKTIF', WmsPolicy::STATUS_ACTIVE);
        $this->assertSame('DRAFT', WmsPolicy::STATUS_DRAFT);
        $this->assertSame('COMPLETED', WmsPolicy::STATUS_COMPLETED);
        $this->assertSame('Unauthorized. Silakan login terlebih dahulu.', WmsPolicy::MESSAGE_UNAUTHORIZED);
        $this->assertSame('Forbidden. Anda tidak memiliki akses ke transaksi inbound.', WmsPolicy::MESSAGE_FORBIDDEN_INBOUND);
        $this->assertSame('Forbidden. Anda tidak memiliki akses ke transaksi outbound.', WmsPolicy::MESSAGE_FORBIDDEN_OUTBOUND);
    }
}
