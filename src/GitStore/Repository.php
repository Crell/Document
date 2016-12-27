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

    public function __construct(string $path, bool $debug = false)
    {
        $this->path = $path;
        $this->debug = $debug;
    }

    public function getBranchPointer(string $name = 'master') : Branch
    {
        return new Branch($this, $name);
    }

    public function init()
    {
        // Make the directory for the git repository to live in.
        if (!@mkdir($this->path, 0755, true) && !is_dir($this->path)) {
            throw new \Exception('Bad race condition initializing the Git path.');
        }

        chdir($this->path);
        exec('git init --bare');

        // Make an empty commit, so we always know there is a parent.
        $this->commit([], "System User <system>", "Initialize new repository", '');
    }

    /**
     * Writes a new commit.
     *
     * @param array $documents
     *   An array of documents to store.  They may be any JSON-ifiable value.
     * @param string $committer
     *   The committer for this commit. Must contain < and >, even if they don't wrap an email address.
     * @param string $message
     *   The commit message.
     * @param string $parent
     *   The commit ID of the parent commit. Pass an empty string literal to create a no-parent commit.
     */
    public function commit(array $documents, string $committer, string $message, string $parent)
    {
        chdir($this->path);

        $this->synchronize('commit', function () use ($documents, $committer, $message, $parent) {
            // Make an initial empty commit.  I think.
            try {
                // Open a new process to the git fast-import tool.
                $command = 'git fast-import --date-format=raw';
                $command .= $this->debug ? ' --stats' : ' --quiet';
                $git = popen($command, 'w');

                if (!$git) {
                    throw new \RuntimeException('Could not open fast-import process.');
                }

                $timestamp = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('U');

                $message_bytes = strlen($message);

                // Add the header material that tells it what commit we're creating.
                // @todo This is a temporary workaround. This way, we can still initialize a repository
                // but can't have multiple roots. That's probably fine in the long-run, though.
                if ($parent) {
                    fwrite($git, "commit refs/heads/{$parent}\n");
                } else {
                    fwrite($git, "commit refs/heads/master\n");
                }
                fwrite($git, "committer {$committer} {$timestamp} +0000\n");
                fwrite($git, "data {$message_bytes}\n");
                fwrite($git, "{$message}\n");

                if ($parent !== '') {
                    $parentHash = $this->getCommitForBranch($parent);
                    fwrite($git, "from {$parentHash}\n");
                }

                foreach ($documents as $filename => $document) {
                    $data = json_encode($document, JSON_PRETTY_PRINT);

                    // Even if there are UTF-8 characters in $data, we want its bytesize, not
                    // character count. That makes strlen() correct in this case.
                    $bytes = strlen($data);

                    fwrite($git, "M 644 inline {$filename}\n");
                    fwrite($git, "data {$bytes}\n");
                    fwrite($git, "$data\n");
                }
            }
            finally {
                // Always close the stream so we don't leave dangling resources.
                pclose($git);
            }
        });
    }

    public function load($name, string $commit) : array
    {
        $results = $this->loadMultiple([$name], $commit);

        $current = $results->current();

        if (!$current) {
            throw new \InvalidArgumentException(sprintf('No Document "%s" found for commit "%s"', $name, $commit));
        }

        return $current;
    }

    public function loadMultiple(array $names, string $commit) : \Iterator
    {
        $descriptorspec = [
            1 => ['pipe', 'w'], // stdout of the process
            2 => ['pipe', 'w'], // stderr of the process
        ];

        $result = '';
        $error = '';

        foreach ($names as $name) {
            try {
                $pipes = [];
                $process = proc_open(sprintf('git show %s:%s', escapeshellarg($commit), escapeshellarg($name)), $descriptorspec, $pipes, $this->path);

                if (!$process) {
                    throw new \RuntimeException('Could not call git show.');
                }

                $result = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
            }
            finally {
                foreach ($pipes as $pipe) {
                    fclose($pipe);
                }
                $exitcode = proc_close($process);
            }

            if ($exitcode === 0) {
                yield $name => json_decode($result, true);
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

    public function getCommitForBranch(string $branch) : string
    {
        // @todo Switch this to running git rev-parse $branch

        // The output from file_get_contents() will have a trailing newline, which we don't want.
        return trim(file_get_contents($this->path .'/refs/heads/' . $branch));
    }

    /**
     * Creates a new branch in the repository.
     *
     * @todo Let this support specifying a commit as well as a branch name to start from.
     *
     * @param string $start
     *   The branch name from which to branch.
     * @param string $name
     *   The name of the branch to create.
     */
    public function createBranch(string $start, string $name)
    {
        // I feel like not wrapping the args in escapeshellargs() is a security hole, but if I do they
        // get wrapped in single quotes which breaks this command. I am not sure how to fix that.
        $command = sprintf('git update-ref refs/heads/%s %s', $name, $this->getCommitForBranch($start));

        $process = new SimpleProcess($command, $this->path);

        $process->run();

        $error = $process->readError();

        if ($errorCode = $process->close()) {
            throw new \RuntimeException(sprintf('Error creating branch: %s', $error));
        }


    }
}
