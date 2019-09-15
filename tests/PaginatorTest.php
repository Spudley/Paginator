<?php
namespace Spudley\Paginator\Test;

use PHPUnit\Framework\TestCase;
use Spudley\Paginator\Paginator;
use Spudley\Paginator\PaginatorException;
use ArrayIterator;
use SplFileObject;

/**
 * @author: Simon Champion <simon@simonchampion.net>
 * @copyright Simon Champion, 2019
 * @version 0.1
 */
class PaginatorTest extends TestCase {
    /**
     * @dataProvider addElementsProvider
     */
    public function testAddElements(iterable $elementSets, int $expectedNumberOfElements)
    {
        $paginator = new Paginator();
        foreach ($elementSets as $elementSet) {
            $paginator->addElements($elementSet);
        }
        $numElems = $paginator->getNumberOfElements();
        $this->assertEquals($expectedNumberOfElements, $numElems);
    }

    public function addElementsProvider()
    {
        $array  = ['alpha', 'beta', 'delta', 'gamma'];
        $arrIt  = function() {
            return new ArrayIterator(['alpha', 'epsilon', 'beta', 'delta', 'gamma']);
        };
        $fileIt = function() {
            return new SplFileObject(__DIR__ . '/testDataFile.txt');
        };

        return [
            [[$array], 4],
            [[$arrIt()], 5],
            [[$array, $arrIt()], 9],
            [[$arrIt(), $fileIt(), $array], 37],
        ];
    }


    public function testUncachedPaginatorThrowsWhenReadToEndWithoutSettingPage()
    {
        $paginator = new Paginator(false);
        $paginator->addElements(['foo', 'bar']);
        $this->expectException(PaginatorException::class);
        $paginator->getNumberOfElements();
    }

    public function testPageNumberNullToStart()
    {
        $paginator = new Paginator();
        $pageNumber = $paginator->getPageNumber();
        $this->assertNull($pageNumber);
    }

    public function testPageNumber()
    {
        $paginator = new Paginator();
        $paginator->setPageNumber(5);
        $pageNumber = $paginator->getPageNumber();
        $this->assertEquals(5, $pageNumber);
    }

    public function testPageNumberOutOfBounds()
    {
        $paginator = new Paginator();
        $this->expectException(PaginatorException::class);
        $paginator->setPageNumber(0);
    }

    public function testPageSizeNullToStart()
    {
        $paginator = new Paginator();
        $pageSize = $paginator->getPageSize();
        $this->assertNull($pageSize);
    }

    public function testPageSize()
    {
        $paginator = new Paginator();
        $paginator->setPageSize(12);
        $pageSize = $paginator->getPageSize();
        $this->assertEquals(12, $pageSize);
    }

    public function testPageSizeOutOfBounds()
    {
        $paginator = new Paginator();
        $this->expectException(PaginatorException::class);
        $paginator->setPageSize(0);
    }

    public function testPageSizeCanChangeIfPaginatorIsCached()
    {
        $paginator = new Paginator(true);
        $paginator->setPageSize(5);
        $paginator->setPageSize(7);
        $this->assertEquals($paginator->getPageSize(), 7);
    }

    public function testPageSizeCantChangeIfPaginatorIsUncached()
    {
        $paginator = new Paginator(false);
        $paginator->setPageSize(5);
        $this->expectException(PaginatorException::class);
        $paginator->setPageSize(7);
    }

    public function testPageNumberCanChangeIfPaginatorIsCached()
    {
        $paginator = new Paginator(true);
        $paginator->setPageNumber(5);
        $paginator->setPageNumber(7);
        $this->assertEquals($paginator->getPageNumber(), 7);
    }

    public function testPageNumberCantChangeIfPaginatorIsUncached()
    {
        $paginator = new Paginator(false);
        $paginator->setPageNumber(5);
        $this->expectException(PaginatorException::class);
        $paginator->setPageNumber(7);
    }

    /**
     * @dataProvider getElementsOnPageProvider
     */
    public function testGetElementsOnPage($data, $perPage, $pageNum, $expectedOutput)
    {
        $paginator = new Paginator();
        $paginator->addElements($data);
        $paginator->setPageSize($perPage);
        $paginator->setPageNumber($pageNum);
        $output = $paginator->getElementsOnPage();
        $this->assertEquals($expectedOutput, $output);
    }

    public function getElementsOnPageProvider()
    {
        $array  = [
            'lima', 'golf', 'quebec', 'zulu', 'uniform',
            'romeo', 'kilo', 'alpha', 'x-ray', 'november',
            'whiskey', 'echo', 'mike', 'sierra', 'bravo',
            'delta', 'hotel', 'foxtrot', 'charlie', 'yankee',
            'india', 'juliet', 'tango', 'oscar', 'pappa',
            'victor'
        ];

        return [
            [$array, 5, 1, ['lima', 'golf', 'quebec', 'zulu', 'uniform']],
            [$array, 4, 3, ['x-ray', 'november', 'whiskey', 'echo']],
            [$array, 10, 3, ['india', 'juliet', 'tango', 'oscar', 'pappa', 'victor']],  //last page.
            [$array, 6, 6, []],     //beyond last page.
        ];
    }

