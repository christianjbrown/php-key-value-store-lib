<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\AbstractDatabaseKeyValueStoreEntity;
use ChristianBrown\KeyValueStore\DatabaseKeyValueStore;
use ChristianBrown\KeyValueStore\DatabaseKeyValueStoreEntityInterface;
use ChristianBrown\KeyValueStore\DatabaseKeyValueStoreInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use stdClass;

use function sprintf;

#[CoversClass(DatabaseKeyValueStore::class)]
#[CoversClass(AbstractDatabaseKeyValueStoreEntity::class)]
final class DatabaseKeyValueStoreTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testConstructorInvalidClass(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(DatabaseKeyValueStoreInterface::ENTITY_CLASS_INVALID_SPRINTF, stdClass::class, DatabaseKeyValueStoreEntityInterface::class));

        $entityManager = self::createStub(EntityManagerInterface::class);

        new DatabaseKeyValueStore($entityManager, stdClass::class, 'test-key');
    }

    /**
     * @throws Exception
     */
    public function testGetTtlEntityExists(): void
    {
        $entity = new TestDatabaseKeyValueStoreEntity();
        $entity->setValue('test-value', 42);

        $store = $this->createStore($entity);

        self::assertSame(42, $store->getTtl());
    }

    /**
     * @throws Exception
     */
    public function testGetTtlEntityNotExists(): void
    {
        $store = $this->createStore(null);

        self::assertNull($store->getTtl());
    }

    /**
     * @throws Exception
     */
    public function testGetValueEntityExists(): void
    {
        $entity = new TestDatabaseKeyValueStoreEntity();
        $entity->setValue('test-value');

        $store = $this->createStore($entity);

        self::assertSame('test-value', $store->getValue());
    }

    /**
     * @throws Exception
     */
    public function testGetValueEntityNotExists(): void
    {
        $store = $this->createStore(null);

        self::assertNull($store->getValue());
    }

    /**
     * @throws Exception
     */
    public function testSetValueEntityExists(): void
    {
        $entity = new TestDatabaseKeyValueStoreEntity();
        $entity->setId('test-key');

        $repository = self::createStub(EntityRepository::class);
        $repository->method('findOneBy')
            ->willReturn($entity);

        $entityManager = self::createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')
            ->willReturn($repository);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entity));
        $entityManager->expects(self::once())
            ->method('flush');

        $store = new DatabaseKeyValueStore($entityManager, TestDatabaseKeyValueStoreEntity::class, 'test-key');

        self::assertSame($store, $store->setValue('updated-value', 7));
        self::assertSame('updated-value', $entity->getValue());
        self::assertSame(7, $entity->getTtl());
    }

    /**
     * @throws Exception
     */
    public function testSetValueEntityNotExists(): void
    {
        $repository = self::createStub(EntityRepository::class);
        $repository->method('findOneBy')
            ->willReturn(null);

        $entityManager = self::createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')
            ->willReturn($repository);
        $entityManager->expects(self::once())
            ->method('persist')
            ->with(self::callback(
                static function (object $entity): bool {
                    self::assertInstanceOf(DatabaseKeyValueStoreEntityInterface::class, $entity);
                    self::assertSame('test-key', $entity->getId());
                    self::assertSame('created-value', $entity->getValue());

                    return true;
                },
            ));
        $entityManager->expects(self::once())
            ->method('flush');

        $store = new DatabaseKeyValueStore($entityManager, TestDatabaseKeyValueStoreEntity::class, 'test-key');

        self::assertSame($store, $store->setValue('created-value'));
    }

    /**
     * Builds a store whose repository returns the given entity (or null) for the key.
     *
     * @throws Exception
     */
    private function createStore(?DatabaseKeyValueStoreEntityInterface $entity): DatabaseKeyValueStore
    {
        $repository = self::createStub(EntityRepository::class);
        $repository->method('findOneBy')
            ->willReturn($entity);

        $entityManager = self::createStub(EntityManagerInterface::class);
        $entityManager->method('getRepository')
            ->willReturn($repository);

        return new DatabaseKeyValueStore($entityManager, TestDatabaseKeyValueStoreEntity::class, 'test-key');
    }
}
