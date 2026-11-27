<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture;

use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

final class CountingInterceptor implements MethodInterceptor
{
    public int $calls = 0;

    /** {@inheritDoc} */
    #[Override]
    public function invoke(MethodInvocation $invocation): mixed
    {
        $this->calls++;

        return $invocation->proceed();
    }
}
