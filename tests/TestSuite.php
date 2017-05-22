<?php

use Basis\Application;
use PHPUnit\Framework\TestCase;

class TestSuite extends TestCase
{
    public function setup()
    {
        $this->app = new Application(__DIR__.DIRECTORY_SEPARATOR.'example');
        chdir(__DIR__.DIRECTORY_SEPARATOR.'example'.DIRECTORY_SEPARATOR.'public');
    }
}
