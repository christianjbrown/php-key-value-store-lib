<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use function time;

final class MemoryKeyValueStore implements KeyValueStoreInterface
{
    private ?int $expiresAt = null;
    private ?string $value = null;

    public function getTtl(): ?int
    {
        $expiresIn = null;
        $time = time();
        if ($this->expiresAt && $time <= $this->expiresAt) {
            $expiresIn = $this->expiresAt - $time;
        }

        return $expiresIn;
    }

    public function getValue(): ?string
    {
        if ($this->expiresAt && time() >= $this->expiresAt) {
            $this->value = null;
        }

        return $this->value;
    }

    public function setValue(?string $value, ?int $ttl = null): self
    {
        $this->value = $value;
        $this->expiresAt = time() + $ttl;

        return $this;
    }
}
