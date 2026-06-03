<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Interceptor;

use ArrayObject;
use LogicException;
use Ray\Aop\MethodInvocation;
use Ray\Aop\ReflectionMethod;

use function is_string;

/** @implements MethodInvocation<object> */
final class NullParameterInvocation implements MethodInvocation
{
    /** @param array<int, mixed> $arguments */
    public function __construct(
        private readonly NullParameterResource $resource,
        private readonly array $arguments,
    ) {
    }

    public function getMethod(): ReflectionMethod
    {
        return new ReflectionMethod(NullParameterResource::class, 'onPatch');
    }

    /** @return ArrayObject<int, mixed> */
    public function getArguments(): ArrayObject
    {
        return new ArrayObject($this->arguments);
    }

    public function getNamedArguments(): ArrayObject
    {
        return new ArrayObject();
    }

    public function proceed(): mixed
    {
        $memo = $this->arguments[0] ?? null;
        if (! is_string($memo) && $memo !== null) {
            throw new LogicException('Invalid memo argument');
        }

        return $this->resource->onPatch($memo);
    }

    public function getThis(): NullParameterResource
    {
        return $this->resource;
    }
}
