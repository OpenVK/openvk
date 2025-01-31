<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use openvk\Web\Models\Exceptions\TooMuchOptionsException;
use openvk\Web\Util\DateTime;
use UnexpectedValueException;
use Nette\InvalidStateException;
use openvk\Web\Models\Repositories\{Users, Posts};
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\Exceptions\PollLockedException;
use openvk\Web\Models\Exceptions\AlreadyVotedException;
use openvk\Web\Models\Exceptions\InvalidOptionException;

class Poll extends Attachable
{
    protected $tableName = "polls";

    private $choicesToPersist = [];

    public function getTitle(): string
    {
        return $this->getRecord()->title;
    }

    public function getMetaDescription(): string
    {
        $props = [];
        $props[] = tr($this->isAnonymous() ? "poll_anon" : "poll_public");
        if ($this->isMultipleChoice()) {
            $props[] = tr("poll_multi");
        }
        if (!$this->isRevotable()) {
            $props[] = tr("poll_lock");
        }
        if (!is_null($this->endsAt())) {
            $props[] = tr("poll_until", $this->endsAt());
        }

        return implode(" • ", $props);
    }

    public function getOwner(): User
    {
        return (new Users())->get($this->getRecord()->owner);
    }

    public function getOptions(): array
    {
        $options = $this->getRecord()->related("poll_options.poll");
        $res     = [];
        foreach ($options as $opt) {
            $res[$opt->id] = $opt->name;
        }

        return $res;
    }

    public function getUserVote(User $user): ?array
    {
        $ctx       = DatabaseConnection::i()->getContext();
        $votedOpts = $ctx->table("poll_votes")
            ->where(["user" => $user->getId(), "poll" => $this->getId()]);

        if ($votedOpts->count() == 0) {
            return null;
        }

        $res = [];
        foreach ($votedOpts as $votedOpt) {
            $option = $ctx->table("poll_options")->get($votedOpt->option);
            $res[]  = [$option->id, $option->name];
        }

        return $res;
    }

    public function getVoters(int $optionId, int $page = 1, ?int $perPage = null): array
    {
        $res     = [];
        $ctx     = DatabaseConnection::i()->getContext();
        $perPage ??= OPENVK_DEFAULT_PER_PAGE;
        $voters  = $ctx->table("poll_votes")->where(["poll" => $this->getId(), "option" => $optionId]);
        foreach ($voters->page($page, $perPage) as $vote) {
            $res[] = (new Users())->get($vote->user);
        }

        return $res;
    }

    public function getVoterCount(?int $optionId = null): int
    {
        $votes = DatabaseConnection::i()->getContext()->table("poll_votes");
        if (!$optionId) {
            return $votes->select("COUNT(DISTINCT user) AS c")->where("poll", $this->getId())->fetch()->c;
        }

        return $votes->where(["poll" => $this->getId(), "option" => $optionId])->count();
    }

    public function getResults(?User $user = null): object
    {
        $ctx   = DatabaseConnection::i()->getContext();
        $voted = null;
        if (!is_null($user)) {
            $voted = $this->getUserVote($user);
        }

        $result = (object) [];
        $result->totalVotes = $this->getVoterCount();

        $unsOptions = [];
        foreach ($this->getOptions() as $id => $title) {
            $option = (object) [];
            $option->id   = $id;
            $option->name = $title;

            $option->votes  = $this->getVoterCount($id);
            $option->pct    = $result->totalVotes == 0 ? 0 : min(100, floor(($option->votes / $result->totalVotes) * 100));
            $option->voters = $this->getVoters($id, 1, 10);
            if (!$user || !$voted) {
                $option->voted = null;
            } else {
                $option->voted = in_array([$id, $title], $voted);
            }

            $unsOptions[$id] = $option;
        }

        $optionsC = sizeof($unsOptions);
        $sOptions = $unsOptions;
        usort($sOptions, function ($a, $b) { return $a->votes <=> $b->votes; });
        for ($i = 0; $i < $optionsC; $i++) {
            $unsOptions[$id]->rate = $optionsC - $i - 1;
        }

        $result->options = array_values($unsOptions);

        return $result;
    }

    public function isAnonymous(): bool
    {
        return (bool) $this->getRecord()->is_anonymous;
    }

    public function isMultipleChoice(): bool
    {
        return (bool) $this->getRecord()->allows_multiple;
    }

    public function isRevotable(): bool
    {
        return (bool) $this->getRecord()->can_revote;
    }

    public function endsAt(): ?DateTime
    {
        if (!$this->getRecord()->until) {
            return null;
        }

        return new DateTime($this->getRecord()->until);
    }

