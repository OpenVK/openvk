<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\{Post, Photo, Club, User};
use openvk\Web\Models\Entities\Notifications\{LikeNotification, RepostNotification, WallPostNotification};
use openvk\Web\Models\Repositories\{Posts, Users, Clubs, Albums};
use Chandler\Database\DatabaseConnection;
use Nette\InvalidStateException as ISE;

final class WallPresenter extends OpenVKPresenter
{
    private $posts;
    
    function __construct(Posts $posts)
    {
        $this->posts = $posts;
        
        parent::__construct();
    }
    
    private function logPostView(Post $post, int $wall): void
    {
        if(is_null($this->user))
            return;
        
        $this->logEvent("postView", [
            "profile"    => $this->user->identity->getId(),
            "post"       => $post->getId(),
            "owner"      => abs($wall),
            "group"      => $wall < 0,
            "subscribed" => $wall < 0 ? $post->getOwner()->getSubscriptionStatus($this->user->identity) : false,
        ]);
    }
    
    private function logPostsViewed(array &$posts, int $wall): void
    {
        $x = array_values($posts); # clone array (otherwise Nette DB objects will become kinda gay)
        
        foreach($x as $post)
            $this->logPostView($post, $wall);
    }
    
    function renderWall(int $user): void
    {
        if(false)
            exit("Ошибка доступа: " . (string) random_int(0, 255));
        
        $owner = ($user < 0 ? (new Clubs) : (new Users))->get(abs($user));
        if(is_null($this->user))
            $canPost = false;
        else if($user > 0)
            $canPost = $owner->getPrivacyPermission("wall.write", $this->user->identity);
        else if($user < 0)
            if($owner->canBeModifiedBy($this->user->identity))
                $canPost = true;
            else
                $canPost = $owner->canPost();
        else
            $canPost = false; 
        
        $this->template->oObj    = $owner;
        $this->template->owner   = $user;
        $this->template->canPost = $canPost;
        $this->template->count   = $this->posts->getPostCountOnUserWall($user);
        $this->template->posts   = iterator_to_array($this->posts->getPostsFromUsersWall($user, (int) ($_GET["p"] ?? 1)));
        $this->template->paginatorConf = (object) [
            "count"   => $this->template->count,
            "page"    => (int) ($_GET["p"] ?? 1),
            "amount"  => sizeof($this->template->posts),
            "perPage" => OPENVK_DEFAULT_PER_PAGE,
        ];
        
        $this->logPostsViewed($this->template->posts, $user);
    }
    
    function renderFeed(): void
    {
        $this->assertUserLoggedIn();
        
        $id    = $this->user->id;
        $subs  = DatabaseConnection::i()
                 ->getContext()
                 ->table("subscriptions")
                 ->where("follower", $id);
        $ids   = array_map(function($rel) {
            return $rel->target * ($rel->model === "openvk\Web\Models\Entities\User" ? 1 : -1);
        }, iterator_to_array($subs));
        $ids[] = $this->user->id;
        
        $perPage = min((int) ($_GET["posts"] ?? OPENVK_DEFAULT_PER_PAGE), 50);
        $posts   = DatabaseConnection::i()
                   ->getContext()
                   ->table("posts")
                   ->select("id")
                   ->where("wall IN (?)", $ids)
                   ->where("deleted", 0)
                   ->order("created DESC");
        $this->template->paginatorConf = (object) [
            "count"   => sizeof($posts),
            "page"    => (int) ($_GET["p"] ?? 1),
            "amount"  => sizeof($posts->page((int) ($_GET["p"] ?? 1), $perPage)),
            "perPage" => $perPage,
        ];
        $this->template->posts = [];
        foreach($posts->page((int) ($_GET["p"] ?? 1), $perPage) as $post)
            $this->template->posts[] = $this->posts->get($post->id);
    }
    
