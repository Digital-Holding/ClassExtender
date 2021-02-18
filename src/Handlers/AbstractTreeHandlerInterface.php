<?php

declare(strict_types=1);

namespace ClassExtender\Handlers;

use Traitor\Handlers\Handler;

interface AbstractTreeHandlerInterface extends Handler
{
    public function handleRemove();

    public function handleInterface();

    public function handleRemoveInterface();
}
