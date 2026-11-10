<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Embed;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceObject;
use RuntimeException;

use function is_array;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * An html-context renderer bound to every resource: it has templates for pages only.
 *
 * @psalm-suppress MixedAssignment A body is untyped.
 */
final class HtmlRenderer implements RenderInterface
{
    public function render(ResourceObject $ro): string
    {
        Trace::$events[] = 'render ' . $ro->uri->scheme . '://self' . $ro->uri->path;
        if ($ro->uri->scheme !== 'page') {
            throw new RuntimeException('Template not found: ' . $ro->uri->path);
        }

        $html = '';
        foreach (is_array($ro->body) ? $ro->body : [] as $key => $value) {
            $shown = $value instanceof AbstractRequest ? $value->body : $value;
            $html .= "<p>{$key}=" . json_encode($shown, JSON_THROW_ON_ERROR) . '</p>';
        }

        return $ro->view = $html;
    }
}
