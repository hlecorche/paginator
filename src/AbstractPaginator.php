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
 * @template TKey
 *
 * @template-covariant TValue
 *
 * @template TOptions of array<string ,mixed>
 * @template TResolvedOptions of array<string ,mixed>
 *
 * @template-implements PaginatorInterface<TKey, TValue, TResolvedOptions>
 */
abstract class AbstractPaginator implements PaginatorInterface
{
    /** @var TResolvedOptions */
    private array $options;

    /** @var \Traversable<TKey, TValue> */
    private \Traversable $iterator;
    /** @var int<0, max> */
    private int $count;
    private int $lastPage;

    private bool $pageExists = true;
    private bool $paginationIsInitialized = false;
    private bool $iteratorIsInitialized = false;

    /**
     * @param TOptions $options
     */
    final public function __construct(array $options = [])
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'page' => 1,
            'max_per_page' => 100,
        ]);
        $resolver->setNormalizer('page', function (Options $options, mixed $page): int {
            if (null === $page || !\is_scalar($page) || !preg_match('/^\d+$/', (string) $page)) {
                $page = 1;
                $this->pageExists = false;
            }
            $page = (int) $page;

            if ($page <= 0) {
                $page = 1;
                $this->pageExists = false;
            }

            return $page;
        });
        $resolver->setAllowedTypes('max_per_page', 'int');
        $resolver->setAllowedValues('max_per_page', static fn (int $value) => $value > 0);
        $this->configureOptions($resolver);
        /** @var TResolvedOptions $resolvedOptions */
        $resolvedOptions = $resolver->resolve($options);
        $this->options = $resolvedOptions;

        $this->buildPagination();
        $this->iterator = $this->buildIterator();
        $this->iteratorIsInitialized = true;

        return $this;
    }

    /**
     * @return int<0, max>
     */
    abstract protected function buildCount(): int;

    /**
     * @return \Traversable<TKey, TValue>
     */
    abstract protected function buildIterator(): \Traversable;

    private function buildPagination(): void
    {
        $this->count = $this->buildCount();

        $lastPage = 1;
        if ($this->count > 0) {
            $lastPage = (int) ceil($this->count / $this->getMaxPerPage());
        }

        if ($this->getPage() > $lastPage) {
            $this->options['page'] = $lastPage;
            $this->pageExists = false;
        }

        $this->lastPage = $lastPage;
        $this->paginationIsInitialized = true;
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
    }

    final public function getOptions(): array
    {
        return $this->options;
    }

    final public function getOption(string $option): mixed
    {
        if (\array_key_exists($option, $this->options)) {
            return $this->options[$option];
        }

        throw new \Exception(\sprintf('Option "%s" not found', $option));
    }

    /**
     * @return int<0, max>
     */
    public function count(): int
    {
        $this->testIfPaginationIsInitialized();

        return $this->count;
    }

    public function haveToPaginate(): bool
    {
        $this->testIfPaginationIsInitialized();

        return $this->count() > $this->getMaxPerPage();
    }

    public function getFirstIndice(): int
    {
        $this->testIfPaginationIsInitialized();

        if (0 === $this->count()) {
            return 0;
        }

        return ($this->getPage() - 1) * $this->getMaxPerPage() + 1;
    }

    public function getLastIndice(): int
    {
        $this->testIfPaginationIsInitialized();

        if ($this->getPage() * $this->getMaxPerPage() >= $this->count()) {
            return $this->count();
        }

        return $this->getPage() * $this->getMaxPerPage();
    }

    public function getFirstPage(): int
    {
        $this->testIfPaginationIsInitialized();

        return 1;
    }

    public function getPreviousPage(): ?int
    {
        $this->testIfPaginationIsInitialized();

        if ($this->isFirstPage()) {
            return null;
        }

        return $this->getPage() - 1;
    }

    public function getPage(): int
    {
        return $this->getOption('page'); // @phpstan-ignore-line
    }

    public function pageExists(): bool
    {
        return $this->pageExists;
    }

    public function getNextPage(): ?int
    {
        $this->testIfPaginationIsInitialized();

        if ($this->getPage() >= $this->getLastPage()) {
            return null;
        }

        return $this->getPage() + 1;
    }

    public function getLastPage(): int
    {
        $this->testIfPaginationIsInitialized();

        return $this->lastPage;
    }

    public function isFirstPage(): bool
    {
        $this->testIfPaginationIsInitialized();

        return 1 === $this->getPage();
    }

    public function isLastPage(): bool
    {
        $this->testIfPaginationIsInitialized();

        return $this->getPage() === $this->getLastPage();
    }

    public function getMaxPerPage(): int
    {
        return $this->getOption('max_per_page'); // @phpstan-ignore-line
    }

    public function getIterator(): \Traversable
    {
        if (!$this->iteratorIsInitialized) {
            throw new \Exception('The iterator must be initialized');
        }

        return $this->iterator;
    }

    public function isInitialized(): bool
    {
        return $this->paginationIsInitialized && $this->iteratorIsInitialized;
    }

    protected function testIfPaginationIsInitialized(): void
    {
        if (!$this->paginationIsInitialized) {
            throw new \Exception('The pagination must be initialized');
        }
    }
}
