<?php declare(strict_types=1);
namespace openvk\ServiceAPI;
use Chandler\MVC\Routing\Router;
use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Exceptions\{AlreadyVotedException, InvalidOptionException, PollLockedException};
use openvk\Web\Models\Repositories\Polls as PollRepo;
use UnexpectedValueException;

class Polls implements Handler
{
    protected $user;
    protected $polls;
    
    function __construct(?User $user)
    {
        $this->user  = $user;
        $this->polls = new PollRepo;
    }
    
    private function getPollHtml(int $poll): string
    {
        return Router::i()->execute("/poll$poll", "SAPI");
    }
    
    function vote(int $pollId, string $options, callable $resolve, callable $reject): void
    {
        $poll = $this->polls->get($pollId);
        if(!$poll) {
            $reject(1, "Poll not found");
            return;
        }

        if(!$poll->canBeViewedBy($this->user)) {
            $reject(12, "Access to poll denied");
            return;
        }
        
        try {
            $options = explode(",", $options);
            $poll->vote($this->user, $options);
        } catch(AlreadyVotedException $ex) {
            $reject(10, "Poll state changed: user has already voted.");
            return;
        } catch(PollLockedException $ex) {
            $reject(25, "Poll state changed: poll has ended.");
            return;
        } catch(InvalidOptionException $ex) {
            $reject(34, "Foreign options passed.");
            return;
        } catch(UnexpectedValueException $ex) {
            $reject(42, "Too much options passed.");
            return;
        }
        
        $resolve(["html" => $this->getPollHtml($pollId)]);
    }
    
    function unvote(int $pollId, callable $resolve, callable $reject): void
    {
        $poll = $this->polls->get($pollId);
        if(!$poll) {
            $reject(28, "Poll not found");
            return;
        }
        
        if(!$poll->canBeViewedBy($this->user)) {
            $reject(12, "Access to poll denied");
            return;
        }
        
        try {
            $poll->revokeVote($this->user);
        } catch(PollLockedException $ex) {
            $reject(19, "Votes can't be revoked from this poll.");
            return;
        }
    
        $resolve(["html" => $this->getPollHtml($pollId)]);
    }
}