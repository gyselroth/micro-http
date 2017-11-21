<?php
namespace Micro\Http\Testsuite;

use \Psr\Log\AbstractLogger;

class MockLogger extends AbstractLogger
{
    public $storage = [];

    public function log($level, $message, array $context = [])
    {
        $this->storage[] = [
            'level'     => $level,
            'message'   => $message,
            'context'   => $context,
        ];
    }
}
