<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Users as UsersRepo;

final class Status extends VKAPIRequestHandler
{
    function get(int $user_id = 0, int $group_id = 0)
    {
        $this->requireUser();
        if ($user_id == 0 && $group_id == 0) {
            return $this->getUser()->getStatus();
        } else {
            if ($group_id > 0)
                $this->fail(1, "OpenVK has no group statuses");
            else
                return (new UsersRepo)->get($user_id)->getStatus();
        }
    }

    function set(string $text, int $group_id = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if ($group_id > 0) {
            $this->fail(1, "OpenVK has no group statuses");
        } else {
            $this->getUser()->setStatus($text);
            $this->getUser()->save();
            
            return 1;
        }
    }
}
