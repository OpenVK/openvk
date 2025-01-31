<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Traits;

use openvk\Web\Models\Entities\User;

trait TOwnable
{
    public function canBeViewedBy(?User $user = null): bool
    {
        # TODO: #950
        if ($this->isDeleted()) {
            return false;
        }

        return true;
    }

    public function canBeModifiedBy(User $user): bool
    {
        if (method_exists($this, "isCreatedBySystem")) {
            if ($this->isCreatedBySystem()) {
                return false;
            }
        }

        if ($this->getRecord()->owner > 0) {
            return $this->getRecord()->owner === $user->getId();
        } else {
            return $this->getOwner()->canBeModifiedBy($user);
        }
    }
}
