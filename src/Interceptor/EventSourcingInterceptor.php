<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Interceptor;

use BEAR\EventSourcing\EventSourcing\Event;
use BEAR\EventSourcing\EventSourcing\EventStoreInterface;
use BEAR\Resource\ResourceObject;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

use function str_starts_with;

/**
 * Event Sourcing Interceptor
 *
 * Records all non-GET resource method calls as events
 */
class EventSourcingInterceptor implements MethodInterceptor
{
    public function __construct(
        private readonly EventStoreInterface $eventStore,
    ) {
    }

    /** @param MethodInvocation<object> $invocation */
    public function invoke(MethodInvocation $invocation): mixed
    {
        $method = $invocation->getMethod()->getName();

        // Skip GET requests (read-only operations)
        if (str_starts_with($method, 'onGet')) {
            return $invocation->proceed();
        }

        /** @var ResourceObject $resource */
        $resource = $invocation->getThis();

        // Get the URI from the resource
        $uri = $this->getResourceUri($resource);

        // Get parameters
        $params = $this->getParameters($invocation);

        // Execute the method
        $result = $invocation->proceed();

        // Extract HTTP method from method name (onPost -> POST, onPut -> PUT, etc.)
        $httpMethod = $this->extractHttpMethod($method);

        // Create and store the event
        $event = Event::create(
            $uri,
            $httpMethod,
            $params,
            $result instanceof ResourceObject ? $result->body : $result,
        );

        $this->eventStore->append($event);

        return $result;
    }

    private function getResourceUri(ResourceObject $resource): string
    {
        return (string) $resource->uri;
    }

    /**
     * @param MethodInvocation<object> $invocation
     *
     * @return array<string, mixed>
     */
    private function getParameters(MethodInvocation $invocation): array
    {
        $params = [];
        $method = $invocation->getMethod();
        $arguments = $invocation->getArguments();
        $parameters = $method->getParameters();

        foreach ($parameters as $index => $parameter) {
            if (! isset($arguments[$index])) {
                continue;
            }

            $params[$parameter->getName()] = $arguments[$index];
        }

        return $params;
    }

    private function extractHttpMethod(string $methodName): string
    {
        if (str_starts_with($methodName, 'onPost')) {
            return 'POST';
        }

        if (str_starts_with($methodName, 'onPut')) {
            return 'PUT';
        }

        if (str_starts_with($methodName, 'onDelete')) {
            return 'DELETE';
        }

        if (str_starts_with($methodName, 'onPatch')) {
            return 'PATCH';
        }

        return 'UNKNOWN';
    }
}
