<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\Ticket;
use openvk\Web\Models\Repositories\Tickets;
use openvk\Web\Models\Entities\TicketComment;
use openvk\Web\Models\Repositories\TicketComments;
use openvk\Web\Util\Telegram;
use Chandler\Session\Session;
use Netcarver\Textile;

final class SupportPresenter extends OpenVKPresenter
{
    protected $banTolerant = true;
    
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

        $tickets = $this->tickets->getTicketsByuId($this->user->id);
        if($tickets)
            $this->template->tickets = $tickets;
        if($_SERVER["REQUEST_METHOD"] === "POST") {
            if(!empty($this->postParam("name")) && !empty($this->postParam("text"))) {
                $this->assertNoCSRF();
                $this->willExecuteWriteAction();

                $ticket = new Ticket;
                $ticket->setType(0);
                $ticket->setUser_id($this->user->id);
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

                header("HTTP/1.1 302 Found");
                header("Location: /support/view/" . $ticket->getId());
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
                header("HTTP/1.1 302 Found");
                if($ticket->getUserId() !== $this->user->id && $this->hasPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0))
                    header("Location: /support/tickets");
                else
                    header("Location: /support");
                $ticket->delete();
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
                
                $this->assertNoCSRF();
                $this->willExecuteWriteAction();
                
                $comment = new TicketComment;
                $comment->setUser_id($this->user->id);
                $comment->setUser_type(0);
                $comment->setText($this->postParam("text"));
                $comment->setTicket_id($id);
                $comment->setCreated(time());
                $comment->save();
                
                header("HTTP/1.1 302 Found");
                header("Location: /support/view/" . $id);
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
                
                $this->assertNoCSRF();
                $comment = new TicketComment;
                $comment->setUser_id($this->user->id);
                $comment->setUser_type(1);
                $comment->setText($this->postParam("text"));
                $comment->setTicket_id($id);
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
        if(file_exists("$base/$name.$lang.textile"))
            $file = "$base/$name.$lang.textile";
        else if(file_exists("$base/$name.textile"))
            $file = "$base/$name.textile";
        else
            $this->notFound();
        
        $lines = file($file);
        if(!preg_match("%^OpenVK-KB-Heading: (.+)$%", $lines[0], $matches)) {
            $heading = "Article $name";
        } else {
            $heading = $matches[1];
            array_shift($lines);
        }
        
        $content = implode("\r\n", $lines);
        
        $parser = new Textile\Parser;
        $this->template->heading = $heading;
        $this->template->content = $parser->parse($content);
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
}