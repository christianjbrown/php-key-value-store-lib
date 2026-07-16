<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Google\Cloud\Firestore\FirestoreClient;

interface FirestoreKeyValueStoreInterface extends KeyValueStoreInterface
{
    public const string FIELD_EXPIRES_AT = 'expiresAt';
    public const string FIELD_VALUE = 'value';

    public static function create(FirestoreClient $client, string $collection, string $documentId): self;
}
