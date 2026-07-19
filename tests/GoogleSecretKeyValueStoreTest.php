<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\GoogleSecretKeyValueStore;
use ChristianBrown\KeyValueStore\GoogleSecretKeyValueStoreExceptionInterface;
use ChristianBrown\KeyValueStore\GoogleSecretKeyValueStoreInterface;
use Exception;
use Google\ApiCore\ApiException;
use Google\Cloud\SecretManager\V1\AccessSecretVersionResponse;
use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\SecretPayload;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;

use function putenv;
use function sprintf;

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
        $this->expectExceptionMessage(GoogleSecretKeyValueStoreInterface::CLIENT_START_FAILED);

        putenv('GOOGLE_APPLICATION_CREDENTIALS=./tests/file-does-not-exist.json');
        GoogleSecretKeyValueStore::create('test/secret/path/here');
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValue(): void
    {
        $secretPayload = self::createStub(SecretPayload::class);
        $secretPayload->method('getData')
            ->willReturn('test-secret-value');

        $secretVersion = self::createStub(AccessSecretVersionResponse::class);
        $secretVersion->method('getPayload')
            ->willReturn($secretPayload);

        $client = self::createStub(SecretManagerServiceClient::class);
        $client->method('accessSecretVersion')
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
        $this->expectExceptionMessage(sprintf(GoogleSecretKeyValueStoreInterface::GET_VALUE_FAILED_SPRINTF, 'here'));

        $client = self::createStub(SecretManagerServiceClient::class);
        $client->method('accessSecretVersion')
            ->willThrowException(new ApiException('test-exception-message', 42));

        $store = new GoogleSecretKeyValueStore($client, 'test/secret/path/here');

        $store->getValue();
    }

    /**
     * @throws MockObjectException
     */
    public function testGetValueNoPayload(): void
    {
        $secretVersion = self::createStub(AccessSecretVersionResponse::class);
        $secretVersion->method('getPayload')
            ->willReturn(null);

        $client = self::createStub(SecretManagerServiceClient::class);
        $client->method('accessSecretVersion')
            ->willReturn($secretVersion);

        $store = new GoogleSecretKeyValueStore($client, 'test/secret/path/here');

        self::assertNull($store->getValue());
    }

    /**
     * @throws MockObjectException
     */
    public function testSetValue(): void
    {
        $secretVersion = self::createStub(AccessSecretVersionResponse::class);

        $client = self::createMock(SecretManagerServiceClient::class);
        $client->expects(self::once())
            ->method('addSecretVersion')
            ->with(
                'test/secret/path/here',
                self::callback(
                    static fn (SecretPayload $payload): bool => 'test-secret-value' === $payload->getData(),
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
        $this->expectExceptionMessage(sprintf(GoogleSecretKeyValueStoreInterface::SET_VALUE_FAILED_SPRINTF, 'here'));

        $client = self::createMock(SecretManagerServiceClient::class);
        $client->expects(self::once())
            ->method('addSecretVersion')
            ->with(
                'test/secret/path/here',
                self::callback(
                    static fn (SecretPayload $payload): bool => 'test-secret-value' === $payload->getData(),
                ),
            )
            ->willThrowException(new ApiException('test-exception-message', 42));

        $store = new GoogleSecretKeyValueStore($client, 'test/secret/path/here');

        $store->setValue('test-secret-value');
    }
}
