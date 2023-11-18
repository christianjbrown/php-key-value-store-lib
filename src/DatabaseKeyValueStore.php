<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use ChristianBrown\KeyValueStore\Entity\DatabaseKeyValueStoreEntity;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

final class DatabaseKeyValueStore implements KeyValueStoreInterface
{
    private EntityManagerInterface $entityManager;
    private string $key;
    private EntityRepository $repository;

    public function __construct(EntityManagerFactoryInterface $entityManagerFactory, string $key)
    {
        $this->entityManager = $entityManagerFactory->getEntityManager();
        $this->repository = $this->entityManager->getRepository(DatabaseKeyValueStoreEntity::class);
        $this->key = $key;
    }

    public function getTtl(): ?int
    {
        $ttl = null;

        $keyValueStoreObj = $this->repository->findOneBy(['id' => $this->key]);
        if ($keyValueStoreObj instanceof DatabaseKeyValueStoreEntity) {
            $ttl = $keyValueStoreObj->getTtl();
        }

        return $ttl;
    }

    public function getValue(): ?string
    {
        $value = null;

        $keyValueStoreObj = $this->repository->findOneBy(['id' => $this->key]);
        if ($keyValueStoreObj instanceof DatabaseKeyValueStoreEntity) {
            $value = $keyValueStoreObj->getValue();
        }

        return $value;
    }

    public function setValue(?string $value, ?int $ttl = null): self
    {
        $keyValueStoreObj = $this->repository->findOneBy(['id' => $this->key]);
        if (!($keyValueStoreObj instanceof DatabaseKeyValueStoreEntity)) {
            $keyValueStoreObj = new DatabaseKeyValueStoreEntity();
            $keyValueStoreObj->setId($this->key);
        }
        $keyValueStoreObj->setValue($value);
        $keyValueStoreObj->setTtl($ttl);

        $this->entityManager->persist($keyValueStoreObj);
        $this->entityManager->flush();

        return $this;
    }
}
