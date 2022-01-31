<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;
use openvk\Web\Util\DateTime;

class EmailVerification extends PasswordReset
{
    protected $tableName = "email_verifications";
}
