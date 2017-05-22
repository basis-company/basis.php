<?php

namespace Basis;

use Tarantool\Mapper\Plugins\Spy;
use ReflectionClass;

class Event
{
    private $dispatcher;
    private $filesystem;
    private $service;
    private $spy;

    public function __construct(Dispatcher $dispatcher, Service $service, Spy $spy, Filesystem $filesystem)
    {
        $this->dispatcher = $dispatcher;
        $this->filesystem = $filesystem;
        $this->service = $service;
        $this->spy = $spy;
    }

    public function getSubscription()
    {
        $subscription = [];
        foreach ($this->filesystem->listClasses('Listeners') as $class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->isAbstract()) {
                continue;
            }
            foreach ($reflection->getStaticPropertyValue('events') as $event) {
                if (!array_key_exists($event, $subscription)) {
                    $subscription[$event] = [];
                }
                $subscription[$event][] = substr($class, strlen('Listeners\\'));
            }
        }

        return $subscription;
    }

    public function fireChanges()
    {
        if ($this->spy->hasChanges()) {
            // reduce changes list
            $changes = $this->spy->getChanges();
            foreach ($changes as $action => $collection) {
                foreach ($collection as $space => $entities) {
                    $event = $this->service->getName().'.'.$space.'.'.$action;

                    if (!$this->service->eventExists($event)) {
                        unset($collection[$space]);
                    }
                }
                if (!count($collection)) {
                    unset($changes->$action);
                }
            }

            if (count($changes)) {
                $this->dispatcher->dispatch('event.changes', [
                    'changes' => $changes,
                    'service' => $this->service->getName(),
                ]);
            }

            $this->spy->reset();
        }
    }
}
