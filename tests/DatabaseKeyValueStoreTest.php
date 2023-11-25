<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\AbstractDatabaseKeyValueStoreEntity;
use ChristianBrown\KeyValueStore\DatabaseKeyValueStore;
use ChristianBrown\KeyValueStore\DatabaseKeyValueStoreEntityInterface;
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
        $this->expectExceptionMessage(sprintf('Entity class %s must implement %s', stdClass::class, DatabaseKeyValueStoreEntityInterface::class));

        $em = $this->createMock(EntityManagerInterface::class);

        new DatabaseKeyValueStore($em, stdClass::class, 'test-key');

    }

    /**
     * @throws Exception
     */
    public function testGetTtlEntityExists(): void
    {
        $entity = $this->createPartialMock(AbstractDatabaseKeyValueStoreEntity::class, ['getTtl']);
        $entity->expects(self::once())
            ->method('getTtl')
            ->willReturn(42);
        $entityClass = $entity::class;

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['id' => 'test-key'])
            ->willReturn($entity);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);

        $store = new DatabaseKeyValueStore($em, $entityClass, 'test-key');
        self::assertSame(42, $store->getTtl());
    }

    /**
     * @throws Exception
     */
    public function testGetTtlEntityNotExists(): void
    {
        $store = $this->getStoreEntityNotExist();
        self::assertNull($store->getTtl());
    }

    /**
     * @throws Exception
     */
    public function testGetValueEntityExists(): void
    {
        $entity = $this->createPartialMock(AbstractDatabaseKeyValueStoreEntity::class, ['getValue']);
        $entity->expects(self::once())
            ->method('getValue')
            ->willReturn('test-value');
        $entityClass = $entity::class;

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['id' => 'test-key'])
            ->willReturn($entity);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);

        $store = new DatabaseKeyValueStore($em, $entityClass, 'test-key');
        self::assertSame('test-value', $store->getValue());
    }

    /**
     * @throws Exception
     */
    public function testGetValueEntityNotExists(): void
    {
        $store = $this->getStoreEntityNotExist();
        self::assertNull($store->getValue());
    }

    /**
     * @throws Exception
     */
    public function testSetValueEntityExists(): void
    {
        $valueSet = false;
        $persistCalled = false;
        $entity = $this->createPartialMock(AbstractDatabaseKeyValueStoreEntity::class, ['setValue']);
        $entity->expects(self::once())
            ->method('setValue')
            ->with('test-value')
            ->willReturnCallback(
                static function () use ($entity, &$valueSet) {
                    $valueSet = true;

                    return $entity;
                }
            );
        $entityClass = $entity::class;

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['id' => 'test-key'])
            ->willReturn($entity);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('persist')
            ->with(
                self::callback(
                    // Make sure setValue was called before persist
                    static function ($arg) use ($entity, &$valueSet, &$persistCalled) {
                        self::assertSame($entity, $arg);
                        self::assertTrue($valueSet);

                        $persistCalled = true;

                        return true;
                    }
                )
            );
        $em->expects(self::once())
            ->method('flush')
            ->willReturnCallback(
                // Make sure persist was called before flush
                static function () use (&$persistCalled): void {
                    self::assertTrue($persistCalled);
                }
            );

        $em->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);

        $store = new DatabaseKeyValueStore($em, $entityClass, 'test-key');
        self::assertSame($store, $store->setValue('test-value'));
    }

    /**
     * @throws Exception
     */
    public function testSetValueEntityNotExists(): void
    {
        $persistCalled = false;

        $entity = $this->createPartialMock(AbstractDatabaseKeyValueStoreEntity::class, []);
        $entityClass = $entity::class;

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['id' => 'test-key'])
            ->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('persist')
            ->with(
                self::callback(
                    // Make sure setValue was called before persist
                    static function ($arg) use (&$persistCalled) {
                        self::assertInstanceOf(DatabaseKeyValueStoreEntityInterface::class, $arg);
                        self::assertSame('test-key', $arg->getId());
                        self::assertSame('test-value', $arg->getValue());

                        $persistCalled = true;

                        return true;
                    }
                )
            );
        $em->expects(self::once())
            ->method('flush')
            ->willReturnCallback(
                // Make sure persist was called before flush
                static function () use (&$persistCalled): void {
                    self::assertTrue($persistCalled);
                }
            );

        $em->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);

        $store = new DatabaseKeyValueStore($em, $entityClass, 'test-key');
        self::assertSame($store, $store->setValue('test-value'));
    }

    /**
     * @throws Exception
     */
    private function getStoreEntityNotExist(): DatabaseKeyValueStore
    {
        $entity = $this->createPartialMock(AbstractDatabaseKeyValueStoreEntity::class, []);
        $entityClass = $entity::class;

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['id' => 'test-key'])
            ->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->with($entityClass)
            ->willReturn($repo);

        $store = new DatabaseKeyValueStore($em, $entityClass, 'test-key');

        return $store;
    }
}
