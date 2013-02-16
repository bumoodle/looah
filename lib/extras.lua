--
-- Simple Lua functions which should fill common teacher needs.
-- 

extras = {}

--Create the MathScript between function, which produces a random number
--between two values.

extras.between = math.random

--
-- Picks a single one of its provided arguments.
--
extras.one_of = function (...)
  local t = {...}
  print( t[ math.random( #t ) ] )
end
