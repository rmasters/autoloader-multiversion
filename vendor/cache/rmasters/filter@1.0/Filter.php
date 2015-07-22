<?php

namespace Rmasters\Filter\v1_0;

class Filter
{
    public static function filter($input)
    {
        return strrev($input);
    }
}
