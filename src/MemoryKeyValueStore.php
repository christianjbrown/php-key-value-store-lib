<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

final class MemoryKeyValueStore implements KeyValueStoreInterface
{
    private ?int $ttl = null;
    private ?string $value = null;

    public function getTtl(): ?int
    {
        return $this->ttl;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(?string $value, ?int $ttl = null): self
    {
        $this->value = $value;
        $this->ttl = $ttl;

        return $this;
    }
}
