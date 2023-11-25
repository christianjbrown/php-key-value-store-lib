<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore\Tests;

use ChristianBrown\KeyValueStore\MemoryKeyValueStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MemoryKeyValueStore::class)]
final class MemoryKeyValueStoreTest extends TestCase
{
    public function test(): void
    {
        $store = new MemoryKeyValueStore();
        self::assertNull($store->getTtl());
        self::assertNull($store->getValue());
        self::assertSame($store, $store->setValue('test-value', 2));
        self::assertSame(2, $store->getTtl());
        self::assertSame('test-value', $store->getValue());
    }
}
