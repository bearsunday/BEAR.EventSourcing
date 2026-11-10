<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Caller;

use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceObject;

use function ob_get_clean;
use function ob_start;

/**
 * Includes a plain PHP template, as a compiling engine includes its compiled copy.
 *
 * @psalm-suppress MixedAssignment The template reads the body as it is.
 */
final class TemplateRenderer implements RenderInterface
{
    public function render(ResourceObject $ro): string
    {
        $body = $ro->body;
        ob_start();
        require __DIR__ . '/template/Index.php';

        return $ro->view = (string) ob_get_clean();
    }
}
