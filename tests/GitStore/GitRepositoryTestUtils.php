<?php

declare(strict_types = 1);

namespace Crell\Document\Test\GitStore;

use Crell\Document\GitStore\Repository;

trait GitRepositoryTestUtils
{
    protected function getRepository(bool $debug = false) : Repository
    {
        $path = dirname(realpath(__FILE__)) . '/repository.git';

        $this->initRepoDirectory($path);

        $repository = new Repository($path, $debug);

        $repository->init();

        return $repository;
    }

    protected function deleteDirectoryContents(string $path)
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
    }

    /**
     * Delete everything in the specified directory so that it can be
     */
    protected function initRepoDirectory(string $path)
    {
        // Make the directory for the git repository to live in.
        if (!@mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \Exception('Bad race condition initializing the Git path.');
        }

        // Make sure the directory is empty so we can reinitalize Git.
        $this->deleteDirectoryContents($path);
    }

}
