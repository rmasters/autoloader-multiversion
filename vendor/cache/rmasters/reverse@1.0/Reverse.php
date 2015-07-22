<?php

namespace Rmasters\Reverse\v1_0;

use Rmasters\Filter\v1_0\Filter;

class Reverse
{
    public static function reverse($input)
    {
        return Filter::filter($input);
    }
}
