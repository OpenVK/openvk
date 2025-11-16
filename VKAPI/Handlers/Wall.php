<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Entities\User;
use openvk\Web\Models\Entities\Notifications\{PostAcceptedNotification, WallPostNotification, NewSuggestedPostsNotification, RepostNotification, CommentNotification};
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Entities\Club;
use openvk\Web\Models\Repositories\Clubs as ClubsRepo;
use openvk\Web\Models\Entities\Post;
use openvk\Web\Models\Repositories\Posts as PostsRepo;
use openvk\Web\Models\Entities\Comment;
use openvk\Web\Models\Repositories\Comments as CommentsRepo;
use openvk\Web\Models\Entities\Photo;
use openvk\Web\Models\Repositories\Photos as PhotosRepo;
use openvk\Web\Models\Entities\Video;
use openvk\Web\Models\Repositories\Videos as VideosRepo;
use openvk\Web\Models\Entities\Note;
use openvk\Web\Models\Repositories\Notes as NotesRepo;
use openvk\Web\Models\Repositories\Polls as PollsRepo;
use openvk\Web\Models\Repositories\Audios as AudiosRepo;

final class Wall extends VKAPIRequestHandler
{
    public function get(int $owner_id, string $domain = "", int $offset = 0, int $count = 30, int $extended = 0, string $filter = "all", int $rss = 0): object
    {
        $this->requireUser();

        $posts    = new PostsRepo();

        $items    = [];
        $profiles = [];
        $groups   = [];
        $cnt      = 0;

        if ($owner_id > 0) {
            $wallOnwer = (new UsersRepo())->get($owner_id);
        } else {
            $wallOnwer = (new ClubsRepo())->get($owner_id * -1);
        }

        if ($owner_id > 0) {
            if (!$wallOnwer || $wallOnwer->isDeleted()) {
                $this->fail(18, "User was deleted or banned");
            }
        }

        if (!$wallOnwer->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        } elseif (!$wallOnwer) {
            $this->fail(15, "Access denied: wall is disabled");
        } // Don't search for logic here pls

        $iteratorv = null;

        switch ($filter) {
            case "all":
                $iteratorv = $posts->getPostsFromUsersWall($owner_id, 1, $count, $offset);
                $cnt       = $posts->getPostCountOnUserWall($owner_id);
                break;
            case "owner":
                $iteratorv = $posts->getOwnersPostsFromWall($owner_id, 1, $count, $offset);
                $cnt       = $posts->getOwnersCountOnUserWall($owner_id);
                break;
            case "others":
                $iteratorv = $posts->getOthersPostsFromWall($owner_id, 1, $count, $offset);
                $cnt       = $posts->getOthersCountOnUserWall($owner_id);
                break;
            case "postponed":
                $this->fail(42, "Postponed posts are not implemented.");
                break;
            case "suggests":
                if ($owner_id < 0) {
                    if ($wallOnwer->getWallType() != 2) {
                        $this->fail(125, "Group's wall type is open or closed");
                    }

                    if ($wallOnwer->canBeModifiedBy($this->getUser())) {
                        $iteratorv = $posts->getSuggestedPosts($owner_id * -1, 1, $count, $offset);
                        $cnt       = $posts->getSuggestedPostsCount($owner_id * -1);
                    } else {
                        $iteratorv = $posts->getSuggestedPostsByUser($owner_id * -1, $this->getUser()->getId(), 1, $count, $offset);
                        $cnt       = $posts->getSuggestedPostsCountByUser($owner_id * -1, $this->getUser()->getId());
                    }
                } else {
                    $this->fail(528, "Suggested posts avaiable only at groups");
                }

                break;
            default:
                $this->fail(254, "Invalid filter");
                break;
        }

        $iteratorv = iterator_to_array($iteratorv);

        foreach ($iteratorv as $post) {
            $from_id = get_class($post->getOwner()) == "openvk\Web\Models\Entities\Club" ? $post->getOwner()->getId() * (-1) : $post->getOwner()->getId();

            $attachments = [];
            $repost = [];
            foreach ($post->getChildren() as $attachment) {
                if ($attachment instanceof \openvk\Web\Models\Entities\Photo) {
                    if ($attachment->isDeleted()) {
                        continue;
                    }

                    $attachments[] = $this->getApiPhoto($attachment);
                } elseif ($attachment instanceof \openvk\Web\Models\Entities\Poll) {
                    $attachments[] = $this->getApiPoll($attachment, $this->getUser());
                } elseif ($attachment instanceof \openvk\Web\Models\Entities\Video) {
                    $attachments[] = $attachment->getApiStructure($this->getUser());
                } elseif ($attachment instanceof \openvk\Web\Models\Entities\Note) {
                    if (VKAPI_DECL_VER === '4.100') {
                        $attachments[] = $attachment->toVkApiStruct();
                    } else {
                        $attachments[] = [
                            'type' => 'note',
                            'note' => $attachment->toVkApiStruct(),
                        ];
                    }
                } elseif ($attachment instanceof \openvk\Web\Models\Entities\Audio) {
                    $attachments[] = [
                        "type" => "audio",
                        "audio" => $attachment->toVkApiStruct($this->getUser()),
                    ];
                } elseif ($attachment instanceof \openvk\Web\Models\Entities\Document) {
                    $attachments[] = [
                        "type" => "doc",
                        "doc" => $attachment->toVkApiStruct($this->getUser()),
                    ];
                } elseif ($attachment instanceof \openvk\Web\Models\Entities\Post) {
                    $repostAttachments = [];

                    foreach ($attachment->getChildren() as $repostAttachment) {
                        if ($repostAttachment instanceof \openvk\Web\Models\Entities\Photo) {
                            if ($repostAttachment->isDeleted()) {
                                continue;
                            }

                            $repostAttachments[] = $this->getApiPhoto($repostAttachment);
                            /* Рекурсии, сука! Заказывали? */
                        }
                    }

                    if ($attachment->isPostedOnBehalfOfGroup()) {
                        $groups[] = $attachment->getOwner()->getId();
                    } else {
                        $profiles[] = $attachment->getOwner()->getId();
                    }

                    $repost[] = [
                        "id" => $attachment->getVirtualId(),
                        "owner_id" => $attachment->isPostedOnBehalfOfGroup() ? $attachment->getOwner()->getId() * -1 : $attachment->getOwner()->getId(),
                        "from_id" => $attachment->isPostedOnBehalfOfGroup() ? $attachment->getOwner()->getId() * -1 : $attachment->getOwner()->getId(),
                        "date" => $attachment->getPublicationTime()->timestamp(),
                        "post_type" => $attachment->getVkApiType(),
                        "text" => $attachment->getText(false),
                        "attachments" => $repostAttachments,
                        "post_source" => $attachment->getPostSourceInfo(),
                    ];

                    if ($attachment->getTargetWall() > 0) {
                        $profiles[] = $attachment->getTargetWall();
                    } else {
                        $groups[] = abs($attachment->getTargetWall());
                    }
                    if ($post->isSigned()) {
                        $profiles[] = $attachment->getOwner()->getId();
                    }
                }
            }

            $signerId = null;
            if ($post->isSigned()) {
                $actualAuthor = $post->getOwner(false);
                $signerId     = $actualAuthor->getId();
            }

            # TODO "can_pin", "copy_history" и прочее не должны возвращаться, если равны null или false
            # Ну и ещё всё надо перенести в toVkApiStruct, а то слишком много дублированного кода

            $post_temp_obj = (object) [
                "id"           => $post->getVirtualId(),
                "from_id"      => $from_id,
                "owner_id"     => $post->getTargetWall(),
                "date"         => $post->getPublicationTime()->timestamp(),
                "post_type"    => $post->getVkApiType(),
                "text"         => $post->getText(false),
                "copy_history" => $repost,
                "can_edit"     => $post->canBeEditedBy($this->getUser()),
                "can_delete"   => $post->canBeDeletedBy($this->getUser()),
                "can_pin"      => $post->canBePinnedBy($this->getUser()),
                "can_archive"  => false, # TODO MAYBE
                "is_archived"  => false,
                "is_pinned"    => $post->isPinned(),
                "is_explicit"  => $post->isExplicit(),
                "attachments"  => $attachments,
                "post_source"  => $post->getPostSourceInfo(),
                "comments"     => (object) [
                    "count"    => $post->getCommentsCount(),
                    "can_post" => 1,
                ],
                "likes" => (object) [
                    "count"       => $post->getLikesCount(),
                    "user_likes"  => (int) $post->hasLikeFrom($this->getUser()),
                    "can_like"    => 1,
                    "can_publish" => 1,
                ],
                "reposts" => (object) [
                    "count"         => $post->getRepostCount(),
                    "user_reposted" => 0,
                ],
            ];

            if ($post->hasSource()) {
                $post_temp_obj->copyright = $post->getVkApiCopyright();
            }

            if ($signerId) {
                $post_temp_obj->signer_id = $signerId;
            }

            if ($post->isDeactivationMessage()) {
                $post_temp_obj->final_post = 1;
            }

            if ($post->getGeo()) {
                $post_temp_obj->geo = $post->getVkApiGeo();
            }

            $items[] = $post_temp_obj;

            if ($from_id > 0) {
                $profiles[] = $from_id;
            } else {
                $groups[]   = $from_id * -1;
            }

            if ($post->isSigned()) {
                $profiles[] = $post->getOwner(false)->getId();
            }

            $attachments = null; # free attachments so it will not clone everythingg
        }

        if ($rss == 1) {
            $channel = new \Bhaktaraz\RSSGenerator\Channel();
            $channel->title($wallOnwer->getCanonicalName() . " — " . OPENVK_ROOT_CONF['openvk']['appearance']['name'])
            ->description('Wall of ' . $wallOnwer->getCanonicalName())
            ->url(ovk_scheme(true) . $_SERVER["HTTP_HOST"] . "/wall" . $wallOnwer->getRealId());

            foreach ($iteratorv as $item) {
                $output = $item->toRss();
                $output->appendTo($channel);
            }

            return $channel;
        }

        if ($extended == 1) {
            $profiles = array_unique($profiles);
            $groups  = array_unique($groups);

            $profilesFormatted = [];
            $groupsFormatted   = [];

            foreach ($profiles as $prof) {
                $user                = (new UsersRepo())->get($prof);
                $profilesFormatted[] = (object) [
                    "first_name"        => $user->getFirstName(),
                    "id"                => $user->getId(),
                    "last_name"         => $user->getLastName(),
                    "can_access_closed" => (bool) $user->canBeViewedBy($this->getUser()),
                    "is_closed"         => $user->isClosed(),
                    "sex"               => $user->isFemale() ? 1 : ($user->isNeutral() ? 0 : 2),
                    "screen_name"       => $user->getShortCode(),
                    "photo_50"          => $user->getAvatarUrl(),
                    "photo_100"         => $user->getAvatarUrl(),
                    "online"            => $user->isOnline(),
                    "verified"          => $user->isVerified(),
                ];
            }

            foreach ($groups as $g) {
                $group             = (new ClubsRepo())->get($g);
                $groupsFormatted[] = (object) [
                    "id"          => $group->getId(),
                    "name"        => $group->getName(),
                    "screen_name" => $group->getShortCode(),
                    "is_closed"   => 0,
                    "type"        => "group",
                    "photo_50"    => $group->getAvatarUrl(),
                    "photo_100"   => $group->getAvatarUrl(),
                    "photo_200"   => $group->getAvatarUrl(),
                    "verified"    => $group->isVerified(),
                ];
            }

            return (object) [
                "count"    => $cnt,
                "items"    => $items,
                "profiles" => $profilesFormatted,
                "groups"   => $groupsFormatted,
            ];
        } else {
            return (object) [
                "count" => $cnt,
                "items" => $items,
            ];
        }
    }

