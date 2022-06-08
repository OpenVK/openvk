<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\{Voucher, Gift, GiftCategory, User};
use openvk\Web\Models\Repositories\{Users, Clubs, Vouchers, Gifts};

final class AdminPresenter extends OpenVKPresenter
{
    private $users;
    private $clubs;
    private $vouchers;
    private $gifts;
    
    function __construct(Users $users, Clubs $clubs, Vouchers $vouchers, Gifts $gifts)
    {
        $this->users    = $users;
        $this->clubs    = $clubs;
        $this->vouchers = $vouchers;
        $this->gifts    = $gifts;
        
        parent::__construct();
    }
    
    private function warnIfNoCommerce(): void
    {
        if(!OPENVK_ROOT_CONF["openvk"]["preferences"]["commerce"])
            $this->flash("warn", tr("admin_commerce_disabled"), tr("admin_commerce_disabled_desc"));
    }
    
    private function searchResults(object $repo, &$count)
    {
        $query = $this->queryParam("q") ?? "";
        $page  = (int) ($this->queryParam("p") ?? 1);
        
        $count = $repo->find($query)->size();
        return $repo->find($query)->page($page, 20);
    }
    
    function onStartup(): void
    {
        parent::onStartup();
        
        $this->assertPermission("admin", "access", -1);
    }
    
    function renderIndex(): void
    {
        
    }
    
    function renderUsers(): void
    {
        $this->template->users = $this->searchResults($this->users, $this->template->count);
    }
    
    function renderUser(int $id): void
    {
        $user = $this->users->get($id);
        if(!$user)
            $this->notFound();
        
        $this->template->user = $user;
        
        if($_SERVER["REQUEST_METHOD"] !== "POST")
            return;
        
        switch($_POST["act"] ?? "info") {
            default:
            case "info":
                $user->setFirst_Name($this->postParam("first_name"));
                $user->setLast_Name($this->postParam("last_name"));
                $user->setPseudo($this->postParam("nickname"));
                $user->setStatus($this->postParam("status"));
                if(!$user->setShortCode(empty($this->postParam("shortcode")) ? NULL : $this->postParam("shortcode")))
                    $this->flash("err", tr("error"), tr("error_shorturl_incorrect"));
                $user->changeEmail($this->postParam("email"));
                if($user->onlineStatus() != $this->postParam("online")) $user->setOnline(intval($this->postParam("online")));
                $user->setVerified(empty($this->postParam("verify") ? 0 : 1));

                $user->save();
                break;
        }
    }
    
    function renderClubs(): void
    {
        $this->template->clubs = $this->searchResults($this->clubs, $this->template->count);
    }
    
    function renderClub(int $id): void
    {
        $club = $this->clubs->get($id);
        if(!$club)
            $this->notFound();
        
        $this->template->mode = in_array($this->queryParam("act"), ["main", "ban", "followers"]) ? $this->queryParam("act") : "main";

        $this->template->club = $club;

        $this->template->followers = $this->template->club->getFollowers((int) ($this->queryParam("p") ?? 1));

        if($_SERVER["REQUEST_METHOD"] !== "POST")
            return;
        
        switch($this->queryParam("act")) {
            default:
            case "main":
                $club->setOwner($this->postParam("id_owner"));
                $club->setName($this->postParam("name"));
                $club->setAbout($this->postParam("about"));
                $club->setShortCode($this->postParam("shortcode"));
                $club->setVerified(empty($this->postParam("verify") ? 0 : 1));
                $club->setHide_From_Global_Feed(empty($this->postParam("hide_from_global_feed") ? 0 : 1));
                $club->save();
                break;
            case "ban":
                $club->setBlock_reason($this->postParam("ban_reason"));
                $club->save();
                break;
        }
    }
    
    function renderVouchers(): void
    {
        $this->warnIfNoCommerce();
        
        $this->template->count    = $this->vouchers->size();
        $this->template->vouchers = iterator_to_array($this->vouchers->enumerate((int) ($this->queryParam("p") ?? 1)));
    }
    
