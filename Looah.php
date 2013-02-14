<?php // $Id$
/*
 * Copyright (c) 2008 Fran Rogers
 * Copyright (c) 2011 sk89q <http://www.sk89q.com>
 * Copyright (c) 2012 Kyle Temkin <ktemkin@binghamton.edu>
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
    const FINAL_STATE_TIMEOUT = 2;

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
    public function execute($input, &$environment=null)
    {
        //Change the local working directory to match the path from
        //which the lua wrapper will be executed, so lua's require works.
        $original_dir = getcwd();
        chdir(dirname($this->getCompiledWrapperPath()));

        //Remove anything data that can't be communicated to the
        //Lua runtime.
        $environment = self::filter_non_communicable($environment);

        //If we haven't been provided with a lua interpreter, attempt
        //to use the LUA php extension. This is preferred, as it doesn't
        //incur the overhead of starting a new process.
        if (!$this->interpreterPath) {
          $result = $this->executeWithExtension($input, $environment);

        //Otherwise, use the Lua interpret provided with the system.
        } else {

          //TODO: remove the posix-specific stuff.
          if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
              throw new LooahException("Windows is not supported with the interpreter");
          }
            
          $result = $this->executeWithInterpreter($input, $environment);
        }

        //Restore the original working directory.
        chdir($original_dir);

        //Extract the newly-created environment from the results.
        $environment = array_pop($result);

        //Process the result, and return. 
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
    private function executeWithExtension($input, $base_environment = null)
    {
        //Create an empty base environment, if we weren't provided with one.
        $base_environment = $base_environment ?: array();

        // We're using the extension - verify it exists
        if (!class_exists('lua')) {
            throw new LooahException("The Lua extension is unavailable");
        }
    
        // Create a lua instance and load the wrapper library
        $lua = new \lua();
        
        try {
            $lua->include($this->getCompiledWrapperPath());
            $lua->assign('base_environment', $base_environment);
            $lua->eval("wrap = make_wrapper({$this->maxLines}, {$this->maxRecursionDepth}, {$this->maxTime}, base_environment)");
            $res = $lua->wrap($input);

            return $res;
        } catch (Exception $e) {
            throw new LooahException("Could not the evaluate Lua sandbox wrapper script");
        }
    }

    /**
     * Removes any items which cannot be correctly communicated to the 
     * interpreter/extension.
     */
    private static function filter_non_communicable($environment) {
        return json_decode(@json_encode($environment), true);
    }

    /**
     * Creates a new Lua interpreter process, and establishes IPC channels.
     *
     * @return array<resource, array>   Returns the process object and all of the pipes created, in array($proc, $pipes) format.
     */
    private function create_interpreter() {

        $wrapper_path = escapeshellarg($this->getCompiledWrapperPath());

        //Consturct the command which will be used to start the sandboxed Lua interpreter.
        $cmd = sprintf("{$this->interpreterPath} %s %d %d %d", $wrapper_path,
           $this->maxLines, $this->maxRecursionDepth, $this->maxTime);

        //Set up the pipes which will be used for IPC with the interpreter.
        $io_pipes = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'));
        $pipes = array();

        //Attempt to open the LUA interpreter.  
        $proc = proc_open($cmd, $io_pipes, $pipes, null, null);

        //If we weren't able to open the LUA interpreter, assume it's missing. 
        if (!is_resource($proc)) {
            throw new LooahException("External Lua interpreter not found");
        }

        //Ensure that we don't buffer or block when communicating with the LUA
        //interpreter; this prevents (potentially infinite) 
        //delays when short input is provided. 
        foreach($pipes as $pipe) {
          stream_set_blocking($pipe, 0);
          stream_set_write_buffer($pipe, 0); 
        }

        //And return the newly-created objects.
        return array($proc, $pipes);
    }

    /**
     * Terminates the specified interpreter.
     *
     * @param resource $proc   The process for the running interpreter.
     * @param array<resource> $pipes  An array containing all pipes belonging to the process. 
     * @param bool $exceeded_time If true, an "Exceeded Time" exception is raised.
     */
    private function killInterpreter($proc, $pipes, $exceeded_time = false) {
          
      //Check to see if the process is still running.
      $status = proc_get_status($proc);

      //If it is, destory all relevant resources.
      if ($status['running'] == true) {

          //Close the pipes connecting to it...
          foreach($pipes as $pipe) {
            fclose($pipe);
          }

          //And kill the process. 
          //Note that both terminates is required for safety.  
          $this->killProcess($status['pid']);
          proc_terminate($proc);
      }

      //Close the process object, and raise an exception indicating 
      //that we timed out.
      proc_close($proc);

      //If the exceeded time flag is set, raise an exception.
      if($exceeded_time) {
        throw new TimeExceededException('Max execution time reached');
      }
    }

    /**
     * Reads a single line of text from the provided stream object.
     *
     * @param array $pipes An array containing the single relevant pipe. An array
     *   is required for compatibility with stream_select.
     * @param integer $timeout The maximum number of seconds that the read should block for.
     * @return string A single line from the 
     */ 
    private function read_line_from_stream($proc, &$pipes, $timeout) {
  
        //Wait for the stream to change statuas, indicating a response.
        //If the stream takes more than maxTime to respond, force it to terminate.
        $null = null;
        $num = stream_select($pipes, $null, $null, $timeout);

        //If a failure condition occured, kill the interpreter. 
        if ($num === false || $num === 0) {
          $this->killInterpreter($proc, $pipes, true);
          //This raises an exception; so we don't need to return.
        }

        //Get a line from the interpreter's standard output... 
        return fgets($pipes[0]);
    }

    /**
     * Sends a "line" of code-data to the Lua interpreter.
     *
     * @param resource $pipe An array whose first element contains the pipe to connect to.
     */
    private static function send_to_interpreter($pipe, $data) {
        fwrite($pipe, $data."\n");
        fflush($pipe);
    }

    /**
     * Formats a chunk of Lua code for transmission to the interpreter.
     * 
     * @param string $chunk The chunk of lua code to be formatted.
     * @return string The lua code in a format ready for transmission.
     */
    private static function format_chunk($chunk) {

      //Sanitize the chunk, removing any existing end-of-chunk delimiters.
      $chunk = trim(preg_replace('/(?<=\n|^)\.(?=\n|$)/', '. --', $chunk));

      //And append the end-of-chunk delimiter.
      return $chunk."\n.";
    }


    /**
     * Execute Lua code with the external Lua interpreter.
     * 
     * @param string $input
     * @param array  $environment The basic environment to be created inside of the interpreter.
     * @return array<string> Stdout and stderr
     */
    private function executeWithInterpreter($input, $base_environment = null)
    {

        //Create a connection to a LUA interpreter.
        list($proc, $pipes) = $this->create_interpreter();

        //Send the base state to the interpreter using JSON.
        $base_environment = $base_environment ?: array();
        self::send_to_interpreter($pipes[0], json_encode($base_environment));

        //Transmit the chunk to be executed.
        self::send_to_interpreter($pipes[0], self::format_chunk($input));

        //Create an object which always points to the currently
        //interpreter output. This will be modified by calls below.
        $interpreter_out = array($pipes[1]);

        // Wait for a response back on the other pipe
        $response = "";

        $state = array();

        //While there's data to read on the standard out, handle it. 
        while (!feof($pipes[1])) {

            //Read a single line of data from the stream.
            $line = $this->read_line_from_stream($proc, $interpreter_out, $this->maxTime);

            //If this was our end-of-response indicator...
            if ($line == ".\n") {

                //... receive the system's state...
                $encoded_state = $this->read_line_from_stream($proc, $interpreter_out, $this->maxTime);
                $state = json_decode($encoded_state, true);
                
                //... and stop reading the response.
                break;
            }

            //Append the output we received to the buffer. 
            $response .= $line;
        }
        
        //Ensure the interpreter process is killed properly.
        $this->killInterpreter($proc, $pipes);

        //If the repsonse didn't match our expected format, raise an exception.
        if (preg_match('/^\'(.*)\', (true|false)$/s', trim($response), $match) != 1) {
            throw new LooahException('Internal error: '.$response);
        }

        //If Lua hasn't reported any problems,  
        //return the output without any errors...
        if($match[2] == true) {
          return array($match[1], null, $state);
        }
        //Otherwise, return only the output.
        else {
          return array('', $match[1], $state);
        }
    }

    /**
     * Process the result, and extract any relevant error messages.
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
                throw new TimeExceededException("Max execution time reached (thrown by Lua)");
            } else {
                $err = preg_replace('/^\[.+?\]:(.+?):/', '$1:', $err);
                throw new LuaErrorException($err);
            }
        }

        //If no meaningful output was produced, truncate the output to an empty string.
        return (trim($out) != "") ? $out: "";
    }
}
