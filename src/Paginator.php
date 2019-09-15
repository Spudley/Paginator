<?php
namespace Spudley\Paginator;

use ArrayIterator;
use AppendIterator;

/**
 * @author: Simon Champion <simon@simonchampion.net>
 * @copyright Simon Champion, 2019
 * @version 0.1
 */
class Paginator implements Pagination {
    private $elementSets = [];
    private $combinedIterables = null;
    private $dataCache = [];
    private $cacheAllElements = false;
    private $perPage = null;
    private $pageNumber = null;
    private $position = 0;
    private $pageStartPos = null;
    private $pageEndPos = null;

    public function __construct(bool $cacheAllElements = true)
    {
        $this->cacheAllElements = $cacheAllElements;
    }

    public function addElements(Iterable $newElements): void
    {
        if (is_array($newElements)) {
            $newElements = new ArrayIterator($newElements);
        }
        $this->elementSets[] = $newElements;
        $this->combinedIterables = $this->combineIterables();
    }

    public function setPageNumber(int $page): void
    {
        if ($page < 1) {
            throw new PaginatorException('Page number must be a positive integer.');
        }
        if ($this->pageNumber && !$this->cacheAllElements) {
            throw new PaginatorException('Cannot change page number once set, when paginator is uncached.');
        }

        $this->pageNumber = $page;

        if ($this->perPage && $this->pageNumber) {
            $this->calculatePageBoundaries();
        }
    }

    public function getPageNumber(): ?int
    {
        return $this->pageNumber;
    }

    public function setPageSize(int $perPage): void
    {
        if ($perPage < 1) {
            throw new PaginatorException('Page size must be a positive integer.');
        }
        if ($this->perPage && !$this->cacheAllElements) {
            throw new PaginatorException('Cannot change page size once set, when paginator is uncached.');
        }

        $this->perPage = $perPage;

        if ($this->perPage && $this->pageNumber) {
            $this->calculatePageBoundaries();
        }
    }

    public function getPageSize(): ?int
    {
        return $this->perPage;
    }

    public function getElementsOnPage(): Iterable
    {
        $this->readToEndOfRequestedPage();

        $output = [];
        for ($pos = $this->pageStartPos; $pos <= $this->pageEndPos; $pos++) {
            if (isset($this->dataCache[$pos])) {
                $output[] = $this->dataCache[$pos];
            }
        }
        return $output;
    }

    public function getNumberOfPages(): ?int
    {
        if (!isset($this->perPage)) {
            throw new PaginatorException('Cannot query number of pages until page size is set.');
        }

        $itemCount = $this->getNumberOfElements();
        return ceil($itemCount / $this->perPage);
    }

    public function getNumberOfElements(): ?int
    {
        $this->readAllElements();
        return $this->position;
    }

    private function readToEndOfRequestedPage(): void
    {
        while ($this->combinedIterables->valid() && !$this->beyondRequestedPage()) {
            $this->readNextElement();
        }
    }

    private function readAllElements(): void
    {
        while ($this->combinedIterables->valid()) {
            $this->readNextElement();
        }
    }

    private function readNextElement()
    {
        if (!$this->cacheAllElements && (!isset($this->perPage) || !isset($this->pageNumber))) {
            throw new PaginatorException('For uncached paginators, you must set the page size and page number before reading the paginator.');
        }

        $element = $this->combinedIterables->current();
        $this->combinedIterables->next();
        if (!isset($element)) {
            return;
        }
        $this->position++;

        if ($this->cacheAllElements || $this->onRequestedPage()) {
            $this->dataCache[$this->position] = $element;
        }
    }

    private function calculatePageBoundaries(): void
    {
        $this->pageEndPos = $this->perPage * $this->pageNumber;
        $this->pageStartPos = $this->pageEndPos - $this->perPage + 1;
    }

    private function onRequestedPage(): bool
    {
        return ($this->position >= $this->pageStartPos && $this->position <= $this->pageEndPos);
    }

    private function beyondRequestedPage(): bool
    {
        return ($this->position > $this->pageEndPos);
    }

    private function combineIterables()
    {
        foreach ($this->elementSets as $elementSet) {
            foreach ($elementSet as $element) {
                yield $element;
            }
        }
    }
}

