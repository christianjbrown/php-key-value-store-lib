<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

interface DatabaseKeyValueStoreEntityInterface extends KeyValueStoreInterface
{
    public function getId(): ?string;

    public function getTtl(): ?int;

    public function getValue(): ?string;

    public function setId(?string $id): self;

    public function setValue(?string $value, ?int $ttl = null): self;
}
