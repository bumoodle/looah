<?php // $Id$
/*
 * Copyright (c) 2008 Fran Rogers
 * Copyright (c) 2010 sk89q <http://www.sk89q.com>
 * 
 * This software is provided 'as-is', without any express or implied
 * warranty.  In no event will the authors be held liable for any damages
 * arising from the use of this software.
 * 
 * Permission is granted to anyone to use this software for any purpose,
 * including commercial applications, and to alter it and redistribute it
 * freely, subject to the following restrictions:
 * 
 * 1. The origin of this software must not be misrepresented; you must not
 *    claim that you wrote the original software. If you use this software
 *    in a product, an acknowledgment in the product documentation would be
 *    appreciated but is not required.
 * 2. Altered source versions must be plainly marked as such, and must not be
 *    misrepresented as being the original software.
 * 3. This notice may not be removed or altered from any source distribution.
*/

namespace looah;

/**
 * Looah allows for execution of Lua code in a sandbox. It is largely based
 * off of code by Fran Rogers for a MediaWiki extension, but it has been
 * reformatted to fit your televi--an easy to use library. Looah can use
 * either an external Lua interpreter binary or use the Lua extension for PHP.
 * 
 * Only *nix systems are supported if using the external Lua binary. Using the
 * Lua extension means that a max execution time will not be enforced.
 * 
 * Using Looah is as simple as:
 * <pre>
 * $lua = new Looah();
 * $result = $lua->execute("print(1234 + 5)");
 * </pre>
 * 
 * The max. lines and calls constraints are enforced using Lua code, but the
 * maximum execution time is enforced using PHP. The process will be killed
 * if the execution time limit is exceeded. Sandboxing is done using Lua code
 * written by Fran Rogers. For more information about Lua sandboxes, please see
 * http://lua-users.org/wiki/SandBoxes
 *
 */
class Looah
{
    /**
     * Maximum number of lines to execute before aborting. This constraint is
     * enforced using Lua sandbox code.
     * 
     * @var int
     */
    private $maxLines = 1000000;
    /**
     * Maximum number of call depth before aborting. This constraint is enforced
     * using Lua sandbox code.
     * 
     * @var int
     */
    private $maxRecursionDepth = 2000;
    /**
     * Maximum execution time. This constraint is enforced using PHP.
     * 
     * @var int
     */
    private $maxTime = 2;
    /**
     * Path to the interpreter.
     * 
     * @var string
     */
    private $interpreterPath = null;
    /**
     * Path to the Lua compiler.
     * 
     * @var string
     */
    private $compilerPath = null;
    /**
     * Wrapper path.
     * 
     * @var string
     */
    private $wrapperPath = null;
    /**
     * Path the compiled Lua wrapper file.
     * 
     * @var string
     */
    private $compiledPath = null;

    /**
     * Creates a new Looah instance. An interpreter path can be provided now
     * or later. The extension will be used if the interpreter path is not
     * specified.
     * 
     * @param string $interpreterPath
     */
    public function __construct($interpreterPath = null)
    {
        $this->interpreterPath = $interpreterPath;
    }
    
    /**
     * Get the maximum number of lines to be executed.
     * 
     * @return int
     */
    public function getMaxLines()
    {
        return $this->maxLines;
    }

    /**
     * Set the maximum number of lines to execute.
     * 
     * @param int $maxLines
     */
    public function setMaxLines($maxLines)
    {
        $this->maxLines = $maxLines;
    }

    /**
     * Get the maximum recursion depth.
     * 
     * @return int
     */
    public function getMaxRecursionDepth()
    {
        return $this->maxRecursionDepth;
    }

    /**
     * Set the maximum recursion depth.
     * 
     * @param int $maxRecursionDepth
     */
    public function setMaxRecursionDepth($maxRecursionDepth)
    {
        $this->maxRecursionDepth = $maxRecursionDepth;
    }

    /**
     * Get the maximum execution time set, in seconds. Can be 0 for an
     * infinite execution time.
     * 
     * @return float
     */
    public function getMaxTime()
    {
        return $this->maxTime;
    }

    /**
     * Set the maximum excution time in seconds. Use 0 for an infinite execution
     * time.
     * 
     * @param float $maxTime
     */
    public function setMaxTime($maxTime)
    {
        $this->maxTime = $maxTime;
    }

