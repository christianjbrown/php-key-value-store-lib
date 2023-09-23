<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use ChristianBrown\UserFriendlyException\UserFriendlyException;
use Exception;
use Google\ApiCore\ApiException;
use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\SecretPayload;
use RuntimeException;

use function basename;
use function sprintf;

final class GoogleSecretKeyValueStore implements KeyValueStoreInterface
{
    private const VERSION_LATEST = '/versions/latest';

    private SecretManagerServiceClient $client;
    private string $secretPath;

    public function __construct(string $secretPath)
    {
        try {
            $this->client = new SecretManagerServiceClient();
        } catch (Exception $exception) {
            throw new RuntimeException('Could not start Google Secret Manager, make sure GOOGLE_APPLICATION_CREDENTIALS environment variables points to valid JSON credentials.', 0, $exception);
        }
        $this->secretPath = trim($secretPath, '/');
    }

    public function getTtl(): ?int
    {
        return null;
    }

    public function getValue(): ?string
    {
        $value = null;

        try {
            $secretVersionName = $this->secretPath.self::VERSION_LATEST;
            $response = $this->client->accessSecretVersion($secretVersionName);
        } catch (ApiException $exception) {
            throw new UserFriendlyException(sprintf('Failed to retrieve the "%s" secret value.', basename($this->secretPath)));
        }
        $payload = $response->getPayload();
        if ($payload instanceof SecretPayload) {
            $value = $payload->getData();
        }

        return $value;
    }

    public function setValue(?string $value, ?int $ttl = null): self
    {
        try {
            $payload = new SecretPayload(['data' => $value]);
            $this->client->addSecretVersion($this->secretPath, $payload);
        } catch (ApiException $exception) {
            throw new UserFriendlyException(sprintf('Failed to update the "%s" secret value.', basename($this->secretPath)));
        }

        return $this;
    }
}
