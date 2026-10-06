<?php

declare(strict_types=1);

namespace hello\Web\Presenters;

use Chandler\MVC\SimplePresenter;

final class HelloPresenter extends SimplePresenter
{
    /** @phpstan-latte-ignore leftover demo presenter: no route and no template */
    public function renderIndex(string $name = "Celestine"): void
    {
        $this->template->name = $name;
    }
}
