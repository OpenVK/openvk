<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\VKAPI\Handlers\Wall;
use openvk\Web\Models\Repositories\Topics as TopicsRepo;
use openvk\Web\Models\Repositories\Clubs as ClubsRepo;
use openvk\Web\Models\Repositories\Photos as PhotosRepo;
use openvk\Web\Models\Repositories\Videos as VideosRepo;
use openvk\Web\Models\Repositories\Comments as CommentsRepo;
use openvk\Web\Models\Entities\{Topic, Comment, User, Photo, Video};

final class Board extends VKAPIRequestHandler
{
    public function addTopic(int $group_id, string $title, string $text = null, bool $from_group = true)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $club = (new ClubsRepo())->get($group_id);

        if (!$club) {
            $this->fail(15, "Access denied");
        }

        if (!$club->canBeModifiedBy($this->getUser()) && !$club->isEveryoneCanCreateTopics()) {
            $this->fail(15, "Access denied");
        }

        $flags = 0;

        if ($from_group == true && $club->canBeModifiedBy($this->getUser())) {
            $flags |= 0b10000000;
        }

        $topic = new Topic();
        $topic->setGroup($club->getId());
        $topic->setOwner($this->getUser()->getId());
        $topic->setTitle(ovk_proc_strtr($title, 127));
        $topic->setCreated(time());
        $topic->setFlags($flags);

        $topic->save();

        try {
            if (!empty($text)) {
                $comment = new Comment();
                $comment->setOwner($this->getUser()->getId());
                $comment->setModel(get_class($topic));
                $comment->setTarget($topic->getId());
                $comment->setContent($text);
                $comment->setCreated(time());
                $comment->setFlags($flags);

                $comment->save();
            }
        } catch (\Throwable $e) {
            return $topic->getId();
        }

        return $topic->getId();
    }

    public function closeTopic(int $group_id, int $topic_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $topic = (new TopicsRepo())->getTopicById($group_id, $topic_id);

        if (!$topic || !$topic->getClub()->canBeModifiedBy($this->getUser())) {
            return 0;
        }

        if (!$topic->isClosed()) {
            $topic->setClosed(1);
            $topic->save();
        }

        return 1;
    }

    public function createComment(int $group_id, int $topic_id, string $message = "", bool $from_group = true)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (empty($message)) {
            $this->fail(100, "Required parameter 'message' missing.");
        }

        $topic = (new TopicsRepo())->getTopicById($group_id, $topic_id);

        if (!$topic || $topic->isDeleted() || $topic->isClosed()) {
            $this->fail(15, "Access denied");
        }

        $flags = 0;
        if ($from_group != 0 && ($topic->getClub()->canBeModifiedBy($this->user))) {
            $flags |= 0b10000000;
        }

        $comment = new Comment();

        $comment->setOwner($this->getUser()->getId());
        $comment->setModel(get_class($topic));
        $comment->setTarget($topic->getId());
        $comment->setContent($message);
        $comment->setCreated(time());
        $comment->setFlags($flags);

        $comment->save();

        return $comment->getId();
    }

    public function deleteTopic(int $group_id, int $topic_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $topic = (new TopicsRepo())->getTopicById($group_id, $topic_id);

        if (!$topic || $topic->isDeleted() || !$topic->getClub()->canBeModifiedBy($this->getUser())) {
            return 0;
        }

        $topic->deleteTopic();

        return 1;
    }

    public function editTopic(int $group_id, int $topic_id, string $title)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $topic = (new TopicsRepo())->getTopicById($group_id, $topic_id);

        if (!$topic || $topic->isDeleted() || !$topic->canBeModifiedBy($this->getUser())) {
            return 0;
        }

        $topic->setTitle(ovk_proc_strtr($title, 127));

        $topic->save();

        return 1;
    }

    public function fixTopic(int $group_id, int $topic_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $topic = (new TopicsRepo())->getTopicById($group_id, $topic_id);

        if (!$topic || !$topic->getClub()->canBeModifiedBy($this->getUser())) {
            return 0;
        }

        $topic->setPinned(1);

        $topic->save();

        return 1;
    }

    public function getComments(int $group_id, int $topic_id, bool $need_likes = false, int $offset = 0, int $count = 10, bool $extended = false)
    {
        $this->requireUser();

        if ($count < 1 || $count > 100) {
            $this->fail(4, "Invalid count");
        }

        $topic = (new TopicsRepo())->getTopicById($group_id, $topic_id);

        if (!$topic || $topic->isDeleted()) {
            $this->fail(5, "Not found");
        }

        $obj = (object) [
            "items" => [],
        ];

        if ($extended) {
            $obj->profiles = [];
            $obj->groups = [];
        }

        $comments = array_slice(iterator_to_array($topic->getComments(1, $count + $offset)), $offset);

        foreach ($comments as $comment) {
            $obj->items[] = $comment->toVkApiStruct($this->getUser(), $need_likes);

            if ($extended) {
                $owner = $comment->getOwner();

                if ($owner instanceof \openvk\Web\Models\Entities\User) {
                    $obj->profiles[] = $owner->toVkApiStruct();
                }

                if ($owner instanceof \openvk\Web\Models\Entities\Club) {
                    $obj->groups[] = $owner->toVkApiStruct();
                }
            }
        }

        return $obj;
    }

    public function getTopics(int $group_id, string $topic_ids = "", int $offset = 0, int $count = 10, bool $extended = false, int $preview = 0, int $preview_length = 90)
    {
        # TODO: $extended

        $this->requireUser();

        if ($count < 1 || $count > 100) {
            $this->fail(4, "Invalid count");
        }

        $obj = (object) [];

        $club = (new ClubsRepo())->get($group_id);

        if (!$club || !$club->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $topics = array_slice(iterator_to_array((new TopicsRepo())->getClubTopics($club, 1, $count + $offset)), $offset);

        $obj->count = (new TopicsRepo())->getClubTopicsCount($club);
        $obj->items = [];
        $obj->profiles = [];
        $obj->can_add_topics = $club->canBeModifiedBy($this->getUser()) ? true : ($club->isEveryoneCanCreateTopics() ? true : false);

        if (empty($topic_ids)) {
            foreach ($topics as $topic) {
                $obj->items[] = $topic->toVkApiStruct($preview, $preview_length > 1 ? $preview_length : 90);
            }
        } else {
            $topics = explode(',', $topic_ids);

            foreach ($topics as $topic_id) {
                $topic = (new TopicsRepo())->getTopicById($group_id, (int) $topic_id);

                if ($topic && !$topic->isDeleted()) {
                    $obj->items[] = $topic->toVkApiStruct($preview, $preview_length > 1 ? $preview_length : 90);
                }
            }
        }

        return $obj;
    }

    public function openTopic(int $group_id, int $topic_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $topic = (new TopicsRepo())->getTopicById($group_id, $topic_id);

        if (!$topic || !$topic->isDeleted() || !$topic->getClub()->canBeModifiedBy($this->getUser())) {
            return 0;
        }

        if ($topic->isClosed()) {
            $topic->setClosed(0);
            $topic->save();
        }

        return 1;
    }

    public function unfixTopic(int $group_id, int $topic_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $topic = (new TopicsRepo())->getTopicById($group_id, $topic_id);

        if (!$topic || !$topic->getClub()->canBeModifiedBy($this->getUser())) {
            return 0;
        }

        if ($topic->isPinned()) {
            $topic->setClosed(0);
            $topic->save();
        }

        $topic->setPinned(0);

        $topic->save();

        return 1;
    }
}
