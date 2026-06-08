<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use DateTimeImmutable;
use JsonSerializable;
use ReflectionClass;

use function array_map;
use function is_array;

/**
 * Base entity class for all EC-CUBE entities
 */
abstract class AbstractEntity implements JsonSerializable
{
    protected DateTimeImmutable|null $createDate = null;
    protected DateTimeImmutable|null $updateDate = null;

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $result = [];
        $reflection = new ReflectionClass($this);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($this);

            if ($value instanceof DateTimeImmutable) {
                $value = $value->format('Y-m-d H:i:s');
            } elseif ($value instanceof AbstractEntity) {
                $value = $value->toArray();
            } elseif (is_array($value)) {
                $value = array_map(
                    static fn ($item) => $item instanceof AbstractEntity ? $item->toArray() : $item,
                    $value,
                );
            }

            $result[$property->getName()] = $value;
        }

        return $result;
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getCreateDate(): DateTimeImmutable|null
    {
        return $this->createDate;
    }

    public function setCreateDate(DateTimeImmutable|null $createDate): static
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getUpdateDate(): DateTimeImmutable|null
    {
        return $this->updateDate;
    }

    public function setUpdateDate(DateTimeImmutable|null $updateDate): static
    {
        $this->updateDate = $updateDate;

        return $this;
    }
}