    function renderGlobalFeed(): void
    {
        $this->assertUserLoggedIn();
        
        $page  = (int) ($_GET["p"] ?? 1);
        $pPage = min((int) ($_GET["posts"] ?? OPENVK_DEFAULT_PER_PAGE), 50);
        $posts = DatabaseConnection::i()
                   ->getContext()
                   ->table("posts")
                   ->where("deleted", 0)
                   ->order("created DESC");
        
        if($this->user->identity->getNsfwTolerance() === User::NSFW_INTOLERANT)
            $posts = $posts->where("nsfw", false);
        
        $this->template->_template     = "Wall/Feed.xml";
        $this->template->globalFeed    = true;
        $this->template->paginatorConf = (object) [
            "count"   => sizeof($posts),
            "page"    => (int) ($_GET["p"] ?? 1),
            "amount"  => sizeof($posts->page($page, $pPage)),
            "perPage" => $pPage,
        ];
        foreach($posts->page($page, $pPage) as $post)
            $this->template->posts[] = $this->posts->get($post->id);
    }
    
    function renderHashtagFeed(string $hashtag): void
    {
        $hashtag = rawurldecode($hashtag);
        
        $page  = (int) ($_GET["p"] ?? 1);
        $posts = $this->posts->getPostsByHashtag($hashtag, $page);
        $count = $this->posts->getPostCountByHashtag($hashtag);
        
        $this->template->hashtag       = $hashtag;
        $this->template->posts         = $posts;
        $this->template->paginatorConf = (object) [
            "count"   => 0,
            "page"    => $page,
            "amount"  => $count,
            "perPage" => OPENVK_DEFAULT_PER_PAGE,
        ];
    }
    
    function renderMakePost(int $wall): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        
        $wallOwner = ($wall > 0 ? (new Users)->get($wall) : (new Clubs)->get($wall * -1))
                     ?? $this->flashFail("err", "Не удалось опубликовать пост", "Такого пользователя не существует.");
        if($wall > 0)
            $canPost = $wallOwner->getPrivacyPermission("wall.write", $this->user->identity);
        else if($wall < 0)
            if($wallOwner->canBeModifiedBy($this->user->identity))
                $canPost = true;
            else
                $canPost = $wallOwner->canPost();
        else
            $canPost = false; 
        
        if(!$canPost)
            $this->flashFail("err", "Ошибка доступа", "Вам нельзя писать на эту стену.");
        
        if(false)
            $this->flashFail("err", "Не удалось опубликовать пост", "Пост слишком большой.");
        
        $flags = 0;
        if($this->postParam("as_group") === "on")
            $flags |= 0b10000000;
        if($this->postParam("force_sign") === "on")
            $flags |= 0b01000000;
        
        
        if($_FILES["_pic_attachment"]["error"] === UPLOAD_ERR_OK) {
            try {
                $photo = new Photo;
                $photo->setOwner($this->user->id);
                $photo->setDescription(iconv_substr($this->postParam("text"), 0, 36) . "...");
                $photo->setCreated(time());
                $photo->setFile($_FILES["_pic_attachment"]);
                $photo->save();
                
                if($wall > 0 && $wall === $this->user->id) {
                    (new Albums)->getUserWallAlbum($wallOwner)->addPhoto($photo);
                }
            } catch(ISE $ex) {
                $this->flashFail("err", "Не удалось опубликовать пост", "Файл изображения повреждён, слишком велик или одна сторона изображения в разы больше другой.");
            }
            
            $post = new Post;
            $post->setOwner($this->user->id);
            $post->setWall($wall);
            $post->setCreated(time());
            $post->setContent($this->postParam("text"));
            $post->setFlags($flags);
            $post->setNsfw($this->postParam("nsfw") === "on");
            $post->save();
            $post->attach($photo);
        } elseif($this->postParam("text")) {
            try {
                $post = new Post;
                $post->setOwner($this->user->id);
                $post->setWall($wall);
                $post->setCreated(time());
                $post->setContent($this->postParam("text"));
                $post->setFlags($flags);
                $post->setNsfw($this->postParam("nsfw") === "on");
                $post->save();
            } catch(\LogicException $ex) {
                $this->flashFail("err", "Не удалось опубликовать пост", "Пост пустой или слишком большой.");
            }
        } else {
            $this->flashFail("err", "Не удалось опубликовать пост", "Пост пустой или слишком большой.");
        }
        
