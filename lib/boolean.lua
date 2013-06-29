--
-- Boolean Algebra Functions for Question Generation
-- TODO: Move me to some kind of meta-mechanism for importing modules?
--

require 'lib/bit'

local __author__ = 'Kyle Temkin'
local __version__ = '0.1'
local __license__ = 'BSD'


boolean = {}

--
-- Simple function to generate a random boolean function.
--
function boolean.random()
    return math.random(0, 1)
end

--Create a TruthTable "class".
boolean.TruthTable = {}
boolean.TruthTable_metatable = { __index = boolean.TruthTable }

local TruthTable = boolean.TruthTable

--
-- Creates a new TruthTable object.
-- 
function TruthTable:new(variables, fill_with)

    --Create the raw truth table object...
    truth_table = setmetatable({variables = variables}, boolean.TruthTable_metatable)
    
    --
    -- If we were provided mapping to fill the truth table with,
    -- apply it.
    --
    if fill_with then
        truth_table:fill(fill_with)
    end

    return truth_table
end

--
-- Returns the highest possible input number, which is a function of the number of variables.
--
function TruthTable:maximum_input()
    return 2 ^ #self.variables - 1
end

--
-- Fills the given truth table with the value of the given mapping.
--
function TruthTable:fill(mapping)
    for i = 0, self:maximum_input() do
        self[i] = mapping(i)
    end
end

--
-- Randomize the Boolean Function of the given truth table.
-- 
function TruthTable:randomize()
    for i = 0, self:maximum_input() do
        self[i] = boolean.random()
    end
end

--
-- Returns a string containing the Boolean algebra form of the minterm
-- with the given number.
--
function TruthTable:minterm(number)
    return self:term(number, 0, '') 
end

--
-- Returns a string containing the Boolean algebra form of the maxterm
-- with the given number.
--
function TruthTable:maxterm(number)
    return '(' .. self:term(number, 1, ' + ') .. ')'
end

--
-- Returns a custom-built Boolean algebra term.
-- number: The number of the term.
-- invert_when_equal_to: '1' iff the given term is active low (e.g. a maxterm). If this is '0', the relvant minterm will be returned.
--- separator: The operation to interject between literals.
--
function TruthTable:term(number, invert_when_equal_to, separator) 

    local term = '' 
    local bits = bit.tobits(number)

    --Iterate over each of the variables in the expression...
    for i = 1, #self.variables do

        --Add the variables' letter to the term...
        term = term .. self.variables[i]

        --Get the relevant bit.
        active_bit = bits[#self.variables - i + 1] or 0

        --Add an inverter if the given bit was one...
        if active_bit == invert_when_equal_to then
            term = term .. "'"
        end
        
        --And add the conjoining separators.
        term = term .. separator
    end

    return term:sub(1, term:len() - separator:len()) 

end

--
-- Generates a sum-of-minterms expression equivalent to the given TT. 
--
function TruthTable:to_sum_of_minterms()
    return self:to_expression(self.minterm, 1, ' + ')
end

--
-- Generates a product-of-maxterms expression equivalent to the given TT.
--
function TruthTable:to_product_of_maxterms() 
    return self:to_expression(self.maxterm, 0, '')
end

--
-- Function which generates an arbitrary boolean expression which includes all of the maxterms or minterms (or all of the Don't Cares, if applicable).
--
function TruthTable:to_expression(term_generator, include_when_equal_to, separator)

    local expression = '';

    --Iterate over all possible input values in the function...
    for i = 0, self:maximum_input() do

        --If the given term should be included...
        if self[i] == include_when_equal_to then

            --... add it to the expression.
            expression = expression .. term_generator(self, i) .. separator

        end
    end

    --And return the final expression.
    return expression:sub(1, expression:len() - separator:len())
end




