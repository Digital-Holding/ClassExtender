<?php

declare(strict_types=1);

namespace ClassExtender;

use Traitor\Traitor as BaseTraitor;

class Traitor extends BaseTraitor
{
    /**
     * @param string $trait
     *
     * @return TraitUseAdder
     */
    public static function addTrait($trait)
    {
        $instance = new TraitUseAdder();

        return $instance->addTraits([$trait]);
    }

    /**
     * @param array $traits
     *
     * @return TraitUseAdder
     */
    public static function addTraits($traits)
    {
        $instance = new TraitUseAdder();

        return $instance->addTraits($traits);
    }

    /**
     * @param string $trait
     *
     * @return TraitUseRemover
     */
    public static function removeTrait($trait)
    {
        $instance = new TraitUseRemover();

        return $instance->removeTraits([$trait]);
    }
}
