<?php

declare(strict_types = 1);

namespace Crell\Document\GitStore;

/**
 * Basic process wrapper for non-interactive processes.
 */
class SimpleProcess
{
    /**
     * The output of the process, if it was executed with run().
     *
     * @var string
     */
    protected $output;

    /**
     * The stderr of the process, if it was executed with run().
     *
     * @var string
     */
    protected $error;

    /**
     * The exit code of the process, if it was executed with run().
     *
     * @var int
     */
    protected $exitcode;

    /**
     * Constructs a new SimpleProcess.
     *
     * @param string $command
     *   The command string to execute. It must already be adequiately escaped.
     * @param string $cwd
     *   The directory from which to run the command.
     */
    public function __construct(string $command, string $cwd)
    {
        $process = new Process($command, $cwd);

        $this->run($process);
    }

    /**
     * Executes the command and terminates.
     *
     * The object will then contain the output and error information, accessible via separate methods.
     */
    protected function run(Process $process) : self
    {
        $process->start();

        $this->output = trim($process->read());
        $this->error = trim($process->readError());

        $this->exitcode = $process->close();

        return $this;
    }

    /**
     * Returns the stdout output of the process.
     *
     * @return string
     */
    public function output() : string
    {
        return $this->output;
    }

    /**
     * Returns the stderr output of the process.
     *
     * @return string
     */
    public function error() : string
    {
        return $this->error;
    }

    /**
     * Returns the exit code of the process.
     *
     * @return int
     */
    public function exitcode() : int
    {
        return $this->exitcode;
    }
}