    /**
     * Get the Lua interpreter path. May be null if using the Lua extension.
     * 
     * @return string
     */
    public function getInterpreterPath()
    {
        return $this->interpreterPath;
    }

    /**
     * Set the path to the Lua interpreter. Use null to use the Lua extension.
     * 
     * @param string $interpreterPath
     */
    public function setInterpreterPath($interpreterPath)
    {
        $this->interpreterPath = $interpreterPath;
    }

    /**
     * Get the compiler path. May be null.
     * 
     * @return strinmg
     */
    public function getCompilerPath()
    {
        return $this->compilerPath;
    }

    /**
     * Get the path to the luac compiler, used to compile the Lua sandbox
     * wrapper. Use null to disable.
     * 
     * @param string $compilerPath
     */
    public function setCompilerPath($compilerPath)
    {
        $this->compilerPath = $compilerPath;
    }

    /**
     * Get the path to the Lua sandbox wrapper.
     * 
     * @return string
     */
    public function getWrapperPath()
    {
        return $this->wrapperPath;
    }
    
    /**
     * @param string $wrapperPath
     */
    public function setWrapperPath($wrapperPath)
    {
        $this->wrapperPath = $wrapperPath;
    }

    /**
     * Get the path to the compiled Lua sandbox wrapper.
     * 
     * @return string
     */
	public function getCompiledPath()
    {
        return $this->compiledPath;
    }

    /**
     * Path to the compiled Lua sandbox wrapper. If null, the generated
     * wrapper will be put into the same directory as the LuaWrapper.lua file.
     * 
     * @param string $compiledPath
     */
    public function setCompiledPath($compiledPath)
    {
        $this->compiledPath = $compiledPath;
    }
    
    /**
     * Gets the LuaWrapper path that is used. A compiled version may be
     * generated and returned.
     * 
     * @return string
     */
    private function getCompiledWrapperPath()
    {
        $source = $this->wrapperPath ? $this->wrapperPath : 
            dirname(__FILE__) . "/LuaWrapper.lua";
        $compiled = $this->compiledPath ? $this->compiledPath : $source . ".c";
        
        if (!file_exists($source) || !is_readable($source)) {
            throw new LooahException("Wrapper Lua file is missing or unreadable");
        }
        
        if ($this->compilerPath && is_writable(dirname($compiled))) {
            if (!file_exists($compiled) ||
                (filemtime($compiled) < filemtime($source))) {
                $cmd = sprintf("{$this->compilerPath} -o %s %s",
                                escapeshellarg($compiled),
                                escapeshellarg($source));
                
                exec($cmd, $output, $ret);
                
                if ($ret === 0) {
                    return $compiled;
                }
            }
        }

        return $source;
    }
    
    /**
     * Execute Lua code.
     * 
     * @param $input Lua code
     * @return string Output
     * @throws LooahException
     */
    public function execute($input)
    {
        if (!$this->interpreterPath) {
            $result = $this->executeWithExtension($input);
        } else {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                throw new LooahException("Windows is not supported with the interpreter");
            }
            
            $result = $this->executeWithInterpreter($input);
        }
        
