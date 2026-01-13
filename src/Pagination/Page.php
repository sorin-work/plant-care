<?php

namespace App\Pagination;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * One page of results plus what the template needs for "newer / older" links.
 *
 * @template T
 */
final class Page
{
    /**
     * @param list<T> $items
     */
    private function __construct(
        public readonly array $items,
        public readonly int $number,
        public readonly int $perPage,
        public readonly int $total,
    ) {
    }

    /**
     * @return self<mixed>
     */
    public static function fromQuery(QueryBuilder $qb, int $requested, int $perPage): self
    {
        $paginator = new Paginator($qb);
        $total = \count($paginator);
        $number = self::clamp($requested, self::countPages($total, $perPage));

        $paginator->getQuery()
            ->setFirstResult(($number - 1) * $perPage)
            ->setMaxResults($perPage);

        return new self(array_values(iterator_to_array($paginator)), $number, $perPage, $total);
    }

    public static function countPages(int $total, int $perPage): int
    {
        return max(1, intdiv($total, $perPage));
    }

    public static function clamp(int $requested, int $pageCount): int
    {
        return min(max(1, $requested), $pageCount);
    }

    public function pageCount(): int
    {
        return self::countPages($this->total, $this->perPage);
    }

    public function hasPrevious(): bool
    {
        return $this->number > 1;
    }

    public function hasNext(): bool
    {
        return $this->number < $this->pageCount();
    }
}
