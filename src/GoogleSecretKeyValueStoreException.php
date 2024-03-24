<?php

declare(strict_types=1);

namespace ChristianBrown\KeyValueStore;

use RuntimeException;

final class GoogleSecretKeyValueStoreException extends RuntimeException implements GoogleSecretKeyValueStoreExceptionInterface
{
}
