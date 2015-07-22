<?php

namespace Rmasters\Rotate\v1_0;

use Rmasters\Filter\v2_0\Filter;

class Rotate
{
    public static function rotate($input)
    {
        return Filter::filter($input);
    }
}
