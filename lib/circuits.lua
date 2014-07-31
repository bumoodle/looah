--
-- Circuits Functions for Question Generation
-- TODO: Move me to some kind of meta-mechanism for importing modules?
--

local __author__ = 'Kyle Temkin'
local __version__ = '0.1'
local __license__ = 'BSD'


circuits = {}

--
-- Constants that define the preferred values for Circuits.
--
circuits.E6_VALUES  = {1.0, 1.5, 2.2, 3.3, 4.7, 6.8}
circuits.E12_VALUES = {1.0, 1.2, 1.5, 1.8, 2.2, 2.7, 3.3, 3.9, 4.7, 5.6, 6.8, 8.2}
circuits.E24_VALUES = {1.0, 1.1, 1.2, 1.3, 1.5, 1.6, 1.8, 2.0, 2.2, 2.4, 2.7, 3.0,
                       3.3, 3.6, 3.9, 4.3, 4.7, 5.1, 5.6, 6.2, 6.8, 7.5, 8.2, 9.1}  

--
-- Returns a random preferred value, 
--
function circuits.preferred_value_from(list, decade)
    decade = decade or 1
    return ( list[ math.random( #list ) ] ) * decade
end

--
-- Convenience functions.
--
function circuits.e6_value(decade)
    return circuits.preferred_value_from(circuits.E6_VALUES, decade)
end

function circuits.e12_value(decade)
    return circuits.preferred_value_from(circuits.E12_VALUES, decade)
end

function circuits.e24_value(decade)
    return circuits.preferred_value_from(circuits.E24_VALUES, decade)
end

function circuits.parallel(a, b)

  --If we have a table as our first argument, process it
  --as a list of arguments.
  if type(a) == "table" then

    conductance = 0

    --Compute the sum of all of the "conductances"...
    for index, resistance in ipairs(a) do
        conductance = conductance + (1 / resistance)
    end

    --... and convert back to a resistance.
    return 1 / conductance

  else
    return 1 / (1 / a + 1 / b)
  end 
end


