<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\Profiler\PhpProfile;
use Koriym\SemanticLogger\Profiler\Profile;
use PHPUnit\Framework\TestCase;

final class ResourceCompleteContextTest extends TestCase
{
    public function testConstructor(): void
    {
        $context = new ResourceCompleteContext(
            'app://self/user',
            200,
            ['Content-Type' => 'application/json'],
            ['id' => 1, 'name' => 'test'],
            '{"id":1,"name":"test"}',
        );

        $this->assertSame('app://self/user', $context->uri);
        $this->assertSame(200, $context->code);
        $this->assertSame(['Content-Type' => 'application/json'], $context->headers);
        $this->assertSame(['id' => 1, 'name' => 'test'], $context->body);
        $this->assertSame('{"id":1,"name":"test"}', $context->view);
    }

    public function testExtendsAbstractContext(): void
    {
        $context = new ResourceCompleteContext('app://self/user', 200, [], null);

        $this->assertInstanceOf(AbstractContext::class, $context);
    }

    public function testWithProfile(): void
    {
        $phpProfile = new PhpProfile();
        $profile = new Profile(null, null, $phpProfile);

        $context = new ResourceCompleteContext(
            'app://self/user',
            200,
            [],
            null,
            null,
            $profile,
        );

        $this->assertSame($profile, $context->profile);
    }

    public function testJsonSerialize(): void
    {
        $context = new ResourceCompleteContext(
            'app://self/user',
            200,
            ['Content-Type' => 'application/json'],
            ['id' => 1],
            '{"id":1}',
        );

        $data = $context->jsonSerialize();

        $this->assertSame('app://self/user', $data['uri']);
        $this->assertSame(200, $data['code']);
        $this->assertArrayHasKey('view', $data);
    }

    public function testJsonSerializeWithoutView(): void
    {
        $context = new ResourceCompleteContext('app://self/user', 200, [], null, null);

        $data = $context->jsonSerialize();

        $this->assertArrayNotHasKey('view', $data);
    }

    public function testJsonSerializeWithProfile(): void
    {
        $phpProfile = new PhpProfile();
        $profile = new Profile(null, null, $phpProfile);

        $context = new ResourceCompleteContext(
            'app://self/user',
            200,
            [],
            null,
            null,
            $profile,
        );

        $data = $context->jsonSerialize();

        $this->assertArrayHasKey('profile', $data);
    }
}
