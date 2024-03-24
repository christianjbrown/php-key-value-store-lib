<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\GoogleSecretKeyValueStore;
use ChristianBrown\KeyValueStore\GoogleSecretKeyValueStoreExceptionInterface;
use Exception;
use Google\ApiCore\ApiException;
use Google\Cloud\SecretManager\V1\AccessSecretVersionResponse;
use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\SecretPayload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function putenv;

#[CoversClass(GoogleSecretKeyValueStore::class)]
final class GoogleSecretKeyValueStoreTest extends TestCase
{
    public function testCreate(): void
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=./tests/test-credentials.json');
        $store = GoogleSecretKeyValueStore::create('test/secret/path/here');
        self::assertInstanceOf(GoogleSecretKeyValueStore::class, $store);
    }

    public function testCreateException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Could not start Google Secret Manager, make sure GOOGLE_APPLICATION_CREDENTIALS environment variables points to valid JSON credentials.');

        putenv('GOOGLE_APPLICATION_CREDENTIALS=./tests/file-does-not-exist.json');
        GoogleSecretKeyValueStore::create('test/secret/path/here');
    }

    /**
     * @throws MockObjectException
     */
    public function testGetTtl(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Google Secret Manager does not support TTL.');

        $client = $this->createMock(SecretManagerServiceClient::class);
        $store = new GoogleSecretKeyValueStore($client, 'test/secret/path/here');

        $store->getTtl();
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValue(): void
    {
        $secretPayload = $this->createMock(SecretPayload::class);
        $secretPayload->method('getData')
            ->willReturn('test-secret-value');

        $secretVersion = $this->createMock(AccessSecretVersionResponse::class);
        $secretVersion->method('getPayload')
            ->willReturn($secretPayload);

        $client = $this->createMock(SecretManagerServiceClient::class);
        $client->method('accessSecretVersion')
            ->with('test/secret/path/here/versions/latest')
            ->willReturn($secretVersion);

        $store = new GoogleSecretKeyValueStore($client, 'test/secret/path/here');

        self::assertSame('test-secret-value', $store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueApiException(): void
    {
        $this->expectException(GoogleSecretKeyValueStoreExceptionInterface::class);
        $this->expectExceptionMessage('Failed to retrieve the "here" secret value.');

        $client = $this->createMock(SecretManagerServiceClient::class);
        $client->method('accessSecretVersion')
            ->with('test/secret/path/here/versions/latest')
            ->willThrowException(new ApiException('test-exception-message', 42));

        $store = new GoogleSecretKeyValueStore($client, 'test/secret/path/here');

        $store->getValue();
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueNoPayload(): void
    {
        $secretVersion = $this->createMock(AccessSecretVersionResponse::class);
        $secretVersion->method('getPayload')
            ->willReturn(null);

        $client = $this->createMock(SecretManagerServiceClient::class);
        $client->method('accessSecretVersion')
            ->with('test/secret/path/here/versions/latest')
            ->willReturn($secretVersion);

        $store = new GoogleSecretKeyValueStore($client, 'test/secret/path/here');

        self::assertNull($store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testSetValue(): void
    {
        $secretVersion = $this->createMock(AccessSecretVersionResponse::class);

        $client = $this->createMock(SecretManagerServiceClient::class);
        $client->expects(self::once())
            ->method('addSecretVersion')
            ->with(
                'test/secret/path/here',
                self::callback(
                    static fn (SecretPayload $payload) => 'test-secret-value' === $payload->getData()
                ),
            )
            ->willReturn($secretVersion);

        $store = new GoogleSecretKeyValueStore($client, 'test/secret/path/here');

        self::assertSame($store, $store->setValue('test-secret-value'));
    }

    /**
     * @throws MockObjectException
     */
    public function testSetValueApiException(): void
    {
        $this->expectException(GoogleSecretKeyValueStoreExceptionInterface::class);
        $this->expectExceptionMessage('Failed to update the "here" secret value.');

        $client = $this->createMock(SecretManagerServiceClient::class);
        $client->expects(self::once())
            ->method('addSecretVersion')
            ->with(
                'test/secret/path/here',
                self::callback(
                    static fn (SecretPayload $payload) => 'test-secret-value' === $payload->getData()
                ),
            )
            ->willThrowException(new ApiException('test-exception-message', 42));

        $store = new GoogleSecretKeyValueStore($client, 'test/secret/path/here');

        $store->setValue('test-secret-value');
    }

    /**
     * @throws MockObjectException
     */
    public function testSetValueTtl(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Google Secret Manager does not support TTL.');

        $client = $this->createMock(SecretManagerServiceClient::class);
        $store = new GoogleSecretKeyValueStore($client, 'test/secret/path/here');

        $store->setValue('test-secret-value', 2);
    }
}
