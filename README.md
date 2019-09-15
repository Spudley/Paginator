# Paginator

### Version 0.1.0

A small PHP class intended to paginate one or more arrays or iterable objects.

## Version History

v0.1.0      2019-09-15

## Requirements

This class has been tested under PHP 7.2. It is not using any deprecated functionality, so it should work in later PHP versions but is using features from PHP 7.2, so will not work in earlier versions.

This class has no external dependencies.

## Functionality

This class is a paginator. In its most basic form, you give it an array of elements, tell it how many items there should be per page, and which page you want, and it will give you the elements for that page.

It does have a few more features than that, but that is the basic use-case.

So what else does it do?

* It can accept multiple arrays, or indeed any iterable object, which will be treated as a single stream of entries, in the order they were provided.

* It loads data from these iterables on-demand to avoid excessive memory usage when paginating.

* It can optionally either cache the data as it reads it (allowing you to retrieve multiple pages), or only store the data for the requested page (minimised memory usage). Default is cached mode.

* Since it supports any iterable, you can pass it a lot of things over and above a simple array. For example, an `SPLFileObject` is iterable, and you can thus paginate a file. Or give it a PDO object to allow you to paginate the records returned from a query.

## Testing

The class has a full suite of unit tests.

If you are testing your own code that uses the Paginator, you may want to use the `Pagination` interface to stub your mocks rather than the full `Paginator` class.

## Usage

```
$paginator = new Paginator();               // Default to cached mode. For uncached, pass false as first arg.
$paginator->addElements($data);             // Pass any array, iterator, generator, or other iterable.
$paginator->addElements($moreData);         // ...repeat as required
$paginator->setPageSize($perPage);          // Tell it the page size you want.
$paginator->setPageNumber($firstPage);      // ...and the page number. Note, pages start at number 1, not zero.

$output = $paginator->getElementsOnPage();  // Retrieve the elements in the page.
$totPages = $paginator->getNumberOfPages(); // Retrieve the number of pages.

//In cached mode, you can subsequently add more data, change the page number or page size.
//Uncached mode does not allow any of this once the page number and size have been set.
```

It is suggested to wrap the paginator calls in a `try` .. `catch` block. `Paginator` can throw a `PaginatorException` for a number of reasons, including trying to change the settings in an uncached paginator, and it is also possible that exceptions may be thrown by the iterable objects you have fed into the paginator.

## Todo

The following is a list of things I've thought would be good improvements for the class, but which I haven't had time to actually work on.

* I should probably extract CachedPaginator and/or UncachedPaginator classes as polymorphic subclasses of Paginator rather than having the caching flag in the constructor.

* I should implement an `advanceToNextPage()` feature which would increment the `pageNumber` so you can load the next page. Looping this would allow you to read through a set of data one page at a time. As with existing functionality, this would not work in an uncached paginator that had already been read past the next page. I would probably implement `backToPreviousPage()`, `goToLastPage()` at the same time. (`goToFirstPage()` is unnecessary as it would be the same as just setting the page number to 1, but may still be worth having for consistency and readability of the API. It doesn't do any of this currently because most PHP use-cases would instantiate the object from scratch each time you wanted to show a new page, but that changes if you're using something like Swoole.

* Along the same lines, I should implement a `getAllPages()` method, which would output all the data as an array of pages.

* Detect whether the iterable objects support rewind; this would allow it to support loading multiple pages on an uncached Paginator. Note that detecting this would likely involve attempting to rewind each object as it is added to the Paginator, and checking for an exception. This may produce unwanted side-effects, eg if the object has already been partially iterated to get it to start in the paginator at a specific position.

* Caching is currently just storing the data in an internal array. I should probably extend this to allow it to cache the data to an external storage such as memcache. This would obviously help performance in cases where you're reloading to move from page to page. I would need to be careful with this, as some iterators may contain data that doesn't lend itself to caching.

* Detect whether the iterable objects have a defined length or are open-ended (maybe check if it implements `Countable`; that wouldn't answer the question definitively, but may be sufficient). Currently, it is risky to ask the Paginator to supply the total number of pages, as it finds this by seeking to the end, which will obviously be a problem if you have given it an infinite iterator. It would be good to be able to know in advance whether this is the case and throw an exception if we try to seek to the end, rather than simply looping forever.

* Along the same lines, if an iterable does implement `Countable` then we could simply use `count()` to get its length, and thus wouldn't need to actually loop through reading them as we do currently. This would obviously be quicker, but only possible for some iterables.


## Copyright and License

This class was written by and copyright Simon Champion.

It is released under the General Public License version 3 (GPLv3); see COPYING.txt for full license text.

