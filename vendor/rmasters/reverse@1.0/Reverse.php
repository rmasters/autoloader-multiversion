<?php

namespace Rmasters\Reverse;

use Rmasters\Filter\Filter;

class Reverse
{
    public static function reverse($input)
    {
        return Filter::filter($input);
    }
}
