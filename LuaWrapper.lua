#!/usr/bin/env lua
-- $Id$
--
-- Copyright (c) 2008 Fran Rogers
-- Copyright (c) 2010 sk89q <http://www.sk89q.com>
-- Modified by Kyle J. Temkin, <ktemkin@binghamton.edu>
-- 
-- This software is provided 'as-is', without any express or implied
-- warranty.  In no event will the authors be held liable for any damages
-- arising from the use of this software.
-- 
-- Permission is granted to anyone to use this software for any purpose,
-- including commercial applications, and to alter it and redistribute it
-- freely, subject to the following restrictions:
-- 
-- 1. The origin of this software must not be misrepresented; you must not
--    claim that you wrote the original software. If you use this software
--    in a product, an acknowledgment in the product documentation would be
--    appreciated but is not required.
-- 2. Altered source versions must be plainly marked as such, and must not be
--    misrepresented as being the original software.
-- 3. This notice may not be removed or altered from any source distribution.
--

require 'lib/json'
require 'lib/base64'

--The extras library contains anything that 
require 'lib/extras'

-- Creates a new sandbox environment for scripts to safely run in.
-- base_environment: A table which will be merged into the sandbox environment automatically.
-- random_seed: If specified, a number which will be used to see Lua's random number generator.
function make_sandbox(base_environment, random_seed)

  -- Dummy function that returns nil, to quietly replace unsafe functions
  local function dummy(...)
    return nil
  end

  -- Deep-copy an object; optionally replace all its leaf members with the
  -- value 'override' if it's non-nil
  local function deepcopy(object, override)
    local lookup_table = {}
    local function _copy(object, override)
      if type(object) ~= "table" then
    return object
      elseif lookup_table[object] then
    return lookup_table[object]
      end
      local new_table = {}
      lookup_table[object] = new_table
      for index, value in pairs(object) do
    if override ~= nil then
      value = override
    end
    new_table[_copy(index)] = _copy(value, override)
      end
      return setmetatable(new_table, _copy(getmetatable(object), override))
    end
    return _copy(object, override)
  end

  -- Our new environment
  local env = {}

  --Copy in the values from the base environment,
  --if one was provided.
  if base_environment then

    --Restore all variables to the environment...
    for i, v in pairs(base_environment) do
      env[i] = v
    end

    --... and restore this environment's functions.
    if type(env._FUNCTIONS) == 'table' then
      restore_functions(env)
    end
  end

  -- "_OUTPUT" will accumulate the results of print() and friends
  env._OUTPUT = ""

  -- _OUTPUT wrapper for io.write()
  local function writewrapper(...)
    local out = ""
    for n = 1, select("#", ...) do
      if out == "" then
    out = tostring(select(n, ...))
      else
    out = out .. tostring(select(n, ...))
      end
    end
    env._OUTPUT = env._OUTPUT .. out
  end

  -- _OUTPUT wrapper for io.stdout:output()
  local function outputwrapper(file)
    if file == nil then
      local file = {}
      file.close = dummy
      file.lines = dummy
      file.read = dummy
      file.flush = dummy
      file.seek = dummy
      file.setvbuf = dummy
      function file:write(...) writewrapper(...); end
      return file
    else
      return nil
    end
  end

  -- _OUTPUT wrapper for print()
  local function printwrapper(...)
    local out = ""
    for n = 1, select("#", ...) do
      if out == "" then
    out = tostring(select(n, ...))
      else
    out = out .. '\t' .. tostring(select(n, ...))
      end
    end
    env._OUTPUT =env._OUTPUT .. out .. "\n"
  end

  -- Safe wrapper for loadstring()
  local oldloadstring = loadstring
  local function safeloadstring(s, chunkname)
    local f, message = oldloadstring(s, chunkname)
    if not f then
      return f, message
    end
    setfenv(f, getfenv(2))
    return f
  end

  -- Populate the sandbox environment
  env.assert = _G.assert
  env.error = _G.error
  env._G = env
  env.ipairs = _G.ipairs
  env.loadstring = safeloadstring
  env.next = _G.next
  env.pairs = _G.pairs
  env.pcall = _G.pcall
  env.print = printwrapper
  env.write = writewrapper
  env.select = _G.select
  env.tonumber = _G.tonumber
  env.tostring = _G.tostring
  env.type = _G.type
  env.unpack = _G.unpack
  env._VERSION = _G._VERSION
  env.xpcall = _G.xpcall
  env.coroutine = deepcopy(_G.coroutine)
  env.string = deepcopy(_G.string)
  env.string.dump = nil
  env.table = deepcopy(_G.table)
  env.math = deepcopy(_G.math)
  env.io = {}
  env.io.write = writewrapper
  env.io.flush = dummy
  env.io.type = typewrapper
  env.io.output = outputwrapper
  env.io.stdout = outputwrapper()
  env.os = {}
  env.os.clock = _G.os.clock
  -- env.os.date = _G.os.date
  env.os.difftime = _G.os.difftime
  env.os.time = _G.os.time

  --Allow JSON encoding/decoding.
  env.json = _G.json

  --If a random seed was provided, use it to generate
  --some nice, random numbers.
  if random_seed then
    env.math.randomseed(random_seed)
    --Waste the first few random numbers, as this apparently
    --increases entropy.
    for i = 1, 10 do math.random() end
  end

  --If an extras module is present, load it into the environment.
  if extras then
    for i, v in pairs(extras) do
      env[i] = v
    end
  end

  
  -- Return the new sandbox environment
  return env
