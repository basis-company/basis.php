<?php

use Basis\Logger;
use Fluent\Logger\FluentLogger;
use League\Container\Container;

class LoggerTest extends TestSuite
{
    public function testLogging()
    {
        $mock = $this->getMockBuilder(FluentLogger::class)
            ->setMethods(['post'])
            ->disableOriginalConstructor()
            ->getMock();

        $container = $this->app->get(Container::class);
        $container->share(FluentLogger::class, $mock);

        $mock->expects($this->once())
            ->method('post')
            ->with('example', ['key' => 'value'])
            ->will($this->returnValue('OK'));

        $this->assertSame(
            $container->get(Logger::class)->log(['key' => 'value']),
            'OK'
        );
    }
}