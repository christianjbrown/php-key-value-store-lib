<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\FirestoreKeyValueStore;
use ChristianBrown\KeyValueStore\FirestoreKeyValueStoreInterface;
use Google\Cloud\Firestore\CollectionReference;
use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\DocumentSnapshot;
use Google\Cloud\Firestore\FirestoreClient;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;

use function time;

#[CoversClass(FirestoreKeyValueStore::class)]
final class FirestoreKeyValueStoreTest extends TestCase
{
    /**
     * @throws MockObjectException
     */
    public function testCreate(): void
    {
        $documentReference = self::createStub(DocumentReference::class);

        $collection = self::createStub(CollectionReference::class);
        $collection->method('document')
            ->willReturn($documentReference);

        $client = self::createStub(FirestoreClient::class);
        $client->method('collection')
            ->willReturn($collection);

        $store = FirestoreKeyValueStore::create($client, 'kv', 'my-key');

        self::assertInstanceOf(FirestoreKeyValueStore::class, $store);
    }

    /**
     * @throws MockObjectException
     */
    public function testGetTtlAbsent(): void
    {
        $snapshot = self::createStub(DocumentSnapshot::class);
        $snapshot->method('exists')
            ->willReturn(true);
        $snapshot->method('get')
            ->willReturn(null);

        $store = new FirestoreKeyValueStore($this->documentReferenceReturning($snapshot));

        self::assertNull($store->getTtl());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetTtlNotExists(): void
    {
        $snapshot = self::createStub(DocumentSnapshot::class);
        $snapshot->method('exists')
            ->willReturn(false);

        $store = new FirestoreKeyValueStore($this->documentReferenceReturning($snapshot));

        self::assertNull($store->getTtl());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetTtlPresent(): void
    {
        $expiresAt = time() + 60;

        $snapshot = self::createStub(DocumentSnapshot::class);
        $snapshot->method('exists')
            ->willReturn(true);
        $snapshot->method('get')
            ->willReturnMap([
                [FirestoreKeyValueStoreInterface::FIELD_EXPIRES_AT, $expiresAt],
            ]);

        $store = new FirestoreKeyValueStore($this->documentReferenceReturning($snapshot));

        self::assertSame($expiresAt - time(), $store->getTtl());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueExpired(): void
    {
        $snapshot = self::createStub(DocumentSnapshot::class);
        $snapshot->method('exists')
            ->willReturn(true);
        $snapshot->method('get')
            ->willReturnMap([
                [FirestoreKeyValueStoreInterface::FIELD_EXPIRES_AT, time() - 60],
                [FirestoreKeyValueStoreInterface::FIELD_VALUE, 'test-value'],
            ]);

        $store = new FirestoreKeyValueStore($this->documentReferenceReturning($snapshot));

        self::assertNull($store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueNotExists(): void
    {
        $snapshot = self::createStub(DocumentSnapshot::class);
        $snapshot->method('exists')
            ->willReturn(false);

        $store = new FirestoreKeyValueStore($this->documentReferenceReturning($snapshot));

        self::assertNull($store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueNotExpiredNotStored(): void
    {
        $snapshot = self::createStub(DocumentSnapshot::class);
        $snapshot->method('exists')
            ->willReturn(true);
        $snapshot->method('get')
            ->willReturnMap([
                [FirestoreKeyValueStoreInterface::FIELD_EXPIRES_AT, time() + 60],
                [FirestoreKeyValueStoreInterface::FIELD_VALUE, null],
            ]);

        $store = new FirestoreKeyValueStore($this->documentReferenceReturning($snapshot));

        self::assertNull($store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueNotStored(): void
    {
        $snapshot = self::createStub(DocumentSnapshot::class);
        $snapshot->method('exists')
            ->willReturn(true);
        $snapshot->method('get')
            ->willReturn(null);

        $store = new FirestoreKeyValueStore($this->documentReferenceReturning($snapshot));

        self::assertNull($store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueNoTtl(): void
    {
        $snapshot = self::createStub(DocumentSnapshot::class);
        $snapshot->method('exists')
            ->willReturn(true);
        $snapshot->method('get')
            ->willReturnMap([
                [FirestoreKeyValueStoreInterface::FIELD_EXPIRES_AT, null],
                [FirestoreKeyValueStoreInterface::FIELD_VALUE, 'test-value'],
            ]);

        $store = new FirestoreKeyValueStore($this->documentReferenceReturning($snapshot));

        self::assertSame('test-value', $store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueValid(): void
    {
        $snapshot = self::createStub(DocumentSnapshot::class);
        $snapshot->method('exists')
            ->willReturn(true);
        $snapshot->method('get')
            ->willReturnMap([
                [FirestoreKeyValueStoreInterface::FIELD_EXPIRES_AT, time() + 60],
                [FirestoreKeyValueStoreInterface::FIELD_VALUE, 'test-value'],
            ]);

        $store = new FirestoreKeyValueStore($this->documentReferenceReturning($snapshot));

        self::assertSame('test-value', $store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testSetValueNoTtl(): void
    {
        $documentReference = self::createMock(DocumentReference::class);
        $documentReference->expects(self::once())
            ->method('set')
            ->with([
                FirestoreKeyValueStoreInterface::FIELD_VALUE => 'test-value',
                FirestoreKeyValueStoreInterface::FIELD_EXPIRES_AT => null,
            ])
            ->willReturn([]);

        $store = new FirestoreKeyValueStore($documentReference);

        self::assertSame($store, $store->setValue('test-value'));
    }

    /**
     * @throws MockObjectException
     */
    public function testSetValueTtl(): void
    {
        $before = time();

        $documentReference = self::createMock(DocumentReference::class);
        $documentReference->expects(self::once())
            ->method('set')
            ->with(self::callback(
                static function (array $fields) use ($before): bool {
                    if ('test-value' !== $fields[FirestoreKeyValueStoreInterface::FIELD_VALUE]) {
                        return false;
                    }

                    return $fields[FirestoreKeyValueStoreInterface::FIELD_EXPIRES_AT] >= $before + 60;
                },
            ))
            ->willReturn([]);

        $store = new FirestoreKeyValueStore($documentReference);

        self::assertSame($store, $store->setValue('test-value', 60));
    }

    /**
     * @throws MockObjectException
     */
    private function documentReferenceReturning(DocumentSnapshot $snapshot): DocumentReference
    {
        $documentReference = self::createStub(DocumentReference::class);
        $documentReference->method('snapshot')
            ->willReturn($snapshot);

        return $documentReference;
    }
}
