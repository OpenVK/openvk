<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Models\Entities\{SupportAgent, Ticket, TicketComment, FAQ};
use openvk\Web\Models\Repositories\{Tickets, Users, TicketComments, SupportAgents};
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
    private $faq;

    public function __construct(Tickets $tickets, TicketComments $ticketComments)
    {
        $this->tickets  = $tickets;
        $this->comments = $ticketComments;
        $this->faq      = new FAQ();

        parent::__construct();
    }
    
    public function renderIndex(): void
    {
        $this->assertUserLoggedIn();
        $this->template->mode = in_array($this->queryParam("act"), ["faq", "new", "list"]) ? $this->queryParam("act") : "faq";

        if ($this->template->mode === "faq") {
            $lang = Session::i()->get("lang", getDefaultLanguage());

            $isAdmin = $this->user->identity->getChandlerUser()->can("access")->model("admin")->whichBelongsTo(NULL) 
                || $this->user->identity->getChandlerUser()->can("write")->model('openvk\Web\Models\Entities\TicketReply')->whichBelongsTo(0)
                || $this->user->identity->getChandlerUser()->can("admin")->model('openvk\Web\Models\Entities\Report')->whichBelongsTo(0)
                || $this->user->identity->getChandlerUser()->can("write")->model('openvk\Web\Models\Entities\Ban')->whichBelongsTo(0);

            if ($this->queryParam("topic") !== null) {
                $this->template->isTopic  = True;
                $this->template->topic    = $this->faq->getTopic($this->queryParam("topic"), $lang, $isAdmin);
                $this->template->question = $this->queryParam("question");
                $this->template->topics   = $this->faq->getTopicList($lang, $isAdmin);
            } else {
                $closedIssues  = json_decode($_COOKIE["faq_closed_issues"] ?? "[]", true);
                $currentIssues = $this->faq->getIssues($lang, $isAdmin);
                $hasUnread     = False;

                foreach ($currentIssues as $i => $issue) {
                    if (!in_array($i, $closedIssues)) {
                        unset($currentIssues[$i]);
                        $currentIssues = [$i => $issue] + $currentIssues;
                        $hasUnread = True;
                    }
                }

                $this->template->isTopic    = False;
                $this->template->topics     = $this->faq->getTopicPreviews($lang, $isAdmin);
                $this->template->issues     = $currentIssues;
                $this->template->hasUnread  = $hasUnread;
                $this->template->firstIssue = reset($currentIssues) ?: null;
            }

            $this->template->issuesJson = json_encode($currentIssues, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        }

        $this->template->count = $this->tickets->getTicketsCountByUserId($this->user->id);
        if ($this->template->mode === "list") {
            $this->template->page    = (int) ($this->queryParam("p") ?? 1);
            $this->template->tickets = iterator_to_array($this->tickets->getTicketsByUserId($this->user->id, $this->template->page));
        }

        if ($this->template->mode === "new") {
            $this->template->banReason = $this->user->identity->getBanInSupportReason();
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if ($this->user->identity->isBannedInSupport()) {
                $this->flashFail("err", tr("not_enough_permissions"), tr("not_enough_permissions_comment"));
            }

            if (!empty($this->postParam("name")) && !empty($this->postParam("text"))) {
                $this->willExecuteWriteAction();

                $ticket = new Ticket();
                $ticket->setType(0);
                $ticket->setUser_Id($this->user->id);
                $ticket->setName($this->postParam("name"));
                $ticket->setText($this->postParam("text"));
                $ticket->setcreated(time());
                $ticket->save();

                $helpdeskChat = OPENVK_ROOT_CONF["openvk"]["credentials"]["telegram"]["helpdeskChat"];
                if ($helpdeskChat) {
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

    public function renderList(): void
    {
        $this->assertUserLoggedIn();
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);

        $act = $this->queryParam("act") ?? "open";
        switch ($act) {
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

    public function renderView(int $id): void
    {
        $this->assertUserLoggedIn();
        $ticket         = $this->tickets->get($id);
        $ticketComments = $this->comments->getCommentsById($id);
        if (!$ticket || $ticket->isDeleted() != 0 || $ticket->getUserId() !== $this->user->id) {
            $this->notFound();
        } else {
            $this->template->ticket   = $ticket;
            $this->template->comments = $ticketComments;
            $this->template->id       = $id;
        }
    }

    public function renderDelete(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->willExecuteWriteAction();

        if (!empty($id)) {
            $ticket = $this->tickets->get($id);
            if (!$ticket || $ticket->isDeleted() != 0 || $ticket->getUserId() !== $this->user->id && !$this->hasPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0)) {
                $this->notFound();
            } else {
                if ($ticket->getUserId() !== $this->user->id && $this->hasPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0)) {
                    $_redirect = "/support/tickets";
                } else {
                    $_redirect = "/support?act=list";
                }

                $helpdeskChat = OPENVK_ROOT_CONF["openvk"]["credentials"]["telegram"]["helpdeskChat"];
                if ($helpdeskChat) {
                    $serverUrl     = ovk_scheme(true) . $_SERVER["SERVER_NAME"];
                    $telegramText  = "❌ <b>Тикет под названием</b> &quot;{$ticket->getName()}&quot; от <a href='$serverUrl{$ticket->getUser()->getURL()}'>{$ticket->getUser()->getCanonicalName()}</a> ({$ticket->getUser()->getRegistrationIP()}) <b>был удалён.</b>\n";
                    Telegram::send($helpdeskChat, $telegramText);
                }

                $ticket->delete();
                $this->redirect($_redirect);
            }
        }
    }

    public function renderMakeComment(int $id): void
    {
        $ticket = $this->tickets->get($id);

        if ($ticket->isDeleted() === 1 || $ticket->getType() === 2 || $ticket->getUserId() !== $this->user->id) {
            header("HTTP/1.1 403 Forbidden");
            header("Location: /support/view/" . $id);
            exit;
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if (!empty($this->postParam("text"))) {
                $ticket->setType(0);
                $ticket->save();

                $this->willExecuteWriteAction();

                $comment = new TicketComment();
                $comment->setUser_id($this->user->id);
                $comment->setUser_type(0);
                $comment->setText($this->postParam("text"));
                $comment->setTicket_id($id);
                $comment->setCreated(time());
                $comment->save();

                $helpdeskChat = OPENVK_ROOT_CONF["openvk"]["credentials"]["telegram"]["helpdeskChat"];
                if ($helpdeskChat) {
                    $serverUrl     = ovk_scheme(true) . $_SERVER["SERVER_NAME"];
                    $commentText   = ovk_proc_strtr($this->postParam("text"), 1500);
                    $telegramText  = "💬 <b>Новый комментарий от автора тикета</b> <a href='$serverUrl/support/reply/$id'>&quot;{$ticket->getName()}&quot;</a>\n";
                    $telegramText .= "$commentText\n\n";
                    $telegramText .= "Автор: <a href='$serverUrl{$ticket->getUser()->getURL()}'>{$ticket->getUser()->getCanonicalName()}</a> ({$ticket->getUser()->getRegistrationIP()})\n";
                    Telegram::send($helpdeskChat, $telegramText);
                }

                $this->redirect("/support/view/" . $id);
            } else {
                $this->flashFail("err", tr("error"), tr("you_have_not_entered_text"));
            }
        }
    }

    public function renderAnswerTicket(int $id): void
    {
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);
        $ticket = $this->tickets->get($id);

        if (!$ticket || $ticket->isDeleted() != 0) {
            $this->notFound();
        }

        $ticketComments = $this->comments->getCommentsById($id);
        $this->template->ticket      = $ticket;
        $this->template->comments    = $ticketComments;
        $this->template->id          = $id;
        $this->template->fastAnswers = OPENVK_ROOT_CONF["openvk"]["preferences"]["support"]["fastAnswers"];
    }

    public function renderAnswerTicketReply(int $id): void
    {
        $this->assertPermission('openvk\Web\Models\Entities\TicketReply', 'write', 0);

        $support_names = new SupportAgents();
        $agent = $support_names->get($this->user->id);
        $ticket = $this->tickets->get($id);

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $this->willExecuteWriteAction();

            $helpdeskChat = OPENVK_ROOT_CONF["openvk"]["credentials"]["telegram"]["helpdeskChat"];

            if (!empty($this->postParam("text")) && !empty($this->postParam("status"))) {
                $status = $this->postParam("status");
                $ticket->setType($status);
                $ticket->save();

                switch ($status) {
                    default:
                        # NOTICE falling through
                    case 0:
                        $state = "Вопрос на рассмотрении";
                        break;
                    case 1:
                        $state = "Есть ответ";
                        break;
                    case 2:
                        $state = "Закрыто";
                }

                $comment = new TicketComment();
                $comment->setUser_id($this->user->id);
                $comment->setUser_type(1);
                $comment->setText($this->postParam("text"));
                $comment->setTicket_Id($id);
                $comment->setCreated(time());
                $comment->save();

                if ($helpdeskChat) {
                    $serverUrl     = ovk_scheme(true) . $_SERVER["SERVER_NAME"] . "/support/agent" . $this->user->id;
                    $ticketUrl     = ovk_scheme(true) . $_SERVER["SERVER_NAME"] . "/support/reply/" . $id;
                    $commentText   = ovk_proc_strtr($this->postParam("text"), 1500);
                    $telegramText  = "💬 <b>Новый комментарий от агента к тикету</b> <a href='$ticketUrl'>&quot;{$ticket->getName()}&quot;</a>\n";
                    $telegramText .= "Статус: {$state}\n\n";
                    $telegramText .= "$commentText\n\n";
                    $telegramText .= "Агент: <a href='$serverUrl'>{$this->user->identity->getFullName()}</a>\n";
                    Telegram::send($helpdeskChat, $telegramText);
                }
            } elseif (empty($this->postParam("text"))) {
                $status = $this->postParam("status");
                $ticket->setType($status);
                $ticket->save();

                switch ($status) {
                    default:
                        # NOTICE falling through
                    case 0:
                        $state = "Вопрос на рассмотрении";
                        break;
                    case 1:
                        $state = "Есть ответ";
                        break;
                    case 2:
                        $state = "Закрыто";
                }

                if ($helpdeskChat) {
                    $serverUrl     = ovk_scheme(true) . $_SERVER["SERVER_NAME"] . "/support/agent" . $this->user->id;
                    $ticketUrl     = ovk_scheme(true) . $_SERVER["SERVER_NAME"] . "/support/reply/" . $id;
                    $telegramText  = "🔔 <b>Изменён статус тикета</b> <a href='$ticketUrl'>&quot;{$ticket->getName()}&quot;</a>: <b>{$state}</b>\n\n";
                    $telegramText .= "Агент: <a href='$serverUrl'>{$this->user->identity->getFullName()}</a>\n";
                    Telegram::send($helpdeskChat, $telegramText);
                }
            }

            $this->flashFail("succ", tr("ticket_changed"), tr("ticket_changed_comment"));
        }
    }

    public function renderKnowledgeBaseArticle(string $name): void
    {
        $lang = Session::i()->get("lang", "ru");
        $base = OPENVK_ROOT . "/data/knowledgebase";
        if (file_exists("$base/$name.$lang.md")) {
            $file = "$base/$name.$lang.md";
        } elseif (file_exists("$base/$name.md")) {
            $file = "$base/$name.md";
        } else {
            $this->notFound();
        }

        $lines = file($file);
        if (!preg_match("%^OpenVK-KB-Heading: (.+)$%", $lines[0], $matches)) {
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

    public function renderDeleteComment(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->assertNoCSRF();

        $comment = $this->comments->get($id);
        if (is_null($comment)) {
            $this->notFound();
        }

        $ticket = $comment->getTicket();

        if ($ticket->isDeleted()) {
            $this->notFound();
        }

        if (!($ticket->getUserId() === $this->user->id && $comment->getUType() === 0)) {
            $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);
        }

        $this->willExecuteWriteAction();
        $comment->delete();

        $this->flashFail("succ", tr("ticket_changed"), tr("ticket_changed_comment"));
    }

    public function renderRateAnswer(int $id, int $mark): void
    {
        $this->willExecuteWriteAction();
        $this->assertUserLoggedIn();
        $this->assertNoCSRF();

        $comment = $this->comments->get($id);

        if ($this->user->id !== $comment->getTicket()->getUser()->getId()) {
            header("HTTP/1.1 403 Forbidden");
            exit();
        }

        if ($mark !== 1 && $mark !== 2) {
            header("HTTP/1.1 400 Bad Request");
            exit();
        }

        $comment->setMark($mark);
        $comment->save();

        header("HTTP/1.1 200 OK");
        exit();
    }

    public function renderQuickBanInSupport(int $id): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);
        $this->assertNoCSRF();

        $user = (new Users())->get($id);
        if (!$user) {
            exit(json_encode([ "error" => "User does not exist" ]));
        }

        $user->setBlock_In_Support_Reason($this->queryParam("reason"));
        $user->save();

        if ($this->queryParam("close_tickets")) {
            DatabaseConnection::i()->getConnection()->query("UPDATE tickets SET type = 2 WHERE user_id = " . $id);
        }

        $this->returnJson([ "success" => true, "reason" => $this->queryParam("reason") ]);
    }

    public function renderQuickUnbanInSupport(int $id): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);
        $this->assertNoCSRF();

        $user = (new Users())->get($id);
        if (!$user) {
            exit(json_encode([ "error" => "User does not exist" ]));
        }

        $user->setBlock_In_Support_Reason(null);
        $user->save();
        $this->returnJson([ "success" => true ]);
    }

    public function renderAgent(int $id): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);

        $support_names = new SupportAgents();

        if (!$support_names->isExists($id)) {
            $this->template->mode = "edit";
        }

        $this->template->agent_id    = $id;
        $this->template->mode        = in_array($this->queryParam("act"), ["info", "edit"]) ? $this->queryParam("act") : "info";
        $this->template->agent       = $support_names->get($id) ?? null;
        $this->template->counters    = [
            "all"    => (new TicketComments())->getCountByAgent($id),
            "good"   => (new TicketComments())->getCountByAgent($id, 1),
            "bad"    => (new TicketComments())->getCountByAgent($id, 2),
        ];

        if ($id != $this->user->identity->getId()) {
            if ($support_names->isExists($id)) {
                $this->template->mode = "info";
            } else {
                $this->redirect("/support/agent" . $this->user->identity->getId());
            }
        }
    }

    public function renderEditAgent(int $id): void
    {
        $this->assertPermission("openvk\Web\Models\Entities\TicketReply", "write", 0);
        $this->assertNoCSRF();

        $support_names = new SupportAgents();
        $agent = $support_names->get($id);

        if ($agent) {
            if ($agent->getAgentId() != $this->user->identity->getId()) {
                $this->flashFail("err", tr("error"), tr("forbidden"));
            }
        }


        $isNameEmpty = mb_strlen(trim($this->postParam("name"))) === 0;
        $avatarUrl = mb_strlen(trim($this->postParam("avatar"))) > 0 ? $this->postParam("avatar") : "/assets/packages/static/openvk/img/support.jpeg";

        if ($support_names->isExists($id)) {
            $agent = $support_names->get($id);

            if ($isNameEmpty) {
                $agent->delete(false);
                $this->redirect("/support/tickets");
                return;
            }

            $agent->setName($this->postParam("name"));
            $agent->setNumerate((int) $this->postParam("number") ?? null);
            $agent->setIcon($avatarUrl);
            $agent->save();
            $this->flashFail("succ", tr("agent_profile_edited"));
        } else {
            if ($isNameEmpty) {
                $this->flashFail("err", tr("helpdesk_agent_name_empty"));
                return;
            }

            $agent = new SupportAgent();
            $agent->setAgent($this->user->identity->getId());
            $agent->setName($this->postParam("name"));
            $agent->setNumerate((int) $this->postParam("number") ?? null);
            $agent->setIcon($avatarUrl);
            $agent->save();
            $this->flashFail("succ", tr("agent_profile_created_1"), tr("agent_profile_created_2"));
        }
    }

    public function renderCloseTicket(int $id): void
    {
        $this->assertUserLoggedIn();
        $this->assertNoCSRF();
        $this->willExecuteWriteAction();

        $ticket = $this->tickets->get($id);

        if ($ticket->isDeleted() === 1 || $ticket->getType() === 2 || $ticket->getUserId() !== $this->user->id) {
            header("HTTP/1.1 403 Forbidden");
            header("Location: /support/view/" . $id);
            exit;
        }

        $ticket->setType(2);
        $ticket->save();

        $helpdeskChat = OPENVK_ROOT_CONF["openvk"]["credentials"]["telegram"]["helpdeskChat"];
        if ($helpdeskChat) {
            $serverUrl     = ovk_scheme(true) . $_SERVER["SERVER_NAME"];
            $telegramText  = "🔒 <b>Тикет под названием</b> <a href='$serverUrl/support/reply/{$ticket->getId()}'>&quot;{$ticket->getName()}&quot;</a> от <a href='$serverUrl{$ticket->getUser()->getURL()}'>{$ticket->getUser()->getCanonicalName()}</a> ({$ticket->getUser()->getRegistrationIP()}) <b>был закрыт автором.</b>\n";
            Telegram::send($helpdeskChat, $telegramText);
        }

        $this->flashFail("succ", tr("ticket_changed"), tr("ticket_changed_comment"));
    }

    public function renderAdmin(): void
    {
        $this->assertPermission("admin", "access", -1);

        $mainLang = getDefaultLanguage();
        $lang = Session::i()->get("lang", $mainLang);

        $this->template->mode     = in_array($this->queryParam("act"), ["knownIssues", "newIssue", "editFaq", "newTopic"]) ? $this->queryParam("act") : "knownIssues";
        $this->template->mainLang = $mainLang;
        
        $this->template->topics  = $this->faq->getTopicList($lang, true, true);
        $this->template->isTopic = array_key_exists($this->queryParam("topic"), $this->template->topics);
        
        $this->template->issues  = $this->faq->getIssues($lang, true, true);
        $this->template->isIssue = array_key_exists($this->queryParam("issue"), $this->template->issues);

        if ($this->template->mode === "editFaq" && $this->template->isTopic) {
            $this->template->topic = $this->faq->getRawTopic($this->queryParam("topic"));
        }
        
        if ($this->template->mode === "knownIssues" && $this->template->isIssue) {
            $this->template->issue = $this->faq->getRawIssue($this->queryParam("issue"));
        }

        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            if ($this->template->mode === "newTopic" || ($this->template->mode === "editFaq" && $this->template->isTopic)) {
                $topic = [];
                $url = trim($_POST["url"] ?? "");

                if ($this->template->isTopic) {
                    unset($this->template->topics[$url]);
                }

                if ($url === "" || !preg_match('/^[a-zA-Z0-9]+$/', $url)) {
                    $this->flashFail("err", tr("support_admin_err_title"), tr("support_admin_err_msg_3"));
                    return;
                } elseif (array_key_exists($url, $this->template->topics)) {
                    $this->flashFail("err", tr("support_admin_err_title"), tr("support_admin_err_msg_2", $url));
                    return;
                }
                
                $titles = [];
                foreach ($_POST["title"] as $l => $t) {
                    if (trim($t ?? "") === "") {
                        if ($l === $this->template->mainLang) {
                            $this->flashFail("err", tr("support_admin_err_title"), tr("support_admin_err_msg_1"));
                            return;
                        }
                        continue;
                    }
                    $titles[$l] = $t;
                }

                foreach ($_POST["questions"] as $l => $q) {
                    if (!array_key_exists($l, $titles)) { 
                        $this->flashFail("err", tr("support_admin_err_title"), tr("support_admin_err_msg_1"));
                        return;                        
                     }
                    foreach ($q as $i => $item) {
                        if (trim($item["q"] ?? "") === "" || trim($item["a"] ?? "") === "") {
                            unset($_POST["questions"][$l][$i]);
                        }
                    }
                    
                }

                $topic[$url] = [
                    "title"     => $titles,
                    "icon"      => (int) $_POST["icon"] ?? 1,
                    "enabled"   => !isset($_POST['disabled']),
                    "for_admin" => isset($_POST['for_admin'])
                ];

                $this->faq->createTopic($topic, $_POST["questions"]);
                $this->flashFail("succ", tr("support_admin_succ_title"), tr("support_admin_succ_msg", $_POST["url"]));
            }

            if ($this->queryParam("act") === "deleteTopic") {
                if (array_key_exists($_POST["name"], $this->template->topics)) {
                    $this->returnJson([ "success" => $this->faq->deleteTopic($_POST["name"]) ]);
                } else {
                    $this->returnJson([ "success" => false ]);
                }
            }

            if ($this->queryParam("act") === "disableTopic") {
                if (array_key_exists($_POST["name"], $this->template->topics)) {
                    $this->returnJson([ "success" => $this->faq->disableTopic($_POST["name"]) ]);
                } else {
                    $this->returnJson([ "success" => -1 ]);
                }
            }
        }
    }
}
