<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\SensitiveParamsFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SensitiveParamsFilterTest extends TestCase
{
    /** @return array<string, array{0: array<string, mixed>, 1: array<string, mixed>, 2: bool}> */
    public static function cases(): array
    {
        return [
            'csrfToken filtered, key kept, stays replayable (transport)' => [
                ['csrfToken' => 'x', 'preOrderId' => 'a'],
                ['csrfToken' => '[FILTERED]', 'preOrderId' => 'a'],
                true,
            ],
            'password filtered, key kept, becomes non-replayable (credential)' => [
                ['password' => 'x', 'loginId' => 'a'],
                ['password' => '[FILTERED]', 'loginId' => 'a'],
                false,
            ],
            'password_confirm filtered, non-replayable (Page\Entry spelling)' => [
                ['password_confirm' => 'x', 'email' => 'a'],
                ['password_confirm' => '[FILTERED]', 'email' => 'a'],
                false,
            ],
            'passwordConfirm filtered, non-replayable (Admin\Member spelling)' => [
                ['passwordConfirm' => 'x'],
                ['passwordConfirm' => '[FILTERED]'],
                false,
            ],
            'currentPassword filtered, non-replayable' => [
                ['currentPassword' => 'x'],
                ['currentPassword' => '[FILTERED]'],
                false,
            ],
            'deviceToken filtered, non-replayable (domain input, not transport)' => [
                ['deviceToken' => 'x', 'mode' => 'a'],
                ['deviceToken' => '[FILTERED]', 'mode' => 'a'],
                false,
            ],
            'secretKey filtered, non-replayable (matches the secret substring)' => [
                ['secretKey' => 'x'],
                ['secretKey' => '[FILTERED]'],
                false,
            ],
            'authKey retained: no generic key-suffix rule in the default' => [
                ['authKey' => 'x'],
                ['authKey' => 'x'],
                true,
            ],
            'resetKey retained: no generic key-suffix rule in the default' => [
                ['resetKey' => 'x'],
                ['resetKey' => 'x'],
                true,
            ],
            'idempotencyKey retained: a key-suffix field is not automatically a credential' => [
                ['idempotencyKey' => 'a1b2c3', 'amount' => 500],
                ['idempotencyKey' => 'a1b2c3', 'amount' => 500],
                true,
            ],
            'csrfToken and password together: transport filtering does not save replayability' => [
                ['csrfToken' => 'x', 'password' => 'y', 'loginId' => 'a'],
                ['csrfToken' => '[FILTERED]', 'password' => '[FILTERED]', 'loginId' => 'a'],
                false,
            ],
            'nameKeyword retained, replayable' => [
                ['nameKeyword' => 'sample'],
                ['nameKeyword' => 'sample'],
                true,
            ],
            'emailKeyword retained, replayable' => [
                ['emailKeyword' => 'a@example.test'],
                ['emailKeyword' => 'a@example.test'],
                true,
            ],
            'one level nested credential filtered, non-replayable' => [
                ['credentials' => ['password' => 'x', 'username' => 'a']],
                ['credentials' => ['password' => '[FILTERED]', 'username' => 'a']],
                false,
            ],
            'two levels nested credential filtered, non-replayable' => [
                ['auth' => ['credentials' => ['password' => 'x']]],
                ['auth' => ['credentials' => ['password' => '[FILTERED]']]],
                false,
            ],
            'credential nested inside a list of maps filtered, non-replayable' => [
                ['accounts' => [['id' => 1, 'password' => 'x'], ['id' => 2, 'password' => 'y']]],
                ['accounts' => [['id' => 1, 'password' => '[FILTERED]'], ['id' => 2, 'password' => '[FILTERED]']]],
                false,
            ],
            'a credential whose value is itself a map is replaced whole, not walked' => [
                ['secret' => ['id' => 'k1', 'value' => 'x']],
                ['secret' => '[FILTERED]'],
                false,
            ],
            'nested idempotencyKey retained, replayable' => [
                ['payload' => ['idempotencyKey' => 'x', 'id' => 1]],
                ['payload' => ['idempotencyKey' => 'x', 'id' => 1]],
                true,
            ],
            'nested csrfToken only, stays replayable' => [
                ['payload' => ['csrfToken' => 'x', 'id' => 1]],
                ['payload' => ['csrfToken' => '[FILTERED]', 'id' => 1]],
                true,
            ],
            'a plain list with no string keys is left structurally alone, replayable' => [
                ['tags' => ['red', 'blue']],
                ['tags' => ['red', 'blue']],
                true,
            ],
            'no credential-shaped keys anywhere, replayable no-op' => [
                ['preOrderId' => 'a', 'items' => [['productCode' => 'sample-001', 'quantity' => 1]]],
                ['preOrderId' => 'a', 'items' => [['productCode' => 'sample-001', 'quantity' => 1]]],
                true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $expectedParams
     */
    #[DataProvider('cases')]
    public function testFiltersByNameAndReportsReplayability(
        array $input,
        array $expectedParams,
        bool $expectedReplayable,
    ): void {
        $filter = new SensitiveParamsFilter();

        $result = $filter($input);

        $this->assertSame($expectedParams, $result->params);
        $this->assertSame($expectedReplayable, $result->replayable);
    }

    public function testPlaceholderIsThePublicConstant(): void
    {
        $this->assertSame('[FILTERED]', SensitiveParamsFilter::FILTERED);
    }
}