    public function testGetNumberOfPagesThrowsIfPageSizeNotSet()
    {
        $paginator = new Paginator();
        $this->expectException(PaginatorException::class);
        $output = $paginator->getNumberOfPages();
    }


    /**
     * @dataProvider getNumberOfPagesProvider
     */
    public function testGetNumberOfPages($data, $perPage, $expectedOutput)
    {
        $paginator = new Paginator();
        $paginator->addElements($data);
        $paginator->setPageSize($perPage);
        $output = $paginator->getNumberOfPages();
        $this->assertEquals($output, $expectedOutput);
    }

    public function getNumberOfPagesProvider()
    {
        $array  = [
            'lima', 'golf', 'quebec', 'zulu', 'uniform',
            'romeo', 'kilo', 'alpha', 'x-ray', 'november',
            'whiskey', 'echo', 'mike', 'sierra', 'bravo',
            'delta', 'hotel', 'foxtrot', 'charlie', 'yankee',
            'india', 'juliet', 'tango', 'oscar', 'pappa',
            'victor'
        ];

        return [
            [$array, 5, 6],
            [$array, 4, 7],
            [$array, 10, 3],
        ];
    }

    /**
     * @dataProvider cachedPaginatorCanProvideMultiplePagesProvider
     */
    public function testCachedPaginatorCanProvideMultiplePages($data, $perPage, $firstPage, $secondPage, $firstExpected, $secondExpected)
    {
        $paginator = new Paginator(true);
        $paginator->addElements($data);
        $paginator->setPageSize($perPage);
        $paginator->setPageNumber($firstPage);
        $firstOutput = $paginator->getElementsOnPage();
        $this->assertEquals($firstOutput, $firstExpected);

        $paginator->setPageNumber($secondPage);
        $secondOutput = $paginator->getElementsOnPage();
        $this->assertEquals($secondOutput, $secondExpected);

    }

    public function cachedPaginatorCanProvideMultiplePagesProvider()
    {
        $array  = [
            'lima', 'golf', 'quebec', 'zulu', 'uniform',
            'romeo', 'kilo', 'alpha', 'x-ray', 'november',
            'whiskey', 'echo', 'mike', 'sierra', 'bravo',
            'delta', 'hotel', 'foxtrot', 'charlie', 'yankee',
            'india', 'juliet', 'tango', 'oscar', 'pappa',
            'victor'
        ];

        return [
            [$array, 5, 3, 5, ['whiskey', 'echo', 'mike', 'sierra', 'bravo'], ['india', 'juliet', 'tango', 'oscar', 'pappa']],
            [$array, 4, 4, 2, ['mike', 'sierra', 'bravo', 'delta'], ['uniform', 'romeo', 'kilo', 'alpha']],
            [$array, 10, 5, 1, [], ['lima', 'golf', 'quebec', 'zulu', 'uniform', 'romeo', 'kilo', 'alpha', 'x-ray', 'november']],
        ];
    }

    /**
     * @dataProvider cachedPaginatorWorksAfterChangingPageSizeProvider
     */
    public function testCachedPaginatorWorksAfterChangingPageSize($data, $perPage1, $perPage2, $pageNumber, $firstExpected, $secondExpected)
    {
        $paginator = new Paginator(true);
        $paginator->addElements($data);
        $paginator->setPageSize($perPage1);
        $paginator->setPageNumber($pageNumber);
        $firstOutput = $paginator->getElementsOnPage();
        $this->assertEquals($firstOutput, $firstExpected);

        $paginator->setPageSize($perPage2);
        $secondOutput = $paginator->getElementsOnPage();
        $this->assertEquals($secondOutput, $secondExpected);

    }

    public function cachedPaginatorWorksAfterChangingPageSizeProvider()
    {
        $array  = [
            'lima', 'golf', 'quebec', 'zulu', 'uniform',
            'romeo', 'kilo', 'alpha', 'x-ray', 'november',
            'whiskey', 'echo', 'mike', 'sierra', 'bravo',
            'delta', 'hotel', 'foxtrot', 'charlie', 'yankee',
            'india', 'juliet', 'tango', 'oscar', 'pappa',
            'victor'
        ];

        return [
            [$array, 5, 7, 2, ['romeo', 'kilo', 'alpha', 'x-ray', 'november'], ['alpha', 'x-ray', 'november', 'whiskey', 'echo', 'mike', 'sierra']],
            [$array, 3, 12, 6, ['delta', 'hotel', 'foxtrot'], []],
            [$array, 7, 5, 1, ['lima', 'golf', 'quebec', 'zulu', 'uniform','romeo', 'kilo'], ['lima', 'golf', 'quebec', 'zulu', 'uniform']],
        ];
    }
}