    public function getById(string $posts, int $extended = 0, string $fields = "", User $user = null)
    {
        if ($user == null) {
            $this->requireUser();
            $user = $this->getUser(); # костыли костыли крылышки
        }

        $items    = [];
        $profiles = [];
        $groups   = [];

        $psts     = explode(',', $posts);

        foreach ($psts as $pst) {
            $id   = explode("_", $pst);
            $post = (new PostsRepo())->getPostById(intval($id[0]), intval($id[1]), true);

            if ($post && !$post->isDeleted()) {
                if (!$post->canBeViewedBy($this->getUser())) {
                    continue;
                }

                if ($post->getSuggestionType() != 0 && !$post->canBeEditedBy($this->getUser())) {
                    continue;
                }

                $from_id = get_class($post->getOwner()) == "openvk\Web\Models\Entities\Club" ? $post->getOwner()->getId() * (-1) : $post->getOwner()->getId();
                $attachments = [];
                $repost = []; // чел высрал семь сигарет 😳 помянем 🕯
                foreach ($post->getChildren() as $attachment) {
                    if ($attachment instanceof \openvk\Web\Models\Entities\Photo) {
                        $attachments[] = $this->getApiPhoto($attachment);
                    } elseif ($attachment instanceof \openvk\Web\Models\Entities\Poll) {
                        $attachments[] = $this->getApiPoll($attachment, $user);
                    } elseif ($attachment instanceof \openvk\Web\Models\Entities\Video) {
                        $attachments[] = $attachment->getApiStructure($this->getUser());
                    } elseif ($attachment instanceof \openvk\Web\Models\Entities\Note) {
                        $attachments[] = [
                            'type' => 'note',
                            'note' => $attachment->toVkApiStruct(),
                        ];
                    } elseif ($attachment instanceof \openvk\Web\Models\Entities\Audio) {
                        $attachments[] = [
                            "type" => "audio",
                            "audio" => $attachment->toVkApiStruct($this->getUser()),
                        ];
                    } elseif ($attachment instanceof \openvk\Web\Models\Entities\Document) {
                        $attachments[] = [
                            "type" => "doc",
                            "doc" => $attachment->toVkApiStruct($this->getUser()),
                        ];
                    } elseif ($attachment instanceof \openvk\Web\Models\Entities\Post) {
                        $repostAttachments = [];

                        foreach ($attachment->getChildren() as $repostAttachment) {
                            if ($repostAttachment instanceof \openvk\Web\Models\Entities\Photo) {
                                if ($attachment->isDeleted()) {
                                    continue;
                                }

                                $repostAttachments[] = $this->getApiPhoto($repostAttachment);
                                /* Рекурсии, сука! Заказывали? */
                            }
                        }

                        if ($attachment->isPostedOnBehalfOfGroup()) {
                            $groups[] = $attachment->getOwner()->getId();
                        } else {
                            $profiles[] = $attachment->getOwner()->getId();
                        }

                        $repost[] = [
                            "id" => $attachment->getVirtualId(),
                            "owner_id" => $attachment->isPostedOnBehalfOfGroup() ? $attachment->getOwner()->getId() * -1 : $attachment->getOwner()->getId(),
                            "from_id" => $attachment->isPostedOnBehalfOfGroup() ? $attachment->getOwner()->getId() * -1 : $attachment->getOwner()->getId(),
                            "date" => $attachment->getPublicationTime()->timestamp(),
                            "post_type" => "post",
                            "text" => $attachment->getText(false),
                            "attachments" => $repostAttachments,
                            "post_source" => $attachment->getPostSourceInfo(),
                        ];

                        if ($attachment->getTargetWall() > 0) {
                            $profiles[] = $attachment->getTargetWall();
                        } else {
                            $groups[] = abs($attachment->getTargetWall());
                        }
                        if ($post->isSigned()) {
                            $profiles[] = $attachment->getOwner()->getId();
                        }
                    }
                }

                if ($post->isSigned()) {
                    $actualAuthor = $post->getOwner(false);
                    $signerId     = $actualAuthor->getId();
                }

                $post_temp_obj = (object) [
                    "id"           => $post->getVirtualId(),
                    "from_id"      => $from_id,
                    "owner_id"     => $post->getTargetWall(),
                    "date"         => $post->getPublicationTime()->timestamp(),
                    "post_type"    => $post->getVkApiType(),
                    "text"         => $post->getText(false),
                    "copy_history" => $repost,
                    "can_edit"     => $post->canBeEditedBy($this->getUser()),
                    "can_delete"   => $post->canBeDeletedBy($user),
                    "can_pin"      => $post->canBePinnedBy($user),
                    "can_archive"  => false, # TODO MAYBE
                    "is_archived"  => false,
                    "is_pinned"    => $post->isPinned(),
                    "is_explicit"  => $post->isExplicit(),
                    "post_source"  => $post->getPostSourceInfo(),
                    "attachments"  => $attachments,
                    "comments"     => (object) [
                        "count"    => $post->getCommentsCount(),
                        "can_post" => 1,
                    ],
                    "likes" => (object) [
                        "count"       => $post->getLikesCount(),
                        "user_likes"  => (int) $post->hasLikeFrom($user),
                        "can_like"    => 1,
                        "can_publish" => 1,
                    ],
                    "reposts" => (object) [
                        "count"         => $post->getRepostCount(),
                        "user_reposted" => 0,
                    ],
                ];

                if ($post->hasSource()) {
                    $post_temp_obj->copyright = $post->getVkApiCopyright();
                }

                if ($signerId) {
                    $post_temp_obj->signer_id = $signerId;
                }

                if ($post->isDeactivationMessage()) {
                    $post_temp_obj->final_post = 1;
                }

                if ($post->getGeo()) {
                    $post_temp_obj->geo = $post->getVkApiGeo();
                }

                $items[] = $post_temp_obj;

                if ($from_id > 0) {
                    $profiles[] = $from_id;
                } else {
                    $groups[]   = $from_id * -1;
                }

                if ($post->isSigned()) {
                    $profiles[] = $post->getOwner(false)->getId();
                }

                $attachments = null; # free attachments so it will not clone everything
                $repost = null;      # same
            }
        }

        if ($extended == 1) {
            $profiles = array_unique($profiles);
            $groups   = array_unique($groups);

            $profilesFormatted = [];
            $groupsFormatted   = [];

            foreach ($profiles as $prof) {
                $user                = (new UsersRepo())->get($prof);
                if ($user) {
                    $profilesFormatted[] = (object) [
                        "first_name"        => $user->getFirstName(),
                        "id"                => $user->getId(),
                        "last_name"         => $user->getLastName(),
                        "can_access_closed" => (bool) $user->canBeViewedBy($this->getUser()),
                        "is_closed"         => $user->isClosed(),
                        "sex"               => $user->isFemale() ? 1 : 2,
                        "screen_name"       => $user->getShortCode(),
                        "photo_50"          => $user->getAvatarUrl(),
                        "photo_100"         => $user->getAvatarUrl(),
                        "online"            => $user->isOnline(),
                        "verified"          => $user->isVerified(),
                    ];
                } else {
                    $profilesFormatted[] = (object) [
                        "id" 		  => (int) $prof,
                        "first_name"  => "DELETED",
                        "last_name"   => "",
                        "deactivated" => "deleted",
                    ];
                }
            }

            foreach ($groups as $g) {
                $group             = (new ClubsRepo())->get($g);
                $groupsFormatted[] = (object) [
                    "id"           => $group->getId(),
                    "name"         => $group->getName(),
                    "screen_name"  => $group->getShortCode(),
                    "is_closed"    => 0,
                    "type"         => "group",
                    "photo_50"     => $group->getAvatarUrl(),
                    "photo_100"    => $group->getAvatarUrl(),
                    "photo_200"    => $group->getAvatarUrl(),
                    "verified"     => $group->isVerified(),
                ];
            }

            return (object) [
                "items"    => (array) $items,
                "profiles" => (array) $profilesFormatted,
                "groups"   => (array) $groupsFormatted,
            ];
        } else {
            return (object) [
                "items" => (array) $items,
            ];
        }
    }

