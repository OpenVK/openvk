<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;
use openvk\Web\Util\DateTime;

class EmailChangeVerification extends PasswordReset
{
    protected $tableName = "email_change_verifications";

    function getNewEmail(): string
    {
        return $this->getRecord()->new_email;
    }
}
