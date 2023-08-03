<?php

class ProcessHandler
{
    public $escapeArgs = true;
    public $escapeCommand = false;
    public $useExec = false;
    public $captureStdErr = true;
    public $procCwd;
    public $procEnv;
    public $procOptions;
    public $nonBlockingMode = false;
    public $timeout = 60;
    public $quietly = false;

    private $process;

    protected $_stdIn;
    protected $_command;
    protected $_args = array();
    protected $_stdOut = '';
    protected $_stdErr = '';
    protected $_exitCode;
    protected $_error = '';
    protected $_executed = false;
    protected $_hash = null;
    protected $_pid = null;
    protected $_isRunning = false;
    protected $_startTime = null;
    protected $_pipes;
    protected $_status;

    public function __construct($options = null)
    {
        if (is_array($options)) {
            $this->setOptions($options);
        } elseif (is_string($options)) {
            $this->setCommand($options);
        }
    }

    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            } else {
                $method = 'set'.ucfirst($key);
                if (method_exists($this, $method)) {
                    call_user_func(array($this,$method), $value);
                } else {
                    throw new \Exception("Unknown configuration option '$key'");
                }
            }
        }
        return $this;
    }

    private function setCommand($command)
    {
        if ($this->escapeCommand) {
            $command = escapeshellcmd($command);
        }
        if ($this->getIsWindows()) {
            // Make sure to switch to correct drive like "E:" first if we have
            // a full path in command
            if (isset($command[1]) && $command[1] === ':') {
                $position = 1;
                // Could be a quoted absolute path because of spaces.
                // i.e. "C:\Program Files (x86)\file.exe"
            } elseif (isset($command[2]) && $command[2] === ':') {
                $position = 2;
            } else {
                $position = false;
            }

            // Absolute path. If it's a relative path, let it slide.
            if ($position) {
                $command = sprintf(
                    $command[$position - 1] . ': && cd %s && %s',
                    escapeshellarg(dirname($command)),
                    escapeshellarg(basename($command))
                );
            }
        }
        $this->_command = $command;
        return $this;
    }

    public function setStdIn($stdIn) {
        $this->_stdIn = $stdIn;
        return $this;
    }

    private function getCommand()
    {
        return $this->_command;
    }

    private function getExecCommand()
    {
        $command = $this->getCommand();
        if (!$command) {
            $this->_error = 'Could not locate any executable command';
            return false;
        }

        $args = $this->getArgs();
        return $args ? $command.' '.$args : $command;
    }

    public function setArgs($args)
    {
        $this->_args = array($args);
        return $this;
    }

    public function getArgs()
    {
        return implode(' ', $this->_args);
    }

    public function addArg($key, $value = null, $escape = null)
    {
        $doEscape = $escape !== null ? $escape : $this->escapeArgs;

        if ($value === null) {
            $this->_args[] = $doEscape ? escapeshellarg($key) : $key;
        } else {
            if (substr($key, -1) === '=') {
                $separator = '=';
                $argKey = substr($key, 0, -1);
            } else {
                $separator = ' ';
                $argKey = $key;
            }
            $argKey = $doEscape ? escapeshellarg($argKey) : $argKey;

            if (is_array($value)) {
                $params = array();
                foreach ($value as $v) {
                    $params[] = $doEscape ? escapeshellarg($v) : $v;
                }
                $this->_args[] = $argKey . $separator . implode(' ', $params);
            } else {
                $this->_args[] = $argKey . $separator .
                    ($doEscape ? escapeshellarg($value) : $value);
            }
        }

        return $this;
    }

    private function getOutput($trim = true)
    {
        return $trim ? trim($this->_stdOut) : $this->_stdOut;
    }

    private function getError($trim = true)
    {
        return $trim ? trim($this->_error) : $this->_error;
    }

    private function getStdErr($trim = true)
    {
        return $trim ? trim($this->_stdErr) : $this->_stdErr;
    }

    public function getExecuted()
    {
        return $this->_executed;
    }

    public function execute()
    {
        $command = $this->getExecCommand();

        if (!$command) {
            return false;
        }

        
        if ($this->useExec) {
            /* $output = null;
            $execCommand = $this->captureStdErr ? "$command 2>&1" : $command;
            $pid = exec($execCommand, ($this->quietly? null : $output), $this->_exitCode);
            
            if (!$this->quietly) {
                $this->_stdOut = implode("\n", $output);
            }

            if ($this->_exitCode !== 0) {
                $this->_stdErr = implode("\n", $output);
                $this->_error = empty($this->_stdErr) ? 'Command failed' : $this->_stdErr;
                return false;
            } */
        } else {
            $isInputStream = $this->_stdIn !== null &&
                is_resource($this->_stdIn) &&
                in_array(get_resource_type($this->_stdIn), array('file', 'stream'));
            $isInputString = is_string($this->_stdIn);
            $hasInput = $isInputStream || $isInputString;
            $hasTimeout = $this->timeout !== null && $this->timeout > 0;

            $descriptors = array(
                1   => array('pipe','w'),
                2   => array('pipe', $this->getIsWindows() ? 'a' : 'w'),
            );
            if ($hasInput) {
                $descriptors[0] = array('pipe', 'r');
            }

            // Issue #20 Set non-blocking mode to fix hanging processes
            $nonBlocking = $this->nonBlockingMode === null ?
                !$this->getIsWindows() : $this->nonBlockingMode;

            $startTime = $hasTimeout ? time() : 0;

            /* if ($nonBlocking) {
                $command .= ' &';
            } */

            $process = proc_open($command, $descriptors, $pipes, $this->procCwd, $this->procEnv, $this->procOptions);

            if (!is_resource($process)) {
                $this->_error = "Could not run command $command";
                return false;
            }

            $status = proc_get_status($this->process);
            //$this->_pid = $s['pid']; // proc_open pid
            $this->_pid = $status['pid'] + 1; // command pid
        
            if ($nonBlocking) {
                stream_set_blocking($pipes[1], false);
                stream_set_blocking($pipes[2], false);
                if ($hasInput) {
                    $writtenBytes = 0;
                    $isInputOpen = true;
                    stream_set_blocking($pipes[0], false);
                    if ($isInputStream) {
                        stream_set_blocking($this->_stdIn, false);
                    }
                }

                // Due to the non-blocking streams we now have to check in
                // a loop if the process is still running. We also need to
                // ensure that all the pipes are written/read alternately
                // until there's nothing left to write/read.
                $isRunning = true;
                while ($isRunning) {
                    $status = proc_get_status($process);
                    $isRunning = $status['running'];

                    // We first write to stdIn if we have an input. For big
                    // inputs it will only write until the input buffer of
                    // the command is full (the command may now wait that
                    // we read the output buffers - see below). So we may
                    // have to continue writing in another cycle.
                    //
                    // After everything is written it's safe to close the
                    // input pipe.
                    if ($isRunning && $hasInput && $isInputOpen) {
                        if ($isInputStream) {
                            $written = stream_copy_to_stream($this->_stdIn, $pipes[0], 16 * 1024, $writtenBytes);
                            if ($written === false || $written === 0) {
                                $isInputOpen = false;
                                fclose($pipes[0]);
                            } else {
                                $writtenBytes += $written;
                            }
                        } else {
                            if ($writtenBytes < strlen($this->_stdIn)) {
                                $writtenBytes += fwrite($pipes[0], substr($this->_stdIn, $writtenBytes));
                            } else {
                                $isInputOpen = false;
                                fclose($pipes[0]);
                            }
                        }
                    }

                    // Read out the output buffers because if they are full
                    // the command may block execution. We do this even if
                    // $isRunning is `false`, because there could be output
                    // left in the buffers.
                    //
                    // The latter is only an assumption and needs to be
                    // verified - but it does not hurt either and works as
                    // expected.
                    //
                    while (($out = fgets($pipes[1])) !== false) {
                        $this->_stdOut .= $out;
                    }
                    while (($err = fgets($pipes[2])) !== false) {
                        $this->_stdErr .= $err;
                    }

                    $runTime = $hasTimeout ? time() - $startTime : 0;
                    if ($isRunning && $hasTimeout && $runTime >= $this->timeout) {
                        // Only send a SIGTERM and handle status in the next cycle
                        proc_terminate($process);
                    }

                    if (!$isRunning) {
                        //dump($status);
                        $this->_exitCode = $status['exitcode'];
                        if ($this->_exitCode !== 0 && empty($this->_stdErr)) {
                            if ($status['stopped']) {
                                $signal = $status['stopsig'];
                                $this->_stdErr = "Command stopped by signal $signal";
                            } elseif ($status['signaled']) {
                                $signal = $status['termsig'];
                                $this->_stdErr = "Command terminated by signal $signal";
                            } else {
                                $this->_stdErr = 'Command unexpectedly terminated without error message';
                            }
                        }
                        fclose($pipes[1]);
                        fclose($pipes[2]);
                        proc_close($process);
                    } else {
                        // The command is still running. Let's wait some
                        // time before we start the next cycle.
                        usleep(10000);
                    }
                }
            } else {
                if ($hasInput) {
                    if ($isInputStream) {
                        stream_copy_to_stream($this->_stdIn, $pipes[0]);
                    } elseif ($isInputString) {
                        fwrite($pipes[0], $this->_stdIn);
                    }
                    fclose($pipes[0]);
                }
                $this->_stdOut = stream_get_contents($pipes[1]);
                $this->_stdErr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $status = proc_get_status($process);
                //dump($status);
                $this->_exitCode = $status['exitcode'];
                proc_close($process);
            }

            dump($this);
            if ($this->_exitCode !== 0) {
                $this->_error = $this->_stdErr ?
                    $this->_stdErr :
                    "Failed without error message: $command (Exit code: {$this->_exitCode})";
                return false;
            }
            
        }

        return $this;
    }

    private function startBackgroundProcess(
        $command,
        $stdin = null,
        $redirectStdout = null,
        $redirectStderr = null,
        $cwd = null,
        $env = null,
        $other_options = null
    ) {
        $descriptorspec = array(
            1 => is_string($redirectStdout) ? array('file', $redirectStdout, 'w') : array('pipe', 'w'),
            2 => is_string($redirectStderr) ? array('file', $redirectStderr, 'w') : array('pipe', 'w'),
        );
        
        if (is_string($stdin)) {
            $descriptorspec[0] = array('pipe', 'r');
        }
        
        $proc = proc_open($command, $descriptorspec, $pipes, $cwd, $env, $other_options);
        
        if (!is_resource($proc)) {
            throw new Exception("Failed to start background process by command: $command");
        }
        
        if (is_string($stdin)) {
            fwrite($pipes[0], $stdin);
            fclose($pipes[0]);
        }
        
        if (!is_string($redirectStdout)) {
            fclose($pipes[1]);
        }
        
        if (!is_string($redirectStderr)) {
            fclose($pipes[2]);
        }
        
        return $proc;
    }

    public function start($command=null)
    {
        if (!$command) {
            throw new Exception("Process command is empty");
        }

        //$this->nonBlockingMode = true;
        //$this->setCommand($command);
        //return $this->execute();


        $this->_hash = md5(microtime(true) . $command);

        $this->_pipes = array(
            'out' => $this->quietly? '/dev/null' : '/tmp/' . $this->_hash . '_out.txt',
            'err' => '/tmp/' . $this->_hash . '_err.txt'
        );

        $this->process = $this->startBackgroundProcess(
            $command, $this->_stdIn, $this->_pipes['out'], $this->_pipes['err'], $this->procCwd, $this->procEnv, $this->procOptions 
        );
        
        $this->_startTime = microtime(true);

        $this->_status = proc_get_status($this->process);
        //$this->_pid = $s['pid']; // proc_open pid
        $this->_pid = $this->_status['pid'] + 1; // command pid

        $this->_isRunning = true;

        //Process::$pool[$this->_hash] = $this;

        return $this;
    }

    private function updateStatus()
    {
        $this->_exitCode = $this->_status['exitcode'];

        if ($this->_pipes['out'] !== null) {
            $this->_stdOut = file_get_contents('/tmp/' . $this->_hash . '_out.txt');
            @unlink('/tmp/' . $this->_hash . '_out.txt');
        }

        $this->_stdErr = file_get_contents('/tmp/' . $this->_hash . '_err.txt');
        @unlink('/tmp/' . $this->_hash . '_err.txt');

        if ($this->_exitCode !== 0 && empty($this->_stdErr)) {
            if ($this->_status['stopped']) {
                $signal = $this->_status['stopsig'];
                $this->_stdErr = "Command stopped by signal $signal";
            } elseif ($this->_status['signaled']) {
                $signal = $this->_status['termsig'];
                $this->_stdErr = "Command terminated by signal $signal";
            } else {
                $this->_stdErr = 'Command unexpectedly terminated without error message';
            }
        }

        proc_close($this->process);
    }


    public function wait()
    {
        while ($this->running()) {
            //$this->updateStatus(false);
            /* if (! $this->checkTimeout()) {
                break;
            } */
            //$this->checkTimeout();
            usleep(1000);
        }

        $this->updateStatus(false);

        return $this;
    }

    public function running()
    {
        if (!$this->_isRunning) {
            return false;
        }

        if ($this->timeout!=null && $this->timeout < (microtime(true) - $this->_startTime)) {
            proc_terminate($this->process, 15);
        }

        $this->_status = proc_get_status($this->process);
        $this->_isRunning = $this->_status['running'];
        return $this->_isRunning;
    }

    /* private function checkTimeout()
    {
        if (!$this->running()) {
            return true;
        }

        if ($this->timeout!=null && $this->timeout < (microtime(true) - $this->_startTime)) {
            $this->stop(0);
            return false;
        }

        return true;

    } */

    /* public function stop($timeout = 10, $signal = null)
    {
        $timeoutMicro = microtime(true) + $timeout;
        if ($this->running()) {

            proc_terminate($this->process, 15);

            do {
                usleep(1000);
            } while ($this->running() && microtime(true) < $timeoutMicro);

        }

        //return $this->_exitCode;
    } */


    public function getIsWindows()
    {
        return strncasecmp(PHP_OS, 'WIN', 3)===0;
    }

    
    public function __toString()
    {
        return (string) $this->getExecCommand();
    }

    public function timeout($seconds)
    {
        return $this->setOptions(array('timeout' => $seconds));
    }

    public function path($path)
    {
        return $this->setOptions(array('procCwd' => $path));
    }

    public function quietly()
    {
        return $this->setOptions(array('quietly' => true));
    }

    public function run($command, $stdin=null)
    {
        $this->_stdIn = $stdin;
        $this->start($command);
        return $this->wait();
    }

    public function command()
    {
        return $this->getExecCommand();
    }

    public function pid()
    {
        return $this->_pid;
    }

    public function id()
    {
        return $this->_pid;
    }

    public function successful()
    {
        return 0 === $this->exitCode();
    }

    public function failed()
    {
        return ! $this->successful();
    }

    public function exitCode()
    {
        return $this->_exitCode;
    }

    public function output()
    {
        return $this->getOutput(true);
    }

    public function seeInOutput(string $output)
    {
        return str_contains($this->output(), $output);
    }

    public function errorOutput()
    {
        return $this->getStdErr(true);
    }

    public function seeInErrorOutput(string $output)
    {
        return str_contains($this->errorOutput(), $output);
    }

    public function latestOutput()
    {
        if ($this->_pipes['out']===null) {
            return null;
        }

        if (!$this->_isRunning) {
            return $this->output();
        }

        return file_get_contents($this->_pipes['out']);
    }

    public function latestErrorOutput()
    {
        if (!$this->_isRunning) {
            return $this->errorOutput();
        }

        return file_get_contents($this->_pipes['err']);
    }

}