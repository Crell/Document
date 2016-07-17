<?php

declare (strict_types = 1);

namespace Crell\Document\Document;


/**
 * A Document set that lazily pulls data out of the generator result.
 *
 * Useful only for large datasets, for some definition of large.
 *
 * Incomplete. Finish later.
 */
class LazyDocumentSet implements DocumentSetInterface, \Iterator
{
    use LazyIteratorTrait;
}
