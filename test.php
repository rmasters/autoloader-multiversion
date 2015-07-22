<?php

require_once __DIR__ . '/vendor/autoload.php';

use Rmasters\Reverse\Reverse;
use Rmasters\Rotate\Rotate;

echo sprintf("Reverse: %s\n", Reverse::reverse("Hello World"));
echo sprintf("Rotate: %s\n", Rotate::rotate("Hello World"));
