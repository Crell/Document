<?php

declare(strict_types = 1);

namespace Crell\Document\GitStore;


class Branch
{
    /**
     * @var string
     */
    protected $branch;

    /**
     * @var Repository
     */
    protected $repository;

    public function __construct(Repository $repository, string $branch)
    {
        $this->repository = $repository;
        $this->branch = $branch;
    }

    public function branch() : string {
        return $this->branch;
    }


    public function createBranch(string $name) : Branch
    {
        // @todo Make an actual new branch in the repository here.

        $this->repository->createBranch($this->branch, $name);

        $new = clone($this);
        $new->branch = $name;
        return $new;
    }

    public function commit(array $documents, string $committer, string $message)
    {
        $this->repository->commit($documents, $committer, $message, $this->branch);
    }

    public function load($name): array
    {
        return $this->repository->load($name, $this->branch);
    }

    public function loadMultiple(array $names): \Iterator
    {
        return $this->repository->loadMultiple($names, $this->branch);
    }
}
