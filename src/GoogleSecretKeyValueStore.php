<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Exception;
use Google\ApiCore\ApiException;
use Google\Cloud\SecretManager\V1\AccessSecretVersionRequest;
use Google\Cloud\SecretManager\V1\AddSecretVersionRequest;
use Google\Cloud\SecretManager\V1\Client\SecretManagerServiceClient;
use Google\Cloud\SecretManager\V1\SecretPayload;
use RuntimeException;

use function basename;
use function mb_trim;
use function sprintf;

final class GoogleSecretKeyValueStore implements GoogleSecretKeyValueStoreInterface
{
    private SecretManagerClientInterface $client;
    private string $secretPath;

    public function __construct(SecretManagerClientInterface $client, string $secretPath)
    {
        $this->client = $client;
        $this->secretPath = mb_trim($secretPath, '/');
    }

    public static function create(string $secretPath): GoogleSecretKeyValueStoreInterface
    {
        try {
            $client = new GoogleSecretManagerClientAdapter(new SecretManagerServiceClient());
        } catch (Exception $exception) {
            throw new RuntimeException(self::CLIENT_START_FAILED, 0, $exception);
        }

        return new self($client, $secretPath);
    }

    public function getValue(): ?string
    {
        $value = null;

        try {
            $secretVersionName = $this->secretPath.self::VERSION_LATEST;
            $request = (new AccessSecretVersionRequest())->setName($secretVersionName);
            $response = $this->client->accessSecretVersion($request);
        } catch (ApiException) {
            $message = sprintf(self::GET_VALUE_FAILED_SPRINTF, basename($this->secretPath));

            throw new GoogleSecretKeyValueStoreException($message);
        }
        $payload = $response->getPayload();
        if ($payload instanceof SecretPayload) {
            $value = $payload->getData();
        }

        return $value;
    }

    public function setValue(?string $value): self
    {
        try {
            $payload = new SecretPayload(['data' => $value]);
            $request = (new AddSecretVersionRequest())
                ->setParent($this->secretPath)
                ->setPayload($payload);
            $this->client->addSecretVersion($request);
        } catch (ApiException) {
            $message = sprintf(self::SET_VALUE_FAILED_SPRINTF, basename($this->secretPath));

            throw new GoogleSecretKeyValueStoreException($message);
        }

        return $this;
    }
}