    public function hasEnded(): bool
    {
        if ($this->getRecord()->ended) {
            return true;
        }

        if (!is_null($this->getRecord()->until)) {
            return time() >= $this->getRecord()->until;
        }

        return false;
    }

    public function hasVoted(User $user): bool
    {
        return !is_null($this->getUserVote($user));
    }

    public function canVote(User $user): bool
    {
        return !$this->hasEnded() && !$this->hasVoted($user) && !is_null($this->getAttachedPost()) && $this->getAttachedPost()->getSuggestionType() == 0;
    }

    public function vote(User $user, array $optionIds): void
    {
        if ($this->hasEnded()) {
            throw new PollLockedException();
        }

        if ($this->hasVoted($user)) {
            throw new AlreadyVotedException();
        }

        $optionIds = array_map(function ($x) { return (int) $x; }, array_unique($optionIds));
        $validOpts = array_keys($this->getOptions());
        if (empty($optionIds) || (sizeof($optionIds) > 1 && !$this->isMultipleChoice())) {
            throw new UnexpectedValueException();
        }

        if (sizeof(array_diff($optionIds, $validOpts)) > 0) {
            throw new InvalidOptionException();
        }

        foreach ($optionIds as $opt) {
            DatabaseConnection::i()->getContext()->table("poll_votes")->insert([
                "user"   => $user->getId(),
                "poll"   => $this->getId(),
                "option" => $opt,
            ]);
        }
    }

    public function revokeVote(User $user): void
    {
        if (!$this->isRevotable()) {
            throw new PollLockedException();
        }

        $this->getRecord()->related("poll_votes.poll")
            ->where("user", $user->getId())->delete();
    }

    public function setOwner(User $owner): void
    {
        $this->stateChanges("owner", $owner->getId());
    }

    public function setEndDate(int $timestamp): void
    {
        if (!is_null($this->getRecord())) {
            throw new PollLockedException();
        }

        $this->stateChanges("until", $timestamp);
    }

    public function setEnded(): void
    {
        $this->stateChanges("ended", 1);
    }

    public function setOptions(array $options): void
    {
        if (!is_null($this->getRecord())) {
            throw new PollLockedException();
        }

        if (sizeof($options) > ovkGetQuirk("polls.max-opts")) {
            throw new TooMuchOptionsException();
        }

        $this->choicesToPersist = $options;
    }

    public function setRevotability(bool $canReVote): void
    {
        if (!is_null($this->getRecord())) {
            throw new PollLockedException();
        }

        $this->stateChanges("can_revote", $canReVote);
    }

    public function setAnonymity(bool $anonymous): void
    {
        $this->stateChanges("is_anonymous", $anonymous);
    }

    public function setMultipleChoice(bool $mc): void
    {
        $this->stateChanges("allows_multiple", $mc);
    }

    public function importXML(User $owner, string $xml): void
    {
        $xml = simplexml_load_string($xml);
        $this->setOwner($owner);
        $this->setTitle($xml["title"] ?? "Untitled");
        $this->setMultipleChoice(($xml["multiple"] ?? "no") == "yes");
        $this->setAnonymity(($xml["anonymous"] ?? "no") == "yes");
        $this->setRevotability(($xml["locked"] ?? "no") == "no");
        if (ctype_digit((string) ($xml["duration"] ?? ""))) {
            $this->setEndDate(time() + ((86400 * (int) $xml["duration"])));
        }

        $options = [];
        foreach ($xml->options->option as $opt) {
            $options[] = (string) $opt;
        }

        if (empty($options)) {
            throw new UnexpectedValueException();
        }

        $this->setOptions($options);
    }

    public static function import(User $owner, string $xml): Poll
    {
        $poll = new Poll();
        $poll->importXML($owner, $xml);
        $poll->save();

        return $poll;
    }

    public function canBeViewedBy(?User $user = null): bool
    {
        # waiting for #935 :(
        /*if(!is_null($this->getAttachedPost())) {
            return $this->getAttachedPost()->canBeViewedBy($user);
        } else {*/
        return true;
        #}

    }

    public function save(?bool $log = false): void
    {
        if (empty($this->choicesToPersist)) {
            throw new InvalidStateException();
        }

        parent::save($log);
        foreach ($this->choicesToPersist as $option) {
            DatabaseConnection::i()->getContext()->table("poll_options")->insert([
                "poll" => $this->getId(),
                "name" => $option,
            ]);
        }
    }

    public function getAttachedPost()
    {
        $post = DatabaseConnection::i()->getContext()->table("attachments")
            ->where(
                ["attachable_type" => static::class,
                    "attachable_id"    => $this->getId()]
            )->fetch();

        if (!is_null($post->target_id)) {
            return (new Posts())->get($post->target_id);
        } else {
            return null;
        }
    }
}
