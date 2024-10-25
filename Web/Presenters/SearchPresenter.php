<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\{User, Club};
use openvk\Web\Models\Repositories\{Users, Clubs, Posts, Videos, Applications, Audios};
use Chandler\Database\DatabaseConnection;

final class SearchPresenter extends OpenVKPresenter
{
    private $users;
    private $clubs;
    private $posts;
    private $videos;
    private $apps;
    private $audios;
    
    function __construct()
    {
        $this->users    = new Users;
        $this->clubs    = new Clubs;
        $this->posts    = new Posts;
        $this->videos   = new Videos;
        $this->apps     = new Applications;
        $this->audios   = new Audios;
        
        parent::__construct();
    }
    
    function renderIndex(): void
    {
        $this->assertUserLoggedIn();

        $query     = $this->queryParam("q") ?? "";
        $section   = $this->queryParam("section") ?? "users";
        $order     = $this->queryParam("order") ?? "id";
        $invert    = (int) ($this->queryParam("invert") ?? 0) == 1;
        $page      = (int) ($this->queryParam("p") ?? 1);

        # https://youtu.be/pSAWM5YuXx8
        # https://youtu.be/FfNZRhIn2Vk

        $repos = [ 
            "groups"   => "clubs", 
            "users"    => "users",
            "posts"    => "posts",
            "videos"   => "videos",
            "audios"   => "audios",
            "apps"     => "apps",
            "audios_playlists" => "audios"
        ];
        $parameters = [
            "ignore_private" => true,
        ];

        foreach($_REQUEST as $param_name => $param_value) {
            if(is_null($param_value)) continue;
            
            switch($param_name) {
                default:
                    $parameters[$param_name] = $param_value;
                    break;
                case 'marital_status':
                case 'polit_views':
                    if((int) $param_value == 0) continue;
                    $parameters[$param_name] = $param_value;

                    break;
                case 'is_online':
                    if((int) $param_value == 1)
                        $parameters['is_online'] = 1;
                    
                    break;
                case 'only_performers':
                    if((int) $param_value == 1 || $param_value == 'on')
                        $parameters['only_performers'] = true;

                    break;
                case 'with_lyrics':
                    if($param_value == 'on' || $param_value == '1')
                        $parameters['with_lyrics'] = true;

                    break;
                # дай бог работал этот case
                case 'from_me':
                    if((int) $param_value != 1) continue;
                    $parameters['from_me'] = $this->user->id;

                    break;
            }
        }

        $repo = $repos[$section] or $this->throwError(400, "Bad Request", "Invalid search entity $section.");
        
        $results = NULL;
        switch($section) {
            default:
                $results  = $this->{$repo}->find($query, $parameters, ['type' => $order, 'invert' => $invert]);
                break;
            case 'audios_playlists':
                $results  = $this->{$repo}->findPlaylists($query, $parameters, ['type' => $order, 'invert' => $invert]);
                break;
        }
        
        $iterator = $results->page($page, OPENVK_DEFAULT_PER_PAGE);
        $count    = $results->size();
        
        $this->template->order    = $order;
        $this->template->invert   = $invert;
        $this->template->data     = $this->template->iterator = iterator_to_array($iterator);
        $this->template->count    = $count;
        $this->template->section  = $section;
        $this->template->page     = $page;
        $this->template->perPage  = OPENVK_DEFAULT_PER_PAGE;
        $this->template->query    = $query;
        $this->template->atSearch = true;

        $this->template->paginatorConf = (object) [
            "page"      => $page,
            "count"     => $count,
            "amount"    => sizeof($this->template->data),
            "perPage"   => $this->template->perPage,
            "atBottom"  => false,
            "tidy"      => true,
            "space"     => 6,
            'pageCount' => ceil($count / $this->template->perPage),
        ];
        $this->template->extendedPaginatorConf = clone $this->template->paginatorConf;
        $this->template->extendedPaginatorConf->space = 12;
    }
}