end

-- Creates a new debug hook that aborts with 'error("LOC_LIMIT")' after 
-- 'maxlines' lines have been passed, 'error("RECURSION_LIMIT")' after 
-- 'maxcalls' levels of recursion have been entered, or 'error("TIME_LIMIT")'
-- after 'maxtime' seconds have passed.
function make_hook(maxlines, maxcalls, maxtime, diefunc)
  local lines = 0
  local calls = 0
  local start = 0
  function _hook(event, ...)
    if start == 0 then
      start = os.clock()
    end
    if maxtime ~= 0 and os.clock() - start > maxtime then
      error("TIME_LIMIT")
    end
    if event == "line" then
      lines = lines + 1
      if lines > maxlines then
        error("LOC_LIMIT")
      end
    elseif event == "call" then
      calls = calls + 1
      if calls > maxcalls then
        error("RECURSION_LIMIT")
      end
    elseif event == "return" then
      calls = calls - 1
    end
  end
  return _hook
end

--
-- Retrieves a reverse-dictionary of all variables in the
-- given environment, which can easily be used to check
-- a variables' existance.
--
function get_all_variable_names(sandbox, exclude, functions)
  local vars = {}
    
  exclude = exclude or {}

  --If the "always include functions" option is set, add the names 
  --of any existing _FUNCTIONS to the _exclude_ array.
  if type(sandbox._FUNCTIONS) == 'table' and functions then
    for i in pairs(sandbox._FUNCTIONS) do
      exclude[i] = true
    end
  end

  --Get the name of all defined variables.
  for i in pairs(sandbox) do
    if not exclude[i] then
      vars[i] = true
    end
  end

  return vars
end

--
-- Creates a duplicate of the given environment which is ready
-- for serialization; and serializes any contained functions.
-- 
-- TODO: Perform deep copy, with nested functions.
--
-- environment: A table containing the environment to be serialized.
-- exclude: A table whose keys specify which elements should be excluded 
--     from serialization.
--
function prepare_for_serialization(environment, exclude, include_functions)
  local vars = {}
  local functions = {}

  --If exclude was omitted, use an empty table instead.
  exclude = exclude or {}

  --Add each variable which isn't in the exceptions list to the 
  --provided table.
  for i, v in pairs(environment) do
    if not exclude[i] then

      --If we have a function, add it to our functions array.
      --
      --Converting this to base64 eliminates the encoding issues
      --which are prevalent on PHP (and in many database applications.)
      --Note that php's json_decode is currently broken when it comes to
      --binary data (e.g. unicode escape codes).
      if type(v) == 'function' then

        --If include functions is on, include a serialization of the function.
        if include_functions then
          functions[i] = to_base64(string.dump(v))
        end

      --If we have a table, recurse to encapsulate each of its components.
      elseif type(v) == 'table' and i ~= '_FUNCTIONS' then
        vars[i] = prepare_for_serialization(v, nil, include_functions)

      --Otherwise, add it to our variables array.
      else
        vars[i] = v
      end

    end
  end

  --Add a local array of serialized functions, which can be used to
  --reacreate the functions provided at deserailziation time.
  if include_functions and next(functions) ~= nil then
    vars._FUNCTIONS = functions
  end

  return vars
end

--
-- Recursively restores all serialized functions (_FUNCTION objects)
-- present in the given environment. 
--
function restore_functions(environment) 

  --Base case: restore each function in the top-level of the environment.
  if type(environment._FUNCTIONS) == 'table' then
    for i, v in pairs(environment._FUNCTIONS) do
      environment[i] = loadstring(from_base64(v))
    end
  end


  --Recursive case: if any member of the environment has its own functions,
  --restore those.
  for i, v in pairs(environment) do
    if type(v) == 'table' then
      restore_functions(v)
    end
  end

