<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\EventSourcing\Query\TaxRuleQueryInterface;
use BEAR\Resource\ResourceObject;

class TaxRules extends ResourceObject
{
    public function __construct(
        private readonly TaxRuleQueryInterface $query,
    ) {
    }

    #[RequireAuth(role: 'admin')]
    public function onGet(int|null $id = null): static
    {
        if ($id !== null) {
            $rule = $this->query->findById($id);
            if ($rule === null) {
                $this->code = 404;
                $this->body = ['error' => 'Tax rule not found'];

                return $this;
            }

            $this->body = $rule;
        } else {
            $this->body = ['tax_rules' => $this->query->findAll()];
        }

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPost(
        float $tax_rate,
        string $apply_date,
        int|null $product_class_id = null,
        int|null $pref_id = null,
        int $calc_rule = 1,
        float $tax_adjust = 0,
    ): static {
        $id = $this->query->create([
            'tax_rate' => $tax_rate,
            'apply_date' => $apply_date,
            'product_class_id' => $product_class_id,
            'pref_id' => $pref_id,
            'calc_rule' => $calc_rule,
            'tax_adjust' => $tax_adjust,
        ]);

        $this->code = 201;
        $this->body = ['id' => $id];

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPut(
        int $id,
        float|null $tax_rate = null,
        string|null $apply_date = null,
        int|null $calc_rule = null,
        float|null $tax_adjust = null,
    ): static {
        $rule = $this->query->findById($id);
        if ($rule === null) {
            $this->code = 404;
            $this->body = ['error' => 'Tax rule not found'];

            return $this;
        }

        $data = [];
        if ($tax_rate !== null) {
            $data['tax_rate'] = $tax_rate;
        }

        if ($apply_date !== null) {
            $data['apply_date'] = $apply_date;
        }

        if ($calc_rule !== null) {
            $data['calc_rule'] = $calc_rule;
        }

        if ($tax_adjust !== null) {
            $data['tax_adjust'] = $tax_adjust;
        }

        if (! empty($data)) {
            $this->query->update($id, $data);
        }

        $this->code = 200;
        $this->body = ['id' => $id, 'updated' => true];

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onDelete(int $id): static
    {
        $rule = $this->query->findById($id);
        if ($rule === null) {
            $this->code = 404;
            $this->body = ['error' => 'Tax rule not found'];

            return $this;
        }

        $this->query->delete($id);

        $this->code = 200;
        $this->body = ['deleted' => true];

        return $this;
    }
}
