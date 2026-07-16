<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
abstract class AbstractDatabaseKeyValueStoreEntity implements DatabaseKeyValueStoreEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $id = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $ttl = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $value = null;

    final public function getId(): ?string
    {
        return $this->id;
    }

    final public function getTtl(): ?int
    {
        return $this->ttl;
    }

    final public function getValue(): ?string
    {
        return $this->value;
    }

    final public function setId(?string $id): DatabaseKeyValueStoreEntityInterface
    {
        $this->id = $id;

        return $this;
    }

    final public function setValue(?string $value, ?int $ttl = null): DatabaseKeyValueStoreEntityInterface
    {
        $this->value = $value;
        $this->ttl = $ttl;

        return $this;
    }
}
