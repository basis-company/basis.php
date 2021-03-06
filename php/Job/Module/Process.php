<?php

namespace Basis\Job\Module;

use Basis\Converter;
use Basis\Job;
use OpenTelemetry\Tracing\Tracer;
use Psr\Log\LoggerInterface;

class Process extends Job
{
    public string $job;
    public ?object $params = null;
    public bool $logging = true;

    public function run(Converter $converter, LoggerInterface $logger)
    {
        $result = null;
        $success = false;

        try {
            $tracer = new Tracer();
            $tracer->getActiveSpan()->setName($this->app->getName() . '.' . $this->job);
            $this->getContainer()->share(Tracer::class, $tracer);

            $params = $converter->toArray($this->params);
            $result = $this->dispatch($this->job, $params);
            $success = true;

            if ($result && $this->logging) {
                if (!is_object($result) || count(get_object_vars($result))) {
                    $logger->info($result);
                }
            }

            $this->dispatch('module.changes', [ 'producer' => $this->job ]);
            $this->dispatch('module.housekeeping');
        } catch (Throwable $e) {
            $logger->info([
                'type' => 'exception',
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return compact('success', 'result');
    }
}