end

--
-- Retrieves the name of all 
function get_all_function_names(environment)

  local target = {}

  --Base case: restore each function in the top-level of the environment.
  if type(environment._FUNCTIONS) == 'table' then
    for i in pairs(environment._FUNCTIONS) do
      target[i] = true
    end
  end

  return target

end

-- Creates and returns a function, 'wrap(input)', which reads a string into 
-- a Lua chunk and executes it in a persistent sandbox environment, returning 
-- 'output, err' where 'output' is the combined output of print() and friends 
-- from within the chunk and 'err' is either nil or an error incurred while 
-- executing the chunk; or halting after 'maxlines' lines, 'maxcalls' levels 
-- of recursion, or 'maxtime' seconds.
function make_wrapper(maxlines, maxcalls, maxtime, base_environment, random_seed)
  
  local hook = make_hook(maxlines, maxcalls, maxtime)
  local env = make_sandbox(base_environment, random_seed)

  --Retreive a list of variables which only exist in the sandbox.
  --This is used to differentiate user variables from variables created by
  --the sandbox.
  local sandbox_variables = get_all_variable_names(env, base_environment, true)

  -- The actual 'wrap()' function.
  -- All of the above variables will be bound in its closure.
  function _wrap(chunkstr)

    local chunk, err, done
    
    -- Clear any leftover output/functions from the last call
    env._OUTPUT = ""
    err = nil
    
    -- Load the string into a chunk; fail on error
    chunk, err = loadstring(chunkstr)

    --If an error has occcurred, return it.
    if err ~= nil then
      return nil, err, base_environment
    end
    
    -- Set the chunk's environment, enable the debug hook, and execute it
    setfenv(chunk, env)
    co = coroutine.create(chunk)
    debug.sethook(co, hook, "crl")
    done, err = coroutine.resume(co)
    
    --If we were able to finish the 
    if done == true then
      err = nil
    end

    export_vars = prepare_for_serialization(env, sandbox_variables, true)

    -- Collect and return the results
    return env._OUTPUT, err, export_vars 

  end
  return _wrap
end

--
-- Attempts to create an initial environment for a given sandbox
-- given a JSON string.
--
function extract_initial_environment(first_line) 
  local base_environment = json.decode(first_line)

  --If we weren't able to correctly parse the json string as a table,
  --then return an empty table.
  if type(base_environment) ~= "table" then
    base_environment = {}
  end

  return base_environment
end

-- Listen on stdin for Lua chunks, parse and execute them, and print the 
-- results of each on stdout.
function main(arg)

  --If we weren't provided the correct amount of arguments, display usage information.
  if #arg ~= 3 then
    io.stderr:write(string.format("usage: %s MAXLINES MAXCALLS MAXTIME\n", arg[0]))
    os.exit(1)
  end
  
  -- Turn off buffering, and loop through the input
  io.stdout:setvbuf("no")
  
  -- Attempt to extract the base environment from the first line provided on the standard input.
  -- This will be used to set variables in the sandbox.
  local base_environment = extract_initial_environment(io.stdin:read("*l"))

  --Read a random seed from the second line provided on the standard input.
  local random_seed = tonumber(io.stdin:read("*l"))
  
  -- Create a wrapper function, wrap()
  local wrap = make_wrapper(tonumber(arg[1]), tonumber(arg[2]), tonumber(arg[3]), base_environment, random_seed)

  -- Parse lines until the file ends.
  while true do
    -- Read in a chunk
    local chunkstr = ""
    while true do

      local line = io.stdin:read("*l")

      if chunkstr == "" and line == nil then
        -- On EOF, exit.
        os.exit(0)

      elseif line == "." or line == nil then
        -- Finished this chunk; move on to the next step
        break
      elseif chunkstr ~= "" then
        chunkstr = chunkstr .. "\n" .. line
      else
        chunkstr = line
      end

    end

    -- Parse and execute the chunk
    local res, err
    res, err, state = wrap(chunkstr, env, hook)

    -- Write out the results
    if err == nil then
      io.stdout:write("'", res, "', true\n.\n")
    else
      io.stdout:write("'", err, "', false\n.\n")
    end
    
    --Write the final execution state to the standard output.
    io.stdout:write(json.encode(state), "\n")
 
  end
end

-- If called as a script instead of imported as a library, run main().
if arg ~= nil then
  main(arg)
end
