# Key-Value Store

[![CI](https://github.com/christianjbrown/key-value-store-php/actions/workflows/ci.yml/badge.svg)](https://github.com/christianjbrown/key-value-store-php/actions/workflows/ci.yml)

A small, strongly-typed PHP library of interchangeable **key-value stores**. Every store hides
behind one tiny contract — `KeyValueStoreInterface` — so you can read, write, and update a single
`?string` value (with an optional `?int` TTL) without caring where it actually lives. It's built for
small pieces of state such as configuration flags, cursors, or OAuth refresh tokens.

Four implementations ship today:

- **Database** (`DatabaseKeyValueStore`) — persists via Doctrine ORM to any database it supports
  (MySQL/MariaDB, PostgreSQL, SQLite, SQL Server, …), keyed by a string id.
- **Google Secret Manager** (`GoogleSecretKeyValueStore`) — reads/writes a Secret Manager secret.
- **Google Firestore** (`FirestoreKeyValueStore`) — reads/writes a single Firestore document; serverless
  and connectionless, with TTL support via an `expiresAt` field.
- **In-memory** (`MemoryKeyValueStore`) — a per-process value, handy for tests and defaults.

Because they share one interface, calling code can accept a `KeyValueStoreInterface` and stay
oblivious to the backing store.



## :heavy_check_mark: Prerequisites

- [Git](https://git-scm.com/)
- [PHP](https://www.php.net/) 8.5 or higher (8.x)
- [Composer](https://getcomposer.org/)

:bulb: If you're on MacOS and have [Homebrew](https://brew.sh/), PHP and Composer will install with `brew install composer`.



## :building_construction: Installation

For your composer-enabled project:

```bash
composer require christianjbrown/key-value-store
```



## :computer: Usage

Every store exposes the same three methods:

```php
public function getTtl(): ?int;
public function getValue(): ?string;
public function setValue(?string $value, ?int $ttl = null): self;
```



### :floppy_disk: Database key-value store

Persists each key to a row in a database table through Doctrine ORM — so it works with any platform
Doctrine DBAL supports (MySQL/MariaDB, PostgreSQL, SQLite, SQL Server, …). First, define a concrete
entity by extending the provided mapped superclass and giving it a table:

```php
use ChristianBrown\KeyValueStore\AbstractDatabaseKeyValueStoreEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'key_value_store')]
class KeyValueStoreEntity extends AbstractDatabaseKeyValueStoreEntity
{
}
```

Then construct a store with your Doctrine `EntityManager`, the entity class name, and the key:

```php
use ChristianBrown\KeyValueStore\DatabaseKeyValueStore;

$store = new DatabaseKeyValueStore($entityManager, KeyValueStoreEntity::class, 'refresh-token');

$store->setValue('a-secret-token', 3600); // value + optional TTL (seconds)

$value = $store->getValue(); // 'a-secret-token', or null if the key has never been set
$ttl   = $store->getTtl();   // 3600, or null
```

Passing an entity class that does not extend `AbstractDatabaseKeyValueStoreEntity` (i.e. does not
implement `DatabaseKeyValueStoreEntityInterface`) throws `InvalidArgumentException`.



### :lock: Google Secret Manager key-value store

Reads and writes a [Google Secret Manager](https://cloud.google.com/secret-manager) secret. The
quickest way to build one is the static `create()` factory, which constructs a real client from the
`GOOGLE_APPLICATION_CREDENTIALS` environment variable:

```php
use ChristianBrown\KeyValueStore\GoogleSecretKeyValueStore;
use ChristianBrown\KeyValueStore\GoogleSecretKeyValueStoreExceptionInterface;

$store = GoogleSecretKeyValueStore::create('projects/my-project/secrets/my-secret');

try {
    $value = $store->getValue();      // the latest secret version's value, or null
    $store->setValue('new-value');    // adds a new secret version
} catch (GoogleSecretKeyValueStoreExceptionInterface $e) {
    // the secret could not be read or written
    print $e->getMessage();
}
```

You can also inject a pre-built `Google\Cloud\SecretManager\V1\SecretManagerServiceClient` directly
via the constructor (useful for tests):

```php
$store = new GoogleSecretKeyValueStore($client, 'projects/my-project/secrets/my-secret');
```

:warning: Secret Manager has no notion of a TTL, so `getTtl()` — and any `setValue()` call that
supplies a non-null `$ttl` — throws a `RuntimeException`.



### :fire: Google Firestore key-value store

Reads and writes a single [Google Firestore](https://cloud.google.com/firestore) document. It is
serverless and connectionless — no VPC connector or database connection to manage. The value and an
integer `expiresAt` unix timestamp are stored as two fields on the document. The static `create()`
factory resolves the document from a `FirestoreClient`, a collection name, and a document id:

> **Optional dependency.** `google/cloud-firestore` is not a hard requirement of this library (it
> pulls in `ext-grpc`), so it is only suggested — install it yourself if you use this store:
> `composer require google/cloud-firestore` (and enable `ext-grpc`). The other stores are unaffected.


```php
use ChristianBrown\KeyValueStore\FirestoreKeyValueStore;
use Google\Cloud\Firestore\FirestoreClient;

$firestoreClient = new FirestoreClient();

$store = FirestoreKeyValueStore::create($firestoreClient, 'kv', 'my-key');

$store->setValue('a-secret-token', 3600); // value + optional TTL (seconds)

$value = $store->getValue(); // 'a-secret-token', or null if unset or expired
$ttl   = $store->getTtl();   // remaining seconds, or null when no TTL was set
```

`getValue()` returns `null` when the document does not exist or its `expiresAt` has passed; `getTtl()`
returns the remaining seconds (`expiresAt - time()`), or `null` when no expiry is set. You can also
inject a pre-built `Google\Cloud\Firestore\DocumentReference` directly via the constructor (useful for
tests):

```php
$store = new FirestoreKeyValueStore($documentReference);
```



### :zap: In-memory key-value store

A per-process value that lives only for the current request. No configuration required:

```php
use ChristianBrown\KeyValueStore\MemoryKeyValueStore;

$store = new MemoryKeyValueStore();
$store->setValue('hello', 60);

$store->getValue(); // 'hello'
$store->getTtl();   // 60
```



## :rotating_light: Error handling

`GoogleSecretKeyValueStore` normalizes Secret Manager access failures into a single library exception
that implements `ChristianBrown\KeyValueStore\GoogleSecretKeyValueStoreExceptionInterface` (which
extends `Throwable`), so one `catch` covers both read and write failures:

```php
use ChristianBrown\KeyValueStore\GoogleSecretKeyValueStoreExceptionInterface;

try {
    $value = $store->getValue();
} catch (GoogleSecretKeyValueStoreExceptionInterface $e) {
    print $e->getMessage();
}
```

The database store throws `InvalidArgumentException` if constructed with an entity class that does not
implement `DatabaseKeyValueStoreEntityInterface`.



## :page_facing_up: License

Released under the [MIT License](LICENSE).
