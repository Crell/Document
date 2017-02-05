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
    const SHA1_LENGTH = 40;

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

        $process = new SimpleProcess('git init --bare', $this->path);

        if ($process->exitcode()) {
            throw new \RuntimeException(sprintf('Could not init repository: %s', $process->error()));
        }

        // Make an empty commit, so we always know there is a parent.
        $this->commit([], "System User <system>", "Initialize new repository", 'master', '');
    }

    /**
     * Writes a new commit.
     *
     * @param iterable $documents
     *   An iterable of documents to store.  The key is a file name and the value is any JSON-ifiable value.
     *   If the value is null the file will be deleted instead.
     * @param string $committer
     *   The committer for this commit. Must contain < and >, even if they don't wrap an email address.
     * @param string $message
     *   The commit message.
     * @param string $branch
     *   The branch on which this commit should happen. If the branch doesn't exist yet it will be created.
     * @param string $parent
     *   The commit ID of the parent commit. Pass an empty string literal to create a no-parent commit.
     * @return string
     *   The commit ID just created.
     */
    public function commit($documents, string $committer, string $message, string $branch, string $parent) : string
    {
        if (!preg_match('/.*\<.*\>/', $committer)) {
            throw new InvalidCommitterException(sprintf('Invalid committer: \'%s\'. Committer identifiers must include at least one character followed by < and >, usually (althougn not always) with an email address between them.', $committer));
        }

        $commitId = $this->synchronize('commit', function () use ($documents, $committer, $message, $branch, $parent) {
            // Open a new process to the git fast-import tool.
            $command = 'git fast-import --date-format=raw';
            $command .= $this->debug ? ' --stats' : ' --quiet';

            $process = (new Process($command, $this->path))->start();

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

                if ($document == null) {
                    $process->write("D {$filename}\n");
                } else {
                    $process->write("M 644 inline {$filename}\n")
                        ->write("data {$bytes}\n")
                        ->write("$data\n");
                }
            }

            // Clean up formally to ensure the data is persisted before we check the commit ID.
            $process->write('done');
            $process->close();

            return $this->getLatestCommitId($branch);
        });

        return $commitId;
    }

    public function delete(array $names, string $branch)
    {
        $documents = array_fill_keys($names, null);

        // @todo Figure out a better way of handling the committer and message, k?
        return $this->commit($documents, 'Deleter <>', 'Delete', $branch, $branch);
    }


    /**
     * Returns the most recent commit ID on the specified branch.
     *
     * @param string $branch
     *   The branch for whch we want the latest commit.
     * @return string
     *   The commit ID of the latest commit on the specified branch.
     */
    public function getLatestCommitId(string $branch) : string
    {
        $process = new SimpleProcess(sprintf('git log -n1 --pretty=oneline %s', $branch), $this->path);
        if ($process->exitcode()) {
            throw new \RuntimeException(sprintf('Could not determine latest commit ID: %s', $process->error()));
        }
        return substr($process->output(), 0, static::SHA1_LENGTH);
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
            throw new RecordNotFoundException(sprintf('No Document "%s" found for commit "%s"', $name, $commit));
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
            $process = new SimpleProcess($command, $this->path);

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
     * @return mixed
     *   If the callable has a return value, it will be returned.
     */
    protected function synchronize(string $name, callable $func)
    {
        try {
            $file = fopen('/tmp/gitdoc-' . $name, 'w');
            if (flock($file, LOCK_EX)) {
                return $func();
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

        $process = new SimpleProcess($command, $this->path);
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

        $process = new SimpleProcess($command, $this->path);

        if ($process->exitcode()) {
            throw new \RuntimeException(sprintf('Error creating branch: %s', $process->error()));
        }
    }

    /**
     * Returns a list of all commit IDs in which this document was modified, newest first.
     *
     * @param string $name
     *   The document name for which we want a list of historical commits.
     * @param string $branch
     *   The branch along which to track back history.
     * @return \Iterator
     *   An iterable of commit IDs.
     */
    public function history(string $name, string $branch) : \Iterator
    {
        $process = new SimpleProcess(sprintf('git log --pretty=oneline %s -- %s', $branch, $name), $this->path);
        if ($process->exitcode()) {
            throw new \RuntimeException(sprintf('Could not get commit history for file: %s', $process->error()));
        }

        // @todo This does kind of defeat the purpose of using a generator, but we can refactor that later
        // if we care.
        $lines = explode("\n", $process->output());
        foreach ($lines as $line) {
            yield substr($line, 0, static::SHA1_LENGTH);
        }
    }
}
