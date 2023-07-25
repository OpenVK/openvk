<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\{SupportAgent, SupportTemplate, SupportTemplateDir, Ticket, TicketComment};
use openvk\Web\Models\Repositories\{SupportTemplates,
    SupportTemplatesDirs,
    Tickets,
    Users,
    TicketComments,
    SupportAgents};
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

        if($this->template->mode === "faq") {
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

    function renderGetTemplatesDirs(): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);

        $dirs = [];
        foreach ((new SupportTemplatesDirs)->getList($this->user->identity->getId()) as $dir) {
            $dirs[] = ["id" => $dir->getId(), "title" => $dir->getTitle()];
        }

        $this->returnJson(["success" => true, "dirs" => $dirs]);
    }

    function renderGetTemplatesInDir(int $dirId): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);

        $dir = (new SupportTemplatesDirs)->get($dirId);

        if (!$dir || $dir->getOwner()->getId() !== $this->user->identity->getId() && !$dir->isPublic()) {
            $this->returnJson(["success" => false, "error" => tr("forbidden")]);
        }

        $templates = [];
        foreach ($dir->getTemplates() as $template) {
            $templateData = [
                "id" => $template->getId(),
                "title" => $template->getTitle(),
                "text" => $template->getText(),
            ];

            if ($this->queryParam("tid")) {
                $ticket = (new Tickets)->get((int) $this->queryParam("tid"));
                $ticket_user = $ticket->getUser();
                $replacements = [
                    "{user_name}" => $ticket_user->getFirstName(),
                    "{last_name}" => $ticket_user->getLastName(),
                    "{unban_time}" => $ticket_user->getUnbanTime(),
                ];

                if ($ticket->getId()) {
                    foreach ($replacements as $search => $replace) {
                        $templateData["text"] = str_replace($search, $replace, $templateData["text"]);
                    }
                }
            }

            $templates[] = $templateData;
        }

        $this->returnJson(["success" => true, "templates" => $templates]);
    }

    function renderCreateTemplatesDir(): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);
        $this->assertNoCSRF();

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $dir = new SupportTemplateDir;
            $dir->setTitle($this->postParam("title"));
            $dir->setOwner($this->user->identity->getId());
            $dir->setIs_Public(!empty($this->postParam("is_public")));
            $dir->save();

            $this->flashFail("succ", tr("changes_saved"));
        }
    }

    function renderTemplates(): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);

        $mode = in_array($this->queryParam("act"), ["dirs", "list", "create_dir", "create_template", "edit_dir", "edit_template"]) ? $this->queryParam("act") : "dirs";

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->assertNoCSRF();

            if ($mode === "create_dir" || $mode === "edit_dir") {
                $dirId = (int) $this->queryParam("dir");
                $dir = ($mode === "create_dir") ? new SupportTemplateDir : (new SupportTemplatesDirs)->get($dirId);

                if ($mode === "edit_dir" && (!$dir || $dir->isDeleted())) {
                    $this->notFound();
                }

                if ($mode === "edit_dir" && $dir->getOwner()->getId() !== $this->user->identity->getId()) {
                    $this->flashFail("err", tr("forbidden"));
                }

                $dir->setTitle($this->postParam("title"));
                $dir->setOwner($this->user->identity->getId());
                $dir->setIs_Public(!empty($this->postParam("is_public")));
                $dir->save();

                if ($mode === "create_dir") {
                    $this->redirect("/support/templates?act=list&dir=" . $dir->getId());
                } else {
                    $this->flashFail("succ", tr("changes_saved"));
                }
            } else if ($mode === "create_template" || $mode === "edit_template") {
                $templateId = (int) $this->queryParam("id");
                $template = ($mode === "create_template") ? new SupportTemplate : (new SupportTemplates)->get($templateId);
                if (!$template || $template->isDeleted()) {
                    $this->notFound();
                }

                if ($mode === "edit_template" && $template->getOwner()->getId() !== $this->user->identity->getId()) {
                    $this->flashFail("err", tr("forbidden"));
                }

                $dirId = ($mode === "create_template") ? (int) $this->queryParam("dir") : $template->getDir()->getId();
                $dir = (new SupportTemplatesDirs)->get($dirId);

                if ($mode === "create_template" && $dir->getOwner()->getId() !== $this->user->identity->getId()) {
                    $this->flashFail("err", tr("forbidden"));
                }

                if (!$dir || $dir->isDeleted()) {
                    $this->notFound();
                }

                if ($dir->getOwner()->getId() !== $this->user->identity->getId()) {
                    $this->flashFail("err", tr("forbidden"));
                }

                $template->setOwner($this->user->identity->getId());
                $template->setDir($dir->getId());
                $template->setTitle($this->postParam("title"));
                $template->setText($this->postParam("text"));
                $template->save();

                if ($mode === "create_template") {
                    $this->redirect("/support/templates?act=list&dir=" . $dirId . "&id=" . $template->getId());
                } else {
                    $this->flashFail("succ", tr("changes_saved"));
                }
            }
        } else {
            $this->template->mode = $mode;

            if (!$this->queryParam("dir")) {
                $dirs = (new SupportTemplatesDirs)->getList($this->user->identity->getId());
                $this->template->dirs = $dirs;
                $this->template->dirsCount = count($dirs);

                if ($mode === "edit_template") {
                    $templateId = (int) $this->queryParam("id");
                    $template = (new SupportTemplates)->get($templateId);

                    if (!$template || $template->isDeleted()) {
                        $this->notFound();
                    }

                    if ($template->getOwner()->getId() !== $this->user->identity->getId()) {
                        $this->flashFail("err", tr("forbidden"));
                    }

                    $this->template->dir = $template->getDir();
                    $this->template->activeTemplate = $template;
                }
            } else {
                $dirId = (int) $this->queryParam("dir");
                $dir = (new SupportTemplatesDirs)->get($dirId);

                if (!$dir || $dir->isDeleted()) {
                    $this->notFound();
                }

                if ($mode === "edit_dir" && $dir->getOwner()->getId() !== $this->user->identity->getId()) {
                    $this->flashFail("err", tr("forbidden"));
                }

                if ($mode === "create_template" && $dir->getOwner()->getId() !== $this->user->identity->getId()) {
                    $this->flashFail("err", tr("forbidden"));
                }

                $this->template->dir = $dir;
                $templates = $dir->getTemplates();
                $this->template->templates = $templates;
                $this->template->templatesCount = count($templates);
                $this->template->selectedTemplate = (int) $this->queryParam("id");
            }
        }
    }

    function renderDeleteDir(int $id): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);
        $dir = (new SupportTemplatesDirs)->get($id);

        if (!$dir || $dir->isDeleted()) {
            $this->notFound();
        }

        if ($dir->getOwner()->getId() !== $this->user->identity->getId()) {
            $this->flashFail("err", tr("forbidden"));
        }

        $templates = $dir->getTemplates();

        foreach ($templates as $template) {
            $template->delete();
        }

        $dir->delete();

        $this->redirect("/support/templates");
    }

    function renderDeleteTemplate(int $id): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);
        $template = (new SupportTemplates)->get($id);

        if (!$template || $template->isDeleted()) {
            $this->notFound();
        }

        if ($template->getOwner()->getId() !== $this->user->identity->getId()) {
            $this->flashFail("err", tr("forbidden"));
        }

        $dir = $template->getDir()->getId();
        $template->delete();

        $this->redirect("/support/templates?act=list&dir=$dir");
    }

}
