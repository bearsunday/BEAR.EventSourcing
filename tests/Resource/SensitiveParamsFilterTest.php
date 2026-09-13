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
            'csrfToken removed, stays replayable (transport)' => [
                ['csrfToken' => 'x', 'preOrderId' => 'a'],
                ['preOrderId' => 'a'],
                true,
            ],
            'password removed, becomes non-replayable (credential)' => [
                ['password' => 'x', 'loginId' => 'a'],
                ['loginId' => 'a'],
                false,
            ],
            'password_confirm removed, non-replayable (Page\Entry spelling)' => [
                ['password_confirm' => 'x', 'email' => 'a'],
                ['email' => 'a'],
                false,
            ],
            'passwordConfirm removed, non-replayable (Admin\Member spelling)' => [
                ['passwordConfirm' => 'x'],
                [],
                false,
            ],
            'currentPassword removed, non-replayable' => [
                ['currentPassword' => 'x'],
                [],
                false,
            ],
            'deviceToken removed, non-replayable (domain input, not transport)' => [
                ['deviceToken' => 'x', 'mode' => 'a'],
                ['mode' => 'a'],
                false,
            ],
            'secretKey removed, non-replayable (matches the secret substring)' => [
                ['secretKey' => 'x'],
                [],
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
            'csrfToken and password together: transport removal does not save replayability' => [
                ['csrfToken' => 'x', 'password' => 'y', 'loginId' => 'a'],
                ['loginId' => 'a'],
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
            'one level nested credential removed, non-replayable' => [
                ['credentials' => ['password' => 'x', 'username' => 'a']],
                ['credentials' => ['username' => 'a']],
                false,
            ],
            'two levels nested credential removed, non-replayable' => [
                ['auth' => ['credentials' => ['password' => 'x']]],
                ['auth' => ['credentials' => []]],
                false,
            ],
            'credential nested inside a list of maps removed, non-replayable' => [
                ['accounts' => [['id' => 1, 'password' => 'x'], ['id' => 2, 'password' => 'y']]],
                ['accounts' => [['id' => 1], ['id' => 2]]],
                false,
            ],
            'nested idempotencyKey retained, replayable' => [
                ['payload' => ['idempotencyKey' => 'x', 'id' => 1]],
                ['payload' => ['idempotencyKey' => 'x', 'id' => 1]],
                true,
            ],
            'nested csrfToken only, stays replayable' => [
                ['payload' => ['csrfToken' => 'x', 'id' => 1]],
                ['payload' => ['id' => 1]],
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
}
