<?php

declare(strict_types = 1);

namespace Crell\Document\Collection;
use Crell\Document\Document\MutableDocumentInterface;
use Traversable;

/**
 * This class is a command object that represents a commit to be made.
 */
class Commit implements \IteratorAggregate, \Countable
{
    /**
     * The commit message for this commit.
     *
     * @var string
     */
    protected $message;

    /**
     * The author of this commit.
     *
     * Generally this is a name and email address, but that is not strictly required.
     *
     * @var string
     */
    protected $author;

    /**
     * An array of revisions to be committed.
     *
     * @var MutableDocumentInterface[]
     */
    protected $revisions = [];

    /**
     * Constructs a new Commit object.
     *
     * @param string $message
     *   The commit message for this commit.
     * @param string $author
     *   The author of this commit.
     */
    public function __construct(string $message = 'No message', string $author = 'Anonymous')
    {
        $this->message = $message;
        $this->author = $author;
    }

    /**
     * Returns an iterator over the current set of revisions in this commit.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->revisions);
    }

    /**
     * Returns the number of revisions in this commit.
     *
     * @return int
     *   The number of revisions in this commit.
     */
    public function count()
    {
        return count($this->revisions);
    }

    /**
     * Returns a new instance of this commit object with the specified message.
     *
     * @param string $message
     *   The commit message to set.
     * @return Commit
     */
    public function withMessage(string $message) : Commit
    {
        $that = clone $this;
        $that->message = $message;
        return $that;
    }

    /**
     * Returns a new instance of this commit object with the specified author.
     *
     * @param string $author
     *   The author string to set.
     * @return Commit
     */
    public function withAuthor(string $author) : Commit
    {
        $that = clone $this;
        $that->author = $author;
        return $that;
    }

    /**
     * Returns a new instance of this commit object with the specified revision enqueued for committing.
     *
     * @param MutableDocumentInterface $document
     *   The document revision to add to the commit.
     * @return static
     */
    public function withRevision(MutableDocumentInterface $document) : Commit
    {
        $that = clone $this;

        $that->revisions[] = $document;

        return $that;
    }
}
