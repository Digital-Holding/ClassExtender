<?php

declare(strict_types=1);

namespace ClassExtender;

use ReflectionClass;
use RuntimeException;
use BadMethodCallException;
use ClassExtender\Handlers\AbstractTreeHandler;
use Traitor\TraitUseAdder as BaseTraitUseAdder;

class TraitUseAdder extends BaseTraitUseAdder
{
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

            $content = $handler->handle()->toArray();
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

            $content = $handler->handleInterface()->toArray();
        }

        file_put_contents($filePath, implode($content));

        return $this;
    }
}
