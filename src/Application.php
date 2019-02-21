<?php

namespace Basis;

use League\Container\Container;
use League\Container\ReflectionContainer;
use Tarantool\Mapper\Mapper;
use Tarantool\Mapper\Plugin\Annotation;
use Tarantool\Mapper\Plugin\Procedure as ProcedurePlugin;
use Tarantool\Mapper\Procedure;
use Tarantool\Mapper\Repository;

class Application extends Container
{
    public function __construct(string $root)
    {
        parent::__construct();

        $fs = new Filesystem($this, $root);

        $this->share(Application::class, $this);
        $this->share(Container::class, $this);
        $this->share(Filesystem::class, $fs);

        $this->addServiceProvider(Provider\ClickhouseProvider::class);
        $this->addServiceProvider(Provider\CoreProvider::class);
        $this->addServiceProvider(Provider\GuzzleProvider::class);
        $this->addServiceProvider(Provider\PoolProvider::class);
        $this->addServiceProvider(Provider\PredisProvider::class);
        $this->addServiceProvider(Provider\ServiceProvider::class);
        $this->addServiceProvider(Provider\TarantoolProvider::class);

        foreach ($fs->listClasses('Provider') as $provider) {
            $this->addServiceProvider($provider);
        }

        $this->delegate(new ReflectionContainer());
    }

    public function dispatch(string $job, array $params = [], string $service = null)
    {
        if ($service !== null) {
            if ($this->get(Service::class)->getName() == $service) {
                $service = null;
            }
        }

        return $this->get(Cache::class)
            ->wrap([$job, $params, $service], function() use ($job, $params, $service) {
                $runner = $this->get(Runner::class);
                if ($service === null) {
                    if ($runner->hasJob($job)) {
                        return $runner->dispatch($job, $params);
                    }
                    if (explode('.', $job)[0] == $this->get(Service::class)->getName()) {
                        return $runner->dispatch($job, $params);
                    }
                }

                $dispatcher = $this->get(Dispatcher::class);
                return $dispatcher->dispatch($job, $params, $service);
            });
    }

    public function get($alias, array $args = [])
    {
        if (!$this->hasShared($alias, true)) {
            $instance = null;
            if (is_subclass_of($alias, Procedure::class)) {
                $instance = $this->get(Mapper::class)
                    ->getPlugin(ProcedurePlugin::class)
                    ->get($alias);
            }
            if (is_subclass_of($alias, Repository::class)) {
                $spaceName = $this->get(Mapper::class)
                    ->getPlugin(Annotation::class)
                    ->getRepositorySpaceName($alias);

                if ($spaceName) {
                    $instance = $this->get(Mapper::class)->getRepository($spaceName);
                }
            }
            if ($instance) {
                $this->share($alias, function () use ($instance) {
                    return $instance;
                });
            }
        }
        return parent::get($alias, $args);
    }
}
