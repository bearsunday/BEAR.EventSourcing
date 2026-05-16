<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\EventSourcing\Query\PluginQueryInterface;

class Plugins extends ResourceObject
{
    public function __construct(
        private readonly PluginQueryInterface $query
    ) {}

    #[RequireAuth(role: 'admin')]
    public function onGet(?int $id = null): static
    {
        if ($id !== null) {
            $plugin = $this->query->findById($id);
            if ($plugin === null) {
                $this->code = 404;
                $this->body = ['error' => 'Plugin not found'];
                return $this;
            }
            $this->body = $plugin;
        } else {
            $this->body = ['plugins' => $this->query->findAll()];
        }
        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPost(string $name, string $code, string $version = '1.0.0', ?string $source = null): static
    {
        $existingPlugin = $this->query->findByCode($code);
        if ($existingPlugin !== null) {
            $this->code = 409;
            $this->body = ['error' => 'Plugin code already exists'];
            return $this;
        }

        $id = $this->query->install([
            'name' => $name,
            'code' => $code,
            'version' => $version,
            'source' => $source,
        ]);

        $this->code = 201;
        $this->body = ['id' => $id, 'code' => $code];
        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPut(int $id, ?bool $enabled = null, ?string $version = null): static
    {
        $plugin = $this->query->findById($id);
        if ($plugin === null) {
            $this->code = 404;
            $this->body = ['error' => 'Plugin not found'];
            return $this;
        }

        if ($enabled !== null) {
            if ($enabled) {
                $this->query->enable($id);
            } else {
                $this->query->disable($id);
            }
        }

        if ($version !== null) {
            $this->query->update($id, ['version' => $version]);
        }

        $this->code = 200;
        $this->body = ['id' => $id, 'updated' => true];
        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onDelete(int $id): static
    {
        $plugin = $this->query->findById($id);
        if ($plugin === null) {
            $this->code = 404;
            $this->body = ['error' => 'Plugin not found'];
            return $this;
        }

        $this->query->uninstall($id);

        $this->code = 200;
        $this->body = ['deleted' => true];
        return $this;
    }
}
