<?php

declare(strict_types=1);

namespace openvk\Web\Models\Search;

use openvk\Web\Models\Repositories\{Users, Clubs, Posts, Videos, Applications, Audios, Documents};
use openvk\Web\Models\Search\Catalogue;
use openvk\Web\Models\Entities\User;

class InvalidSectionError extends \RuntimeException {}
class SearchState {
    private string $query;
    private string $section;
    private string $order_type;
    private bool $invert;
    public array $params;
    private $user;

    public function __construct(User $user, string $query, string $section, string $order_type, bool $invert = false)
    {
        $this->user = $user;
        $this->query = $query;
        $this->section = $section;
        $this->order_type = $order_type;
        $this->invert = $invert;
        $this->params = [];
    }

    public function buildParamsFromRequest(array $req): void
    {
        $this->params["ignore_private"] = true;

        foreach ($_REQUEST as $param_name => $param_value) {
            if (is_null($param_value)) {
                continue;
            }

            switch ($param_name) {
                default:
                    $this->params[$param_name] = $param_value;
                    break;
                case 'marital_status':
                case 'polit_views':
                    if ((int) $param_value == 0) {
                        break;
                    }
                    $this->params[$param_name] = $param_value;

                    break;
                case 'is_online':
                    if ((int) $param_value == 1) {
                        $this->params['is_online'] = 1;
                    }

                    break;
                case 'only_performers':
                    if ((int) $param_value == 1 || $param_value == 'on') {
                        $this->params['only_performers'] = true;
                    }

                    break;
                case 'with_lyrics':
                    if ($param_value == 'on' || $param_value == '1') {
                        $this->params['with_lyrics'] = true;
                    }

                    break;
                case 'from_me':
                    if ((int) $param_value != 1) {
                        break;
                    }
                    $this->params['from_me'] = $this->user->id;

                    break;
            }
        }
    }

    public function execute(int $page, int $perPage)
    {
        if ($this->section == "main") {
            $cat = new Catalogue($this->user);

            return $cat->search($this->query, $page);
        } else {
            $repos = [
                "groups"   => "Groups",
                "events"   => "Groups",
                "users"    => "Users",
                "posts"    => "Posts",
                "videos"   => "Videos",
                "audios"   => "Audios",
                "apps"     => "Apps",
                "audios_playlists" => "Audios",
                "docs" => "Documents",
            ];
            $repo = $repos[$this->section];

            if (!$repo) {
                throw new InvalidSectionError();
            }

            $results = null;

            switch ($section) {
                case 'groups':
                    $results  = (new Groups)->find($this->query, $this->params, ['type' => $this->order_type, 'invert' => $this->invert]);
                    break;
                case 'events':
                    $results  = (new Groups)->findEvents($this->query, $this->params, ['type' => $this->order_type, 'invert' => $this->invert]);
                    break;
                case 'audios_playlists':
                    $results  = (new Audios)->findPlaylists($this->query, $this->params, ['type' => $this->order_type, 'invert' => $this->invert]);
                    break;
                default:
                    $name = "openvk\\Web\\Models\\Repositories\\". $repo;
                    $results  = (new $name())->find($this->query, $this->params, ['type' => $this->order_type, 'invert' => $this->invert]);
                    break;
            }

            $iterator = $results->page($page, $perPage);
            $count    = $results->size();

            $res = iterator_to_array($iterator);

            return [$res, $count, sizeof($res)];
        }
    }
}
