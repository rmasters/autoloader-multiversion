<?php

namespace Rmasters\Filter\v2_0;

class Filter
{
    public static function filter($input)
    {
        return str_rot13($input);
    }
}
