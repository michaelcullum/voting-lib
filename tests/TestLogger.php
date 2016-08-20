<?php

namespace Tests\Michaelc\Voting;

use Psr\Log\LoggerInterface as Logger;
use Psr\Log\LoggerTrait;

class TestLogger implements Logger
{
    use LoggerTrait;

    public function log($level, $message, array $context = array())
    {
        fputs(STDERR, $message);
        fputs(STDERR, "\r\n");
    }
}
