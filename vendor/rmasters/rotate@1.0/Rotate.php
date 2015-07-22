<?php

namespace Rmasters\Rotate;

use Rmasters\Filter\Filter;

class Rotate
{
    public static function rotate($input)
    {
        return Filter::filter($input);
    }
}