    function renderVoucher(int $id): void
    {
        $this->warnIfNoCommerce();
        
        $voucher = NULL;
        $this->template->form = (object) [];
        if($id === 0) {
            $this->template->form->id     = 0;
            $this->template->form->token  = NULL;
            $this->template->form->coins  = 0;
            $this->template->form->rating = 0;
            $this->template->form->usages = -1;
            $this->template->form->users  = [];
        } else {
            $voucher = $this->vouchers->get($id);
            if(!$voucher)
                $this->notFound();
            
            $this->template->form->id     = $voucher->getId();
            $this->template->form->token  = $voucher->getToken();
            $this->template->form->coins  = $voucher->getCoins();
            $this->template->form->rating = $voucher->getRating();
            $this->template->form->usages = $voucher->getRemainingUsages();
            $this->template->form->users  = iterator_to_array($voucher->getUsers());
            
            if($this->template->form->usages === INF)
                $this->template->form->usages = -1;
            else
                $this->template->form->usages = (int) $this->template->form->usages;
        }
        
        if($_SERVER["REQUEST_METHOD"] !== "POST")
            return;
        
        $voucher ??= new Voucher;
        $voucher->setCoins((int) $this->postParam("coins"));
        $voucher->setRating((int) $this->postParam("rating"));
        $voucher->setRemainingUsages($this->postParam("usages") === '-1' ? INF : ((int) $this->postParam("usages")));
        if(!empty($tok = $this->postParam("token")) && strlen($tok) === 24)
            $voucher->setToken($tok);
        
        $voucher->save();
        
        $this->redirect("/admin/vouchers/id" . $voucher->getId(), static::REDIRECT_TEMPORARY);
        exit;
    }
    
    function renderGiftCategories(): void
    {
        $this->warnIfNoCommerce();
        
        $this->template->act        = $this->queryParam("act") ?? "list";
        $this->template->categories = iterator_to_array($this->gifts->getCategories((int) ($this->queryParam("p") ?? 1), NULL, $this->template->count));
    }
    
    function renderGiftCategory(string $slug, int $id): void
    {
        $this->warnIfNoCommerce();
        
        $cat;
        $gen = false;
        if($id !== 0) {
            $cat = $this->gifts->getCat($id);
            if(!$cat)
                $this->notFound();
            else if($cat->getSlug() !== $slug)
                $this->redirect("/admin/gifts/" . $cat->getSlug() . "." . $id . ".meta", static::REDIRECT_TEMPORARY);
        } else {
            $gen = true;
            $cat = new GiftCategory;
        }
        
        $this->template->form = (object) [];
        $this->template->form->id        = $id;
        $this->template->form->languages = [];
        foreach(getLanguages() as $language) {
            $language = (object) $language;
            $this->template->form->languages[$language->code] = (object) [];
            
            $this->template->form->languages[$language->code]->name        = $gen ? "" : ($cat->getName($language->code, true) ?? "");
            $this->template->form->languages[$language->code]->description = $gen ? "" : ($cat->getDescription($language->code, true) ?? "");
        }
        
        $this->template->form->languages["master"] = (object) [
            "name"        => $gen ? "Unknown Name" : $cat->getName(),
            "description" => $gen ?             "" : $cat->getDescription(),
        ];
        
        if($_SERVER["REQUEST_METHOD"] !== "POST")
            return;
        
        if($gen) {
            $cat->setAutoQuery(NULL);
            $cat->save();
        }
        
        $cat->setName("_", $this->postParam("name_master"));
        $cat->setDescription("_", $this->postParam("description_master"));
        foreach(getLanguages() as $language) {
            $code = $language["code"];
            if(!empty($this->postParam("name_$code") ?? NULL))
                $cat->setName($code, $this->postParam("name_$code"));
                
            if(!empty($this->postParam("description_$code") ?? NULL))
                $cat->setDescription($code, $this->postParam("description_$code"));
        }
        
        $this->redirect("/admin/gifts/" . $cat->getSlug() . "." . $cat->getId() . ".meta", static::REDIRECT_TEMPORARY);
    }
    
    function renderGifts(string $catSlug, int $catId): void
    {
        $this->warnIfNoCommerce();
        
        $cat = $this->gifts->getCat($catId);
        if(!$cat)
            $this->notFound();
        else if($cat->getSlug() !== $catSlug)
            $this->redirect("/admin/gifts/" . $cat->getSlug() . "." . $catId . "/", static::REDIRECT_TEMPORARY);
        
        $this->template->cat   = $cat;
        $this->template->gifts = iterator_to_array($cat->getGifts((int) ($this->queryParam("p") ?? 1), NULL, $this->template->count));
    }
    
