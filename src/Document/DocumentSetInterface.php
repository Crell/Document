<?php

declare (strict_types = 1);

namespace Crell\Document\Document;

/**
 * Document Sets are collections of documents that may be acted on in tandem.
 *
 * Note: Most Document Sets will make use of iterators or Generators in some form,
 * so take care in your own code.
 *
 * Note: Implementations must also implement either \Iterator or \IteratorAggregate.
 */
interface DocumentSetInterface extends \ArrayAccess, \Countable
{

}

