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
extras.one_of = function (t)
  return ( t[ math.random( #t ) ] )
end

--
-- Shuffles the provided table.
-- Source: http://developer.coronalabs.com/forum/2011/03/23/shuffling-table-values
--
extras.shuffle = function (array)
    local arrayCount = #array
    for i = arrayCount, 2, -1 do
        local j = math.random(1, i)
        array[i], array[j] = array[j], array[i]
    end
    return array
end

--
-- Formats the given number using a Lua string formatting display base.
--
function format_and_pad(input, format, length)

    --Assume a null length if no length was provided.
    if length then
        length = '0' .. length
    else
        length = ''
    end

    --Zero pad the string, if required.
    return string.format("%" .. length .. format, input)

end

--
-- Converts a decimal number to a binary string, optionally padding
-- to the provided length.
-- 
extras.decbin = function (int, length) 

    --Assume a null length if no length was provided.
    if length then
        length = '0' .. length
    else
        length = ''
    end

    --Convert the integer into a table of bits...
    bin = List(bit.tobits(int))

    --Then merge that into a string.
    bin = bin:reverse():concat()

    --If we wound up with an empty string, replace it with 0.
    if bin == '' then
        bin = 0
    end

    --Zero pad the string, if required.
    return string.format("%" .. length .. "d", bin)
end

--
-- Converts a decimal number to a hex string, optionally padding
-- to the provided length.
--
extras.dechex = function(int, length) 
    return format_and_pad(int, 'x', length)
end

extras.decoct = function(int, length)
    return format_and_pad(int, 'o', length)
end


--
-- Generates a function, which picks unique random numbers from within a range.
--
extras.generate_unique_pool = function (low, high)

    --Create the unique value pool...
    local pool = tablex.range(low, high)

    --Replace the unique_value function with a closure over the unique value pool.
    return function() 
        --Remove a random element from the pool, and return it.
        return table.remove(pool, math.random(1, #pool))
    end
end

--
-- Alternate name for the above.
--
extras.geneate_unique_numbers_between = extras.generate_unique_pool

--
-- Round a number to the specified number of decimal places.
-- Source: http://lua-users.org/wiki/SimpleRound
--
extras.round = function (num, idp)
  local mult = 10^(idp or 0)
  return math.floor(num * mult + 0.5) / mult
end