    function renderGift(int $id): void
    {
        $this->warnIfNoCommerce();
        
        $gift = $this->gifts->get($id);
        $act  = $this->queryParam("act") ?? "edit";
        switch($act) {
            case "delete":
                $this->assertNoCSRF();
                if(!$gift)
                    $this->notFound();
                
                $gift->delete();
                $this->flashFail("succ", "Gift moved successfully", "This gift will now be in <b>Recycle Bin</b>.");
                break;
            case "copy":
            case "move":
                $this->assertNoCSRF();
                if(!$gift)
                    $this->notFound();
                
                $catFrom = $this->gifts->getCat((int) ($this->queryParam("from") ?? 0));
                $catTo   = $this->gifts->getCat((int) ($this->queryParam("to") ?? 0));
                if(!$catFrom || !$catTo || !$catFrom->hasGift($gift))
                    $this->badRequest();
                
                if($act === "move")
                    $catFrom->removeGift($gift);
                
                $catTo->addGift($gift);
                
                $name = $catTo->getName();
                $this->flash("succ", "Gift moved successfully", "This gift will now be in <b>$name</b>.");
                $this->redirect("/admin/gifts/" . $catTo->getSlug() . "." . $catTo->getId() . "/", static::REDIRECT_TEMPORARY);
                break;
            default:
            case "edit":
                $gen = false;
                if(!$gift) {
                    $gen  = true;
                    $gift = new Gift;
                }
                
                $this->template->form = (object) [];
                $this->template->form->id     = $id;
                $this->template->form->name   = $gen ? "New Gift (1)" : $gift->getName();
                $this->template->form->price  = $gen ?              0 : $gift->getPrice();
                $this->template->form->usages = $gen ?              0 : $gift->getUsages();
                $this->template->form->limit  = $gen ?             -1 : ($gift->getLimit() === INF ? -1 : $gift->getLimit());
                $this->template->form->pic    = $gen ?           NULL : $gift->getImage(Gift::IMAGE_URL);
                
                if($_SERVER["REQUEST_METHOD"] !== "POST")
                    return;
                
                $limit = $this->postParam("limit") ?? $this->template->form->limit;
                $limit = $limit == "-1" ? INF : (float) $limit;
                $gift->setLimit($limit, is_null($this->postParam("reset_limit")) ? Gift::PERIOD_SET_IF_NONE : Gift::PERIOD_SET);
                
                $gift->setName($this->postParam("name"));
                $gift->setPrice((int) $this->postParam("price"));
                $gift->setUsages((int) $this->postParam("usages"));
                if(isset($_FILES["pic"]) && $_FILES["pic"]["error"] === UPLOAD_ERR_OK) {
                    if(!$gift->setImage($_FILES["pic"]["tmp_name"]))
                        $this->flashFail("err", "Не удалось сохранить подарок", "Изображение подарка кривое.");
                } else if($gen) {
                    # If there's no gift pic but it's newly created
                    $this->flashFail("err", "Не удалось сохранить подарок", "Пожалуйста, загрузите изображение подарка.");
                }
                
                $gift->save();
                
                if($gen && !is_null($cat = $this->postParam("_cat"))) {
                    $cat = $this->gifts->getCat((int) $cat);
                    if(!is_null($cat))
                        $cat->addGift($gift);
                }
                
                $this->redirect("/admin/gifts/id" . $gift->getId(), static::REDIRECT_TEMPORARY);
        }
    }
    
    function renderFiles(): void
    {
        
    }
    
    function renderQuickBan(int $id): void
    {
        $this->assertNoCSRF();
        
        $user = $this->users->get($id);
        if(!$user)
            exit(json_encode([ "error" => "User does not exist" ]));
        
        $user->ban($this->queryParam("reason"));
        exit(json_encode([ "success" => true, "reason" => $this->queryParam("reason") ]));
    }

    function renderQuickUnban(int $id): void
    {
        $this->assertNoCSRF();
        
        $user = $this->users->get($id);
        if(!$user)
            exit(json_encode([ "error" => "User does not exist" ]));
        
        $user->setBlock_Reason(null);
        $user->save();
        exit(json_encode([ "success" => true ]));
    }
    
    function renderQuickWarn(int $id): void
    {
        $this->assertNoCSRF();
        
        $user = $this->users->get($id);
        if(!$user)
            exit(json_encode([ "error" => "User does not exist" ]));
        
        $user->adminNotify("⚠️ " . $this->queryParam("message"));
        exit(json_encode([ "message" => $this->queryParam("message") ]));
    }
}
