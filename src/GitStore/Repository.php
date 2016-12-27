<?php

declare(strict_types = 1);

namespace Crell\Document\GitStore;

/**
 * Class Repository
 *
 * @todo This is totally racy. It needs lock files.
 *
 * There's a race condition on reading, if a branch is updated in the
 * process of a request. If we cache the branch->hash lookup, we have to worry about
 * clearing it for long-running processes. If we don't, a given request could pull
 * some loads from one commit hash and later ones from another, if the branch is
 * updated by another process. Hm. Not sure how to resolve this.
 */
class Repository
{

    /**
     * An absolute path to the bare git repository.
     *
     * @var string
     */
    protected $path;

    /**
     * Whether to show verbose output for debugging or not.
     *
     * @var bool
     */
    protected $debug;

    /**
     * Constructs a new Repository.
     *
     * @param string $path
     *   The absolute path of the bare repository on disk.
     * @param bool $debug
     *   True to use extra-verbose output, False to supress stdout/stderr output.
     */
    public function __construct(string $path, bool $debug = false)
    {
        $this->path = $path;
        $this->debug = $debug;
    }

    /**
     * Returns a branch pointer object for a specified branch.
     *
     * In 99% of cases, user code should only interact with a repository through a
     * branch pointer. It is extraordinarily rare that it should access the repository
     * directly.
     *
     * @param string $name
     *   The name of the branch for which to get a pointer.
     * @return Branch
     *   A new branch pointer for the specified branch.
     */
    public function getBranchPointer(string $name = 'master') : Branch
    {
        return new Branch($this, $name);
    }

    /**
     * Initialize a new repository.
     *
     * This includes creating the initial commit so that there is alwaysa  parent.
     *
     * @throws \Exception
     */
    public function init()
    {
        // Make the directory for the git repository to live in.
        if (!@mkdir($this->path, 0755, true) && !is_dir($this->path)) {
            throw new \Exception('Bad race condition initializing the Git path.');
        }

        chdir($this->path);
        exec('git init --bare');

        // Make an empty commit, so we always know there is a parent.
        $this->commit([], "System User <system>", "Initialize new repository", 'master', '');
    }

    /**
     * Writes a new commit.
     *
     * @param iterable $documents
     *   An iterable of documents to store.  They may be any JSON-ifiable value.
     * @param string $committer
     *   The committer for this commit. Must contain < and >, even if they don't wrap an email address.
     * @param string $message
     *   The commit message.
     * @param string $branch
     *   The branch on which this commit should happen. If the branch doesn't exist yet it will be created.
     * @param string $parent
     *   The commit ID of the parent commit. Pass an empty string literal to create a no-parent commit.
     */
    public function commit($documents, string $committer, string $message, string $branch, string $parent)
    {
        $this->synchronize('commit', function () use ($documents, $committer, $message, $branch, $parent) {
            // Open a new process to the git fast-import tool.
            $command = 'git fast-import --date-format=raw';
            $command .= $this->debug ? ' --stats' : ' --quiet';

            $process = (new SimpleProcess($command, $this->path))->start();

            $timestamp = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('U');

            $message_bytes = strlen($message);

            // Add the header material that tells it what commit we're creating.
            $process->write("commit refs/heads/{$branch}\n")
                    ->write("committer {$committer} {$timestamp} +0000\n")
                    ->write("data {$message_bytes}\n")
                    ->write("{$message}\n");

            if ($parent !== '') {
                $parentHash = $this->getCommitForBranch($parent);
                $process->write("from {$parentHash}\n");
            }

            foreach ($documents as $filename => $document) {
                $data = json_encode($document, JSON_PRETTY_PRINT);

                // Even if there are UTF-8 characters in $data, we want its bytesize, not
                // character count. That makes strlen() correct in this case.
                $bytes = strlen($data);

                $process->write("M 644 inline {$filename}\n")
                    ->write("data {$bytes}\n")
                    ->write("$data\n");
            }
        });
    }

    /**
     * Loads a single saved document by name.
     *
     * @param $name
     *   The name of the document to retrieve. This may be a path, but should NOT include a leading /.
     * @param string $commit
     *   The commit-ish from which to load the document.
     * @return array
     *   The deserialized stored value for the specified object name at a given commit.
     *
     * @throws \InvalidArgumentException
     *   Thrown if the requested document does not exist in that commit.
     */
    public function load($name, string $commit) : array
    {
        $current = $this->loadMultiple([$name], $commit)->current();

        if (!$current) {
            throw new \InvalidArgumentException(sprintf('No Document "%s" found for commit "%s"', $name, $commit));
        }

        return $current;
    }

    /**
     * Loads multiple saved documents by name.
     *
     * @param array $names
     *   An array of the document names to retrieve. Names may be paths, but should NOT include a leading /.
     * @param string $commit
     *   The commit-ish from which to load the document.
     * @return \Iterator
     *   An iterator of the results that were found. Missing documents will be omitted. Keys are the filename,
     *   values decoded to an array.
     */
    public function loadMultiple(array $names, string $commit) : \Iterator
    {
        foreach ($names as $name) {
            $command = sprintf('git show %s:%s', escapeshellarg($commit), escapeshellarg($name));
            $process = (new SimpleProcess($command, $this->path))->run();

            if ($process->exitcode() === 0) {
                yield $name => json_decode($process->output(), true);
            }
            else {
                // @todo Some kind of error handling here.
                // We shouldn't throw an exception, as that would break looking up all further
                // documents being loaded.
            }
        }
    }

    /**
     * Executes a callable with a lock, to ensure it's not run by two processes simultaneously.
     *
     * @param string $name
     *   The name of the lock to hold.  Multiple calls with the same name will block.
     * @param callable $func
     *   The callable to execute.
     */
    protected function synchronize(string $name, callable $func)
    {
        try {
            $file = fopen('/tmp/gitdoc-' . $name, 'w');
            if (flock($file, LOCK_EX)) {
                $func();
            } else {
                throw new \RuntimeException('flock() failed for some reason.');
            }
        }
        finally {
            if (!empty($file)) {
                flock($file, LOCK_UN);
                fclose($file);
            }
        }
    }

    /**
     * Determines the commit ID of a branch.
     *
     * @param string $branch
     *   The branch to look up. If a commit ID is provided it will simply be returned.
     * @return string
     *   The commit ID of the specified branch.
     */
    public function getCommitForBranch(string $branch) : string
    {
        $command = sprintf('git rev-parse %s', $branch);

        $process = (new SimpleProcess($command, $this->path))->run();
        if ($process->exitcode()) {
            throw new \RuntimeException(sprintf('Error creating branch: %s', $process->error()));
        }

        return $process->output();
    }

    /**
     * Creates a new branch in the repository.
     *
     * @param string $start
     *   The branch or commit-ish name from which to branch.
     * @param string $name
     *   The name of the branch to create.
     */
    public function createBranch(string $start, string $name)
    {
        // I feel like not wrapping the args in escapeshellargs() is a security hole, but if I do they
        // get wrapped in single quotes which breaks this command. I am not sure how to fix that.
        $command = sprintf('git update-ref refs/heads/%s %s', $name, $this->getCommitForBranch($start));

        $process = (new SimpleProcess($command, $this->path))->run();

        if ($process->exitcode()) {
            throw new \RuntimeException(sprintf('Error creating branch: %s', $process->error()));
        }
    }
}
