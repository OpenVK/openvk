<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\{Club, Photo};
use openvk\Web\Models\Entities\Notifications\ClubModeratorNotification;
use openvk\Web\Models\Repositories\{Clubs, Users, Albums, Managers};

final class GroupPresenter extends OpenVKPresenter
{
    private $clubs;
    
    function __construct(Clubs $clubs)
    {
        $this->clubs = $clubs;
        
        parent::__construct();
    }
    
    function renderView(int $id): void
    {
        $club = $this->clubs->get($id);
        if(!$club) {
            $this->notFound();
        } else {
            if($club->getShortCode())
                if(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH) !== "/" . $club->getShortCode())
                    $this->redirect("/" . $club->getShortCode(), static::REDIRECT_TEMPORARY_PRESISTENT);
            
            $this->template->club        = $club;
            $this->template->albums      = (new Albums)->getClubAlbums($club, 1, 3);
            $this->template->albumsCount = (new Albums)->getClubAlbumsCount($club);
        }
    }
    
    function renderCreate(): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            if(!empty($this->postParam("name")))
            {
                $club = new Club;
                $club->setName($this->postParam("name"));
                $club->setAbout(empty($this->postParam("about")) ? NULL : $this->postParam("about"));
                $club->setOwner($this->user->id);
                
                try {
                    $club->save();
                } catch(\PDOException $ex) {
                    if($ex->getCode() == 23000)
                        $this->flashFail("err", "Ошибка", "Произошла ошибка на стороне сервера. Обратитесь к системному администратору.");
                    else
                        throw $ex;
                }
                
                $club->toggleSubscription($this->user->identity);
                header("HTTP/1.1 302 Found");
                header("Location: /club" . $club->getId());
            }else{
                $this->flashFail("err", "Ошибка", "Вы не ввели название группы.");
            }
        }
    }
    
    function renderSub(): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        
        if($_SERVER["REQUEST_METHOD"] !== "POST") exit("Invalid state");
        
        $club = $this->clubs->get((int) $this->postParam("id"));
        if(!$club) exit("Invalid state");
        
        $club->toggleSubscription($this->user->identity);
        
        header("HTTP/1.1 302 Found");
        header("Location: /club" . $club->getId());
        exit;
    }
    
    function renderFollowers(int $id): void
    {
        $this->assertUserLoggedIn();
        
        $this->template->paginatorConf = (object) [
            "count"   => $this->template->count,
            "page"    => $this->queryParam("p") ?? 1,
            "amount"  => NULL,
            "perPage" => OPENVK_DEFAULT_PER_PAGE,
        ];

        $this->template->club              = $this->clubs->get($id);
        $this->template->onlyShowManagers  = $this->queryParam("onlyAdmins") == "1";
        if($this->template->onlyShowManagers) {
            $this->template->followers     = null;

            $this->template->managers     = $this->template->club->getManagers((int) ($this->queryParam("p") ?? 1), !$this->template->club->canBeModifiedBy($this->user->identity));
            if($this->template->club->canBeModifiedBy($this->user->identity) || !$this->template->club->isOwnerHidden()) {
                $this->template->managers  = array_merge([$this->template->club->getOwner()], iterator_to_array($this->template->managers));
            }

            $this->template->count         = $this->template->club->getManagersCount();
            return;
        }

        $this->template->followers         = $this->template->club->getFollowers((int) ($this->queryParam("p") ?? 1));
        $this->template->managers          = null;
        $this->template->count             = $this->template->club->getFollowersCount();
    }
    
    function renderModifyAdmin(int $id): void
    {
        $user = is_null($this->queryParam("user")) ? $this->postParam("user") : $this->queryParam("user");
        $comment = $this->postParam("comment");
        $removeComment = $this->postParam("removeComment") === "1";
        $hidden = ["0" => false, "1" => true][$this->queryParam("hidden")] ?? null;
        //$index = $this->queryParam("index");
        if(!$user)
            $this->badRequest();
        
        $club = $this->clubs->get($id);
        $user = (new Users)->get((int) $user);
        if(!$user || !$club)
            $this->notFound();
        
        if(!$club->canBeModifiedBy($this->user->identity ?? NULL))
            $this->flashFail("err", "Ошибка доступа", "У вас недостаточно прав, чтобы изменять этот ресурс.");

        if(!is_null($hidden)) {
            if($club->getOwner()->getId() == $user->getId()) {
                $club->setOwner_Hidden($hidden);
                $club->save();
            } else {
                $manager = (new Managers)->getByUserAndClub($user->getId(), $club->getId());
                $manager->setHidden($hidden);
                $manager->save();
            }

            if($club->getManagersCount(true) == 0) {
                $club->setAdministrators_List_Display(2);
                $club->save();
            }

            if($hidden) {
                $this->flashFail("succ", "Операция успешна", "Теперь " . $user->getCanonicalName() . " будет показываться как обычный подписчик всем кроме других администраторов");
            } else {
                $this->flashFail("succ", "Операция успешна", "Теперь все будут знать про то что " . $user->getCanonicalName() . " - администратор");
            }
        } elseif($removeComment) {
            if($club->getOwner()->getId() == $user->getId()) {
                $club->setOwner_Comment(null);
                $club->save();
            } else {
                $manager = (new Managers)->getByUserAndClub($user->getId(), $club->getId());
                $manager->setComment(null);
                $manager->save();
            }

            $this->flashFail("succ", "Операция успешна", "Комментарий к администратору удален");
        } elseif($comment) {
            if(strlen($comment) > 36) {
                $commentLength = (string) strlen($comment);
                $this->flashFail("err", "Ошибка", "Комментарий слишком длинный ($commentLength символов вместо 36 символов)");
                return;
            }

            if($club->getOwner()->getId() == $user->getId()) {
                $club->setOwner_Comment($comment);
                $club->save();
            } else {
                $manager = (new Managers)->getByUserAndClub($user->getId(), $club->getId());
                $manager->setComment($comment);
                $manager->save();
            }

            $this->flashFail("succ", "Операция успешна", "Комментарий к администратору изменён");
        }else{
            if($club->canBeModifiedBy($user)) {
                $club->removeManager($user);
                $this->flashFail("succ", "Операция успешна", $user->getCanonicalName() . " более не администратор.");
            } else {
                $club->addManager($user);
                
                (new ClubModeratorNotification($user, $club, $this->user->identity))->emit();
                $this->flashFail("succ", "Операция успешна", $user->getCanonicalName() . " назначен(а) администратором.");
            }
        }
        
    }
    
    function renderEdit(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        
        $club = $this->clubs->get($id);
        if(!$club->canBeModifiedBy($this->user->identity))
            $this->notFound();
        else
            $this->template->club = $club;
            
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            $club->setName(empty($this->postParam("name")) ? $club->getName() : $this->postParam("name"));
            $club->setAbout(empty($this->postParam("about")) ? NULL : $this->postParam("about"));
            $club->setShortcode(empty($this->postParam("shortcode")) ? NULL : $this->postParam("shortcode"));
	        $club->setWall(empty($this->postParam("wall")) ? 0 : 1);
            $club->setAdministrators_List_Display(empty($this->postParam("administrators_list_display")) ? 0 : $this->postParam("administrators_list_display"));
            
            if($_FILES["ava"]["error"] === UPLOAD_ERR_OK) {
                $photo = new Photo;
                try {
                    $photo->setOwner($this->user->id);
                    $photo->setDescription("Profile image");
                    $photo->setFile($_FILES["ava"]);
                    $photo->setCreated(time());
                    $photo->save();
                    
                    (new Albums)->getClubAvatarAlbum($club)->addPhoto($photo);
                } catch(ISE $ex) {
                    $name = $album->getName();
                    $this->flashFail("err", "Неизвестная ошибка", "Не удалось сохранить фотографию.");
                }
            }
            
            try {
                $club->save();
            } catch(\PDOException $ex) {
                if($ex->getCode() == 23000)
                    $this->flashFail("err", "Ошибка", "Произошла ошибка на стороне сервера. Обратитесь к системному администратору.");
                else
                    throw $ex;
            }
            
            $this->flash("succ", "Изменения сохранены", "Новые данные появятся в вашей группе.");
        }
    }
    
    function renderStatistics(int $id): void
    {
        $this->assertUserLoggedIn();
        
        if(!eventdb())
            $this->flashFail("err", "Ошибка подключения", "Не удалось подключится к службе телеметрии.");
        
        $club = $this->clubs->get($id);
        if(!$club->canBeModifiedBy($this->user->identity))
            $this->notFound();
        else
            $this->template->club = $club;
        
        $this->template->reach = $club->getPostViewStats(true);
        $this->template->views = $club->getPostViewStats(false);
    }

    function renderAdmin(int $clb, int $id): void
    {
        $this->assertUserLoggedIn();

        $manager = (new Managers)->get($id);
        if($manager->getClub()->canBeModifiedBy($this->user->identity)){
            $this->template->manager = $manager;
            $this->template->club = $manager->getClub();
        }else{
            $this->notFound();
        }
    }
}
