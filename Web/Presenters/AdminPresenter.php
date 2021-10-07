<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\{Voucher, User};
use openvk\Web\Models\Repositories\{Users, Clubs, Vouchers};

final class AdminPresenter extends OpenVKPresenter
{
    private $users;
    private $clubs;
    private $vouchers;
    
    function __construct(Users $users, Clubs $clubs, Vouchers $vouchers)
    {
        $this->users    = $users;
        $this->clubs    = $clubs;
        $this->vouchers = $vouchers;
        
        parent::__construct();
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
                $user->setVerified(empty($this->postParam("verify") ? 0 : 1));
                if($user->onlineStatus() != $this->postParam("online")) $user->setOnline(intval($this->postParam("online")));
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
        $this->template->count    = $this->vouchers->size();
        $this->template->vouchers = iterator_to_array($this->vouchers->enumerate((int) ($this->queryParam("p") ?? 1)));
    }
    
    function renderVoucher(int $id): void
    {
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
        exit(json_encode([ "reason" => $this->queryParam("reason") ]));
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