    public function post(
        string $owner_id,
        string $message = "",
        string $copyright = "",
        int $from_group = 0,
        int $signed = 0,
        string $attachments = "",
        int $post_id = 0,
        float $lat = null,
        float $long = null,
        string $place_name = ''
    ): object {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $owner_id  = intval($owner_id);

        $wallOwner = ($owner_id > 0 ? (new UsersRepo())->get($owner_id) : (new ClubsRepo())->get($owner_id * -1))
                     ?? $this->fail(18, "User was deleted or banned");
        if ($owner_id > 0) {
            $canPost = $wallOwner->getPrivacyPermission("wall.write", $this->getUser()) && $wallOwner->canBeViewedBy($this->getUser());
        } elseif ($owner_id < 0) {
            if ($wallOwner->canBeModifiedBy($this->getUser())) {
                $canPost = true;
            } else {
                $canPost = $wallOwner->canPost();
            }
        } else {
            $canPost = false;
        }

        if ($canPost == false) {
            $this->fail(15, "Access denied");
        }

        if ($post_id > 0) {
            if ($owner_id > 0) {
                $this->fail(62, "Suggested posts available only at groups");
            }

            $post = (new PostsRepo())->getPostById($owner_id, $post_id, true);

            if (!$post || $post->isDeleted()) {
                $this->fail(32, "Invald post");
            }

            if ($post->getSuggestionType() == 0) {
                $this->fail(20, "Post is not suggested");
            }

            if ($post->getSuggestionType() == 2) {
                $this->fail(16, "Post is declined");
            }

            if (!$post->canBePinnedBy($this->getUser())) {
                $this->fail(51, "Access denied");
            }

            $author = $post->getOwner();
            $flags = 0;
            $flags |= 0b10000000;

            if ($signed == 1) {
                $flags |= 0b01000000;
            }

            $post->setSuggested(0);
            $post->setCreated(time());
            $post->setFlags($flags);

            if (!empty($message) && iconv_strlen($message) > 0) {
                $post->setContent($message);
            }

            $post->save();

            if ($author->getId() != $this->getUser()->getId()) {
                (new PostAcceptedNotification($author, $post, $post->getWallOwner()))->emit();
            }

            return (object) ["post_id" => $post->getVirtualId()];
        }

        $anon = OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["anonymousPosting"]["enable"];
        if ($wallOwner instanceof Club && $from_group == 1 && $signed != 1 && $anon) {
            $manager = $wallOwner->getManager($this->getUser());
            if ($manager) {
                $anon = $manager->isHidden();
            } elseif ($this->getUser()->getId() === $wallOwner->getOwner()->getId()) {
                $anon = $wallOwner->isOwnerHidden();
            }
        } else {
            $anon = false;
        }

        $flags = 0;
        if ($from_group == 1 && $wallOwner instanceof Club && $wallOwner->canBeModifiedBy($this->getUser())) {
            $flags |= 0b10000000;
        }
        if ($signed == 1) {
            $flags |= 0b01000000;
        }

        $parsed_attachments  = parseAttachments($attachments, ['photo', 'video', 'note', 'poll', 'audio', 'doc']);
        $final_attachments   = [];
        $should_be_suggested = $owner_id < 0 && !$wallOwner->canBeModifiedBy($this->getUser()) && $wallOwner->getWallType() == 2;
        foreach ($parsed_attachments as $attachment) {
            if ($attachment && !$attachment->isDeleted() && $attachment->canBeViewedBy($this->getUser()) &&
            !(method_exists($attachment, 'getVoters') && $attachment->getOwner()->getId() != $this->getUser()->getId())) {
                $final_attachments[] = $attachment;
            }
        }

        if ((empty($message) && (empty($attachments) || sizeof($final_attachments) < 1))) {
            $this->fail(100, "Required parameter 'message' missing.");
        }

        try {
            $post = new Post();
            $post->setOwner($this->getUser()->getId());
            $post->setWall($owner_id);
            $post->setCreated(time());
            $post->setContent($message);
            $post->setFlags($flags);
            $post->setApi_Source_Name($this->getPlatform());

            if (!is_null($copyright) && !empty($copyright)) {
                try {
                    $post->setSource($copyright);
                } catch (\Throwable) {
                }
            }

            /*$info = file_get_contents("https://nominatim.openstreetmap.org/reverse?lat=${latitude}&lon=${longitude}&format=jsonv2", false, stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", [
                        'User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2',
                        "Referer: https://$_SERVER[SERVER_NAME]/"
                    ])
                ]
            ]));

            if ($info) {
                $info = json_decode($info, true, JSON_UNESCAPED_UNICODE);
                if (key_exists("place_id", $info)) {
                    $geo["name"] = $info["name"] ?? $info["display_name"];
                }
            }*/
            if ($lat && $long) {
                if (($lat > 90 || $lat < -90) || ($long > 180 || $long < -180)) {
                    $this->fail(-785, 'Invalid geo info');
                }

                $latitude = number_format((float) $lat, 8, ".", '');
                $longitude = number_format((float) $long, 8, ".", '');

                $res = [
                    'lat' => $latitude,
                    'lng' => $longitude,
                ];
                if ($place_name && mb_strlen($place_name) > 0) {
                    $res['name'] = $place_name;
                } else {
                    $res['name'] = 'Geopoint';
                }

                $post->setGeo($res);
                $post->setGeo_Lat($latitude);
                $post->setGeo_Lon($longitude);
            }

            if ($should_be_suggested) {
                $post->setSuggested(1);
            }

            if (\openvk\Web\Util\EventRateLimiter::i()->tryToLimit($this->getUser(), "wall.post")) {
                $this->failTooOften();
            }

            $post->save();
        } catch (\LogicException $ex) {
            $this->fail(100, "One of the parameters specified was missing or invalid");
        }

        foreach ($final_attachments as $attachment) {
            $post->attach($attachment);
        }

        if ($owner_id > 0 && $owner_id !== $this->getUser()->getId()) {
            (new WallPostNotification($wallOwner, $post, $this->getUser()))->emit();
        }

        return (object) ["post_id" => $post->getVirtualId()];
    }

