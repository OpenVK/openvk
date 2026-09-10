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

        if (empty($result) && !empty($ids)) {
            $result[] = (object) [
                "cid"  => (int) ($ids[0] ?? 0),
                "name" => "",
            ];
        }

        return $result;
    }

    public function getCountryById(mixed $cids = ""): array
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

        $countryNames = [
            1 => "Россия",
            2 => "Украина",
            3 => "Беларусь",
            4 => "Казахстан",
            5 => "Азербайджан",
            6 => "Армения",
            7 => "Грузия",
            8 => "Израиль",
            9 => "США",
            10 => "Канада",
            11 => "Германия",
        ];

        $result = [];
        foreach ($ids as $id) {
            $cid = is_numeric($id) ? (int) $id : 0;
            if ($cid <= 0) {
                continue;
            }

            $name = $countryNames[$cid] ?? "Россия";
            $result[] = (object) [
                "cid"  => $cid,
                "name" => $name,
            ];
        }

        if (empty($result) && !empty($ids)) {
            $result[] = (object) [
                "cid"  => (int) ($ids[0] ?? 1),
                "name" => "Россия",
            ];
        }

        return $result;
    }

    public function checkin(
        int $place_id = 0,
        string $text = "",
        float $lat = 0.0,
        float $long = 0.0,
        int $friends_only = 0,
        string $services = ""
    ): mixed {
        $this->requireUser();
        $this->willExecuteWriteAction();
        return 1;
    }

    public function getCheckins(
        float $latitude = 0.0,
        float $longitude = 0.0,
        int $offset = 0,
        int $count = 20
    ): array {
        if (defined("VKAPI_DECL_VER_MAJOR") && VKAPI_DECL_VER_MAJOR < 5) {
            return [0];
        }
        return [
            "count" => 0,
            "items" => [],
        ];
    }
}