        return $this->process($result);
    }
    
    /**
     * Find the children of a process.
     * 
     * @param array<string> $pids
     * @param int $ppid
     * @return array<int>
     */
    private function findProcChildren($pids, $ppid)
    {
        $kill = array();
        
        foreach ($pids as $line) {
            if (preg_match("/^([0-9]+)\\s+([0-9]+)$/", trim($line), $m)) {
                $p = intval($m[1]);
                $pid = intval($m[2]);
                
                if ($p == $ppid) {
                    $kill[] = $pid;
                    $kill = array_merge($kill, $this->findProcChildren($pids, $pid));
                }
            }
        }
        
        return $kill;
    }
    
    /**
     * Kill a process and all of its children.
     * 
     * @param int $ppid
     */
    private function killProcess($ppid)
    {
        // ps --ppid is not cross-platform
        $pids = explode("\n", shell_exec("ps -o ppid,pid"));
        
        $kill = $this->findProcChildren($pids, $ppid);
        foreach ($kill as $pid) {
            posix_kill($pid, 9);
        }
    }
    
    /**
     * Execute Lua code with the PHP extension.
     * 
     * @param string $input
     * @return array<string> Stdout and stderr
     */
    private function executeWithExtension($input)
    {
        // We're using the extension - verify it exists
        if (!class_exists('lua')) {
            throw new LooahException("The Lua extension is unavailable");
        }

        // Create a lua instance and load the wrapper library
        $lua = new lua();
        
        try {
            $lua->evaluatefile($this->getCompiledWrapperPath());
            $lua->evaluate("wrap = make_wrapper({$this->maxLines}, {$this->maxRecursionDepth})");
            $res = $lua->wrap($input);
            return array($res[0], $res[1]);
        } catch (Exception $e) {
            throw new LooahException("Could not the evaluate Lua sandbox wrapper script");
        }
    }
    
    /**
     * Execute Lua code with the external Lua interpreter.
     * 
     * @param string $input
     * @return array<string> Stdout and stderr
     */
    private function executeWithInterpreter($input)
    {
        $cmd = sprintf("{$this->interpreterPath} %s %d %d %d",
                       escapeshellarg($this->getCompiledWrapperPath()),
                       $this->maxLines, $this->maxRecursionDepth, $this->maxTime);
        
        $dspec = array(0 => array('pipe', 'r'),
                       1 => array('pipe', 'w'));
        
        $pipes = array();
        
        $proc = proc_open($cmd, $dspec, $pipes, null, null);
        
        if (!is_resource($proc)) {
            throw new LooahException("External Lua interpreter not found");
        }
        
        stream_set_blocking($pipes[0], 0);
        stream_set_blocking($pipes[1], 0);
        stream_set_write_buffer($pipes[0], 0);
        stream_set_write_buffer($pipes[1], 0);
        
        // We're using an external binary; send the chunk through the pipe
        $input = trim(preg_replace('/(?<=\n|^)\.(?=\n|$)/', '. --', $input));
        fwrite($pipes[0], "$input\n.\n");
        fflush($pipes[0]);

        // Wait for a response back on the other pipe
        $res = "";
        $read = array($pipes[1]);
        $write = null;
        $except = null;
        
        while (!feof($pipes[1])) {
            $num = stream_select($read, $write, $except, $this->maxTime);
            
            if ($num === false || $num === 0) {
                // Time to kill the thread
                // proc_terminate() is useless in this endeavor
                $status = proc_get_status($proc);
                
                if ($status['running'] == true) {
                    fclose($pipes[0]);
                    fclose($pipes[1]);
                    
                    $this->killProcess($status['pid']);
                    // We have to do this just in case
                    proc_terminate($proc);
                }
                
                proc_close($proc);
                
                throw new TimeExceededException('Max execution time reached (thrown by Lua)');
            }
            
            $line = fgets($pipes[1]);
            if ($line == ".\n") {
                break;
            }
            
            $res .= $line;
        }
        
        // No try {} finally {} support, because PHP sucks
        fclose($pipes[0]);
        fclose($pipes[1]);
        proc_close($proc);

        // Parse the response and collect the results
        if (preg_match('/^\'(.*)\', (true|false)$/s', trim($res), $match) != 1) {
            throw new LooahException('Internal error');
        }
        
        $success = ($match[2] == "true");
        $out = $success ? $match[1] : "";
        $err = $success ? null : $match[1];
        
        return array($out, $err);
    }

    /**
     * Do some processing.
     * 
     * @param array<string> $result
     * @return string
     */
    private function process($result)
    {
        list($out, $err) = $result;
        
        // If an error was raised, abort and throw an exception
        if ($err != null) {
            if (preg_match('/LOC_LIMIT$/', $err)) {
                throw new LineLimitException("Line limit reached");
            } else if (preg_match('/RECURSION_LIMIT$/', $err)) {
                throw new StackOverflowException("Stack overflow");
            } else if (preg_match('/TIME_LIMIT$/', $err)) {
                throw new TimeExceededException("Max execution time reached");
            } else {
                $err = preg_replace('/^\[.+?\]:(.+?):/', '$1:', $err);
                throw new LuaErrorException($err);
            }
        }

        return (trim($out) != "") ? $out : "";
    }
}
