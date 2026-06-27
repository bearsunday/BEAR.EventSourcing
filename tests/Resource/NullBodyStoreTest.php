<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\NullBodyStore;
use BEAR\Resource\Method;
use BEAR\Resource\Request;
use PHPUnit\Framework\TestCase;

final class NullBodyStoreTest extends TestCase
{
    public function testDoesNotRenderBody(): void
    {
        $ro = new FakeResourceObject(body: ['id' => 1]);
        $ro->setRenderer(new ThrowingRenderer());
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        $bodyRef = (new NullBodyStore())($request, $ro);

        $this->assertNull($bodyRef);
        $this->assertNull($ro->view);
    }
}