        if($wall > 0 && $wall !== $this->user->identity->getId())
            (new WallPostNotification($wallOwner, $post, $this->user->identity))->emit();
        
        if($wall > 0)
            $this->redirect("/id$wall", 2); #Will exit
        
        $wall = $wall * -1;
        $this->redirect("/club$wall", 2);
    }
    
    function renderPost(int $wall, int $post_id): void
    {
        $this->assertUserLoggedIn();
        
        $post = $this->posts->getPostById($wall, $post_id);
        if(!$post || $post->isDeleted())
            $this->notFound();
        
        $this->logPostView($post, $wall);
        
        $this->template->post     = $post;
        $this->template->cCount   = $post->getCommentsCount();
        $this->template->cPage    = (int) ($_GET["p"] ?? 1);
        $this->template->comments = iterator_to_array($post->getComments($this->template->cPage));
    }
    
    function renderLike(int $wall, int $post_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertNoCSRF();
        
        $post = $this->posts->getPostById($wall, $post_id);
        if(!$post || $post->isDeleted()) $this->notFound();
        
        if(!is_null($this->user)) {
            $post->toggleLike($this->user->identity);
            
            if($post->getOwner(false)->getId() !== $this->user->identity->getId() && !($post->getOwner() instanceof Club))
                (new LikeNotification($post->getOwner(false), $post, $this->user->identity))->emit();
        }
        
        $this->redirect(
            "$_SERVER[HTTP_REFERER]#postGarter=" . $post->getId(),
            static::REDIRECT_TEMPORARY
        );
    }
    
    function renderShare(int $wall, int $post_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        $this->assertNoCSRF();
        
        $post = $this->posts->getPostById($wall, $post_id);
        if(!$post || $post->isDeleted()) $this->notFound();
        
        if(!is_null($this->user)) {
            $nPost = new Post;
            $nPost->setOwner($this->user->id);
            $nPost->setWall($this->user->id);
            $nPost->setContent("");
            $nPost->save();
            $nPost->attach($post);
            
            if($post->getOwner(false)->getId() !== $this->user->identity->getId() && !($post->getOwner() instanceof Club))
                (new RepostNotification($post->getOwner(false), $post, $this->user->identity))->emit();
        };
        
        $this->flash("succ", "Успешно", "Запись появится на вашей стене. <a href='/wall" . $wall . "_" . $post_id . "'>Вернуться к записи.</a>");
        $this->redirect($this->user->identity->getURL());
    }
    
    function renderDelete(int $wall, int $post_id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();
        
        $post = $this->posts->getPostById($wall, $post_id);
        if(!$post)
            $this->notFound();
        $user = $this->user->id;

        $wallOwner = ($wall > 0 ? (new Users)->get($wall) : (new Clubs)->get($wall * -1))
                     ?? $this->flashFail("err", "Не удалось удалить пост", "Такого пользователя не существует.");

        if($wall < 0) $canBeDeletedByOtherUser = $wallOwner->canBeModifiedBy($this->user->identity);
            else $canBeDeletedByOtherUser = false;

        if(!is_null($user)) {
            if($post->getOwnerPost() == $user || $post->getTargetWall() == $user || $canBeDeletedByOtherUser) {
                $post->unwire();
                $post->delete();
            }
        } else {
            $this->flashFail("err", "Не удалось удалить пост", "Вы не вошли в аккаунт.");
        }
        
        $this->redirect($wall < 0 ? "/club".($wall*-1) : "/id".$wall, static::REDIRECT_TEMPORARY);
        exit;
    }
}
