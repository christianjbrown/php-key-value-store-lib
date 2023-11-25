<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

interface GoogleSecretKeyValueStoreInterface extends KeyValueStoreInterface
{
    public const VERSION_LATEST = '/versions/latest';

    public static function create(string $secretPath): self;
}
