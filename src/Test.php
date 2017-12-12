<?php

namespace Basis;

use PHPUnit\Framework\TestCase;

abstract class Test extends TestCase
{
    use Toolkit;

    public $params = [];

    public function setup()
    {
        $this->app = new class(getcwd(), $this) extends Application {
            public function __construct(string $root, Test $testInstance)
            {
                parent::__construct($root);
                $this->testInstance = $testInstance;
            }
            public function dispatch(string $job, array $params = [], string $service = null)
            {
                if (array_key_exists($job, $this->testInstance->mocks)) {
                    $mocks = $this->testInstance->mocks[$job];
                    $valid = null;
                    foreach ($mocks as $mock) {
                        if ($mock->params == $params || (!$mock->params && !$valid)) {
                            $valid = $mock;
                        }
                    }
                    if ($valid) {
                        return is_callable($valid->result) ? ($valid->result)() : $valid->result;
                    }
                }
                return parent::dispatch($job, $params, $service);
            }
        };
        $this->dispatch('tarantool.migrate');
    }

    public function tearDown()
    {
        $this->dispatch('tarantool.clear');
    }

    public $mocks = [];
    public function mock(string $job, array $params = [])
    {
        if (!array_key_exists($job, $this->mocks)) {
            $this->mocks[$job] = [];
        }

        $mock = new class {
            public $params;
            public $result;
            public function withParams($params)
            {
                $this->params = $params;
                return $this;
            }
            public function willReturn($result)
            {
                $this->result = $result;
                return $this;
            }
        };

        if (count($params)) {
            $mock->params = $params;
        }

        $this->mocks[$job][] = $mock;

        return $mock;
    }
}
