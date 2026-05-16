<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\EventSourcing\Query\MemberQueryInterface;

class Members extends ResourceObject
{
    public function __construct(
        private readonly MemberQueryInterface $query
    ) {}

    #[RequireAuth(role: 'admin')]
    public function onGet(?int $id = null): static
    {
        if ($id !== null) {
            $member = $this->query->findById($id);
            if ($member === null) {
                $this->code = 404;
                $this->body = ['error' => 'Member not found'];
                return $this;
            }
            unset($member['password'], $member['salt']);
            $this->body = $member;
        } else {
            $members = $this->query->findAll();
            foreach ($members as &$member) {
                unset($member['password'], $member['salt']);
            }
            $this->body = ['members' => $members];
        }
        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPost(
        string $name,
        string $login_id,
        string $password,
        ?string $department = null,
        int $authority_id = 1,
        int $work_id = 1
    ): static {
        $existingMember = $this->query->findByLoginId($login_id);
        if ($existingMember !== null) {
            $this->code = 409;
            $this->body = ['error' => 'Login ID already exists'];
            return $this;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $id = $this->query->create([
            'name' => $name,
            'login_id' => $login_id,
            'password' => $hashedPassword,
            'department' => $department,
            'authority_id' => $authority_id,
            'work_id' => $work_id,
        ]);

        $this->code = 201;
        $this->body = ['id' => $id, 'login_id' => $login_id];
        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPut(
        int $id,
        ?string $name = null,
        ?string $password = null,
        ?string $department = null,
        ?int $authority_id = null,
        ?int $work_id = null
    ): static {
        $member = $this->query->findById($id);
        if ($member === null) {
            $this->code = 404;
            $this->body = ['error' => 'Member not found'];
            return $this;
        }

        $data = [];
        if ($name !== null) $data['name'] = $name;
        if ($password !== null) $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        if ($department !== null) $data['department'] = $department;
        if ($authority_id !== null) $data['authority_id'] = $authority_id;
        if ($work_id !== null) $data['work_id'] = $work_id;

        if (!empty($data)) {
            $this->query->update($id, $data);
        }

        $this->code = 200;
        $this->body = ['id' => $id, 'updated' => true];
        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onDelete(int $id): static
    {
        $member = $this->query->findById($id);
        if ($member === null) {
            $this->code = 404;
            $this->body = ['error' => 'Member not found'];
            return $this;
        }

        $this->query->delete($id);

        $this->code = 200;
        $this->body = ['deleted' => true];
        return $this;
    }
}
