<?php

namespace Rmasters\Filter;

class Filter
{
    public static function filter($input)
    {
        return strrev($input);
    }
}
