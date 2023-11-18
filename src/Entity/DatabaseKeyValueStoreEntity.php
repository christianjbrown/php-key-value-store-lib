<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'key_value_store')]
class DatabaseKeyValueStoreEntity implements DatabaseKeyValueStoreEntityInterface
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

    final public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    final public function setTtl(?int $ttl): self
    {
        $this->ttl = $ttl;

        return $this;
    }

    final public function setValue(?string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
