<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\NullViewStore;
use BEAR\Resource\Method;
use BEAR\Resource\Request;
use PHPUnit\Framework\TestCase;

final class NullViewStoreTest extends TestCase
{
    public function testDoesNotRenderView(): void
    {
        $ro = new FakeResourceObject(body: ['id' => 1]);
        $ro->setRenderer(new ThrowingRenderer());
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        $viewRef = (new NullViewStore())($request, $ro);

        $this->assertNull($viewRef);
        $this->assertNull($ro->view);
    }
}
