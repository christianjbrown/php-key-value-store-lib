<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

interface GoogleSecretKeyValueStoreInterface extends KeyValueStoreInterface
{
    public const string CLIENT_START_FAILED = 'Could not start Google Secret Manager, make sure GOOGLE_APPLICATION_CREDENTIALS environment variables points to valid JSON credentials.';
    public const string GET_VALUE_FAILED_SPRINTF = 'Failed to retrieve the "%s" secret value.';
    public const string SET_VALUE_FAILED_SPRINTF = 'Failed to update the "%s" secret value.';
    public const string VERSION_LATEST = '/versions/latest';

    public static function create(string $secretPath): self;
}
