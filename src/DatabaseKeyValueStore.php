<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;

use function is_a;

final class DatabaseKeyValueStore implements KeyValueStoreInterface
{
    private string $entityClassName;
    private EntityManagerInterface $entityManager;
    private string $key;
    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager, string $entityClassName, string $key)
    {
        if (!is_a($entityClassName, DatabaseKeyValueStoreEntityInterface::class, true)) {
            throw new InvalidArgumentException(sprintf('Entity class %s must implement %s', $entityClassName, DatabaseKeyValueStoreEntityInterface::class));
        }

        $this->entityManager = $entityManager;
        $this->entityClassName = $entityClassName;
        $this->repository = $this->entityManager->getRepository($entityClassName);
        $this->key = $key;
    }

    public function getTtl(): ?int
    {
        $ttl = null;

        $keyValueStoreObj = $this->repository->findOneBy(['id' => $this->key]);
        if ($keyValueStoreObj instanceof DatabaseKeyValueStoreEntityInterface) {
            $ttl = $keyValueStoreObj->getTtl();
        }

        return $ttl;
    }

    public function getValue(): ?string
    {
        $value = null;

        $keyValueStoreObj = $this->repository->findOneBy(['id' => $this->key]);
        if ($keyValueStoreObj instanceof DatabaseKeyValueStoreEntityInterface) {
            $value = $keyValueStoreObj->getValue();
        }

        return $value;
    }

    public function setValue(?string $value, ?int $ttl = null): self
    {
        $keyValueStoreObj = $this->repository->findOneBy(['id' => $this->key]);
        if (!($keyValueStoreObj instanceof DatabaseKeyValueStoreEntityInterface)) {
            $keyValueStoreObj = new $this->entityClassName();
            $keyValueStoreObj->setId($this->key);
        }
        $keyValueStoreObj->setValue($value, $ttl);

        $this->entityManager->persist($keyValueStoreObj);
        $this->entityManager->flush();

        return $this;
    }
}
