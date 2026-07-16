<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

interface DatabaseKeyValueStoreInterface extends KeyValueStoreInterface
{
    public const string ENTITY_CLASS_INVALID_SPRINTF = 'Entity class %s must implement %s';
    public const string FIELD_ID = 'id';
}
