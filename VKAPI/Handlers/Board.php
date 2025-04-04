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
    public function addTopic(int $group_id, string $title, string $text = "", bool $from_group = true)
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

    public function createComment(int $group_id, int $topic_id, string $message = "", string $attachments = "", bool $from_group = true)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (empty($message) && empty($attachments)) {
            $this->fail(100, "Required parameter 'message' missing.");
        }

        $topic = (new TopicsRepo())->getTopicById($group_id, $topic_id);
        if (!$topic || $topic->isDeleted() || $topic->isClosed()) {
            $this->fail(15, "Access denied");
        }

        $flags = 0;
        if ($from_group != 0 && !is_null($topic->getClub()) && $topic->getClub()->canBeModifiedBy($this->user)) {
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

        if (!$topic || !$topic->getClub() || $topic->isDeleted() || !$topic->getClub()->canBeModifiedBy($this->getUser())) {
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

        if (!$topic || !$topic->getClub() || $topic->isDeleted() || !$topic->getClub()->canBeModifiedBy($this->getUser())) {
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

        if (!$topic || !$topic->getClub() || !$topic->getClub()->canBeModifiedBy($this->getUser())) {
            return 0;
        }

        $topic->setPinned(1);

        $topic->save();

        return 1;
    }

    public function getComments(int $group_id, int $topic_id, bool $need_likes = false, int $start_comment_id = 0, int $offset = 0, int $count = 40, bool $extended = false, string $sort = "asc")
    {
        # start_comment_id ne robit
        $this->requireUser();

        $topic = (new TopicsRepo())->getTopicById($group_id, $topic_id);

        if (!$topic || !$topic->getClub() || $topic->isDeleted()) {
            $this->fail(5, "Invalid topic");
        }

        $arr = [
            "items" => [],
        ];

        $comms = array_slice(iterator_to_array($topic->getComments(1, $count + $offset)), $offset);
        foreach ($comms as $comm) {
            $arr["items"][] = $this->getApiBoardComment($comm, $need_likes);

            if ($extended) {
                if ($comm->getOwner() instanceof \openvk\Web\Models\Entities\User) {
                    $arr["profiles"][] = $comm->getOwner()->toVkApiStruct();
                }

                if ($comm->getOwner() instanceof \openvk\Web\Models\Entities\Club) {
                    $arr["groups"][] = $comm->getOwner()->toVkApiStruct();
                }
            }
        }

        return $arr;
    }

    public function getTopics(int $group_id, string $topic_ids = "", int $order = 1, int $offset = 0, int $count = 40, bool $extended = false, int $preview = 0, int $preview_length = 90)
    {
        # order и extended ничё не делают
        $this->requireUser();

        $arr = [];
        $club = (new ClubsRepo())->get($group_id);

        $topics = array_slice(iterator_to_array((new TopicsRepo())->getClubTopics($club, 1, $count + $offset)), $offset);
        $arr["count"] = (new TopicsRepo())->getClubTopicsCount($club);
        $arr["items"] = [];
        $arr["default_order"] = $order;
        $arr["can_add_topics"] = $club->canBeModifiedBy($this->getUser()) ? true : ($club->isEveryoneCanCreateTopics() ? true : false);
        $arr["profiles"] = [];

        if (empty($topic_ids)) {
            foreach ($topics as $topic) {
                if ($topic->isDeleted()) {
                    continue;
                }
                $arr["items"][] = $topic->toVkApiStruct($preview, $preview_length > 1 ? $preview_length : 90);
            }
        } else {
            $topics = explode(',', $topic_ids);

            foreach ($topics as $topic) {
                $id = explode("_", $topic);
                $topicy = (new TopicsRepo())->getTopicById((int) $id[0], (int) $id[1]);

                if ($topicy && !$topicy->isDeleted()) {
                    $arr["items"][] = $topicy->toVkApiStruct($preview, $preview_length > 1 ? $preview_length : 90);
                }
            }
        }

        return $arr;
    }

    public function openTopic(int $group_id, int $topic_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $topic = (new TopicsRepo())->getTopicById($group_id, $topic_id);

        if (!$topic || !$topic->getClub() || !$topic->isDeleted() || !$topic->getClub()->canBeModifiedBy($this->getUser())) {
            return 0;
        }

        if ($topic->isClosed()) {
            $topic->setClosed(0);
            $topic->save();
        }

        return 1;
    }

    public function restoreComment(int $group_id, int $topic_id, int $comment_id)
    {
        $this->fail(501, "Not implemented");
    }

    public function unfixTopic(int $group_id, int $topic_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $topic = (new TopicsRepo())->getTopicById($group_id, $topic_id);

        if (!$topic || !$topic->getClub() || !$topic->getClub()->canBeModifiedBy($this->getUser())) {
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

    private function getApiBoardComment(?Comment $comment, bool $need_likes = false)
    {
        $res = (object) [];

        $res->id            = $comment->getId();
        $res->from_id       = $comment->getOwner()->getId();
        $res->date          = $comment->getPublicationTime()->timestamp();
        $res->text          = $comment->getText(false);
        $res->attachments   = [];
        $res->likes         = [];
        if ($need_likes) {
            $res->likes = [
                "count"      => $comment->getLikesCount(),
                "user_likes" => (int) $comment->hasLikeFrom($this->getUser()),
                "can_like"   => 1, # а чё типо не может ахахаххахах
            ];
        }

        foreach ($comment->getChildren() as $attachment) {
            if ($attachment->isDeleted()) {
                continue;
            }

            $res->attachments[] = $attachment->toVkApiStruct();
        }

        return $res;
    }
}
