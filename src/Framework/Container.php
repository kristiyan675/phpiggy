<?php

declare(strict_types=1);

namespace Framework;

use ReflectionClass, ReflectionNamedType;
use Framework\Exceptions\ContainerException;

class Container
{
    private array $definitions = [];
    private array $resolved = [];

    public function addDefinitions(array $newDefinitions)
    {
        $this->definitions = [...$this->definitions, ...$newDefinitions];
    }

    public function resolve(string $className)
    {
        $reflectionClass = new ReflectionClass($className);

        if (!$reflectionClass->isInstantiable()) {
            throw new ContainerException("Not instantiable");
        }

        $constructor = $reflectionClass->getConstructor();

        if (!$constructor) {
            return new $className;
        }

        $params = $constructor->getParameters();

        if (count($params) === 0) {
            return new $className;
        }

        $dependecies = [];

        foreach ($params as $param) {
            $name = $param->getName();
            $type = $param->getType();


            if (!$type) {
                throw new ContainerException("Failed to resolve class.");
            }

            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                throw new ContainerException("Failed to resolve class because invalid param name.");
            }

            $dependecies[] = $this->get($type->getName());
        }
        return $reflectionClass->newInstanceArgs($dependecies);
    }


    public function get(string $id)
    {
        if (!array_key_exists($id, $this->definitions)) {
            throw new ContainerException("No factory function for this {$id}");
        }

        if (array_key_exists($id, $this->resolved)) {
            return $this->resolved[$id];
        }

        $factory = $this->definitions[$id];
        $dependency = $factory();

        $this->resolved[$id] = $dependency;

        return $dependency;
    }
}
