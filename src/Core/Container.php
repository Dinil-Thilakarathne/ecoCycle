<?php

namespace Core;

use ReflectionClass;
use ReflectionParameter;

/**
 * Dependency Injection Container
 * 
 * Advanced dependency injection container with auto-wiring capabilities.
 * Similar to Laravel's service container functionality.
 * 
 * @package Core
 * @author Digital Waste Management Team
 * @version 1.0.0
 */
class Container
{
    /**
     * Container bindings
     * 
     * @var array
     */
    protected array $bindings = [];

    /**
     * Singleton instances
     * 
     * @var array
     */
    protected array $instances = [];

    /**
     * Shared instances
     * 
     * @var array
     */
    protected array $shared = [];

    /**
     * Bind an abstract to concrete implementation
     * 
     * @param string $abstract
     * @param callable|string|null $concrete
     * @param bool $shared
     * @return void
     */
    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'shared' => $shared
        ];
    }

    /**
     * Bind singleton instance
     * 
     * @param string $abstract
     * @param callable|string|null $concrete
     * @return void
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Bind existing instance
     * 
     * @param string $abstract
     * @param mixed $instance
     * @return void
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve abstract from container
     * 
     * @param string $abstract
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    public function make(string $abstract, array $parameters = [])
    {
        // Return existing instance if available
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get binding information
        $binding = $this->bindings[$abstract] ?? null;

        if ($binding) {
            $concrete = $binding['concrete'];
            $shared = $binding['shared'];
        } else {
            $concrete = $abstract;
            $shared = false;
        }

        // Build the instance
        $instance = $this->build($concrete, $parameters);

        // Store singleton instance
        if ($shared) {
            $this->instances[$abstract] = $instance;
        }

        return $instance;
    }

    /**
     * Build concrete instance
     * 
     * @param callable|string $concrete
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    protected function build($concrete, array $parameters = [])
    {
        // Handle callable
        if (is_callable($concrete)) {
            return call_user_func($concrete, $this);
        }

        // Handle class string
        if (is_string($concrete)) {
            return $this->buildClass($concrete, $parameters);
        }

        throw new \Exception("Invalid concrete type for dependency injection");
    }

    /**
     * Build class instance with dependency injection
     * 
     * @param string $className
     * @param array $parameters
     * @return object
     * @throws \Exception
     */
    protected function buildClass(string $className, array $parameters = []): object
    {
        // Auto-resolve controller namespace
        if (substr($className, -10) === 'Controller' && strpos($className, '\\') === false) {
            $className = 'Controllers\\' . $className;
        }

        try {
            $reflection = new ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new \Exception("Class {$className} not found");
        }

        if (!$reflection->isInstantiable()) {
            throw new \Exception("Class {$className} is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $className();
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters(), $parameters);

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Resolve constructor dependencies
     * 
     * @param array $parameters
     * @param array $primitives
     * @return array
     * @throws \Exception
     */
    protected function resolveDependencies(array $parameters, array $primitives = []): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $dependency = $this->resolveDependency($parameter, $primitives);
            $dependencies[] = $dependency;
        }

        return $dependencies;
    }

    /**
     * Resolve single dependency
     * 
     * @param ReflectionParameter $parameter
     * @param array $primitives
     * @return mixed
     * @throws \Exception
     */
    protected function resolveDependency(ReflectionParameter $parameter, array $primitives = [])
    {
        $name = $parameter->getName();

        // Check if primitive value provided
        if (isset($primitives[$name])) {
            return $primitives[$name];
        }

        // Get parameter type
        $type = $parameter->getType();

        if ($type === null) {
            // Try default value
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new \Exception("Cannot resolve dependency {$name}");
        }

        // Handle different PHP versions
        $typeName = '';
        $isBuiltin = false;

        if (method_exists($type, 'getName')) {
            $typeName = $type->getName();
            $isBuiltin = method_exists($type, 'isBuiltin') ? $type->isBuiltin() : false;
        } else {
            $typeName = (string) $type;
            $isBuiltin = in_array($typeName, ['string', 'int', 'float', 'bool', 'array', 'object']);
        }

        // Handle built-in types
        if ($isBuiltin) {
            if ($parameter->isDefaultValueAvailable()) {
                return $parameter->getDefaultValue();
            }

            throw new \Exception("Cannot resolve primitive dependency {$name}");
        }

        // Resolve class dependency
        return $this->make($typeName);
    }

    /**
     * Check if abstract is bound
     * 
     * @param string $abstract
     * @return bool
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Remove binding
     * 
     * @param string $abstract
     * @return void
     */
    public function forget(string $abstract): void
    {
        unset($this->bindings[$abstract], $this->instances[$abstract]);
    }

    /**
     * Get all bindings
     * 
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Resolve and call method with dependency injection
     * 
     * @param callable|array $callback
     * @param array $parameters
     * @return mixed
     */
    public function call($callback, array $parameters = [])
    {
        if (is_array($callback)) {
            list($class, $method) = $callback;

            if (is_string($class)) {
                $class = $this->make($class);
            }

            $callback = [$class, $method];
        }

        return call_user_func($callback, ...$parameters);
    }
}