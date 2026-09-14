<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use JsonException;

use function array_map;
use function array_merge;
use function is_array;
use function is_object;
use function is_string;
use function json_decode;
use function json_encode;
use function str_contains;
use function str_replace;
use function strtolower;

use const JSON_THROW_ON_ERROR;

/**
 * Replaces credential/transport-shaped param values with a placeholder by key name, secure by
 * default, and marks the result non-replayable only when the withheld value was domain input.
 *
 * Both package recorders — SemanticLogInvoker (request params) and SemanticLogMediaQueryLogger
 * (query bind values) — construct this when no #[Filtered] ParamsFilterInterface is bound, so a
 * CSRF token or password in either never reaches the observation log or an extracted Event by
 * default — a consuming application has to opt out, not opt in, to get that protection. What
 * lies outside it: a response body a BodyStoreInterface records, the message of an exception
 * recorded in a close context, and an application's own loggers.
 *
 * The key stays and only its value becomes `[FILTERED]` — the same convention as Rails'
 * `filter_parameters` and Sentry's event scrubber. A deleted key would hide that a credential
 * was sent at all; a kept key with a placeholder shows it in the log and preserves the shape
 * of the recorded params, so a reader or a replay engine sees exactly what was withheld.
 *
 * Two key shapes are filtered, and they carry different consequences:
 *
 * - **Transport** (`csrf` substring): a CSRF token is single-use and session-bound. A replay
 *   engine mints its own regardless of what was recorded, so withholding it from the recorded
 *   params changes nothing about what those params are for — the request stays replayable.
 *   The value is replaced whole even when it is a map, but a credential key nested inside it
 *   (`csrfConfig => ['secret' => …]`) still decides replayability.
 * - **Credential** (`passw`/`pwd`/`passphrase`/`privatekey`/`token`/`secret`/`apikey` substring):
 *   domain input the handler actually reads to complete the operation (a login password, a
 *   device token, an API key or secret). Withholding it leaves the recorded params genuinely
 *   insufficient to reproduce the operation, so the result is marked non-replayable. `passw`
 *   rather than `password` so `passwd` matches too; `passphrase`, `pwd` and `privateKey` are
 *   the other spellings a form or an integration-settings page actually uses.
 *
 * `token` alone is deliberately in the credential set, not the transport one: `deviceToken` or
 * `accessToken` are domain input a handler verifies, unlike a CSRF token a replay never reuses.
 * `csrfToken` matches the transport rule first (checked before the credential rule), so it is
 * filtered without flipping replayability even though it also contains `token`. The same
 * substring also catches cursor-shaped fields (`pageToken`, `nextToken`): they are replaced and
 * the request marked non-replayable, so an application paginating that way binds its own filter.
 *
 * The default deliberately does **not** match a generic `key` suffix: an application's own
 * identifiers just as often end in `Key` for reasons that have nothing to do with secrecy — an
 * `idempotencyKey` is exactly the domain input a replay needs to stay deterministic, and
 * filtering it would make an otherwise-safe write silently non-replayable. `apikey` is the
 * exception because nothing non-secret is named that way. `resetKey`/`authKey`-shaped fields an
 * application actually wants redacted are a `#[Filtered] ParamsFilterInterface` the application
 * binds itself — this package does not know an arbitrary caller's naming conventions well
 * enough to guess safely.
 *
 * A substring match on the key with case and word separators (`_`, `-`) ignored, not an
 * enumerated list, so `password_confirm`, `passwordConfirm`, `current_password` and `api_key`
 * all match the same rule (and `secretary` or `tokenizer` match too — the cost of the
 * coverage). Nested arrays and lists of maps are walked the same way a flat one is; an object
 * value is walked on the JSON view the log will record
 * (`JsonSerializable` and public properties alike), and a value that cannot be encoded as JSON,
 * or nests more than 128 levels deep, is withheld whole rather than guessed at. This is a
 * name-based guard, not a secret-value scanner — a field shaped differently (a bare `pin` or
 * `otp`) is not matched by default. Such a key is added to the credential set through the
 * constructor (`new SensitiveParamsFilter(['otp'])`); anything beyond that — narrowing the
 * default for a `pageToken`, a different replayability verdict — is an application-supplied
 * `#[Filtered] ParamsFilterInterface` that composes this one.
 */
