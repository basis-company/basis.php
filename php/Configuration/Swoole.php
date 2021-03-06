<?php

namespace Basis\Configuration;

use Basis\Application;
use Basis\Container;
use Psr\Log\LoggerInterface;
use Swoole\Http\Server;

class Swoole
{
    public function init(Container $container)
    {
        $container->share(Server::class, function () use ($container) {

            $host = getenv('SWOOLE_HTTP_SERVER_HOST') ?: "0.0.0.0";
            $port = getenv('SWOOLE_HTTP_SERVER_PORT') ?: "80";

            $server = new Server($host, $port);
            $server->set([
                'buffer_output_size' => getenv('SWOOLE_HTTP_SERVER_BUFFER_OUTPUT_SIZE') ?: 128 * 1024 * 1024,
                'document_root' => getcwd(),
                'enable_static_handler' => true,
                'http_compression' => false,
                'http_parse_post' => true,
                'max_request' => getenv('SWOOLE_HTTP_SERVER_MAX_REQUEST') ?: 128,
                'open_http2_protocol' => getenv('SWOOLE_HTTP_SERVER_HTTP2') == 'true',
                'open_http_protocol' => true,
                'open_length_check' => false,
                'package_max_length' => getenv('SWOOLE_PACKAGE_MAX_LENGTH') ?: 128 * 1024 * 1024,
                'reactor_num' => getenv('SWOOLE_HTTP_SERVER_REACTOR_NUM') ?: 1,
                'worker_num' => getenv('SWOOLE_HTTP_SERVER_WORKER_NUM') ?: 8,
            ]);

            $server->on("start", function () use ($container, $host, $port) {
                $container->get(LoggerInterface::class)
                    ->info([
                        'message' => 'server started',
                        'url' => "http://$host:$port",
                    ]);
            });

            $server->on("shutdown", function () use ($container) {
                $container->get(LoggerInterface::class)
                    ->info([
                        'message' => 'server stopped',
                    ]);
            });

            return $server;
        });
    }
}
