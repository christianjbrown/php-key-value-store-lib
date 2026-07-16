<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\AbstractDatabaseKeyValueStoreEntity;

/**
 * Concrete fixture entity used to exercise the abstract mapped-superclass and to
 * back the {@see \ChristianBrown\KeyValueStore\DatabaseKeyValueStore} in tests.
 */
final class TestDatabaseKeyValueStoreEntity extends AbstractDatabaseKeyValueStoreEntity
{
}
