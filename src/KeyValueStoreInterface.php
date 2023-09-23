<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

interface KeyValueStoreInterface
{
    public function getTtl(): ?int;

    public function getValue(): ?string;

    public function setValue(?string $value, ?int $ttl = null): self;
}
