<?php
namespace spudley\Paginator;

/**
 * @author: Simon Champion <simon@simonchampion.net>
 * @copyright Simon Champion, 2019
 * @version 0.1
 */
interface Pagination
{
    public function addElements(Iterable $newElements): void;
    public function setPageNumber(int $page): void;
    public function getPageNumber(): ?int;
    public function setPageSize(int $perPage): void;
    public function getPageSize(): ?int;

    public function getElementsOnPage(): Iterable;

    public function getNumberOfPages(): ?int;
    public function getNumberOfElements(): ?int;
}

