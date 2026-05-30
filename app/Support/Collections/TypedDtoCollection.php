<?php

namespace App\Support\Collections;

use Illuminate\Support\Collection;

/**
 * @template TKey of array-key
 * @template TValue of object
 *
 * @extends Collection<TKey, TValue>
 */
abstract class TypedDtoCollection extends Collection
{
    /**
     * @return class-string<TValue>
     */
    abstract protected static function itemClass(): string;

    /**
     * @param  \Illuminate\Contracts\Support\Arrayable<TKey, mixed>|iterable<TKey, mixed>|null  $items
     */
    public function __construct($items = [])
    {
        parent::__construct($items);

        $this->items = array_map(
            fn (mixed $item): mixed => $this->castItem($item),
            $this->items,
        );
    }

    /**
     * @param  \Illuminate\Contracts\Support\Arrayable<TKey, mixed>|iterable<TKey, mixed>|null  $items
     * @return static
     */
    public static function from($items = []): static
    {
        return new static($items);
    }

    /**
     * @param  array<TKey, mixed>  $items
     * @return static
     */
    public static function fromArray(array $items): static
    {
        return new static($items);
    }

    /**
     * @return static
     */
    public function map(callable $callback): static
    {
        return new static(parent::map($callback)->all());
    }

    /**
     * @param  class-string  $class
     * @return Collection|\Illuminate\Support\Traits\EnumeratesValues
     */
    public function mapInto($class)
    {
        return $this->toBase()->mapInto($class);
    }

    protected function castItem(mixed $item): mixed
    {
        $itemClass = static::itemClass();

        if ($item instanceof $itemClass) {
            return $item;
        }

        return $itemClass::from($item);
    }
}
