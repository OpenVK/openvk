<?php

declare(strict_types=1);

namespace openvk\Web\Models\Search;

use openvk\Web\Models\Repositories\{Users, Clubs, Posts, Videos, Applications, Audios, Documents};
use openvk\Web\Models\Entities\User;

class Catalogue
{
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function search(string $query = ""): array
    {
        $dict = [
            "users" => [],
            "groups" => [],
            "photos" => [],
            "videos" => [],
            "audios" => [],
        ];

        if ($query == "") {
            $catalogues = OPENVK_ROOT_CONF["openvk"]["preferences"]["catalogues"];
            $catalogue = $catalogues[0];
            $items = $catalogue["items"];
            $div = [[], []];

            foreach ($items as $item) {
                if ($item > 0) {
                    $div[0][] = $item;
                } else {
                    $div[1][] = abs($item);
                }
            }

            $dict["users"] = (new Users())->getByIds($div[0]);
            $dict["groups"] = (new Clubs())->getByIds($div[0]);
        }

        return [(object) $dict, 1];
    }
}
