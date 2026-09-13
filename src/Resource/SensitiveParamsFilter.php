<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use function array_keys;
use function is_array;
use function is_string;
use function str_contains;
use function strtolower;

/**
 * Removes credential/transport-shaped param keys by name, secure by default, and marks the
 * result non-replayable only when the removed value was domain input.
 *
 * SemanticLogInvoker constructs this when no #[Filtered] ParamsFilterInterface is bound, so a
 * CSRF token or password never reaches an observation log or an extracted Event by default — a
 * consuming application has to opt out, not opt in, to get that protection.
 *
 * Two key shapes are removed, and they carry different consequences:
 *
 * - **Transport** (`csrf` substring): a CSRF token is single-use and session-bound. A replay
 *   engine mints its own regardless of what was recorded, so removing it from the recorded
 *   params changes nothing about what those params are for — the request stays replayable.
 * - **Credential** (`password`/`token`/`secret` substring): domain input the handler actually
 *   reads to complete the operation (a login password, a device token, an API secret).
 *   Removing it leaves the recorded params genuinely insufficient to reproduce the operation,
 *   so the result is marked non-replayable.
 *
 * `token` alone is deliberately in the credential set, not the transport one: `deviceToken` or
 * `accessToken` are domain input a handler verifies, unlike a CSRF token a replay never reuses.
 * `csrfToken` matches the transport rule first (checked before the credential rule), so it is
 * removed without flipping replayability even though it also contains `token`.
 *
 * The default deliberately stops at three substrings and does **not** match a generic `key`
 * suffix: an application's own identifiers just as often end in `Key` for reasons that have
 * nothing to do with secrecy — an `idempotencyKey` is exactly the domain input a replay needs
 * to stay deterministic, and stripping it would make an otherwise-safe write silently
 * non-replayable. `resetKey`/`authKey`/`secretKey`-shaped fields an application actually wants
 * redacted are a `#[Filtered] ParamsFilterInterface` the application binds itself — this
 * package does not know an arbitrary caller's naming conventions well enough to guess safely.
 *
 * A case-insensitive substring match, not an enumerated list, so `password_confirm`,
 * `passwordConfirm`, and `current_password` all match the same rule; nested arrays and lists of
 * maps are walked the same way a flat one is. This is a name-based guard, not a secret-value
 * scanner — a field shaped differently (a bare `pin` or `otp`) still needs an
 * application-supplied `#[Filtered] ParamsFilterInterface`.
 */
final class SensitiveParamsFilter implements ParamsFilterInterface
{
    /** @var list<string> substrings that are transport metadata: removed, replayability unaffected */
    private const array TRANSPORT_SUBSTRINGS = ['csrf'];

    /** @var list<string> substrings that are domain credentials: removed, marks the result non-replayable */
    private const array CREDENTIAL_SUBSTRINGS = ['password', 'token', 'secret'];

    /** @param array<string, mixed> $params */
    public function __invoke(array $params): FilteredParams
    {
        $replayable = true;
        $filtered = self::filterRecursive($params, $replayable);

        /** @var array<string, mixed> $filtered */
        return new FilteredParams($filtered, $replayable);
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    private static function filterRecursive(array $values, bool &$replayable): array
    {
        foreach (array_keys($values) as $key) {
            if (is_string($key)) {
                if (self::isTransportKey($key)) {
                    unset($values[$key]);
                    continue;
                }

                if (self::isCredentialKey($key)) {
                    unset($values[$key]);
                    $replayable = false;
                    continue;
                }
            }

            /** @psalm-suppress MixedAssignment Params values are schema-free by design. */
            $value = $values[$key];
            if (is_array($value)) {
                $values[$key] = self::filterRecursive($value, $replayable);
            }
        }

        return $values;
    }

    private static function isTransportKey(string $key): bool
    {
        $lower = strtolower($key);
        foreach (self::TRANSPORT_SUBSTRINGS as $substring) {
            if (str_contains($lower, $substring)) {
                return true;
            }
        }

        return false;
    }

    private static function isCredentialKey(string $key): bool
    {
        $lower = strtolower($key);
        foreach (self::CREDENTIAL_SUBSTRINGS as $substring) {
            if (str_contains($lower, $substring)) {
                return true;
            }
        }

        return false;
    }
}
