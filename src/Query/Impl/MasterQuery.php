<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\MasterQueryInterface;

class MasterQuery implements MasterQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo) {}

    public function getPrefectures(): array
    {
        return $this->pdo->fetchAll('SELECT id, name FROM mtb_pref ORDER BY sort_no');
    }

    public function getSexes(): array
    {
        return $this->pdo->fetchAll('SELECT id, name FROM mtb_sex ORDER BY sort_no');
    }

    public function getOrderStatuses(): array
    {
        return $this->pdo->fetchAll('SELECT id, name FROM mtb_order_status ORDER BY sort_no');
    }

    public function getProductStatuses(): array
    {
        return $this->pdo->fetchAll('SELECT id, name FROM mtb_product_status ORDER BY sort_no');
    }

    public function getCustomerStatuses(): array
    {
        return $this->pdo->fetchAll('SELECT id, name FROM mtb_customer_status ORDER BY sort_no');
    }

    public function getTaxTypes(): array
    {
        return $this->pdo->fetchAll('SELECT id, name FROM mtb_tax_type ORDER BY sort_no');
    }

    public function getSaleTypes(): array
    {
        return $this->pdo->fetchAll('SELECT id, name FROM mtb_sale_type ORDER BY sort_no');
    }
}
