<?php

declare(strict_types=1);

namespace ClassExtender;

class ClassExtender
{
    /**
     * @param string $trait
     */
    public static function extendClass($class): ClassExtendAdder
    {
        $instance = new ClassExtendAdder();

        return $instance->extendClasses([$class]);
    }

    /**
     * @param array $classes
     *
     * @return ClassExtendAdder
     */
    public function extendClasses(array $classes): ClassExtendAdder
    {
        $instance = new ClassExtendAdder();

        return $instance->extendClasses($classes);
    }

    /**
     * @param string $trait
     *
     * @return ClassExtendRemover
     */
    public static function removeExtendedClass($trait)
    {
        $instance = new ClassExtendRemover();

        return $instance->removeExtendedClasses([$trait]);
    }

    /**
     * Check if provided interface extends a specific interface.
     *
     * @param string $interfaceName
     * @param string $extendedInterfaceName
     * @return bool
     */
    public static function alreadyUsesInterface($interfaceName, $extendedInterfaceName)
    {
        $classReflection = new \ReflectionClass($interfaceName);

        if ($classReflection->isInterface()) {
            return in_array($extendedInterfaceName, $classReflection->getInterfaceNames());
        }

        return false;
    }
}
