<?php

declare(strict_types = 1);

namespace Crell\Document\GitStore;

/**
 * A very basic proc_open wrapper to simplify annoying code.
 *
 * This may get replaced with the Symfony Process component in the future if appropriate. TBD.
 */
class SimpleProcess
{

    const PROCESS_STDIN = 0;
    const PROCESS_STDOUT = 1;
    const PROCESS_STDERR = 2;

    /**
     * @var string
     */
    protected $command;

    /**
     * @var array
     */
    protected $pipes = [];

    /**
     * @var string
     */
    protected $cwd;

    /**
     * The running process file descriptor.
     *
     * @var resource
     */
    protected $process;

    /**
     * Flag to mark this process as completed and now useless.
     *
     * @var bool
     */
    protected $finished = false;

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
        $this->command = $command;
        $this->cwd = $cwd;
    }

    /**
     * Executes the command.
     *
     * @todo Should this happen on the constructor?
     *
     * @return self
     *   The invoked object.
     */
    public function start() : self
    {
        if ($this->finished) {
            throw new \RuntimeException('A Process object may not be reused.');
        }

        $descriptorspec = [
            static::PROCESS_STDIN => ['pipe', 'r'], // stdin of the process
            static::PROCESS_STDOUT => ['pipe', 'w'], // stdout of the process
            static::PROCESS_STDERR => ['pipe', 'w'], // stderr of the process
        ];

        $this->process = proc_open($this->command, $descriptorspec, $this->pipes, $this->cwd);

        return $this;
    }

    /**
     * Executes the command and terminates.
     *
     * The object will then contain the output and error information, accessible via separate methods.
     */
    public function run() : self
    {
        $this->start();

        $this->output = trim($this->read());
        $this->error = trim($this->readError());

        $this->exitcode = $this->close();

        return $this;
    }

    /**
     * Returns the stdout output of the process, if it was executed with run().
     *
     * @return string
     */
    public function output() : string
    {
        return $this->output;
    }

    /**
     * Returns the stderr output of the process, if it was executed with run().
     *
     * @return string
     */
    public function error() : string
    {
        return $this->error;
    }

    /**
     * Returns the exit code of the process, if it was executed with run().
     *
     * @return int
     */
    public function exitcode() : int
    {
        return $this->exitcode;
    }

    /**
     * Writes a value to the stdin buffer for the process.
     *
     * @param string $value
     *   A string to write to the process.
     */
    public function write(string $value)
    {
        if ($this->finished) {
            throw new \RuntimeException('A Process object may not be reused.');
        }

        fwrite($this->pipes[static::PROCESS_STDIN], $value);
    }

    /**
     * Reads back the entire stdout buffer of the process.
     *
     * Note this may or may not include a trailing newline.
     *
     * @return string
     */
    public function read() : string
    {
        if ($this->finished) {
            throw new \RuntimeException('A Process object may not be reused.');
        }

        return stream_get_contents($this->pipes[static::PROCESS_STDOUT]);
    }

    /**
     * Reads back the entire stderr buffer of the process.
     *
     * Note this may or may not include a trailing newline.
     *
     * @return string
     */
    public function readError() : string
    {
        if ($this->finished) {
            throw new \RuntimeException('A Process object may not be reused.');
        }

        return stream_get_contents($this->pipes[static::PROCESS_STDERR]);
    }

    /**
     * Terminates the process.
     *
     * @return int
     *   The exit code of the process. 0 generally indicates "no error". Other
     *   error codes will vary by the program.
     */
    public function close() : int
    {
        if ($this->finished) {
            throw new \RuntimeException('A Process object may not be reused.');
        }

        foreach ($this->pipes as $pipe) {
            fclose($pipe);
        }

        $this->finished = true;

        return proc_close($this->process);
    }

    /**
     * Destruct a SimpleProcess.
     *
     * If the process is still open when this wrapper object goes out of scope,
     * clean up after ourselves.
     */
    public function __destruct()
    {
        if (!$this->finished) {
            $this->close();
        }
    }

}
