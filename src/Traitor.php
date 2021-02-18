<?php

declare(strict_types=1);

namespace ClassExtender;

use Traitor\Traitor as BaseTraitor;

class Traitor extends BaseTraitor
{
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
