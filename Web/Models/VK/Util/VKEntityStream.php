<?php

declare(strict_types=1);

namespace openvk\Web\Models\VK\Util;

/**
 * Ленивый поток VK-сущностей — аналог EntityStream.
 *
 * Вызов VK API происходит только при первом обращении к page(), size() или offsetLimit().
 * Результат кешируется, повторные вызовы используют закешированные данные.
 *
 * Использование:
 *   $stream = new VKEntityStream(
 *       fn($offset, $count) => VKAPIClient::i()->call("users.search", [...]),
 *       fn(array $data) => new VKUser($data)
 *   );
 *   $stream->size();                    // API call здесь
 *   foreach ($stream->page(1) as $u);   // из кеша
 */
class VKEntityStream implements \IteratorAggregate
{
    /** @var callable(int $offset, int $count): array{items: array, count: int}|null */
    private $loader;

    /** @var callable(array): object */
    private $factory;

    private ?array $cachedItems = null;
    private ?int   $cachedCount = null;

    /**
     * @param callable $loader   fn(int $offset, int $count) => ["items" => [...], "count" => int]
     * @param callable $factory  fn(array $rawData) => VKEntity
     */
    public function __construct(callable $loader, callable $factory)
    {
        $this->loader  = $loader;
        $this->factory = $factory;
    }

    /**
     * Загружает данные из VK API (единственный реальный запрос).
     */
    private function load(int $offset = 0, int $count = 10): void
    {
        if ($this->cachedItems !== null) {
            return;
        }

        $loader = $this->loader;
        $result = $loader($offset, $count);

        $this->cachedItems = $result["items"] ?? [];
        $this->cachedCount = $result["count"] ?? count($this->cachedItems);
    }

    /**
     * Принудительная перезагрузка (сбрасывает кеш).
     */
    public function reload(int $offset = 0, int $count = 10): void
    {
        $this->cachedItems = null;
        $this->cachedCount = null;
        $this->load($offset, $count);
    }

    /**
     * Возвращает итератор по всем элементам.
     */
    public function getIterator(): \Traversable
    {
        $this->load();

        foreach ($this->cachedItems ?? [] as $item) {
            $factory = $this->factory;
            yield $factory($item);
        }
    }

    /**
     * Возвращает страницу сущностей.
     *
     * @return \Traversable|array
     */
    public function page(int $page, ?int $perPage = null): \Traversable
    {
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $offset = $perPage * ($page - 1);

        return $this->offsetLimit($offset, $perPage);
    }

    /**
     * Возвращает сущности со смещением.
     *
     * @return \Traversable|array
     */
    public function offsetLimit(int $offset = 0, ?int $limit = null): \Traversable
    {
        $limit ??= OPENVK_DEFAULT_PER_PAGE;
        $this->load($offset, $limit);

        $slice = array_slice($this->cachedItems ?? [], $offset, $limit);
        $factory = $this->factory;

        $results = [];
        foreach ($slice as $item) {
            $results[] = $factory($item);
        }

        return new \ArrayIterator($results);
    }

    /**
     * Общее количество элементов.
     */
    public function size(): int
    {
        $this->load(0, 0);

        return $this->cachedCount ?? 0;
    }

    /**
     * Преобразует в массив.
     *
     * @return object[]
     */
    public function toArray(): array
    {
        return iterator_to_array($this->getIterator());
    }
}
