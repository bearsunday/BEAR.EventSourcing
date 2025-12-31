<?php

declare(strict_types=1);

namespace BearEccube\Query;

interface MasterQueryInterface
{
    public function getPrefectures(): array;
    public function getSexes(): array;
    public function getOrderStatuses(): array;
    public function getProductStatuses(): array;
    public function getCustomerStatuses(): array;
    public function getTaxTypes(): array;
    public function getSaleTypes(): array;
}
