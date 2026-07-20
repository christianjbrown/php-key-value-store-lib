<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\GoogleSecretManagerClientAdapter;
use Google\ApiCore\CredentialsWrapper;
use Google\ApiCore\Testing\MockTransport;
use Google\Cloud\SecretManager\V1\AccessSecretVersionRequest;
use Google\Cloud\SecretManager\V1\AccessSecretVersionResponse;
use Google\Cloud\SecretManager\V1\AddSecretVersionRequest;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\SecretPayload;
use Google\Cloud\SecretManager\V1\SecretVersion;
use Google\Protobuf\Internal\Message;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception as MockObjectException;
use PHPUnit\Framework\TestCase;

#[CoversClass(GoogleSecretManagerClientAdapter::class)]
final class GoogleSecretManagerClientAdapterTest extends TestCase
{
    /**
     * @throws MockObjectException
     */
    public function testAccessSecretVersion(): void
    {
        $client = $this->createClient(new AccessSecretVersionResponse([
            'payload' => new SecretPayload(['data' => 'test-secret-value']),
        ]));
        $adapter = new GoogleSecretManagerClientAdapter($client);

        $request = (new AccessSecretVersionRequest())->setName('test/secret/path/here/versions/latest');

        self::assertSame('test-secret-value', $adapter->accessSecretVersion($request)->getPayload()?->getData());
    }

    /**
     * @throws MockObjectException
     */
    public function testAddSecretVersion(): void
    {
        $client = $this->createClient(new SecretVersion(['name' => 'test-secret-version-name']));
        $adapter = new GoogleSecretManagerClientAdapter($client);

        $request = (new AddSecretVersionRequest())
            ->setParent('test/secret/path/here')
            ->setPayload(new SecretPayload(['data' => 'test-secret-value']));

        self::assertSame('test-secret-version-name', $adapter->addSecretVersion($request)->getName());
    }

    /**
     * @throws MockObjectException
     */
    private function createClient(Message $response): SecretManagerServiceClient
    {
        $transport = new MockTransport();
        $transport->addResponse($response);

        return new SecretManagerServiceClient([
            'credentials' => self::createStub(CredentialsWrapper::class),
            'transport' => $transport,
        ]);
    }
}