    public function repost(string $object, string $message = "", string $attachments = "", int $group_id = 0, int $as_group = 0, int $signed = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $postArray = [];
        if (preg_match('/(wall|video|photo)((?:-?)[0-9]+)_([0-9]+)/', $object, $postArray) == 0) {
            $this->fail(100, "One of the parameters specified was missing or invalid: object is incorrect");
        }

        $parsed_attachments  = parseAttachments($attachments, ['photo', 'video', 'note', 'audio', 'doc']);
        $final_attachments   = [];
        foreach ($parsed_attachments as $attachment) {
            if ($attachment && !$attachment->isDeleted() && $attachment->canBeViewedBy($this->getUser()) &&
            !(method_exists($attachment, 'getVoters') && $attachment->getOwner()->getId() != $this->getUser()->getId())) {
                $final_attachments[] = $attachment;
            }
        }

        $repost_entity = null;
        $repost_type   = $postArray[1];
        switch ($repost_type) {
            default:
            case 'wall':
                $repost_entity = (new PostsRepo())->getPostById((int) $postArray[2], (int) $postArray[3]);
                break;
            case 'photo':
                $repost_entity = (new PhotosRepo())->getByOwnerAndVID((int) $postArray[2], (int) $postArray[3]);
                break;
            case 'video':
                $repost_entity = (new VideosRepo())->getByOwnerAndVID((int) $postArray[2], (int) $postArray[3]);
                break;
        }

        if (!$repost_entity || $repost_entity->isDeleted() || !$repost_entity->canBeViewedBy($this->getUser())) {
            $this->fail(100, "One of the parameters specified was missing or invalid");
        }

        $nPost = new Post();
        $nPost->setOwner($this->user->getId());

        if ($group_id > 0) {
            $club = (new ClubsRepo())->get($group_id);
            if (!$club) {
                $this->fail(42, "Invalid group");
            }

            if (!$club->canBeModifiedBy($this->user)) {
                $this->fail(16, "Access to group denied");
            }

            $nPost->setWall($club->getRealId());
            $flags = 0;
            if ($as_group === 1 || $signed === 1) {
                $flags |= 0b10000000;
            }

            if ($signed === 1) {
                $flags |= 0b01000000;
            }

            $nPost->setFlags($flags);
        } else {
            $nPost->setWall($this->user->getId());
        }

        $nPost->setContent($message);
        $nPost->setApi_Source_Name($this->getPlatform());
        $nPost->save();

        $nPost->attach($repost_entity);

        foreach ($parsed_attachments as $attachment) {
            $nPost->attach($attachment);
        }

        if ($repost_type == 'wall' && $repost_entity->getOwner(false)->getId() !== $this->user->getId() && !($repost_entity->getOwner() instanceof Club)) {
            (new RepostNotification($repost_entity->getOwner(false), $repost_entity, $this->user))->emit();
        }

        $repost_count = 1;
        if ($repost_type == 'wall') {
            $repost_count = $repost_entity->getRepostCount();
        }

        return (object) [
            "success" => 1, // 👍
            "post_id" => $nPost->getVirtualId(),
            "pretty_id" => $nPost->getPrettyId(),
            "reposts_count" => $repost_count,
            "likes_count" => $repost_entity->getLikesCount(),
        ];
    }


