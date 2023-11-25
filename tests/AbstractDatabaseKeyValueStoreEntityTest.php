<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\AbstractDatabaseKeyValueStoreEntity;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractDatabaseKeyValueStoreEntity::class)]
final class AbstractDatabaseKeyValueStoreEntityTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function test(): void
    {
        $entity = $this->createPartialMock(AbstractDatabaseKeyValueStoreEntity::class, []);
        self::assertNull($entity->getId());
        self::assertNull($entity->getTtl());
        self::assertNull($entity->getValue());
        $entity->setId('test-id');
        $entity->setValue('test-value', 2);
        self::assertSame('test-id', $entity->getId());
        self::assertSame(2, $entity->getTtl());
        self::assertSame('test-value', $entity->getValue());
    }
}
