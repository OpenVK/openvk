<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Models\Entities\Poll;
use openvk\Web\Models\Repositories\Polls;

final class PollPresenter extends OpenVKPresenter
{
    private $polls;

    public function __construct(Polls $polls)
    {
        $this->polls = $polls;

        parent::__construct();
    }

    public function renderView(int $id): void
    {
        $poll = $this->polls->get($id);
        if (!$poll) {
            $this->notFound();
        }

        $this->template->id       = $poll->getId();
        $this->template->title    = $poll->getTitle();
        $this->template->isAnon   = $poll->isAnonymous();
        $this->template->multiple = $poll->isMultipleChoice();
        $this->template->unlocked = $poll->isRevotable();
        $this->template->until    = $poll->endsAt();
        $this->template->votes    = $poll->getVoterCount();
        $this->template->meta     = $poll->getMetaDescription();
        $this->template->ended    = $ended = $poll->hasEnded();
        if ((is_null($this->user) || $poll->canVote($this->user->identity)) && !$ended) {
            $this->template->options = $poll->getOptions();

            $this->template->_template = "Poll/Poll.latte";
            return;
        }

        if (is_null($this->user)) {
            $this->template->voted   = false;
            $this->template->results = $poll->getResults();
        } else {
            $this->template->voted   = $poll->hasVoted($this->user->identity);
            $this->template->results = $poll->getResults($this->user->identity);
        }

        $this->template->_template = "Poll/PollResults.latte";
    }

    public function renderVoters(int $pollId): void
    {
        $poll = $this->polls->get($pollId);
        if (!$poll) {
            $this->notFound();
        }

        if ($poll->isAnonymous()) {
            $this->flashFail("err", tr("forbidden"), tr("poll_err_anonymous"));
        }

        $options = $poll->getOptions();
        $option  = (int) base_convert($this->queryParam("option"), 32, 10);
        if (!in_array($option, array_keys($options))) {
            $this->notFound();
        }

        $page   = (int) ($this->queryParam("p") ?? 1);
        $voters = $poll->getVoters($option, $page);

        $this->template->pollId   = $pollId;
        $this->template->options  = $options;
        $this->template->option   = [$option, $options[$option]];
        $this->template->tabs     = $options;
        $this->template->iterator = $voters;
        $this->template->count    = $poll->getVoterCount($option);
        $this->template->page     = $page;
    }
}