    public function getComments(int $owner_id, int $post_id, bool $need_likes = true, int $offset = 0, int $count = 10, string $fields = "sex,screen_name,photo_50,photo_100,online_info,online", string $sort = "asc", bool $extended = false)
    {
        $this->requireUser();

        $post = (new PostsRepo())->getPostById($owner_id, $post_id);
        if (!$post || $post->isDeleted()) {
            $this->fail(100, "One of the parameters specified was missing or invalid");
        }

        if (!$post->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $comments = (new CommentsRepo())->getCommentsByTarget($post, $offset + 1, $count, $sort == "desc" ? "DESC" : "ASC");

        $items = [];
        $profiles = [];

        foreach ($comments as $comment) {
            $owner = $comment->getOwner();
            $oid   = $owner->getId();
            if ($owner instanceof Club) {
                $oid *= -1;
            }

            $attachments = [];

            foreach ($comment->getChildren() as $attachment) {
                if ($attachment instanceof \openvk\Web\Models\Entities\Photo) {
                    $attachments[] = $this->getApiPhoto($attachment);
                } elseif ($attachment instanceof \openvk\Web\Models\Entities\Note) {
                    $attachments[] = $attachment->toVkApiStruct();
                } elseif ($attachment instanceof \openvk\Web\Models\Entities\Audio) {
                    $attachments[] = [
                        "type"  => "audio",
                        "audio" => $attachment->toVkApiStruct($this->getUser()),
                    ];
                } elseif ($attachment instanceof \openvk\Web\Models\Entities\Document) {
                    $attachments[] = [
                        "type" => "doc",
                        "doc" => $attachment->toVkApiStruct($this->getUser()),
                    ];
                }
            }

            $item = [
                "id"            => $comment->getId(),
                "from_id"       => $oid,
                "date"          => $comment->getPublicationTime()->timestamp(),
                "can_edit"      => $post->canBeEditedBy($this->getUser()),
                "can_delete"    => $post->canBeDeletedBy($this->getUser()),
                "text"          => $comment->getText(false),
                "post_id"       => $post->getVirtualId(),
                "owner_id"      => method_exists($post, 'isPostedOnBehalfOfGroup') && $post->isPostedOnBehalfOfGroup() ? $post->getOwner()->getId() * -1 : $post->getOwner()->getId(),
                "parents_stack" => [],
                "attachments"   => $attachments,
                "thread"        => [
                    "count"             => 0,
                    "items"             => [],
                    "can_post"          => false,
                    "show_reply_button" => true,
                    "groups_can_post"   => false,
                ],
            ];

            if ($comment->isFromPostAuthor($post)) {
                $item['is_from_post_author'] = true;
            }

            if ($need_likes == true) {
                $item['likes'] = [
                    "can_like"    => 1,
                    "count"       => $comment->getLikesCount(),
                    "user_likes"  => (int) $comment->hasLikeFrom($this->getUser()),
                    "can_publish" => 1,
                ];
            }

            $items[] = $item;
            if ($extended == true) {
                $profiles[] = $comment->getOwner()->getId();
            }

            $attachments = null;
            // Reset $attachments to not duplicate prikols
        }

        $response = [
            "count"               => (new CommentsRepo())->getCommentsCountByTarget($post),
            "items"               => $items,
            "current_level_count" => (new CommentsRepo())->getCommentsCountByTarget($post),
            "can_post"            => true,
            "show_reply_button"   => true,
            "groups_can_post"     => false,
        ];

        if ($extended == true) {
            $profiles = array_unique($profiles);
            $response['profiles'] = (!empty($profiles) ? (new Users())->get(implode(',', $profiles), $fields) : []);
        }

        return (object) $response;
    }

    public function getComment(int $owner_id, int $comment_id, bool $extended = false, string $fields = "sex,screen_name,photo_50,photo_100,online_info,online")
    {
        $this->requireUser();

        $comment = (new CommentsRepo())->get($comment_id); # один хуй айди всех комментов общий

        if (!$comment || $comment->isDeleted()) {
            $this->fail(100, "Invalid comment");
        }

        if (!$comment->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $profiles = [];

        $attachments = [];

        foreach ($comment->getChildren() as $attachment) {
            if ($attachment instanceof \openvk\Web\Models\Entities\Photo) {
                $attachments[] = $this->getApiPhoto($attachment);
            } elseif ($attachment instanceof \openvk\Web\Models\Entities\Video) {
                $attachments[] = $attachment->getApiStructure();
            } elseif ($attachment instanceof \openvk\Web\Models\Entities\Note) {
                $attachments[] = [
                    'type' => 'note',
                    'note' => $attachment->toVkApiStruct(),
                ];
            } elseif ($attachment instanceof \openvk\Web\Models\Entities\Audio) {
                $attachments[] = [
                    "type" => "audio",
                    "audio" => $attachment->toVkApiStruct($this->getUser()),
                ];
            } elseif ($attachment instanceof \openvk\Web\Models\Entities\Document) {
                $attachments[] = [
                    "type" => "doc",
                    "doc" => $attachment->toVkApiStruct($this->getUser()),
                ];
            }
        }

        $item = [
            "id"            => $comment->getId(),
            "from_id"       => $comment->getOwner()->getId(),
            "date"          => $comment->getPublicationTime()->timestamp(),
            "text"          => $comment->getText(false),
            "post_id"       => $comment->getTarget()->getVirtualId(),
            "owner_id"      => method_exists($comment->getTarget(), 'isPostedOnBehalfOfGroup') && $comment->getTarget()->isPostedOnBehalfOfGroup() ? $comment->getTarget()->getOwner()->getId() * -1 : $comment->getTarget()->getOwner()->getId(),
            "parents_stack" => [],
            "attachments"   => $attachments,
            "likes"         => [
                "can_like"    => 1,
                "count"       => $comment->getLikesCount(),
                "user_likes"  => (int) $comment->hasLikeFrom($this->getUser()),
                "can_publish" => 1,
            ],
            "thread"        => [
                "count"             => 0,
                "items"             => [],
                "can_post"          => false,
                "show_reply_button" => true,
                "groups_can_post"   => false,
            ],
        ];

        if ($comment->isFromPostAuthor()) {
            $item['is_from_post_author'] = true;
        }

        if ($extended == true) {
            $profiles[] = $comment->getOwner()->getId();
        }

        $response = [
            "items"               => [$item],
            "can_post"            => true,
            "show_reply_button"   => true,
            "groups_can_post"     => false,
        ];

        if ($extended == true) {
            $profiles = array_unique($profiles);
            $response['profiles'] = (!empty($profiles) ? (new Users())->get(implode(',', $profiles), $fields) : []);
        }

        return $response;
    }

    public function createComment(int $owner_id, int $post_id, string $message = "", int $from_group = 0, string $attachments = "")
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $post = (new PostsRepo())->getPostById($owner_id, $post_id);
        if (!$post || $post->isDeleted()) {
            $this->fail(100, "Invalid post");
        }

        if (!$post->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        if ($post->getTargetWall() < 0) {
            $club = (new ClubsRepo())->get(abs($post->getTargetWall()));
        }

        $parsed_attachments  = parseAttachments($attachments, ['photo', 'video', 'note', 'audio', 'doc']);
        $final_attachments   = [];
        foreach ($parsed_attachments as $attachment) {
            if ($attachment && !$attachment->isDeleted() && $attachment->canBeViewedBy($this->getUser()) &&
            !(method_exists($attachment, 'getVoters') && $attachment->getOwner()->getId() != $this->getUser()->getId())) {
                $final_attachments[] = $attachment;
            }
        }

        if ((empty($message) && (empty($attachments) || sizeof($final_attachments) < 1))) {
            $this->fail(100, "Required parameter 'message' missing.");
        }

        $flags = 0;
        if ($from_group != 0 && !is_null($club) && $club->canBeModifiedBy($this->user)) {
            $flags |= 0b10000000;
        }

        try {
            $comment = new Comment();
            $comment->setOwner($this->user->getId());
            $comment->setModel(get_class($post));
            $comment->setTarget($post->getId());
            $comment->setContent($message);
            $comment->setCreated(time());
            $comment->setFlags($flags);
            $comment->save();
        } catch (\LengthException $ex) {
            $this->fail(1, "ошибка про то что коммент большой слишком");
        }

        foreach ($final_attachments as $attachment) {
            $comment->attach($attachment);
        }

        if ($post->getOwner()->getId() !== $this->user->getId()) {
            if (($owner = $post->getOwner()) instanceof User) {
                (new CommentNotification($owner, $comment, $post, $this->user))->emit();
            }
        }

        return (object) [
            "comment_id" => $comment->getId(),
            "parents_stack" => [],
        ];
    }

    public function deleteComment(int $comment_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $comment = (new CommentsRepo())->get($comment_id);
        if (!$comment) {
            $this->fail(100, "One of the parameters specified was missing or invalid");
        };
        if (!$comment->canBeDeletedBy($this->user)) {
            $this->fail(7, "Access denied");
        }

        $comment->delete();

        return 1;
    }

    public function delete(int $owner_id, int $post_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $post = (new PostsRepo())->getPostById($owner_id, $post_id, true);
        if (!$post || $post->isDeleted()) {
            $this->fail(15, "Not found");
        }

        $wallOwner = $post->getWallOwner();

        # trying to solve the condition below.
        # $post->getTargetWall() < 0 - if post on wall of club
        # !$post->getWallOwner()->canBeModifiedBy($this->getUser()) - group is cannot be modifiet by %user%
        # $post->getWallOwner()->getWallType() != 1 - wall is not open
        # $post->getSuggestionType() == 0 - post is not suggested
        if ($post->getTargetWall() < 0 && !$post->getWallOwner()->canBeModifiedBy($this->getUser()) && $post->getWallOwner()->getWallType() != 1 && $post->getSuggestionType() == 0) {
            $this->fail(15, "Access denied");
        }

        if ($post->getOwnerPost() == $this->getUser()->getId() || $post->getTargetWall() == $this->getUser()->getId() || $owner_id < 0 && $wallOwner->canBeModifiedBy($this->getUser())) {
            $post->unwire();
            $post->delete();

            return 1;
        } else {
            $this->fail(15, "Access denied");
        }
    }

    public function edit(int $owner_id, int $post_id, string $message = "", string $attachments = "", string $copyright = null, int $explicit = -1, int $from_group = 0, int $signed = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $parsed_attachments  = parseAttachments($attachments, ['photo', 'video', 'note', 'audio', 'poll', 'doc']);
        $final_attachments   = [];
        foreach ($parsed_attachments as $attachment) {
            if ($attachment && !$attachment->isDeleted() && $attachment->canBeViewedBy($this->getUser()) &&
            !(method_exists($attachment, 'getVoters') && $attachment->getOwner()->getId() != $this->getUser()->getId())) {
                $final_attachments[] = $attachment;
            }
        }

        if (empty($message) && sizeof($final_attachments) < 1) {
            $this->fail(-66, "Post will be empty, don't saving.");
        }

        $post = (new PostsRepo())->getPostById($owner_id, $post_id, true);

        if (!$post || $post->isDeleted()) {
            $this->fail(102, "Invalid post");
        }

        if (!$post->canBeEditedBy($this->getUser())) {
            $this->fail(7, "Access to editing denied");
        }

        if (!empty($message) || (empty($message) && sizeof($final_attachments) > 0)) {
            $post->setContent($message);
        }

        $post->setEdited(time());
        if (!is_null($copyright) && !empty($copyright)) {
            if ($copyright == 'remove') {
                $post->resetSource();
            } else {
                try {
                    $post->setSource($copyright);
                } catch (\Throwable) {
                }
            }
        }

        if ($explicit != -1) {
            $post->setNsfw($explicit == 1);
        }

        $wallOwner = ($owner_id > 0 ? (new UsersRepo())->get($owner_id) : (new ClubsRepo())->get($owner_id * -1));
        $flags = 0;
        if ($from_group == 1 && $wallOwner instanceof Club && $wallOwner->canBeModifiedBy($this->getUser())) {
            $flags |= 0b10000000;
        }
        if ($post->isSigned() && $from_group == 1) {
            $flags |= 0b01000000;
        }

        $post->setFlags($flags);
        $post->save(true);

        if ($attachments == 'remove' || sizeof($final_attachments) > 0) {
            foreach ($post->getChildren() as $att) {
                if (!($att instanceof Post)) {
                    $post->detach($att);
                }
            }

            foreach ($final_attachments as $attachment) {
                $post->attach($attachment);
            }
        }

        return ["post_id" => $post->getVirtualId()];
    }

    public function editComment(int $comment_id, int $owner_id = 0, string $message = "", string $attachments = "")
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $comment = (new CommentsRepo())->get($comment_id);
        $parsed_attachments  = parseAttachments($attachments, ['photo', 'video', 'note', 'audio', 'doc']);
        $final_attachments   = [];
        foreach ($parsed_attachments as $attachment) {
            if ($attachment && !$attachment->isDeleted() && $attachment->canBeViewedBy($this->getUser()) &&
            !(method_exists($attachment, 'getVoters') && $attachment->getOwner()->getId() != $this->getUser()->getId())) {
                $final_attachments[] = $attachment;
            }
        }

        if (empty($message) && sizeof($final_attachments) < 1) {
            $this->fail(100, "Required parameter 'message' missing.");
        }

        if (!$comment || $comment->isDeleted()) {
            $this->fail(102, "Invalid comment");
        }

        if (!$comment->canBeEditedBy($this->getUser())) {
            $this->fail(15, "Access to editing comment denied");
        }

        if (!empty($message) || (empty($message) && sizeof($final_attachments) > 0)) {
            $comment->setContent($message);
        }

        $comment->setEdited(time());
        $comment->save(true);

        if (sizeof($final_attachments) > 0) {
            $comment->unwire();
            foreach ($final_attachments as $attachment) {
                $comment->attach($attachment);
            }
        }

        return 1;
    }

    public function checkCopyrightLink(string $link): int
    {
        $this->requireUser();

        try {
            $result = check_copyright_link($link);
        } catch (\InvalidArgumentException $e) {
            $this->fail(3102, "Specified link is incorrect (can't find source)");
        } catch (\LengthException $e) {
            $this->fail(3103, "Specified link is incorrect (too long)");
        } catch (\LogicException $e) {
            $this->fail(3104, "Link is suspicious");
        } catch (\Throwable $e) {
            $this->fail(3102, "Specified link is incorrect");
        }

        return 1;
    }

    public function pin(int $owner_id, int $post_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $post = (new PostsRepo())->getPostById($owner_id, $post_id);
        if (!$post || $post->isDeleted()) {
            $this->fail(100, "One of the parameters specified was missing or invalid: post_id is undefined");
        }

        if (!$post->canBePinnedBy($this->getUser())) {
            return 0;
        }

        if ($post->isPinned()) {
            return 1;
        }

        $post->pin();
        return 1;
    }

    public function unpin(int $owner_id, int $post_id)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $post = (new PostsRepo())->getPostById($owner_id, $post_id);
        if (!$post || $post->isDeleted()) {
            $this->fail(100, "One of the parameters specified was missing or invalid: post_id is undefined");
        }

        if (!$post->canBePinnedBy($this->getUser())) {
            return 0;
        }

        if (!$post->isPinned()) {
            return 1;
        }

        $post->unpin();
        return 1;
    }

