<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\EventSourcing\Query\ContactQueryInterface;
use BEAR\EventSourcing\Service\MailServiceInterface;
use BEAR\Resource\ResourceObject;

class Contacts extends ResourceObject
{
    public function __construct(
        private readonly ContactQueryInterface $query,
        private readonly MailServiceInterface $mailService,
    ) {
    }

    #[RequireAuth(role: 'admin')]
    public function onGet(
        int|null $id = null,
        int|null $status = null,
        int $limit = 20,
        int $offset = 0,
    ): static {
        if ($id !== null) {
            $contact = $this->query->findById($id);
            if ($contact === null) {
                $this->code = 404;
                $this->body = ['error' => 'Contact not found'];

                return $this;
            }

            $this->body = $contact;
        } else {
            $filters = [];
            if ($status !== null) {
                $filters['status'] = $status;
            }

            $contacts = $this->query->findByFilters($filters, $limit, $offset);
            $total = $this->query->countByFilters($filters);

            $this->body = [
                'contacts' => $contacts,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ];
        }

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPut(int $id, int $status, string|null $response = null): static
    {
        $contact = $this->query->findById($id);
        if ($contact === null) {
            $this->code = 404;
            $this->body = ['error' => 'Contact not found'];

            return $this;
        }

        $data = ['status' => $status];
        if ($response !== null) {
            $data['response'] = $response;

            // Send response email to customer
            $this->mailService->send(
                $contact['email'],
                "{$contact['name01']} {$contact['name02']}",
                "Re: {$contact['subject']}",
                $response,
            );
        }

        $this->query->update($id, $data);

        $this->code = 200;
        $this->body = ['id' => $id, 'status' => $status];

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onDelete(int $id): static
    {
        $contact = $this->query->findById($id);
        if ($contact === null) {
            $this->code = 404;
            $this->body = ['error' => 'Contact not found'];

            return $this;
        }

        $this->query->delete($id);

        $this->code = 200;
        $this->body = ['deleted' => true];

        return $this;
    }
}
