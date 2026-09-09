<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\Users as UsersRepo;

final class Places extends VKAPIRequestHandler
{
    public function getCityById(mixed $cids = ""): array
    {
        if (empty($cids)) {
            return [];
        }

        if (is_array($cids)) {
            $ids = $cids;
        } elseif (is_numeric($cids)) {
            $ids = [(int) $cids];
        } elseif (is_string($cids)) {
            $ids = explode(',', $cids);
        } else {
            $ids = [(string) $cids];
        }

        $usersRepo = new UsersRepo();
        $result = [];

        foreach ($ids as $id) {
            $cid = is_numeric($id) ? (int) $id : 0;
            if ($cid <= 0) {
                continue;
            }

            $user = $usersRepo->get($cid);
            $cityName = $user?->getCity();
            if (!empty($cityName)) {
                $result[] = (object) [
                    "cid"  => $cid,
                    "name" => $cityName,
                ];
            }
        }

        return $result;
    }
}
