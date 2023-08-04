<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\{FAQArticle, FAQCategory, SupportAgent, Ticket, TicketComment};
use openvk\Web\Models\Repositories\{FAQCategories, Tickets, Users, TicketComments, SupportAgents, FAQArticles};
use openvk\Web\Util\Telegram;
use Chandler\Session\Session;
use Chandler\Database\DatabaseConnection;
use Parsedown;

final class SupportPresenter extends OpenVKPresenter
{
    protected $banTolerant = true;
    protected $deactivationTolerant = true;
    protected $presenterName = "support";
    
    private $tickets;
    private $comments;
    
    function __construct(Tickets $tickets, TicketComments $ticketComments)
    {
        $this->tickets  = $tickets;
        $this->comments = $ticketComments;
        
        parent::__construct();
    }
    
    function renderIndex(): void
    {
        $this->assertUserLoggedIn();
        $this->template->mode = in_array($this->queryParam("act"), ["faq", "new", "list"]) ? $this->queryParam("act") : "faq";
        $canEdit = $this->user->identity->getChandlerUser()->can("write")->model('openvk\Web\Models\Entities\TicketReply')->whichBelongsTo(0);

        if($this->template->mode === "faq") {
            $this->template->categories = (new FAQCategories)->getList($canEdit ? $this->queryParam("lang") ?? Session::i()->get("lang", "ru") : Session::i()->get("lang", "ru"), $canEdit);
            $this->template->canEditFAQ = $canEdit;

            $lang = Session::i()->get("lang", "ru");
            $base = OPENVK_ROOT . "/data/knowledgebase/faq";
            if(file_exists("$base.$lang.md"))
                $file = "$base.$lang.md";
            else if(file_exists("$base.md"))
                $file = "$base.md";
            else
                $file = NULL;

            if(is_null($file)) {
                $this->template->faq = [];
            } else {
                $lines = file($file);
                $faq   = [];
                $index = 0;

                foreach($lines as $line) {
                    if(strpos($line, "# ") === 0)
                        ++$index;

                    $faq[$index][] = $line;
                }

                $this->template->faq = array_map(function($section) {
                    $title = substr($section[0], 2);
                    array_shift($section);
                    return [
                        $title,
                        (new Parsedown())->text(implode("\n", $section))
                    ];
                }, $faq);
            }
        }

        $this->template->count = $this->tickets->getTicketsCountByUserId($this->user->id);
        if($this->template->mode === "list") {
            $this->template->page    = (int) ($this->queryParam("p") ?? 1);
            $this->template->tickets = $this->tickets->getTicketsByUserId($this->user->id, $this->template->page);
        }

        if($this->template->mode === "new")
            $this->template->banReason = $this->user->identity->getBanInSupportReason();
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            if($this->user->identity->isBannedInSupport())
                $this->flashFail("err", tr("not_enough_permissions"), tr("not_enough_permissions_comment"));

            if(!empty($this->postParam("name")) && !empty($this->postParam("text"))) {
                $this->willExecuteWriteAction();

                $ticket = new Ticket;
                $ticket->setType(0);
                $ticket->setUser_Id($this->user->id);
                $ticket->setName($this->postParam("name"));
                $ticket->setText($this->postParam("text"));
                $ticket->setcreated(time());
                $ticket->save();

                $helpdeskChat = OPENVK_ROOT_CONF["openvk"]["credentials"]["telegram"]["helpdeskChat"];
                if($helpdeskChat) {
                    $serverUrl     = ovk_scheme(true) . $_SERVER["SERVER_NAME"];
                    $ticketText    = ovk_proc_strtr($this->postParam("text"), 1500);
                    $telegramText  = "<b>📬 Новый тикет!</b>\n\n";
                    $telegramText .= "<a href='$serverUrl/support/reply/{$ticket->getId()}'>{$ticket->getName()}</a>\n";
                    $telegramText .= "$ticketText\n\n";
                    $telegramText .= "Автор: <a href='$serverUrl{$ticket->getUser()->getURL()}'>{$ticket->getUser()->getCanonicalName()}</a> ({$ticket->getUser()->getRegistrationIP()})\n";
                    Telegram::send($helpdeskChat, $telegramText);
                }

                $this->redirect("/support/view/" . $ticket->getId());
            } else {
                $this->flashFail("err", tr("error"), tr("you_have_not_entered_name_or_text"));
            }
        }

