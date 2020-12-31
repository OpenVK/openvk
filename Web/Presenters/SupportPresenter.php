<?php declare(strict_types=1);
namespace openvk\Web\Presenters;
use openvk\Web\Models\Entities\Ticket;
use openvk\Web\Models\Repositories\Tickets;
//
use openvk\Web\Models\Entities\TicketComment;
use openvk\Web\Models\Repositories\TicketComments;
// use openvk\Web\Models\Repositories\Users;
use openvk\Web\Models\RowModel;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;
use Chandler\Session\Session;
use Netcarver\Textile;

final class SupportPresenter extends OpenVKPresenter
{
    protected $banTolerant = true;
    
    private $tickets;
    private $comments;
    
    function __construct(Tickets $tickets, TicketComments $ticketComments)
    {
        $this->tickets = $tickets;
        $this->comments = $ticketComments;
        
        parent::__construct();
    }
    
    function renderIndex(): void
    {
         $this->assertUserLoggedIn();
         $this->template->mode = in_array($this->queryParam("act"), ["faq", "new", "list"]) ? $this->queryParam("act") : "faq";
            
         $tickets = $this->tickets->getTicketsByuId($this->user->id);
         if ($tickets) {
             $this->template->tickets = $tickets;
         }
               
            
        if($_SERVER["REQUEST_METHOD"] === "POST") 
        {
            if(!empty($this->postParam("name")) && !empty($this->postParam("text")))
            {
                $this->assertNoCSRF();
                $this->willExecuteWriteAction();
                
                $ticket = new Ticket;
                $ticket->setType(0);
                $ticket->setUser_id($this->user->id);
                $ticket->setName($this->postParam("name"));
                $ticket->setText($this->postParam("text"));
                $ticket->setcreated(time());
                $ticket->save();
                header("HTTP/1.1 302 Found");
                header("Location: /support/view/" . $ticket->getId());
            } else {
                $this->flashFail("err", "Ошибка", "Вы не ввели имя или текст ");
            }
            // $this->template->test = 'cool post';
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
        $ticket = $this->tickets->get($id);
        $ticketComments1 = $this->comments->getCommentsById($id);
        if(!$ticket || $ticket->isDeleted() != 0 || $ticket->authorId() !== $this->user->id) {
            $this->notFound();
        } else {
                $this->template->ticket        = $ticket;
                $this->template->comments     = $ticketComments1;
                $this->template->id         = $id;
        }
    }
    
    function renderDelete(int $id): void 
    {
            $this->assertUserLoggedIn();
            $this->willExecuteWriteAction();
            if (!empty($id)) {
                $ticket = $this->tickets->get($id);
                if (!$ticket || $ticket->isDeleted() != 0 || $ticket->authorId() !== $this->user->id)
                {
                    $this->notFound();
                } else {
                    $ticket->delete();
                    header("HTTP/1.1 302 Found");
                    header("Location: /support");
                }
            }
    }
    
    function renderMakeComment(int $id): void 
    {
        $ticket = $this->tickets->get($id);
        
        if($ticket->isDeleted() === 1 || $ticket->getType() === 2 || $ticket->authorId() !== $this->user->id) {
            header("HTTP/1.1 403 Forbidden");
            header("Location: /support/view/" . $id);
            exit;
        }
        
        if($_SERVER["REQUEST_METHOD"] === "POST") 
        {
            if(!empty($this->postParam("text")))
            {
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
                $this->flashFail("err", "Ошибка", "Вы не ввели текст");
            }
        }
    }
    
    function renderAnswerTicket(int $id): void
    {
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);
        $ticket = $this->tickets->get($id);
        $ticketComments = $this->comments->getCommentsById($id);
        $this->template->ticket        = $ticket;
        $this->template->comments     = $ticketComments;
        $this->template->id         = $id;
    }
    
    function renderAnswerTicketReply(int $id): void
    {
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);
        
        $ticket = $this->tickets->get($id);
        
        if($_SERVER["REQUEST_METHOD"] === "POST") 
        {
            $this->willExecuteWriteAction();
            
            if(!empty($this->postParam("text")) && !empty($this->postParam("status")))
            {
                $ticket->setType($this->postParam("status"));
                $ticket->save();
                
                $this->assertNoCSRF();
                $comment = new TicketComment;
                $comment->setUser_id($this->user->id);
                $comment->setUser_type(1);
                $comment->setText('Здравствуйте, '.$ticket->getUser()->getFirstName().'!<br></br>'.$this->postParam("text").'<br></br>С уважением,<br/> Команда поддержки OpenVK.');
                $comment->setTicket_id($id);
                $comment->setCreated(time());
                $comment->save();
            } elseif (empty($this->postParam("text"))) {
                $ticket->setType($this->postParam("status"));
                $ticket->save();
            }
            
            $this->flashFail("succ", "Тикет изменён", "Изменения вступят силу через несколько секунд.");
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
}
