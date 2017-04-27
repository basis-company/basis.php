<?php

namespace Basis;

use Tarantool\Mapper\Plugins\Spy;

class Event
{
    private $dispatcher;

    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function fire($event, $context)
    {
        $this->dispatcher->dispatch('event.fire', [
            'event' => $event,
            'context' => $context
        ]);
    }

    public function subscribe($event, $listener)
    {
        $this->dispatcher->dispatch('event.subscribe', [
            'event' => $event,
            'listener' => $listener,
        ]);
    }

    public function fireDataChange(Spy $spy)
    {
        if($spy->hasChanges()) {
            $this->dispatcher->dispatch('event.changes', $spy->getChanges());
        }
    }
}