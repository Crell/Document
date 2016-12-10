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
    public function persist(CollectionInterface $collection, MutableDocumentInterface $document, bool $setDefault)
    {

        $data = json_encode($document, true);

        chdir($this->path);

        $git = popen('git fast-import', 'w');

        // @todo this is where the fast-import commands would go.  But we need to convert persist to be multi-value first. Sigh.


        pclose($git);
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
