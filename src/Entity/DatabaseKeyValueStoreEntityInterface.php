<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Entity;

interface DatabaseKeyValueStoreEntityInterface
{
    public function getId(): ?string;

    public function getTtl(): ?int;

    public function getValue(): ?string;

    public function setId(?string $id): self;

    public function setTtl(?int $ttl): self;

    public function setValue(?string $value): self;
}
