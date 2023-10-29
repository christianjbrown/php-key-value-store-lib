<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity()]
#[ORM\Table(name: 'key_value_store')]
class DatabaseKeyValueStoreEntity
{
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $key = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?string $ttl = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $value = null;

    final public function getKey(): ?string
    {
        return $this->key;
    }

    final public function getTtl(): ?int
    {
        return $this->ttl;
    }

    final public function getValue(): ?string
    {
        return $this->value;
    }

    final public function setKey(?string $key): self
    {
        $this->key = $key;

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
