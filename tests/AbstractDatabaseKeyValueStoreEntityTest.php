<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\AbstractDatabaseKeyValueStoreEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractDatabaseKeyValueStoreEntity::class)]
final class AbstractDatabaseKeyValueStoreEntityTest extends TestCase
{
    public function testDefaultsAreNull(): void
    {
        $entity = new TestDatabaseKeyValueStoreEntity();

        self::assertNull($entity->getId());
        self::assertNull($entity->getTtl());
        self::assertNull($entity->getValue());
    }

    public function testSettersAreFluentAndPersistState(): void
    {
        $entity = new TestDatabaseKeyValueStoreEntity();

        self::assertSame($entity, $entity->setId('test-id'));
        self::assertSame($entity, $entity->setValue('test-value', 2));
        self::assertSame('test-id', $entity->getId());
        self::assertSame(2, $entity->getTtl());
        self::assertSame('test-value', $entity->getValue());
    }
}
