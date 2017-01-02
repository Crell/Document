<?php

declare(strict_types = 1);

namespace Crell\Document\GitStore;

/**
 * A pointer/tracking object for a specific branch on a repository.
  */
class Branch
{
    /**
     * The Git branch this object is tracking.
     *
     * @var string
     */
    protected $branch;

    /**
     * The repository this branch is tracking.
     *
     * @var Repository
     */
    protected $repository;

    /**
     * Constructs a new Branch.
     *
     * @param Repository $repository
     *   The repository this branch is tracking.
     * @param string $branch
     *   The branch that should be tracked. This value is immutable once the object is created.
     */
    public function __construct(Repository $repository, string $branch)
    {
        $this->repository = $repository;
        $this->branch = $branch;
    }

    /**
     * Returns the name of the branch being tracked.
     *
     * @return string
     */
    public function branch() : string
    {
        return $this->branch;
    }

    /**
     * Creates a new branch in the repository, using this branch as a starting point.
     *
     * @param string $name
     *   The branch to create.
     * @return Branch
     *   A new branch pointer bound to the newly created branch.
     */
    public function createBranch(string $name) : Branch
    {
        $this->repository->createBranch($this->branch, $name);

        $new = clone($this);
        $new->branch = $name;
        return $new;
    }

    /**
     * Writes a new commit on this branch.
     *
     * @param iterable $documents
     *   An iterable of documents to store.  They may be any JSON-ifiable value.
     * @param string $committer
     *   The committer for this commit. Must contain < and >, even if they don't wrap an email address.
     * @param string $message
     *   The commit message.
     * @return string
     *   The commit ID of the just-created commit.
     */
    public function commit($documents, string $committer, string $message)
    {
        return $this->repository->commit($documents, $committer, $message, $this->branch, $this->branch);
    }

    /**
     * Loads a single saved document by name.
     *
     * @param $name
     *   The name of the document to retrieve. This may be a path, but should NOT include a leading /.
     * @return array
     *   The deserialized stored value for the specified object name at a given commit.
     *
     * @throws \InvalidArgumentException
     *   Thrown if the requested document does not exist in that commit.
     */
    public function load($name): array
    {
        return $this->repository->load($name, $this->branch);
    }

    /**
     * Loads multiple saved documents by name.
     *
     * @param array $names
     *   An iterable of the document names to retrieve. Names may be paths, but should NOT include a leading /.
     * @return \Iterator
     *   An iterator of the results that were found. Missing documents will be omitted. Keys are the filename,
     *   values decoded to an array.
     */
    public function loadMultiple(array $names): \Iterator
    {
        return $this->repository->loadMultiple($names, $this->branch);
    }

    /**
     * Returns a list of all commit IDs in which this document was modified, newest first.
     *
     * @param string $name
     *   The document name for which we want a list of historical commits.
     * @return \Iterator
     *   An iterable of commit IDs.
     */
    public function history(string $name) : \Iterator
    {
        return $this->repository->history($name, $this->branch);
    }
}
