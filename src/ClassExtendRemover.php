<?php

declare(strict_types=1);

namespace ClassExtender;

use ReflectionClass;
use RuntimeException;
use BadMethodCallException;
use ClassExtender\Handlers\AbstractTreeHandler;

class ClassExtendRemover
{
    /** @var array */
    protected $externalClassReflections = [];

    /**
     * @param string $class
     *
     * @return static
     */
    public function removeExtendedClass($class)
    {
        return $this->removeExtendedClasses([$class]);
    }

    /**
     * @param array $class
     *
     * @return static
     */
    public function removeExtendedClasses(array $classes)
    {
        foreach ($classes as $class) {
            $this->externalClassReflections[] = new ReflectionClass($class);
        }

        return $this;
    }

    /**
     * @param string $class
     *
     * @return $this
     * @throws RuntimeException
     *
     * @throws BadMethodCallException
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
                $classReflection->getName()
            );

            $content = $handler->handleRemove()->toArray();
        }

        file_put_contents($filePath, implode($content));

        return $this;
    }

    /**
     * @param string $interface
     *
     * @return $this
     * @throws RuntimeException
     *
     * @throws BadMethodCallException
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
                $interfaceReflection->getName()
            );

            $content = $handler->handleRemoveInterface()->toArray();
        }

        file_put_contents($filePath, implode($content));

        return $this;
    }
}