final class SensitiveParamsFilter implements ParamsFilterInterface
{
    /** What a filtered value is recorded as: the key stays, so the log shows the field was sent. */
    public const string FILTERED = '[FILTERED]';

    /**
     * Nesting deeper than this is withheld whole. No request params legitimately nest this
     * deep; a reference cycle would otherwise walk until the engine's own limit, and the walk
     * stays clear of a debugger's nesting limit (Xdebug: 512 frames) with room to spare.
     */
    public const int MAX_DEPTH = 128;

    /** @var list<string> substrings that are transport metadata: filtered, replayability unaffected */
    private const array TRANSPORT_SUBSTRINGS = ['csrf'];

    /** @var list<string> substrings that are domain credentials: filtered, marks the result non-replayable */
    private const array CREDENTIAL_SUBSTRINGS = [
        'passw',
        'pwd',
        'passphrase',
        'privatekey',
        'token',
        'secret',
        'apikey',
    ];

    /** @var list<string> the credential set in effect: the default plus what the application added */
    private readonly array $credentialSubstrings;

    /**
     * @param list<string> $extraCredentialSubstrings Key substrings this application treats as
     *                                                credentials on top of the default set (`otp`,
     *                                                `pin`, `resetKey`); matched the same way,
     *                                                with case and `_`/`-` ignored.
     */
    public function __construct(array $extraCredentialSubstrings = [])
    {
        $this->credentialSubstrings = array_merge(
            self::CREDENTIAL_SUBSTRINGS,
            array_map(self::normalize(...), $extraCredentialSubstrings),
        );
    }

    /** @param array<string, mixed> $params */
    public function __invoke(array $params): FilteredParams
    {
        $replayable = true;
        $filtered = $this->filterMap($params, $replayable, 1);

        /** @var array<string, mixed> $filtered */
        return new FilteredParams($filtered, $replayable);
    }

    /**
     * Builds a new array rather than assigning into a copy: an element that is a PHP reference
     * would otherwise write the placeholder through to the caller's own request params.
     *
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     *
     * @psalm-suppress MixedAssignment Params values are schema-free by design.
     */
    private function filterMap(array $values, bool &$replayable, int $depth): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            if (is_string($key) && self::isTransportKey($key)) {
                // Replaced whole, but walked first for its verdict: a credential nested inside
                // a transport-named map still makes the request non-replayable.
                $this->filterValue($value, $replayable, $depth + 1);
                $result[$key] = self::FILTERED;
                continue;
            }

            if (is_string($key) && $this->isCredentialKey($key)) {
                $result[$key] = self::FILTERED;
                $replayable = false;
                continue;
            }

            $result[$key] = $this->filterValue($value, $replayable, $depth + 1);
        }

        return $result;
    }

    /** Walks arrays and the JSON view of objects; a value that cannot be walked is withheld whole. */
    private function filterValue(mixed $value, bool &$replayable, int $depth): mixed
    {
        if ($depth > self::MAX_DEPTH) {
            $replayable = false;

            return self::FILTERED;
        }

        if (is_object($value)) {
            try {
                /** @psalm-suppress MixedAssignment */
                $value = json_decode(
                    json_encode($value, JSON_THROW_ON_ERROR),
                    true,
                    self::MAX_DEPTH,
                    JSON_THROW_ON_ERROR,
                );
            } catch (JsonException) {
                // The log could not record this value either; withholding it is the safe side.
                $replayable = false;

                return self::FILTERED;
            }
        }

        return is_array($value) ? $this->filterMap($value, $replayable, $depth) : $value;
    }

    private static function isTransportKey(string $key): bool
    {
        return self::containsAny(self::normalize($key), self::TRANSPORT_SUBSTRINGS);
    }

    private function isCredentialKey(string $key): bool
    {
        return self::containsAny(self::normalize($key), $this->credentialSubstrings);
    }

    /** Case and word separators are spelling, not meaning: `api_key`, `API-Key`, `apiKey` are one name. */
    private static function normalize(string $key): string
    {
        return strtolower(str_replace(['_', '-'], '', $key));
    }

    /** @param list<string> $substrings */
    private static function containsAny(string $haystack, array $substrings): bool
    {
        foreach ($substrings as $substring) {
            if (str_contains($haystack, $substring)) {
                return true;
            }
        }

        return false;
    }
}
