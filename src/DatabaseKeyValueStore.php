<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMSetup;

final class DatabaseKeyValueStore implements KeyValueStoreInterface
{
    private EntityManagerInterface $entityManager;
    private string $key;
    private EntityRepository $repository;

    public function __construct(array $dbConfig, string $key)
    {
        $config = ORMSetup::createAttributeMetadataConfiguration(paths: [__DIR__]);
        $connection = DriverManager::getConnection($dbConfig, $config);
        $this->entityManager = new EntityManager($connection, $config);
        $this->repository = $this->entityManager->getRepository(self::class);
        $this->key = $key;
    }

    public function getTtl(): ?int
    {
        $ttl = null;

        $keyValueStoreObj = $this->repository->findOneBy(['key' => $this->key]);
        if ($keyValueStoreObj instanceof DatabaseKeyValueStoreEntity) {
            $ttl = $keyValueStoreObj->getTtl();
        }

        return $ttl;
    }

    public function getValue(): ?string
    {
        $value = null;

        $keyValueStoreObj = $this->repository->findOneBy(['key' => $this->key]);
        if ($keyValueStoreObj instanceof DatabaseKeyValueStoreEntity) {
            $value = $keyValueStoreObj->getValue();
        }

        return $value;
    }

    public function setValue(?string $value, ?int $ttl = null): self
    {
        $keyValueStoreObj = $this->repository->findOneBy(['key' => $this->key]);
        if (!($keyValueStoreObj instanceof self)) {
            $keyValueStoreObj = new DatabaseKeyValueStoreEntity();
            $keyValueStoreObj->setKey($this->key);
        }
        $keyValueStoreObj->setValue($value);
        $keyValueStoreObj->setTtl($ttl);

        return $this;
    }
}
