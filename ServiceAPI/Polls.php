<?php

declare(strict_types=1);

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

    public function __construct(?User $user)
    {
        $this->user  = $user;
        $this->polls = new PollRepo();
    }

    private function getPollHtml(int $poll): string
    {
        return Router::i()->execute("/poll$poll", "SAPI");
    }

    public function vote(int $pollId, string $options, callable $resolve, callable $reject): void
    {
        $poll = $this->polls->get($pollId);
        if (!$poll) {
            $reject("Poll not found");
            return;
        }

        try {
            $options = explode(",", $options);
            $poll->vote($this->user, $options);
        } catch (AlreadyVotedException $ex) {
            $reject("Poll state changed: user has already voted.");
            return;
        } catch (PollLockedException $ex) {
            $reject("Poll state changed: poll has ended.");
            return;
        } catch (InvalidOptionException $ex) {
            $reject("Foreign options passed.");
            return;
        } catch (UnexpectedValueException $ex) {
            $reject("Too much options passed.");
            return;
        }

        $resolve(["html" => $this->getPollHtml($pollId)]);
    }

    public function unvote(int $pollId, callable $resolve, callable $reject): void
    {
        $poll = $this->polls->get($pollId);
        if (!$poll) {
            $reject("Poll not found");
            return;
        }

        try {
            $poll->revokeVote($this->user);
        } catch (PollLockedException $ex) {
            $reject("Votes can't be revoked from this poll.");
            return;
        }

        $resolve(["html" => $this->getPollHtml($pollId)]);
    }
}
