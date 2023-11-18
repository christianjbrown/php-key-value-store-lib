<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests\Entity;

use ChristianBrown\KeyValueStore\Entity\DatabaseKeyValueStoreEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DatabaseKeyValueStoreEntity::class)]
final class DatabaseKeyValueStoreEntityTest extends TestCase
{
    public function test(): void
    {
        $entity = new DatabaseKeyValueStoreEntity();
        self::assertNull($entity->getId());
        self::assertNull($entity->getTtl());
        self::assertNull($entity->getValue());
        $entity->setId('test-id');
        $entity->setTtl(2);
        $entity->setValue('test-value');
        self::assertSame('test-id', $entity->getId());
        self::assertSame(2, $entity->getTtl());
        self::assertSame('test-value', $entity->getValue());
    }
}
