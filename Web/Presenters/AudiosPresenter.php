<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\Audio;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\Repositories\Audios;
use Nette\InvalidStateException as ISE;

class AudiosPresenter extends OpenVKPresenter
{
    private $music;
    
    function __construct(Audios $music)
    {
        $this->music = $music;
        
        parent::__construct();
    }
    
    function renderApp(int $user = 0): void
    {
        $this->assertUserLoggedIn();
        
        $user = (new Users)->get($user === 0 ? $this->user->id : $user);
        if(!$user)
            $this->notFound();
        
        $this->template->user = $user;
    }
    
    function renderUpload(): void
    {
        $this->assertUserLoggedIn();
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            if(!isset($_FILES["blob"]))
                $this->flashFail("err", "Нету файла", "Выберите файл.");
            
            try {
                $audio = new Audio;
                $audio->setFile($_FILES["blob"]);
                $audio->setOwner($this->user->id);
                $audio->setCreated(time());
                if(!empty($this->postParam("name")))
                    $audio->setName($this->postParam("name"));
                if(!empty($this->postParam("performer")))
                    $audio->setPerformer($this->postParam("performer"));
                if(!empty($this->postParam("lyrics")))
                    $audio->setLyrics($this->postParam("lyrics"));
                if(!empty($this->postParam("genre")))
                    $audio->setGenre($this->postParam("genre"));
            } catch(ISE $ex) {
                $this->flashFaile("err", "Произшла ошибка", "Файл повреждён или имеет неверный формат.");
            }
            
            $audio->save();
            $audio->wire();
            
            $this->redirect("/audios" . $this->user->id, static::REDIRECT_TEMPORARY);
        }
    }
    
    function renderApiList(int $user, int $page = 1): void
    {
        $this->assertUserLoggedIn();
        
        header("Content-Type: application/json");
        
        $owner = (new Users)->get($user);
        if(!$owner) {
            header("HTTP/1.1 404 Not Found");
            exit(json_encode([
                "result"   => "error",
                "response" => [
                    "error" => [
                        "code" => 2 << 4,
                        "desc" => "No user with id = $user",
                    ],
                ],
            ]));
        }
        
        $music = [];
        foreach($this->music->getByUser($owner, $page) as $audio) {
            $music[] = [
                "id"        => $audio->getId(),
                "name"      => [
                    "actual" => $audio->getName(),
                    "full"   => $audio->getCanonicalName(),
                ],
                "performer" => $audio->getPerformer(),
                "genre"     => $audio->getGenre(),
                "lyrics"    => $audio->getLyrics(),
                "meta"      => [
                    "available_formats" => ["mp3"],
                    "user_unique_id"    => $audio->getVirtualId(),
                    "created"           => (string) $audio->getPublicationTime(),
                ],
                "files" => [
                    [
                        "format" => "mp3",
                        "url"    => $audio->getURL(),
                    ],
                ],
            ];
        }
        
        exit(json_encode([
            "result"   => "success",
            "method"   => "list",
            "response" => [
                "count" => $this->music->getUserAudiosCount($owner),
                "music" => $music,
                "page"  => $page,
            ],
        ]));
    }
}
