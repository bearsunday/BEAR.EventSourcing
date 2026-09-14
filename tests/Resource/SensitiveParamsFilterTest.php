<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\SensitiveParamsFilter;
use JsonSerializable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function fclose;
use function fopen;
use function is_array;

use const INF;
use const NAN;

/** @psalm-suppress MixedAssignment,MixedArrayAccess,MixedArgument Filtered params are untyped by design. */
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
            'passwd, pwd and passphrase filtered, non-replayable (older form spellings)' => [
                ['passwd' => 'x', 'pwd' => 'y', 'passphrase' => 'z', 'loginId' => 'a'],
                ['passwd' => '[FILTERED]', 'pwd' => '[FILTERED]', 'passphrase' => '[FILTERED]', 'loginId' => 'a'],
                false,
            ],
            'privateKey and private_key filtered, non-replayable (integration-settings spelling)' => [
                ['privateKey' => 'x', 'private_key' => 'y'],
                ['privateKey' => '[FILTERED]', 'private_key' => '[FILTERED]'],
                false,
            ],
            'apiKey filtered, non-replayable (the one key-suffixed field that is always a credential)' => [
                ['apiKey' => 'sk-live-1', 'sku' => 'A'],
                ['apiKey' => '[FILTERED]', 'sku' => 'A'],
                false,
            ],
            'api_key and APIKEY spellings filtered, non-replayable' => [
                ['api_key' => 'x', 'APIKEY' => 'y'],
                ['api_key' => '[FILTERED]', 'APIKEY' => '[FILTERED]'],
                false,
            ],
            'transport-named map replaced whole, a credential inside still flips replayability' => [
                ['csrfConfig' => ['secret' => 'x', 'ttl' => 5], 'id' => 1],
                ['csrfConfig' => '[FILTERED]', 'id' => 1],
                false,
            ],
            'transport-named map without a credential inside replaced whole, stays replayable' => [
                ['csrfConfig' => ['ttl' => 5], 'id' => 1],
                ['csrfConfig' => '[FILTERED]', 'id' => 1],
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

    public function testObjectValuesAreFilteredOnTheirJsonView(): void
    {
        // ContextFreezer records an object as its JSON view, so a credential inside a stdClass
        // would reach the log verbatim if only arrays were walked.
        $result = (new SensitiveParamsFilter())([
            'credentials' => (object) ['password' => 'plain', 'user' => 'a'],
        ]);

        $this->assertSame(['credentials' => ['password' => '[FILTERED]', 'user' => 'a']], $result->params);
        $this->assertFalse($result->replayable);
    }

    public function testJsonSerializableValuesAreFilteredOnWhatTheySerializeTo(): void
    {
        $device = new class implements JsonSerializable {
            /** @return array<string, string> */
            public function jsonSerialize(): array
            {
                return ['deviceToken' => 'abc', 'model' => 'x1'];
            }
        };

        $result = (new SensitiveParamsFilter())(['device' => $device]);

        $this->assertSame(['device' => ['deviceToken' => '[FILTERED]', 'model' => 'x1']], $result->params);
        $this->assertFalse($result->replayable);
    }

    public function testAValueThatCannotBeEncodedIsWithheldWhole(): void
    {
        // INF cannot be JSON-encoded; the log could not record it either, so it is withheld
        // rather than passed through unwalked.
        $result = (new SensitiveParamsFilter())(['ratio' => (object) ['value' => INF], 'id' => 1]);

        $this->assertSame(['ratio' => '[FILTERED]', 'id' => 1], $result->params);
        $this->assertFalse($result->replayable);
    }

    public function testANonFiniteFloatOrAResourceIsWithheldWhole(): void
    {
        // Bare INF/NAN and a resource cannot be JSON-encoded either; they are withheld the same way
        // an unencodable object is, instead of being returned as scalars the log cannot record.
        $handle = fopen('php://memory', 'r');

        $result = (new SensitiveParamsFilter())(['ratio' => INF, 'nan' => NAN, 'handle' => $handle, 'id' => 1]);

        $this->assertSame(
            ['ratio' => '[FILTERED]', 'nan' => '[FILTERED]', 'handle' => '[FILTERED]', 'id' => 1],
            $result->params,
        );
        $this->assertFalse($result->replayable);
        fclose($handle);
    }

    public function testNestingDeeperThanJsonAllowsIsWithheldWholeNotWalkedForever(): void
    {
        $leaf = 'leaf';
        for ($i = 0; $i < 600; $i++) {
            $leaf = ['n' => $leaf];
        }

        $result = (new SensitiveParamsFilter())(['deep' => $leaf, 'id' => 1]);

        $this->assertFalse($result->replayable);
        $this->assertSame(1, $result->params['id']);
        [$depth, $end] = self::descend($result->params['deep']);
        $this->assertSame('[FILTERED]', $end);
        // The top-level params are depth 1 and 'deep' itself depth 2, so the placeholder lands
        // one level short of MAX_DEPTH: a regression of the bound moves this number.
        $this->assertSame(SensitiveParamsFilter::MAX_DEPTH - 1, $depth);
    }

    public function testExtraCredentialSubstringsExtendTheDefaultSet(): void
    {
        // The one-argument path for "also filter this app's key": matched like the built-in
        // set (case and _/- ignored), and a credential, so the request becomes non-replayable.
        $filter = new SensitiveParamsFilter(['otp', 'Reset-Key']);

        $result = $filter(['otp' => '123456', 'resetKey' => 'r', 'reset_key' => 's', 'password' => 'p', 'id' => 1]);

        $this->assertSame(
            [
                'otp' => '[FILTERED]',
                'resetKey' => '[FILTERED]',
                'reset_key' => '[FILTERED]',
                'password' => '[FILTERED]',
                'id' => 1,
            ],
            $result->params,
        );
        $this->assertFalse($result->replayable);
        $this->assertSame(
            ['otp' => '1'],
            (new SensitiveParamsFilter())(['otp' => '1'])->params,
            'the no-argument default is unchanged',
        );
    }

    public function testNestingWithinTheLimitIsWalkedUnchanged(): void
    {
        $leaf = 'leaf';
        for ($i = 0; $i < 100; $i++) {
            $leaf = ['n' => $leaf];
        }

        $result = (new SensitiveParamsFilter())(['deep' => $leaf]);

        $this->assertTrue($result->replayable);
        $this->assertSame(['deep' => $leaf], $result->params);
    }

    /** @psalm-suppress RedundantCondition Psalm cannot see the write-through a reference would cause. */
    public function testDoesNotWriteThePlaceholderThroughAReferenceIntoTheCallersParams(): void
    {
        $secret = 'plain';
        $params = ['loginId' => 'a'];
        $params['password'] = &$secret;

        $result = (new SensitiveParamsFilter())($params);

        $this->assertSame('[FILTERED]', $result->params['password']);
        $this->assertSame('plain', $secret, 'the real request must still carry the credential the handler reads');
    }

    /** @return array{0: int, 1: mixed} */
    private static function descend(mixed $value): array
    {
        $depth = 0;
        while (is_array($value)) {
            $value = $value['n'];
            $depth++;
        }

        return [$depth, $value];
    }
}
