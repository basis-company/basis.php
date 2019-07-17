<?php

namespace Basis\Controller;

use Basis\Application;
use Basis\Context;
use Basis\Event;
use Basis\Runner;
use Basis\Service;
use Basis\Toolkit;
use Exception;

class Api
{
    use Toolkit;

    public function __process()
    {
        return $this->index();
    }

    public function index()
    {
        if (!array_key_exists('rpc', $_REQUEST)) {
            return [
                'success' => false,
                'message' => 'No rpc defined',
            ];
        }
        $data = json_decode($_REQUEST['rpc']);

        if (!$data) {
            return [
                'success' => false,
                'message' => 'Invalid rpc format',
            ];
        }

        if ($data->context) {
            $this->get(Context::class)->apply($data->context);
        }

        $request = is_array($data) ? $data : [$data];

        $response = [];
        foreach ($request as $rpc) {
            $start = microtime(1);
            $result = $this->process($rpc);
            if (is_null($result)) {
                $result = [];
            }
            $result['timing'] = microtime(1) - $start;
            if (property_exists($rpc, 'tid')) {
                $result['tid'] = $rpc->tid;
            }
            $response[] = $result;
        }

        try {
            $this->get(Event::class)->fireChanges($request[0]->job);
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Fire changes failure: '.$e->getMessage()];
        }

        return is_array($data) ? $response : $response[0];
    }

    private function process($rpc)
    {
        if (!property_exists($rpc, 'job')) {
            return [
                'success' => false,
                'message' => 'Invalid rpc format: no job',
            ];
        }

        if (!property_exists($rpc, 'params')) {
            return [
                'success' => false,
                'message' => 'Invalid rpc format: no params',
            ];
        }

        try {
            $params = is_object($rpc->params) ? get_object_vars($rpc->params) : [];
            $data = $this->dispatch(strtolower($rpc->job), $params);
            $data = $this->removeSystemObjects($data);

            return [
                'success' => true,
                'data' => $data,
            ];
        } catch (Exception $e) {
            $error = [
                'success' => false,
                'message' => $e->getMessage(),
                'service' => $this->get(Service::class)->getName(),
                'trace' => explode(PHP_EOL, $e->getTraceAsString()),
            ];
            if (property_exists($e, 'remoteTrace')) {
                $error['remoteTrace'] = $e->remoteTrace;
            }
            return $error;
        }
    }

    private function removeSystemObjects($data)
    {
        if (!$data) {
            return [];
        }

        if (is_object($data)) {
            $data = get_object_vars($data);
        }

        foreach ($data as $k => $v) {
            if (is_array($v) || is_object($v)) {
                if ($v instanceof Application) {
                    unset($data[$k]);
                } else {
                    $data[$k] = $this->removeSystemObjects($v);
                }
            }
        }

        return $data;
    }
}
