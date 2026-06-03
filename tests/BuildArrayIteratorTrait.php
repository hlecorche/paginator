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

namespace Ecommit\Paginator\Tests;

trait BuildArrayIteratorTrait
{
    /** @var \ArrayIterator<int|string, mixed>|null */
    protected static ?\ArrayIterator $defaultIterator = null;

    /**
     * @param array<mixed> $data
     *
     * @return \ArrayIterator<int|string, mixed>
     */
    protected static function createIterator(array $data): \ArrayIterator
    {
        return new \ArrayIterator($data);
    }

    /**
     * @return \ArrayIterator<int|string, mixed>
     */
    protected static function getDefaultIterator(): \ArrayIterator
    {
        if (null === static::$defaultIterator) {
            static::$defaultIterator = static::createIterator(range(0, 51));
        }

        return static::$defaultIterator;
    }
}
