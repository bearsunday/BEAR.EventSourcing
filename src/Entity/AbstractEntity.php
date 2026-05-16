<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use DateTimeImmutable;
use JsonSerializable;

/**
 * Base entity class for all EC-CUBE entities
 */
abstract class AbstractEntity implements JsonSerializable
{
    protected ?DateTimeImmutable $createDate = null;
    protected ?DateTimeImmutable $updateDate = null;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];
        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($this);

            if ($value instanceof DateTimeImmutable) {
                $value = $value->format('Y-m-d H:i:s');
            } elseif ($value instanceof AbstractEntity) {
                $value = $value->toArray();
            } elseif (is_array($value)) {
                $value = array_map(
                    fn($item) => $item instanceof AbstractEntity ? $item->toArray() : $item,
                    $value
                );
            }

            $result[$property->getName()] = $value;
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getCreateDate(): ?DateTimeImmutable
    {
        return $this->createDate;
    }

    public function setCreateDate(?DateTimeImmutable $createDate): static
    {
        $this->createDate = $createDate;
        return $this;
    }

    public function getUpdateDate(): ?DateTimeImmutable
    {
        return $this->updateDate;
    }

    public function setUpdateDate(?DateTimeImmutable $updateDate): static
    {
        $this->updateDate = $updateDate;
        return $this;
    }
}
