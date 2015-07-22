<?php

namespace Rmasters\Filter;

class Filter
{
    public static function filter($input)
    {
        return str_rot13($input);
    }
}
