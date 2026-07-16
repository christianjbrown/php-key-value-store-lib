<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use InvalidArgumentException;

use function is_a;
use function sprintf;

final class DatabaseKeyValueStore implements DatabaseKeyValueStoreInterface
{
    /**
     * @var class-string<DatabaseKeyValueStoreEntityInterface>
     */
    private string $entityClassName;
    private EntityManagerInterface $entityManager;
    private string $key;

    /**
     * @var EntityRepository<DatabaseKeyValueStoreEntityInterface>
     */
    private EntityRepository $repository;

    public function __construct(EntityManagerInterface $entityManager, string $entityClassName, string $key)
    {
        if (!is_a($entityClassName, DatabaseKeyValueStoreEntityInterface::class, true)) {
            throw new InvalidArgumentException(sprintf(self::ENTITY_CLASS_INVALID_SPRINTF, $entityClassName, DatabaseKeyValueStoreEntityInterface::class));
        }

        $this->entityManager = $entityManager;
        $this->entityClassName = $entityClassName;
        $this->repository = $this->entityManager->getRepository($entityClassName);
        $this->key = $key;
    }

    public function getTtl(): ?int
    {
        $ttl = null;

        $keyValueStoreObj = $this->repository->findOneBy([self::FIELD_ID => $this->key]);
        if ($keyValueStoreObj instanceof DatabaseKeyValueStoreEntityInterface) {
            $ttl = $keyValueStoreObj->getTtl();
        }

        return $ttl;
    }

    public function getValue(): ?string
    {
        $value = null;

        $keyValueStoreObj = $this->repository->findOneBy([self::FIELD_ID => $this->key]);
        if ($keyValueStoreObj instanceof DatabaseKeyValueStoreEntityInterface) {
            $value = $keyValueStoreObj->getValue();
        }

        return $value;
    }

    public function setValue(?string $value, ?int $ttl = null): self
    {
        $keyValueStoreObj = $this->repository->findOneBy([self::FIELD_ID => $this->key]);
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
