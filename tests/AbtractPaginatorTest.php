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

use Ecommit\Paginator\AbstractPaginator;
use Ecommit\Paginator\Tests\Paginator\TestPaginator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * @phpstan-import-type PaginatorOptions from TestPaginator
 */
class AbtractPaginatorTest extends TestCase
{
    use BuildArrayIteratorTrait;

    /**
     * @dataProvider getTestBadPageOptionProvider
     */
    public function testBadPageOption(mixed $page, int $expectedPage): void
    {
        $options = $this->getDefaultOptions();
        $options['page'] = $page;
        $paginator = $this->createPaginator($options);

        $this->assertSame($expectedPage, $paginator->getPage());
        $this->assertFalse($paginator->pageExists());
    }

    /**
     * @return array<array{mixed, int}>
     */
    public static function getTestBadPageOptionProvider(): array
    {
        return [
            ['string', 1],
            [-1, 1],
            [100000, 11],
        ];
    }

    public function testDefaultPageOption(): void
    {
        $options = $this->getDefaultOptions();
        unset($options['page']);
        $paginator = $this->createPaginator($options);

        $this->assertSame(1, $paginator->getPage());
    }

    public function testBadFormatMaxPerPageOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"max_per_page"');

        $options = $this->getDefaultOptions();
        $options['max_per_page'] = 'string';
        $this->createPaginator($options); // @phpstan-ignore-line
    }

    public function testBadNumberMaxPerPageOption(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"max_per_page"');

        $options = $this->getDefaultOptions(1, -5, static::getDefaultIterator());
        $this->createPaginator($options);
    }

    public function testDefaultMaxPerPageOption(): void
    {
        $options = $this->getDefaultOptions();
        unset($options['max_per_page']);
        $paginator = $this->createPaginator($options);

        $this->assertSame(100, $paginator->getMaxPerPage());
    }

    public function testGetOptions(): void
    {
        $options = $this->getDefaultOptions();
        $paginator = $this->createPaginator($options);

        $this->assertSame($options, $paginator->getOptions());
    }

    public function testGetOptionsReadOnly(): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions());
        $options = $paginator->getOptions();
        $options['page'] = 8;

        $this->assertSame(1, $paginator->getOptions()['page']);
    }

    public function testGetOption(): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions());

        $this->assertSame(1, $paginator->getOption('page'));
    }

    public function testGetOptionNotFound(): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Option "bad" not found');

        $paginator->getOption('bad'); // @phpstan-ignore-line
    }

    public function testCount(): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions());
        $this->assertSame(52, $paginator->count());
        $this->assertCount(52, $paginator);
    }

    public function testCountWithoutData(): void
    {
        $options = $this->getDefaultOptions(1, 5, static::createIterator([]));
        $paginator = $this->createPaginator($options);
        $this->assertSame(0, $paginator->count());
        $this->assertCount(0, $paginator);
    }

    public function testCountIfPaginationNotInitialized(): void
    {
        $paginator = $this->createPaginatorMockWithoutConstructor();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The pagination must be initialized');

        $paginator->count();
    }

    public function testHaveToPaginate(): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions());
        $this->assertTrue($paginator->haveToPaginate());
    }

    public function testHaveToPaginateNotEnoughData(): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions(1, 500));

        $this->assertFalse($paginator->haveToPaginate());
    }

    public function testHaveToPaginateWithoutData(): void
    {
        $options = $this->getDefaultOptions(1, 5, static::createIterator([]));
        $paginator = $this->createPaginator($options);

        $this->assertFalse($paginator->haveToPaginate());
    }

    public function testHaveToPaginatefIfPaginationNotInitialized(): void
    {
        $paginator = $this->createPaginatorMockWithoutConstructor();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The pagination must be initialized');

        $paginator->haveToPaginate();
    }

    /**
     * @dataProvider getTestGetFirstIndiceProvider
     *
     * @param \ArrayIterator<int|string, mixed> $iterator
     */
    public function testGetFirstIndice(mixed $page, int $maxPerPage, \ArrayIterator $iterator, int $expectedResult): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions($page, $maxPerPage, $iterator));

        $this->assertSame($expectedResult, $paginator->getFirstIndice());
    }

    /**
     * @return array<array{mixed, int, \ArrayIterator<int|string, mixed>, int}>
     */
    public static function getTestGetFirstIndiceProvider(): array
    {
        return [
            [1, 5, static::getDefaultIterator(), 1],
            [3, 5, static::getDefaultIterator(), 11],
            [11, 5, static::getDefaultIterator(), 51],
            [1, 5, static::createIterator([]), 0], // No data
            ['page', 5, static::getDefaultIterator(), 1], // Bad page
            [1000, 5, static::getDefaultIterator(), 51], // Page too high
        ];
    }

    public function testGetFirstIndiceIfPaginationNotInitialized(): void
    {
        $paginator = $this->createPaginatorMockWithoutConstructor();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The pagination must be initialized');

        $paginator->getFirstIndice();
    }

    /**
     * @dataProvider getTestGetLastIndiceProvider
     *
     * @param \ArrayIterator<int|string, mixed> $iterator
     */
    public function testGetLastIndice(mixed $page, int $maxPerPage, \ArrayIterator $iterator, int $expectedResult): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions($page, $maxPerPage, $iterator));

        $this->assertSame($expectedResult, $paginator->getLastIndice());
    }

    /**
     * @return array<array{mixed, int, \ArrayIterator<int|string, mixed>, int}>
     */
    public static function getTestGetLastIndiceProvider(): array
    {
        return [
            [1, 5, static::getDefaultIterator(), 5],
            [3, 5, static::getDefaultIterator(), 15],
            [11, 5, static::getDefaultIterator(), 52],
            [1, 5, static::createIterator([]), 0], // No data
            ['page', 5, static::getDefaultIterator(), 5], // Bad page
            [1000, 5, static::getDefaultIterator(), 52], // Page too high
        ];
    }

    public function testGetLastIndiceIfPaginationNotInitialized(): void
    {
        $paginator = $this->createPaginatorMockWithoutConstructor();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The pagination must be initialized');

        $paginator->getLastIndice();
    }

    /**
     * @dataProvider getTestGetFirstPageProvider
     *
     * @param \ArrayIterator<int|string, mixed> $iterator
     */
    public function testGetFirstPage(mixed $page, int $maxPerPage, \ArrayIterator $iterator, int $expectedResult): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions($page, $maxPerPage, $iterator));

        $this->assertSame($expectedResult, $paginator->getFirstPage());
    }

    /**
     * @return array<array{mixed, int, \ArrayIterator<int|string, mixed>, int}>
     */
    public static function getTestGetFirstPageProvider(): array
    {
        return [
            [1, 5, static::getDefaultIterator(), 1],
            [3, 5, static::getDefaultIterator(), 1],
            [11, 5, static::getDefaultIterator(), 1],
            [1, 5, static::createIterator([]), 1], // No data
            ['page', 5, static::getDefaultIterator(), 1], // Bad page
            [1000, 5, static::getDefaultIterator(), 1], // Page too high
        ];
    }

    public function testGetFirstPageIfPaginationNotInitialized(): void
    {
        $paginator = $this->createPaginatorMockWithoutConstructor();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The pagination must be initialized');

        $paginator->getFirstPage();
    }

    /**
     * @dataProvider getTestGetPreviousPageProvider
     *
     * @param \ArrayIterator<int|string, mixed> $iterator
     */
    public function testGetPreviousPage(mixed $page, int $maxPerPage, \ArrayIterator $iterator, ?int $expectedResult): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions($page, $maxPerPage, $iterator));

        $this->assertSame($expectedResult, $paginator->getPreviousPage());
    }

    /**
     * @return array<array{mixed, int, \ArrayIterator<int|string, mixed>, ?int}>
     */
    public static function getTestGetPreviousPageProvider(): array
    {
        return [
            [1, 5, static::getDefaultIterator(), null],
            [3, 5, static::getDefaultIterator(), 2],
            [11, 5, static::getDefaultIterator(), 10],
            [1, 5, static::createIterator([]), null], // No data
            ['page', 5, static::getDefaultIterator(), null], // Bad page
            [1000, 5, static::getDefaultIterator(), 10], // Page too high
        ];
    }

    public function testGetPreviousPageIfPaginationNotInitialized(): void
    {
        $paginator = $this->createPaginatorMockWithoutConstructor();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The pagination must be initialized');

        $paginator->getPreviousPage();
    }

    /**
     * @dataProvider getTestGetPageProvider
     *
     * @param \ArrayIterator<int|string, mixed> $iterator
     */
    public function testGetPage(mixed $page, int $maxPerPage, \ArrayIterator $iterator, int $expectedResult): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions($page, $maxPerPage, $iterator));

        $this->assertSame($expectedResult, $paginator->getPage());
    }

    /**
     * @return array<array{mixed, int, \ArrayIterator<int|string, mixed>, int}>
     */
    public static function getTestGetPageProvider(): array
    {
        return [
            [1, 5, static::getDefaultIterator(), 1],
            [3, 5, static::getDefaultIterator(), 3],
            [11, 5, static::getDefaultIterator(), 11],
            [1, 5, static::createIterator([]), 1], // No data
            ['page', 5, static::getDefaultIterator(), 1], // Bad page
            [1000, 5, static::getDefaultIterator(), 11], // Page too high
        ];
    }

    /**
     * @dataProvider getTestPageExistsProdiver
     *
     * @param \ArrayIterator<int|string, mixed> $iterator
     */
    public function testPageExists(mixed $page, int $maxPerPage, \ArrayIterator $iterator, bool $expectedResult): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions($page, $maxPerPage, $iterator));

        $this->assertSame($expectedResult, $paginator->pageExists());
    }

    /**
     * @return array<array{mixed, int, \ArrayIterator<int|string, mixed>, bool}>
     */
    public static function getTestPageExistsProdiver(): array
    {
        return [
            [1, 5, static::getDefaultIterator(), true],
            [3, 5, static::getDefaultIterator(), true],
            [11, 5, static::getDefaultIterator(), true],
            [1, 5, static::createIterator([]), true], // No data
            ['page', 5, static::getDefaultIterator(), false], // Bad page
            [1000, 5, static::getDefaultIterator(), false], // Page too high
        ];
    }

    /**
     * @dataProvider getTestGetNextPageProvider
     *
     * @param \ArrayIterator<int|string, mixed> $iterator
     */
    public function testGetNextPage(mixed $page, int $maxPerPage, \ArrayIterator $iterator, ?int $expectedResult): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions($page, $maxPerPage, $iterator));

        $this->assertSame($expectedResult, $paginator->getNextPage());
    }

    /**
     * @return array<array{mixed, int, \ArrayIterator<int|string, mixed>, ?int}>
     */
    public static function getTestGetNextPageProvider(): array
    {
        return [
            [1, 5, static::getDefaultIterator(), 2],
            [3, 5, static::getDefaultIterator(), 4],
            [11, 5, static::getDefaultIterator(), null],
            [1, 5, static::createIterator([]), null], // No data
            ['page', 5, static::getDefaultIterator(), 2], // Bad page
            [1000, 5, static::getDefaultIterator(), null], // Page too high
        ];
    }

    public function testGetNextPageIfPaginationNotInitialized(): void
    {
        $paginator = $this->createPaginatorMockWithoutConstructor();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The pagination must be initialized');

        $paginator->getNextPage();
    }

    /**
     * @dataProvider getTestGetLastPageProvider
     *
     * @param \ArrayIterator<int|string, mixed> $iterator
     */
    public function testGetLastPage(mixed $page, int $maxPerPage, \ArrayIterator $iterator, int $expectedResult): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions($page, $maxPerPage, $iterator));

        $this->assertSame($expectedResult, $paginator->getLastPage());
    }

    /**
     * @return array<array{mixed, int, \ArrayIterator<int|string, mixed>, int}>
     */
    public static function getTestGetLastPageProvider(): array
    {
        return [
            [1, 5, static::getDefaultIterator(), 11],
            [3, 5, static::getDefaultIterator(), 11],
            [11, 5, static::getDefaultIterator(), 11],
            [1, 5, static::createIterator([]), 1], // No data
            ['page', 5, static::getDefaultIterator(), 11], // Bad page
            [1000, 5, static::getDefaultIterator(), 11], // Page too high
        ];
    }

    public function testGetLastPageIfPaginationNotInitialized(): void
    {
        $paginator = $this->createPaginatorMockWithoutConstructor();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The pagination must be initialized');

        $paginator->getLastPage();
    }

    /**
     * @dataProvider getTestIsFirstPageProvider
     *
     * @param \ArrayIterator<int|string, mixed> $iterator
     */
    public function testIsFirstPage(mixed $page, int $maxPerPage, \ArrayIterator $iterator, bool $expectedResult): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions($page, $maxPerPage, $iterator));

        $this->assertSame($expectedResult, $paginator->isFirstPage());
    }

    /**
     * @return array<array{mixed, int, \ArrayIterator<int|string, mixed>, bool}>
     */
    public static function getTestIsFirstPageProvider(): array
    {
        return [
            [1, 5, static::getDefaultIterator(), true],
            [3, 5, static::getDefaultIterator(), false],
            [11, 5, static::getDefaultIterator(), false],
            [1, 5, static::createIterator([]), true], // No data
            ['page', 5, static::getDefaultIterator(), true], // Bad page
            [1000, 5, static::getDefaultIterator(), false], // Page too high
        ];
    }

    public function testIsFirstPageIfPaginationNotInitialized(): void
    {
        $paginator = $this->createPaginatorMockWithoutConstructor();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The pagination must be initialized');

        $paginator->isFirstPage();
    }

    /**
     * @dataProvider getTestIsLastPageProvider
     *
     * @param \ArrayIterator<int|string, mixed> $iterator
     */
    public function testIsLastPage(mixed $page, int $maxPerPage, \ArrayIterator $iterator, bool $expectedResult): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions($page, $maxPerPage, $iterator));

        $this->assertSame($expectedResult, $paginator->isLastPage());
    }

    /**
     * @return array<array{mixed, int, \ArrayIterator<int|string, mixed>, bool}>
     */
    public static function getTestIsLastPageProvider(): array
    {
        return [
            [1, 5, static::getDefaultIterator(), false],
            [3, 5, static::getDefaultIterator(), false],
            [11, 5, static::getDefaultIterator(), true],
            [1, 5, static::createIterator([]), true], // No data
            ['page', 5, static::getDefaultIterator(), false], // Bad page
            [1000, 5, static::getDefaultIterator(), true], // Page too high
        ];
    }

    public function testIsLastPageIfPaginationNotInitialized(): void
    {
        $paginator = $this->createPaginatorMockWithoutConstructor();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('The pagination must be initialized');

        $paginator->isLastPage();
    }

    public function testGetMaxPerPage(): void
    {
        $options = $this->getDefaultOptions(1, 88, static::getDefaultIterator());
        $paginator = $this->createPaginator($options);

        $this->assertSame(88, $paginator->getMaxPerPage());
    }

    /**
     * @dataProvider getTestGetIteratorProvider
     *
     * @param \ArrayIterator<int|string, mixed> $iterator
     */
    public function testGetIterator(mixed $page, int $maxPerPage, \ArrayIterator $iterator): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions($page, $maxPerPage, $iterator));

        $this->assertSame($iterator, $paginator->getIterator());
    }

    /**
     * @return array<array{mixed, int, \ArrayIterator<int|string, mixed>}>
     */
    public static function getTestGetIteratorProvider(): array
    {
        return [
            [1, 5, static::getDefaultIterator()],
            [3, 5, static::getDefaultIterator()],
            [11, 5, static::getDefaultIterator()],
            [1, 5, static::createIterator([])], // No data
            ['page', 5, static::getDefaultIterator()], // Bad page
            [1000, 5, static::getDefaultIterator()], // Page too high
        ];
    }

    /**
     * @dataProvider getTestGetIteratorProvider
     *
     * @param \ArrayIterator<int|string, mixed> $iterator
     */
    public function testIterate(mixed $page, int $maxPerPage, \ArrayIterator $iterator): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions($page, $maxPerPage, $iterator));
        $expectedCount = \count($iterator);

        $count = 0;
        foreach ($paginator as $result) {
            ++$count;
        }

        $this->assertSame($expectedCount, $count);
    }

    public function testIsInitializedTrue(): void
    {
        $paginator = $this->createPaginator($this->getDefaultOptions());

        $this->assertTrue($paginator->isInitialized());
    }

    /**
     * @param \ArrayIterator<int|string, mixed> $iterator
     *
     * @return PaginatorOptions
     */
    protected function getDefaultOptions(mixed $page = 1, int $perPage = 5, ?\ArrayIterator $iterator = null): array
    {
        if (null === $iterator) {
            $iterator = static::getDefaultIterator();
        }

        return [
            'page' => $page,
            'max_per_page' => $perPage,
            'iterator' => $iterator,
        ];
    }

    /**
     * @param PaginatorOptions $options
     */
    protected function createPaginator(array $options): TestPaginator
    {
        return new TestPaginator($options);
    }

    /**
     * @return AbstractPaginator<mixed, mixed, array<string ,mixed>, array<string ,mixed>>
     */
    protected function createPaginatorMockWithoutConstructor(): AbstractPaginator
    {
        return $this->getMockBuilder(AbstractPaginator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['buildCount', 'buildIterator'])
            ->getMock();
    }
}
