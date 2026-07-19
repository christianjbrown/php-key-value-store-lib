<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

interface TtlAwareKeyValueStoreInterface extends KeyValueStoreInterface
{
    public function getTtl(): ?int;

    public function setValue(?string $value, ?int $ttl = null): self;
}
