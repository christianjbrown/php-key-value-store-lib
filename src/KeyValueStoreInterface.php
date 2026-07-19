<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

interface KeyValueStoreInterface
{
    public function getValue(): ?string;

    public function setValue(?string $value): self;
}
