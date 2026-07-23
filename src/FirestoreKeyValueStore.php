<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Google\Cloud\Firestore\DocumentReference;
use Google\Cloud\Firestore\DocumentSnapshot;
use Google\Cloud\Firestore\FirestoreClient;

use function is_int;
use function is_string;
use function time;

final class FirestoreKeyValueStore implements FirestoreKeyValueStoreInterface
{
    private DocumentReference $documentReference;

    public function __construct(DocumentReference $documentReference)
    {
        $this->documentReference = $documentReference;
    }

    public static function create(FirestoreClient $client, string $collection, string $documentId): FirestoreKeyValueStoreInterface
    {
        $documentReference = $client->collection($collection)->document($documentId);

        return new self($documentReference);
    }

    public function getTtl(): ?int
    {
        $snapshot = $this->documentReference->snapshot();
        if (!$snapshot->exists()) {
            return null;
        }

        $expiresAt = self::readExpiresAt($snapshot);
        if (null === $expiresAt) {
            return null;
        }

        return $expiresAt - time();
    }

    public function getValue(): ?string
    {
        $snapshot = $this->documentReference->snapshot();
        if (!$snapshot->exists()) {
            return null;
        }

        $expiresAt = self::readExpiresAt($snapshot);
        if (null !== $expiresAt) {
            if ($expiresAt < time()) {
                return null;
            }
        }

        $value = $snapshot->get(self::FIELD_VALUE);
        if (!is_string($value)) {
            return null;
        }

        return $value;
    }

    public function setValue(?string $value, ?int $ttl = null): self
    {
        $expiresAt = null;
        if (null !== $ttl) {
            $expiresAt = time() + $ttl;
        }

        $this->documentReference->set([
            self::FIELD_VALUE => $value,
            self::FIELD_EXPIRES_AT => $expiresAt,
        ]);

        return $this;
    }

    private static function readExpiresAt(DocumentSnapshot $snapshot): ?int
    {
        $expiresAt = $snapshot->get(self::FIELD_EXPIRES_AT);
        if (!is_int($expiresAt)) {
            return null;
        }

        return $expiresAt;
    }
}
