<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use JsonSerializable;
use Koriym\SemanticLogger\AbstractContext;

/**
 * Verbose context for resource completion with profiling data.
 *
 * @psalm-immutable
 */
final class ResourceCompleteContext extends AbstractContext implements JsonSerializable
{
    public const TYPE = 'resource.complete';
    public const SCHEMA_URL = '';

    public function __construct(
        public readonly string $uri,
        public readonly int $code,
        /** @var array<string, string> */
        public readonly array $headers,
        public readonly mixed $body,
        public readonly ?string $view = null,
        public readonly ?Profile $profile = null,
    ) {
    }

    public function jsonSerialize(): array
    {
        $data = [
            'uri' => $this->uri,
            'code' => $this->code,
            'headers' => $this->headers,
            'body' => $this->body,
        ];

        if ($this->view !== null) {
            $data['view'] = $this->view;
        }

        if ($this->profile !== null) {
            $data['profile'] = $this->profile->jsonSerialize();
        }

        return $data;
    }
}
