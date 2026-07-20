<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Google\Cloud\SecretManager\V1\AccessSecretVersionRequest;
use Google\Cloud\SecretManager\V1\AccessSecretVersionResponse;
use Google\Cloud\SecretManager\V1\AddSecretVersionRequest;
use Google\Cloud\SecretManager\V1\SecretVersion;

interface SecretManagerClientInterface
{
    public function accessSecretVersion(AccessSecretVersionRequest $request): AccessSecretVersionResponse;

    public function addSecretVersion(AddSecretVersionRequest $request): SecretVersion;
}
