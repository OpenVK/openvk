<?php

declare(strict_types=1);

namespace openvk\Web\Events;

interface ILPEmitable
{
    public function getLongPoolSummary(): object;
}
