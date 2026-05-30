<?php

namespace App\Collections\Video;

use App\Dto\Video\TranscriptSegmentDto;
use ArrayAccess;
use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use JsonSerializable;
use LogicException;
use Traversable;

/**
 * @implements ArrayAccess<int, TranscriptSegmentDto>
 * @implements IteratorAggregate<int, TranscriptSegmentDto>
 */
readonly class TranscriptSegmentCollection implements Arrayable, ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param  array<int, TranscriptSegmentDto>  $items
     */
    public function __construct(
        private array $items = [],
    ) {}

    /**
     * @param  array<int, mixed>|null  $segments
     */
    public static function fromArray(?array $segments, ?string $fallbackText = null, ?float $fallbackDuration = null): self
    {
        $mapped = [];

        foreach ($segments ?? [] as $segment) {
            if ($segment instanceof TranscriptSegmentDto) {
                if (trim($segment->text) !== '') {
                    $mapped[] = $segment;
                }

                continue;
            }

            if (! is_array($segment)) {
                continue;
            }

            $dto = TranscriptSegmentDto::from($segment);

            if ($dto->text === '') {
                continue;
            }

            $mapped[] = $dto;
        }

        if ($mapped === [] && is_string($fallbackText) && trim($fallbackText) !== '') {
            $mapped[] = TranscriptSegmentDto::from([
                'startSeconds' => 0.0,
                'endSeconds' => max(1.0, $fallbackDuration ?? 1.0),
                'text' => trim($fallbackText),
            ]);
        }

        return new self($mapped);
    }

    /**
     * @return array<int, TranscriptSegmentDto>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function first(): ?TranscriptSegmentDto
    {
        return $this->items[0] ?? null;
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): ?TranscriptSegmentDto
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('Transcript segment collection is immutable.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('Transcript segment collection is immutable.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (TranscriptSegmentDto $segment): array => $segment->toArray(),
            $this->items,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
