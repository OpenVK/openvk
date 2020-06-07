<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\Video;
use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\Repositories\Videos;
use Nette\InvalidStateException as ISE;

final class VideosPresenter extends OpenVKPresenter
{
    private $videos;
    private $users;
    
    function __construct(Videos $videos, Users $users)
    {
        $this->videos = $videos;
        $this->users  = $users;
        
        parent::__construct();
    }
    
    function renderList(int $id): void
    {
        $user = $this->users->get($id);
        if(!$user) $this->notFound();
        
        $this->template->user   = $user;
        $this->template->videos = $this->videos->getByUser($user, $this->queryParam("p") ?? 1);
        $this->template->count  = $this->videos->getUserVideosCount($user);
        $this->template->paginatorConf = (object) [
            "count"   => $this->template->count,
            "page"    => $this->queryParam("p") ?? 1,
            "amount"  => NULL,
            "perPage" => OPENVK_DEFAULT_PER_PAGE,
        ];
    }
    
    function renderView(int $owner, int $vId): void
    {
        $user = $this->users->get($owner);
        if(!$user) $this->notFound();
        
        $this->template->user     = $user;
        $this->template->video    = $this->videos->getByOwnerAndVID($owner, $vId);
        $this->template->cCount   = $this->template->video->getCommentsCount();
        $this->template->cPage    = (int) ($this->queryParam("p") ?? 1);
        $this->template->comments = iterator_to_array($this->template->video->getComments($this->template->cPage));
    }
    
    function renderUpload(): void
    {
        $this->assertUserLoggedIn();
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            if(empty($this->postParam("name"))) {
                $video = new Video;
                $video->setOwner($this->user->id);
                $video->setName($this->postParam("name"));
                $video->setDescription($this->postParam("desc"));
                $video->setCreated(time());
                
                try {
                    if(isset($_FILES["blob"]) && file_exists($_FILES["blob"]["tmp_name"]))
                        $video->setFile($_FILES["blob"]);
                    else if(!empty($this->postParam("link")))
                        $video->setLink($this->postParam("link"));
                    else
                        $this->flashFail("err", "Нету видеозаписи", "Выберите файл или укажите ссылку.");
                } catch(ISE $ex) {
                    $this->flashFail("err", "Произошла ошибка", "Возможно, ссылка некорректна.");
                }
                
                $video->save();
                
                $this->redirect("/video" . $video->getPrettyId(), static::REDIRECT_TEMPORARY);
            }else{
                $this->flashFail("err", "Произошла ошибка", "Видео не может быть опубликовано без названия.");
            }
        }
    }
    
    function renderEdit(int $owner, int $vId): void
    {
        $this->assertUserLoggedIn();
        
        $video = $this->videos->getByOwnerAndVID($owner, $vId);
        if(!$video)
            $this->notFound();
        if(is_null($this->user) || $this->user->id !== $owner)
            $this->flashFail("err", "Ошибка доступа", "Вы не имеете права редактировать этот ресурс.");
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            $video->setName(empty($this->postParam("name")) ? NULL : $this->postParam("name"));
            $video->setDescription(empty($this->postParam("desc")) ? NULL : $this->postParam("desc"));
            $video->save();
            
            $this->flash("succ", "Изменения сохранены", "Обновлённое описание появится на странице с видосиком.");
            $this->redirect("/video" . $video->getPrettyId(), static::REDIRECT_TEMPORARY);
        } 
        
        $this->template->video = $video;
    }
}