        $this->template->languages = getLanguages();
        $this->template->activeLang = $this->queryParam("lang") ?? Session::i()->get("lang", "ru");
    }
    
    function renderList(): void
    {
        $this->assertUserLoggedIn();
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);
        
        $act = $this->queryParam("act") ?? "open";
        switch($act) {
            default:
                # NOTICE falling through
            case "open":
                $state = 0;
                break;
            case "answered":
                $state = 1;
                break;
            case "closed":
                $state = 2;
        }
        
        $this->template->act      = $act;
        $this->template->page     = (int) ($this->queryParam("p") ?? 1);
        $this->template->count    = $this->tickets->getTicketCount($state);
        $this->template->iterator = $this->tickets->getTickets($state, $this->template->page);
    }
    
    function renderView(int $id): void
    {
        $this->assertUserLoggedIn();
        $ticket         = $this->tickets->get($id);
        $ticketComments = $this->comments->getCommentsById($id);
        if(!$ticket || $ticket->isDeleted() != 0 || $ticket->getUserId() !== $this->user->id) {
            $this->notFound();
        } else {
                $this->template->ticket   = $ticket;
                $this->template->comments = $ticketComments;
                $this->template->id       = $id;
        }
    }
    
    function renderDelete(int $id): void 
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        if(!empty($id)) {
            $ticket = $this->tickets->get($id);
            if(!$ticket || $ticket->isDeleted() != 0 || $ticket->getUserId() !== $this->user->id && !$this->hasPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0)) {
                $this->notFound();
            } else {
                if($ticket->getUserId() !== $this->user->id && $this->hasPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0))
                    $_redirect = "/support/tickets";
                else
                    $_redirect = "/support?act=list";

                $ticket->delete();
                $this->redirect($_redirect);
            }
        }
    }
    
    function renderMakeComment(int $id): void 
    {
        $ticket = $this->tickets->get($id);
        
        if($ticket->isDeleted() === 1 || $ticket->getType() === 2 || $ticket->getUserId() !== $this->user->id) {
            header("HTTP/1.1 403 Forbidden");
            header("Location: /support/view/" . $id);
            exit;
        }
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            if(!empty($this->postParam("text"))) {
                $ticket->setType(0);
                $ticket->save();

                $this->willExecuteWriteAction();
                
                $comment = new TicketComment;
                $comment->setUser_id($this->user->id);
                $comment->setUser_type(0);
                $comment->setText($this->postParam("text"));
                $comment->setTicket_id($id);
                $comment->setCreated(time());
                $comment->save();
                
                $this->redirect("/support/view/" . $id);
            } else {
                $this->flashFail("err", tr("error"), tr("you_have_not_entered_text"));
            }
        }
    }
    
    function renderAnswerTicket(int $id): void
    {
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);
        $ticket = $this->tickets->get($id);

        if(!$ticket || $ticket->isDeleted() != 0)
            $this->notFound();

        $ticketComments = $this->comments->getCommentsById($id);
        $this->template->ticket      = $ticket;
        $this->template->comments    = $ticketComments;
        $this->template->id          = $id;
        $this->template->fastAnswers = OPENVK_ROOT_CONF["openvk"]["preferences"]["support"]["fastAnswers"];
    }
    
    function renderAnswerTicketReply(int $id): void
    {
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);
        
        $ticket = $this->tickets->get($id);
        
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->willExecuteWriteAction();
            
            if(!empty($this->postParam("text")) && !empty($this->postParam("status"))) {
                $ticket->setType($this->postParam("status"));
                $ticket->save();

                $comment = new TicketComment;
                $comment->setUser_id($this->user->id);
                $comment->setUser_type(1);
                $comment->setText($this->postParam("text"));
                $comment->setTicket_Id($id);
                $comment->setCreated(time());
                $comment->save();
            } elseif(empty($this->postParam("text"))) {
                $ticket->setType($this->postParam("status"));
                $ticket->save();
            }
            
            $this->flashFail("succ", tr("ticket_changed"), tr("ticket_changed_comment"));
        }
    }
    
    function renderKnowledgeBaseArticle(string $name): void
    {
        $lang = Session::i()->get("lang", "ru");
        $base = OPENVK_ROOT . "/data/knowledgebase";
        if(file_exists("$base/$name.$lang.md"))
            $file = "$base/$name.$lang.md";
        else if(file_exists("$base/$name.md"))
            $file = "$base/$name.md";
        else
            $this->notFound();
        
        $lines = file($file);
        if(!preg_match("%^OpenVK-KB-Heading: (.+)$%", $lines[0], $matches)) {
            $heading = "Article $name";
        } else {
            $heading = $matches[1];
            array_shift($lines);
        }
        
        $content = implode($lines);
        
        $parser = new Parsedown();
        $this->template->heading = $heading;
        $this->template->content = $parser->text($content);
    }

    function renderDeleteComment(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->assertNoCSRF();

        $comment = $this->comments->get($id);
        if(is_null($comment))
            $this->notFound();

        $ticket = $comment->getTicket();

        if($ticket->isDeleted())
            $this->notFound();

        if(!($ticket->getUserId() === $this->user->id && $comment->getUType() === 0))
            $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);

        $this->willExecuteWriteAction();
        $comment->delete();

        $this->flashFail("succ", tr("ticket_changed"), tr("ticket_changed_comment"));
    }

    function renderRateAnswer(int $id, int $mark): void
    {
        $this->willExecuteWriteAction();
        $this->assertUserLoggedIn();
        $this->assertNoCSRF();

        $comment = $this->comments->get($id);

        if($this->user->id !== $comment->getTicket()->getUser()->getId())
            exit(header("HTTP/1.1 403 Forbidden"));

        if($mark !== 1 && $mark !== 2)
            exit(header("HTTP/1.1 400 Bad Request"));

        $comment->setMark($mark);
        $comment->save();

        exit(header("HTTP/1.1 200 OK"));
    }

    function renderQuickBanInSupport(int $id): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);
        $this->assertNoCSRF();

        $user = (new Users)->get($id);
        if(!$user)
            exit(json_encode([ "error" => "User does not exist" ]));
        
        $user->setBlock_In_Support_Reason($this->queryParam("reason"));
        $user->save();

        if($this->queryParam("close_tickets"))
            DatabaseConnection::i()->getConnection()->query("UPDATE tickets SET type = 2 WHERE user_id = ".$id);

        $this->returnJson([ "success" => true, "reason" => $this->queryParam("reason") ]);
    }

    function renderQuickUnbanInSupport(int $id): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);
        $this->assertNoCSRF();
        
        $user = (new Users)->get($id);
        if(!$user)
            exit(json_encode([ "error" => "User does not exist" ]));
        
        $user->setBlock_In_Support_Reason(null);
        $user->save();
        $this->returnJson([ "success" => true ]);
    }

    function renderAgent(int $id): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);

        $support_names = new SupportAgents;

        if(!$support_names->isExists($id))
            $this->template->mode = "edit";

        $this->template->agent_id    = $id;
        $this->template->mode        = in_array($this->queryParam("act"), ["info", "edit"]) ? $this->queryParam("act") : "info";
        $this->template->agent       = $support_names->get($id) ?? NULL;
        $this->template->counters    = [
          "all"    => (new TicketComments)->getCountByAgent($id),
          "good"   => (new TicketComments)->getCountByAgent($id, 1),
          "bad"    => (new TicketComments)->getCountByAgent($id, 2)
        ];

        if($id != $this->user->identity->getId())
            if ($support_names->isExists($id))
                $this->template->mode = "info";
            else
                $this->redirect("/support/agent" . $this->user->identity->getId());
    }

    function renderEditAgent(int $id): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);
        $this->assertNoCSRF();

        $support_names = new SupportAgents;
        $agent = $support_names->get($id);

        if($agent)
            if($agent->getAgentId() != $this->user->identity->getId()) $this->flashFail("err", tr("error"), tr("forbidden"));

        if ($support_names->isExists($id)) {
            $agent = $support_names->get($id);
            $agent->setName($this->postParam("name") ?? tr("helpdesk_agent"));
            $agent->setNumerate((int) $this->postParam("number") ?? NULL);
            $agent->setIcon($this->postParam("avatar"));
            $agent->save();
            $this->flashFail("succ", "Успех", "Профиль отредактирован.");
        } else {
            $agent = new SupportAgent;
            $agent->setAgent($this->user->identity->getId());
            $agent->setName($this->postParam("name") ?? tr("helpdesk_agent"));
            $agent->setNumerate((int) $this->postParam("number") ?? NULL);
            $agent->setIcon($this->postParam("avatar"));
            $agent->save();
            $this->flashFail("succ", "Успех", "Профиль создан. Теперь пользователи видят Ваши псевдоним и аватарку вместо стандартных аватарки и номера.");
        }
    }

    function renderFAQArticle(int $id): void
    {
        $article = (new FAQArticles)->get($id);
        if (!$article || $article->isDeleted())
            $this->notFound();

        $category = $article->getCategory();

        if ($category->isDeleted())
            $this->notFound();

        if (!$article->canSeeByUnloggedUsers())
            $this->assertUserLoggedIn();

        if (!$category->canSeeByUsers() || (!$article->canSeeByUsers() && !$article->canSeeByUnloggedUsers()))
            $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);

        $canEdit = false;
        if ($this->user->identity)
            $canEdit = $this->user->identity->getChandlerUser()->can("write")->model('openvk\Web\Models\Entities\TicketReply')->whichBelongsTo(0);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->assertNoCSRF();
            if (!$canEdit) {
                $this->flashFail("err", tr("not_enough_permissions"), tr("not_enough_permissions_comment"));
            }

            $cid = $this->postParam("category");
            if ($this->postParam("language") != $article->getLanguage()) {
                $categories = iterator_to_array((new FAQCategories)->getList($this->postParam("language")));
                if (sizeof($categories) > 0) {
                    $cid = iterator_to_array((new FAQCategories)->getList($this->postParam("language")))[0]->getId();
                } else {
                    $this->flashFail("err", tr("error"), tr("support_cant_change_lang_no_cats"));
                }
            }

            $article->setTitle($this->postParam("title"));
            $article->setText($this->postParam("text"));
            $article->setUnlogged_Can_see(empty($this->postParam("unlogged_can_see") ? 0 : 1));
            $article->setUsers_Can_See(empty($this->postParam("users_can_see") ? 0 : 1));
            $article->setCategory($cid);
            $article->setLanguage($this->postParam("language"));
            $article->save();
            $this->flashFail("succ", tr("changes_saved"));
        } else {
            $this->template->mode = $canEdit ? in_array($this->queryParam("act"), ["view", "edit"]) ? $this->queryParam("act") : "view" : "view";
            $this->template->category = $category;
            $this->template->article = $article;
            $this->template->text = (new Parsedown())->text($article->getText());
            $this->template->canEditFAQ = $canEdit;
            $this->template->categories = (new FAQCategories)->getList($this->queryParam("lang") ?? $article->getLanguage(), TRUE);
            $this->template->languages = getLanguages();
            $this->template->activeLang = $this->queryParam("lang") ?? $article->getLanguage();
        }
    }

    function renderFAQCategory(int $id): void
    {
        $category = (new FAQCategories)->get($id);

        if (!$category)
            $this->notFound();

        if (!$category->canSeeByUsers())
            $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);

        $canEdit = $this->user->identity->getChandlerUser()->can("write")->model('openvk\Web\Models\Entities\TicketReply')->whichBelongsTo(0);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->assertNoCSRF();
            if (!$canEdit) {
                $this->flashFail("err", tr("not_enough_permissions"), tr("not_enough_permissions_comment"));
            }

            if ($this->queryParam("mode") === "copy") {
                $orig_cat = $category;
                $category = new FAQCategory;
            }

            $category->setTitle($this->postParam("title"));
            $category->setIcon($orig_cat->getIcon() ?? $this->postParam("icon"));
            $category->setFor_Agents_Only(empty($this->postParam("for_agents_only") ? 0 : 1));
            $category->setLanguage($this->postParam("language"));
            $category->save();

            if ($this->queryParam("mode") === "copy" && !empty($this->postParam("copy_with_articles"))) {
                $articles = $orig_cat->getArticles(NULL, TRUE);
                foreach ($articles as $article) {
                    $_article = new FAQArticle;
                    $_article->setCategory($category->getId());
                    $_article->setTitle($article->getTitle());
                    $_article->setText($article->getText());
                    $_article->setUsers_Can_See($article->canSeeByUsers());
                    $_article->setUnlogged_Can_See($article->canSeeByUnloggedUsers());
                    $_article->setLanguage($category->getLanguage());
                    $_article->save();
                }
            }

            $this->flashFail("succ", tr("changes_saved"));
        }

        $this->template->mode = $canEdit ? in_array($this->queryParam("act"), ["view", "edit"]) ? $this->queryParam("act") : "view" : "view";
        $this->template->copyMode = $canEdit ? $this->queryParam("mode") === "copy" : FALSE;
        $this->template->category = $category;
        $this->template->canEditFAQ = $canEdit;
        $this->template->languages = getLanguages();
    }

    function renderFAQNewArticle(): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!$this->postParam("category"))
                $this->flashFail("err", tr("support_category_not_specified"));

            $article = new FAQArticle;
            $article->setTitle($this->postParam("title"));
            $article->setText($this->postParam("text"));
            $article->setUnlogged_Can_see(empty($this->postParam("unlogged_can_see") ? 0 : 1));
            $article->setUsers_Can_See(empty($this->postParam("users_can_see") ? 0 : 1));
            $article->setCategory($this->postParam("category"));
            $article->setLanguage(Session::i()->get("lang", "ru"));
            $article->save();
            $this->redirect("/faq" . $article->getId());
        } else {
            $this->template->categories = (new FAQCategories)->getList($this->queryParam("lang") ?? Session::i()->get("lang", "ru"), TRUE);
            $this->template->category_id = $this->queryParam("cid");
            $this->template->activeLang = $this->queryParam("lang") ?? Session::i()->get("lang", "ru");
            $this->template->languages = getLanguages();
        }
    }

    function renderFAQNewCategory(): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $category = new FAQCategory;
            $category->setTitle($this->postParam("title"));
            $category->setIcon($this->postParam("icon"));
            $category->setFor_Agents_Only(empty($this->postParam("for_agents_only") ? 0 : 1));
            $category->setLanguage($this->postParam("language"));
            $category->save();
            $this->redirect("/faqs" . $category->getId());
        }

        $this->template->activeLang = $this->queryParam("lang") ?? Session::i()->get("lang", "ru");
        $this->template->languages = getLanguages();
    }

    function renderFAQDeleteArticle(int $id): void
    {
        $this->assertNoCSRF();
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);

        $article = (new FAQArticles)->get($id);
        if (!$article || $article->isDeleted())
            $this->notFound();

        $cid = $article->getCategory()->getId();
        $article->delete();
        $this->redirect("/faqs" . $cid);
    }

    function renderFAQDeleteCategory(int $id): void
    {
        $this->assertNoCSRF();
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);

        $category = (new FAQCategories)->get($id);
        if (!$category || $category->isDeleted())
            $this->notFound();

        if (!empty($this->postParam("delete_articles"))) {
            $articles = $category->getArticles(NULL, TRUE);
            foreach ($articles as $article) {
                $article->delete();
            }
        }

        $category->delete();
        $this->redirect("/support");
    }
}
