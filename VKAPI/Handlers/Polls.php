<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Entities\Poll;
use openvk\Web\Models\Exceptions\AlreadyVotedException;
use openvk\Web\Models\Exceptions\InvalidOptionException;
use openvk\Web\Models\Exceptions\PollLockedException;
use openvk\Web\Models\Repositories\Polls as PollsRepo;

final class Polls extends VKAPIRequestHandler
{
    function getById(int $poll_id, bool $extended = false, string $fields = "sex,screen_name,photo_50,photo_100,online_info,online") 
    {
        $poll = (new PollsRepo)->get($poll_id);

        if (!$poll)
            $this->fail(100, "One of the parameters specified was missing or invalid: poll_id is incorrect");
        
        $users = array();
        $answers = array();
        foreach($poll->getResults()->options as $answer) {
            $answers[] = (object)[
                "id"    => $answer->id,
                "rate"  => $answer->pct,
                "text"  => $answer->name,
                "votes" => $answer->votes
            ];
        }
        
        $userVote = array();
        foreach($poll->getUserVote($this->getUser()) as $vote)
            $userVote[] = $vote[0];

        $response = [
            "multiple"       => $poll->isMultipleChoice(),
            "end_date"       => $poll->endsAt() == NULL ? 0 : $poll->endsAt()->timestamp(),
            "closed"         => $poll->hasEnded(),
            "is_board"       => false,
            "can_edit"       => false,
            "can_vote"       => $poll->canVote($this->getUser()),
            "can_report"     => false,
            "can_share"      => true,
            "created"        => 0,
            "id"             => $poll->getId(),
            "owner_id"       => $poll->getOwner()->getId(),
            "question"       => $poll->getTitle(),
            "votes"          => $poll->getVoterCount(),
            "disable_unvote" => $poll->isRevotable(),
            "anonymous"      => $poll->isAnonymous(),
            "answer_ids"     => $userVote,
            "answers"        => $answers,
            "author_id"      => $poll->getOwner()->getId(),
        ];

        if ($extended) {
            $response["profiles"] = (new Users)->get(strval($poll->getOwner()->getId()), $fields, 0, 1);
            /* Currently there is only one person that can be shown trough "Extended" param.
             * As "friends" param will be implemented, "profiles" will show more users
             */
        }

        return (object) $response;  
    }

    function addVote(int $poll_id, string $answers_ids) 
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $poll = (new PollsRepo)->get($poll_id);

        if(!$poll)
            $this->fail(251, "Invalid poll id");

        try {
            $poll->vote($this->getUser(), explode(",", $answers_ids));
            return 1;
        } catch(AlreadyVotedException $ex) {
            return 0;
        } catch(PollLockedException $ex) {
            return 0;
        } catch(InvalidOptionException $ex) {
            $this->fail(8, "бдсм вибратор купить в киеве");
        }
    }

    function deleteVote(int $poll_id) 
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $poll = (new PollsRepo)->get($poll_id);

        if(!$poll)
            $this->fail(251, "Invalid poll id");

        try {
            $poll->revokeVote($this->getUser());
            return 1;
        } catch(PollLockedException $ex) {
            $this->fail(15, "Access denied: Poll is locked or isn't revotable");
        } catch(InvalidOptionException $ex) {
            $this->fail(8, "how.to. ook.bacon.in.microwova.");
        }
    }
}