    public function getNearby(int $owner_id, int $post_id)
    {
        $this->requireUser();

        $post = (new PostsRepo())->getPostById($owner_id, $post_id);
        if (!$post || $post->isDeleted()) {
            $this->fail(100, "One of the parameters specified was missing or invalid: post_id is undefined");
        }

        if (!$post->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $lat = $post->getLat();
        $lon = $post->getLon();

        if (!$lat || !$lon) {
            $this->fail(-97, "Post doesn't contains geo");
        }

        $query = file_get_contents(__DIR__ . "/../../Web/Models/sql/get-nearest-posts.tsql");
        $_posts = \Chandler\Database\DatabaseConnection::i()->getContext()->query($query, $lat, $lon, $post->getId())->fetchAll();
        $posts = [];

        foreach ($_posts as $post) {
            $distance = $post["distance"];
            $post = (new PostsRepo())->get($post["id"]);
            if (!$post || $post->isDeleted() || !$post->canBeViewedBy($this->getUser())) {
                continue;
            }

            $owner = $post->getOwner();
            $preview = mb_substr($post->getText(), 0, 50) . (strlen($post->getText()) > 50 ? "..." : "");
            $posts[] = [
                "message" => strlen($preview) > 0 ? $preview : "(нет текста)",
                "url" => "/wall" . $post->getPrettyId(),
                "created" => $post->getPublicationTime()->html(),
                "owner" => [
                    "domain" => $owner->getURL(),
                    "photo_50" => $owner->getAvatarURL(),
                    "name" => $owner->getCanonicalName(),
                    "verified" => $owner->isVerified(),
                ],
                "geo" => $post->getGeo(),
                "distance" => $distance,
            ];
        }

        return $posts;
    }

    private function getApiPhoto($attachment)
    {
        return [
            "type"  => "photo",
            "photo" => [
                "album_id" => $attachment->getAlbum() ? $attachment->getAlbum()->getId() : 0,
                "date"     => $attachment->getPublicationTime()->timestamp(),
                "id"       => $attachment->getVirtualId(),
                "owner_id" => $attachment->getOwner()->getId(),
                "sizes"    => !is_null($attachment->getVkApiSizes()) ? array_values($attachment->getVkApiSizes()) : null,
                "text"     => "",
                "has_tags" => false,
            ],
        ];
    }

    private function getApiPoll($attachment, $user)
    {
        $answers = [];
        foreach ($attachment->getResults()->options as $answer) {
            $answers[] = (object) [
                "id"    => $answer->id,
                "rate"  => $answer->pct,
                "text"  => $answer->name,
                "votes" => $answer->votes,
            ];
        }

        $userVote = [];
        foreach ($attachment->getUserVote($user) as $vote) {
            $userVote[] = $vote[0];
        }

        return [
            "type"  => "poll",
            "poll" => [
                "multiple"       => $attachment->isMultipleChoice(),
                "end_date"       => $attachment->endsAt() == null ? 0 : $attachment->endsAt()->timestamp(),
                "closed"         => $attachment->hasEnded(),
                "is_board"       => false,
                "can_edit"       => false,
                "can_vote"       => $attachment->canVote($user),
                "can_report"     => false,
                "can_share"      => true,
                "created"        => 0,
                "id"             => $attachment->getId(),
                "owner_id"       => $attachment->getOwner()->getId(),
                "question"       => $attachment->getTitle(),
                "votes"          => $attachment->getVoterCount(),
                "disable_unvote" => $attachment->isRevotable(),
                "anonymous"      => $attachment->isAnonymous(),
                "answer_ids"     => $userVote,
                "answers"        => $answers,
                "author_id"      => $attachment->getOwner()->getId(),
            ],
        ];
    }
}
