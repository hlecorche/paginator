<?php

declare(strict_types=1);

/*
 * This file is part of the ecommit/paginator package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\Paginator;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @phpstan-type PaginatorOptions array{
 *      page?: mixed,
 *      max_per_page?: int<1, max>,
 *      data: array<array-key, mixed>|\ArrayIterator<array-key, mixed>,
 *      count?: int<0, max>|null
 * }
 * @phpstan-type PaginatorResolvedOptions array{
 *      page: int<0, max>,
 *      max_per_page: int<1, max>,
 *      data: array<array-key, mixed>,
 *      count: int<0, max>|null
 * }
 *
 * @template-extends AbstractPaginator<mixed, mixed, PaginatorOptions, PaginatorResolvedOptions>
 */
class ArrayPaginator extends AbstractPaginator
{
    protected function buildCount(): int
    {
        return (null === $this->getOption('count')) ? \count($this->getOption('data')) : $this->getOption('count');
    }

    protected function buildIterator(): \Traversable
    {
        if (null === $this->getOption('count')) {
            $offset = 0;
            $limit = 0;
            if ($this->count() > 0) {
                $offset = ($this->getPage() - 1) * $this->getMaxPerPage();
                $limit = $this->getMaxPerPage();
            }
            $partialData = \array_slice($this->getOption('data'), $offset, $limit);

            return new \ArrayIterator($partialData);
        }

        return new \ArrayIterator($this->getOption('data'));
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('data');
        $resolver->setAllowedTypes('data', ['array', \ArrayIterator::class]);
        $resolver->setNormalizer('data', static function (Options $options, array|\ArrayIterator $value) {
            if ($value instanceof \ArrayIterator) {
                return $value->getArrayCopy();
            }

            return $value;
        });

        $resolver->setDefault('count', null);
        $resolver->setAllowedTypes('count', ['int', 'null']);
        $resolver->setAllowedValues('count', static fn (?int $value) => null === $value || $value >= 0);
    }
}
