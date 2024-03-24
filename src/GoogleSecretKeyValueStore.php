<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Exception;
use Google\ApiCore\ApiException;
use Google\Cloud\SecretManager\V1\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\SecretPayload;

use RuntimeException;

use function basename;
use function sprintf;

final class GoogleSecretKeyValueStore implements GoogleSecretKeyValueStoreInterface
{
    private SecretManagerServiceClient $client;
    private string $secretPath;

    public function __construct(SecretManagerServiceClient $client, string $secretPath)
    {
        $this->client = $client;
        $this->secretPath = trim($secretPath, '/');
    }

    public static function create(string $secretPath): GoogleSecretKeyValueStoreInterface
    {
        try {
            $client = new SecretManagerServiceClient();
        } catch (Exception $exception) {
            throw new RuntimeException('Could not start Google Secret Manager, make sure GOOGLE_APPLICATION_CREDENTIALS environment variables points to valid JSON credentials.', 0, $exception);
        }
        $new = new self($client, $secretPath);

        return $new;
    }

    public function getTtl(): ?int
    {
        throw new RuntimeException('Google Secret Manager does not support TTL.');
    }

    public function getValue(): ?string
    {
        $value = null;

        try {
            $secretVersionName = $this->secretPath.self::VERSION_LATEST;
            $response = $this->client->accessSecretVersion($secretVersionName);
        } catch (ApiException) {
            $message = sprintf('Failed to retrieve the "%s" secret value.', basename($this->secretPath));

            throw new GoogleSecretKeyValueStoreException($message);
        }
        $payload = $response->getPayload();
        if ($payload instanceof SecretPayload) {
            $value = $payload->getData();
        }

        return $value;
    }

    public function setValue(?string $value, ?int $ttl = null): self
    {
        if (null !== $ttl) {
            throw new RuntimeException('Google Secret Manager does not support TTL.');
        }

        try {
            $payload = new SecretPayload(['data' => $value]);
            $this->client->addSecretVersion($this->secretPath, $payload);
        } catch (ApiException) {
            $message = sprintf('Failed to update the "%s" secret value.', basename($this->secretPath));

            throw new GoogleSecretKeyValueStoreException($message);
        }

        return $this;
    }
}
