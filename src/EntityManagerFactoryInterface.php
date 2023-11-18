<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use Doctrine\ORM\EntityManagerInterface;

interface EntityManagerFactoryInterface
{
    public function getEntityManager(): EntityManagerInterface;
}
