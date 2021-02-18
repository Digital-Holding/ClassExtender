<?php

declare(strict_types=1);

namespace ClassExtender;

use ReflectionClass;
use RuntimeException;
use BadMethodCallException;
use ClassExtender\Handlers\AbstractTreeHandler;

class ClassExtendAdder
{
    /** @var array */
    protected $externalClassReflections = [];

    /**
     * @param string $class
     *
     * @return static
     */
    public function extendClass($class)
    {
        return $this->extendClasses([$class]);
    }

    /**
     * @param array $classes
     *
     * @return static
     */
    public function extendClasses(array $classes)
    {
        foreach ($classes as $class) {
            $this->externalClassReflections[] = new ReflectionClass($class);
        }

        return $this;
    }

    /**
     * @param string $class
     *
     * @throws BadMethodCallException
     * @throws RuntimeException
     *
     * @return $this
     */
    public function toClass($class)
    {
        if (count($this->externalClassReflections) == 0) {
            throw new BadMethodCallException("No traits to add were found. Call 'addTrait' first.");
        }

        $classReflection = new ReflectionClass($class);

        $filePath = $classReflection->getFileName();

        $content = file($filePath);

        foreach ($this->externalClassReflections as $externalClassReflection) {
            $handler = new AbstractTreeHandler(
                $content,
                $externalClassReflection->getName(),
                $externalClassReflection->getName()
            );

            $content = $handler->handle()->toArray();
        }

        file_put_contents($filePath, implode($content));

        return $this;
    }

    /**
     * @param string $interface
     *
     * @throws BadMethodCallException
     * @throws RuntimeException
     *
     * @return $this
     */
    public function toInterface($interface)
    {
        if (count($this->externalClassReflections) == 0) {
            throw new BadMethodCallException("No interfaces to add were found. Call 'addTrait' first.");
        }

        $interfaceReflection = new ReflectionClass($interface);

        $filePath = $interfaceReflection->getFileName();

        $content = file($filePath);

        foreach ($this->externalClassReflections as $externalClassReflection) {
            $handler = new AbstractTreeHandler(
                $content,
                $externalClassReflection->getName(),
                $externalClassReflection->getName()
            );

            $content = $handler->handleInterface()->toArray();
        }

        file_put_contents($filePath, implode($content));

        return $this;
    }
}
