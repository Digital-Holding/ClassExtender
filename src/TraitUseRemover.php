<?php

declare(strict_types=1);

namespace ClassExtender;

use ReflectionClass;
use RuntimeException;
use BadMethodCallException;
use ClassExtender\Handlers\AbstractTreeHandler;

class TraitUseRemover
{
    /** @var array */
    protected $traitReflections = [];

    /**
     * @param string $trait
     *
     * @return static
     */
    public function removeTrait($trait)
    {
        return $this->removeTraits([$trait]);
    }

    /**
     * @param array $traits
     *
     * @return static
     */
    public function removeTraits(array $traits)
    {
        foreach ($traits as $trait) {
            $this->traitReflections[] = new ReflectionClass($trait);
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
        if (count($this->traitReflections) == 0) {
            throw new BadMethodCallException("No traits to add were found. Call 'addTrait' first.");
        }

        $classReflection = new ReflectionClass($class);

        $filePath = $classReflection->getFileName();

        $content = file($filePath);

        foreach ($this->traitReflections as $traitReflection) {
            $handler = new AbstractTreeHandler(
                $content,
                $traitReflection->getName(),
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
        if (count($this->traitReflections) == 0) {
            throw new BadMethodCallException("No interfaces to add were found. Call 'addTrait' first.");
        }

        $interfaceReflection = new ReflectionClass($interface);

        $filePath = $interfaceReflection->getFileName();

        $content = file($filePath);

        foreach ($this->traitReflections as $traitReflection) {
            $handler = new AbstractTreeHandler(
                $content,
                $traitReflection->getName(),
                $interfaceReflection->getName()
            );

            $content = $handler->handleRemoveInterface()->toArray();
        }

        file_put_contents($filePath, implode($content));

        return $this;
    }
}
