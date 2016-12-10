<?php

declare (strict_types = 1);

namespace Crell\Document\Driver\Git;

use Crell\Document\Collection\CollectionInterface;
use Crell\Document\Collection\DocumentRecordNotFoundException;
use Crell\Document\Document\MutableDocumentInterface;
use Crell\Document\Driver\CollectionDriverInterface;

/**
 * A Git-based storage backend.
 *
 * @todo This is all sorts of Racy.  It needs a lockfile.
 *
 * Notes: This needs to use git plumbing commands.  Based on a suggestion
 * from thiago in #Git on Freenode, writing should rely primarily on
 * git fast-import; it was written for importers but it's the fastest
 * option, probably.  Use popen() to write to it, if possible, but
 * that may be a problem in non-CLI SAPIs.  Needs investigation.
 *
 * Reading will need to use normal git porcelin commands.
 *
 */
class GitCollectionDriver implements CollectionDriverInterface
{

    /**
     * An absolute path to the bare git repository.
     *
     * @var string
     */
    protected $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeSchema(CollectionInterface $collection)
    {
        // Kill the existing directory if it exists.
//        if (file_exists($this->path)) {
//            $this->recursiveRemoveDirectory($this->path);
//        }

        // Make the directory for the git repository to live in.
        if (!@mkdir($this->path, 0755, true) && !is_dir($this->path)) {
            throw new \Exception('Bad race condition initializing the Git path.');
        }

        // Make sure the directory is empty so we can reinitalize Git.
        $this->deleteDirectoryContents($this->path);

        chdir($this->path);

        exec('git init --bare');

        // @todo Add an initial commit here, and return it somehow, so that the collection
        // Can know what commit it's at. Erf.
    }

    /**
     * {@inheritdoc}
     */
    public function loadLatestRevisionData(CollectionInterface $collection, string $uuid): array
    {
        // TODO: Implement loadLatestRevisionData() method.
    }

    /**
     * {@inheritdoc}
     */
    public function loadDefaultRevisionData(CollectionInterface $collection, string $uuid, bool $includeArchived = false): array
    {
        // TODO: Implement loadDefaultRevisionData() method.
    }

    /**
     * {@inheritdoc}
     */
    public function loadRevisionData(CollectionInterface $collection, string $uuid, string $revision): array
    {
        // TODO: Implement loadRevisionData() method.
    }

    /**
     * {@inheritdoc}
     */
    public function loadMultipleDefaultRevisionData(CollectionInterface $collection, array $uuids, bool $includeArchived = false): \Iterator
    {
        // TODO: Implement loadMultipleDefaultRevisionData() method.
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultRevision(CollectionInterface $collection, string $uuid, string $language, string $revision)
    {
        // TODO: Implement setDefaultRevision() method.
    }

    /**
     * {@inheritdoc}
     */
    public function persist(CollectionInterface $collection, array $documents, bool $setDefault)
    {
        $branch = $collection->branch();
        $parent = $collection->commit();

        chdir($this->path);

        try {
            // Open a new process to the git fast-import tool.
            $git = popen('git fast-import --date-format=raw', 'w');

            $timestamp = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('U');

            $commit_message = 'Commit message here';
            $commit_message_bytes = strlen($commit_message);

            // Add the header material that tells it what commit we're creating.
            fwrite($git, "commit refs/heads/{$branch}\n");
            fwrite($git, "committer Test User <test> {$timestamp} +0000\n");
            fwrite($git, "data {$commit_message_bytes}\n");
            fwrite($git, "{$commit_message}\n");

            fwrite($git, "from {$parent}\n");

            /** @var MutableDocumentInterface $document */
            foreach ($documents as $document) {
                $data = json_encode($document, true);

                // Even if there are UTF-8 characters in $data, we want its bytesize, not
                // character count. That makes strlen() correct in this case.
                $bytes = strlen($data);

                $filename = $document->uuid();
                fwrite($git, "'M' 100644 'inline' {$filename}\n");
                fwrite($git, "data {$bytes}\n");
                fwrite($git, "$data\n");
            }
        }
        finally {
            // Always clean up after yourself.
            pclose($git);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setArchived(CollectionInterface $collection, string $revision)
    {
        // TODO: Implement setArchived() method.
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
     * Recursively deletes a directory and its contents.
     *
     * Courtesy of the PHP manual:
     *
     * @see http://php.net/manual/en/function.rmdir.php#116585
     *
     * @param string $path
     *   The absolute path to delete.
     * @return bool
     */
    protected function recursiveRemoveDirectory(string $path)
    {
        try {
            $iterator = new \DirectoryIterator($path);
            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isDot()) {
                    continue;
                }
                if ($fileinfo->isDir()) {
                    if($this->recursiveRemoveDirectory($fileinfo->getPathname())) {
                        @rmdir($fileinfo->getPathname());
                    }
                }
                if($fileinfo->isFile()){
                    @unlink($fileinfo->getPathname());
                }
            }
        } catch ( \Exception $e ){
            // write log
            return false;
        }
        return true;
    }
}
