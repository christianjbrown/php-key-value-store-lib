<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Google\Cloud\SecretManager\V1\AccessSecretVersionRequest;
use Google\Cloud\SecretManager\V1\AccessSecretVersionResponse;
use Google\Cloud\SecretManager\V1\AddSecretVersionRequest;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\SecretVersion;

final class GoogleSecretManagerClientAdapter implements SecretManagerClientInterface
{
    private SecretManagerServiceClient $client;

    public function __construct(SecretManagerServiceClient $client)
    {
        $this->client = $client;
    }

    public function accessSecretVersion(AccessSecretVersionRequest $request): AccessSecretVersionResponse
    {
        return $this->client->accessSecretVersion($request);
    }

    public function addSecretVersion(AddSecretVersionRequest $request): SecretVersion
    {
        return $this->client->addSecretVersion($request);
    }
}
