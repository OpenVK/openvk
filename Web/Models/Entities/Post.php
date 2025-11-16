<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use Chandler\Database\DatabaseConnection as DB;
use openvk\Web\Models\Repositories\{Clubs, Users};
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\Notifications\LikeNotification;

class Post extends Postable
{
    use Traits\TRichText;
    protected $tableName = "posts";
    protected $upperNodeReferenceColumnName = "wall";

    private function setLikeRecursively(bool $liked, User $user, int $depth): void
    {
        $searchData = [
            "origin" => $user->getId(),
            "model"  => static::class,
            "target" => $this->getRecord()->id,
        ];

        if ((sizeof(DB::i()->getContext()->table("likes")->where($searchData)) > 0) !== $liked) {
            if ($this->getOwner(false)->getId() !== $user->getId() && !($this->getOwner() instanceof Club) && !$this instanceof Comment) {
                (new LikeNotification($this->getOwner(false), $this, $user))->emit();
            }

            parent::setLike($liked, $user);
        }

        if ($depth < ovkGetQuirk("wall.repost-liking-recursion-limit")) {
            foreach ($this->getChildren() as $attachment) {
                if ($attachment instanceof Post) {
                    $attachment->setLikeRecursively($liked, $user, $depth + 1);
                }
            }
        }
    }

    /**
     * May return fake owner (group), if flags are [1, (*)]
     *
     * @param bool $honourFlags - check flags
     */
    public function getOwner(bool $honourFlags = true, bool $real = false): RowModel
    {
        if ($honourFlags && $this->isPostedOnBehalfOfGroup()) {
            if ($this->getRecord()->wall < 0) {
                return (new Clubs())->get(abs($this->getRecord()->wall));
            }
        }

        return parent::getOwner($real);
    }

    public function getPrettyId(): string
    {
        return $this->getRecord()->wall . "_" . $this->getVirtualId();
    }

    public function getTargetWall(): int
    {
        return $this->getRecord()->wall;
    }

    public function getWallOwner()
    {
        $w = $this->getRecord()->wall;
        if ($w < 0) {
            return (new Clubs())->get(abs($w));
        }

        return (new Users())->get($w);
    }

    public function getRepostCount(): int
    {
        return sizeof(
            $this->getRecord()
                 ->related("attachments.attachable_id")
                 ->where("attachable_type", get_class($this))
        );
    }

    public function isPinned(): bool
    {
        return (bool) $this->getRecord()->pinned;
    }

    public function hasSource(): bool
    {
        return $this->getRecord()->source != null;
    }

    public function getSource(bool $format = false)
    {
        $orig_source = $this->getRecord()->source;
        if (!str_contains($orig_source, "https://") && !str_contains($orig_source, "http://")) {
            $orig_source = "https://" . $orig_source;
        }

        if (!$format) {
            return $orig_source;
        }

        return $this->formatLinks($orig_source);
    }

    public function setSource(string $source)
    {
        $result = check_copyright_link($source);

        $this->stateChanges("source", $source);
    }

    public function resetSource()
    {
        $this->stateChanges("source", null);
    }

    public function getVkApiCopyright(): object
    {
        return (object) [
            'id'   => 0,
            'link' => $this->getSource(false),
            'name' => $this->getSource(false),
            'type' => 'link',
        ];
    }

    public function isAd(): bool
    {
        return (bool) $this->getRecord()->ad;
    }

    public function isPostedOnBehalfOfGroup(): bool
    {
        return ($this->getRecord()->flags & 0b10000000) > 0;
    }

    public function isSigned(): bool
    {
        return ($this->getRecord()->flags & 0b01000000) > 0;
    }

    public function isDeactivationMessage(): bool
    {
        return (($this->getRecord()->flags & 0b00100000) > 0) && ($this->getRecord()->owner > 0);
    }

    public function isUpdateAvatarMessage(): bool
    {
        return (($this->getRecord()->flags & 0b00010000) > 0) && ($this->getRecord()->owner > 0);
    }

    public function isExplicit(): bool
    {
        return (bool) $this->getRecord()->nsfw;
    }

    public function isDeleted(): bool
    {
        return (bool) $this->getRecord()->deleted;
    }

    public function getOwnerPost(): int
    {
        return $this->getOwner(false)->getId();
    }

    public function getPlatform(bool $forAPI = false): ?string
    {
        $platform = $this->getRecord()->api_source_name;
        if ($forAPI) {
            switch ($platform) {
                case 'openvk_native':
                case 'openvk_refresh_android':
                case 'openvk_legacy_android':
                    return 'android';
                    break;

                case 'openvk_native_ios':
                case 'openvk_ios':
                case 'openvk_legacy_ios':
                    return 'iphone';
                    break;

                case 'windows_phone':
                    return 'wphone';
                    break;

                case 'vika_touch': // кика хохотач ахахахаххахахахахах
                case 'vk4me':
                    return 'mobile';
                    break;

                case null:
                    return null;
                    break;

                default:
                    return 'api';
                    break;
            }
        } else {
            return $platform;
        }
    }

    public function getPlatformDetails(): array
    {
        $clients = simplexml_load_file(OPENVK_ROOT . "/data/clients.xml");

        foreach ($clients as $client) {
            if ($client['tag'] == $this->getPlatform()) {
                return [
                    "tag"  => $client['tag'],
                    "name" => $client['name'],
                    "url"  => $client['url'],
                    "img"  => $client['img'],
                ];
                break;
            }
        }

        return [
            "tag"  => $this->getPlatform(),
            "name" => null,
            "url"  => null,
            "img"  => null,
        ];
    }

    public function getPostSourceInfo(): array
    {
        $post_source = ["type" => "vk"];
        if ($this->getPlatform(true) !== null) {
            $post_source = [
                "type" => "api",
                "platform" => $this->getPlatform(true),
            ];
        }

        if ($this->isUpdateAvatarMessage()) {
            $post_source['data'] = 'profile_photo';
        }

        return $post_source;
    }

    public function getVkApiType(): string
    {
        $type = 'post';
        if ($this->getSuggestionType() != 0) {
            $type = 'suggest';
        }

        return $type;
    }

    public function pin(): void
    {
        DB::i()
            ->getContext()
            ->table("posts")
            ->where([
                "wall"   => $this->getTargetWall(),
                "pinned" => true,
            ])
            ->update(["pinned" => false]);

        $this->stateChanges("pinned", true);
        $this->save();
    }

    public function unpin(): void
    {
        $this->stateChanges("pinned", false);
        $this->save();
    }

    public function canBePinnedBy(User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->getTargetWall() < 0) {
            return (new Clubs())->get(abs($this->getTargetWall()))->canBeModifiedBy($user);
        }

        return $this->getTargetWall() === $user->getId();
    }

    public function canBeDeletedBy(User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->getTargetWall() < 0 && !$this->getWallOwner()->canBeModifiedBy($user) && $this->getWallOwner()->getWallType() != 1 && $this->getSuggestionType() == 0) {
            return false;
        }

        return $this->getOwnerPost() === $user->getId() || $this->canBePinnedBy($user);
    }

    public function setContent(string $content): void
    {
        if (ctype_space($content)) {
            throw new \LengthException("Content length must be at least 1 character (not counting whitespaces).");
        } elseif (iconv_strlen($content) > OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["postSizes"]["maxSize"]) {
            throw new \LengthException("Content is too large.");
        }

        $this->stateChanges("content", $content);
    }

    public function toggleLike(User $user): bool
    {
        $liked = parent::toggleLike($user);

        if (!$user->isPrivateLikes() && $this->getOwner(false)->getId() !== $user->getId() && !($this->getOwner() instanceof Club) && !$this instanceof Comment) {
            (new LikeNotification($this->getOwner(false), $this, $user))->emit();
        }

        foreach ($this->getChildren() as $attachment) {
            if ($attachment instanceof Post) {
                $attachment->setLikeRecursively($liked, $user, 2);
            }
        }

        return $liked;
    }

    public function setLike(bool $liked, User $user): void
    {
        $this->setLikeRecursively($liked, $user, 1);
    }

    public function deletePost(): void
    {
        $this->setDeleted(1);
        $this->unwire();
        $this->save();
    }

    public function canBeViewedBy(?User $user = null): bool
    {
        if ($this->isDeleted()) {
            return false;
        }

        return $this->getWallOwner()->canBeViewedBy($user);
    }

    public function getSuggestionType()
    {
        return $this->getRecord()->suggested;
    }

    public function getPageURL(): string
    {
        return "/wall" . $this->getPrettyId();
    }

    public function toNotifApiStruct()
    {
        $res = (object) [];

        $res->id      = $this->getVirtualId();
        $res->to_id   = $this->getOwner() instanceof Club ? $this->getOwner()->getId() * -1 : $this->getOwner()->getId();
        $res->from_id = $res->to_id;
        $res->date    = $this->getPublicationTime()->timestamp();
        $res->text    = $this->getText(false);
        $res->attachments = []; # todo

        $res->copy_owner_id = null; # todo
        $res->copy_post_id  = null; # todo

        return $res;
    }

    public function canBeEditedBy(?User $user = null): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->isDeactivationMessage() || $this->isUpdateAvatarMessage()) {
            return false;
        }

        if ($this->getTargetWall() > 0) {
            return $this->getPublicationTime()->timestamp() + WEEK > time() && $user->getId() == $this->getOwner(false)->getId();
        } else {
            if ($this->isPostedOnBehalfOfGroup()) {
                return $this->getWallOwner()->canBeModifiedBy($user);
            } else {
                return $user->getId() == $this->getOwner(false)->getId();
            }
        }

        return $user->getId() == $this->getOwner(false)->getId();
    }

    public function toRss(): \Bhaktaraz\RSSGenerator\Item
    {
        $domain = ovk_scheme(true) . $_SERVER["HTTP_HOST"];
        $description = $this->getText(false);
        $title = str_replace("\n", "", ovk_proc_strtr($description, 79));
        $description_html = $description;
        $url = $domain . "/wall" . $this->getPrettyId();

        if ($this->isUpdateAvatarMessage()) {
            $title = tr('upd_in_general');
        }
        if ($this->isDeactivationMessage()) {
            $title = tr('post_deact_in_general');
        }

        $author = $this->getOwner();
        $target_wall = $this->getWallOwner();
        $author_name = escape_html($author->getCanonicalName());
        if ($this->isExplicit()) {
            $title = 'NSFW: ' . $title;
        }

        foreach ($this->getChildren() as $child) {
            if ($child instanceof Photo) {
                $child_page = $domain . $child->getPageURL();
                $child_url = $child->getURL();
                $description_html .= "<br /><a href='$child_page'><img src='$child_url'></a><br />";
            } elseif ($child instanceof Video) {
                $child_page = $domain . '/video' . $child->getPrettyId();

                if ($child->getType() != 1) {
                    $description_html .= "" .
                    "<br />" .
                    "<video width=\"320\" height=\"240\" controls><source src=\"" . $child->getURL() . "\" type=\"video/mp4\"></video><br />" .
                    "<b>" . escape_html($child->getName()) . "</b><br />";
                } else {
                    $description_html .= "" .
                    "<br />" .
                    "<a href=\"" . $child->getVideoDriver()->getURL() . "\"><b>" . escape_html($child->getName()) . "</b></a><br />";
                }
            } elseif ($child instanceof Audio) {
                if (!$child->isWithdrawn()) {
                    $description_html .= "<br />"
                    . "<b>" . escape_html($child->getName()) . "</b>:"
                    . "<br />"
                    . "<audio controls>"
                    . "<source src=\"" . $child->getOriginalURL() . "\" type=\"audio/mpeg\"></audio>"
                    . "<br />";
                }
            } elseif ($child instanceof Poll) {
                $description_html .= "<br />" . tr('poll') . ": " . escape_html($child->getTitle());
            } elseif ($child instanceof Note) {
                $description_html .= "<br />" . tr('note') . ": " . escape_html($child->getName());
            }
        }

        $description_html .= "<br />" . tr('author') . ": <img width='15px' src='" . $author->getAvatarURL() . "'><a href='" . $author->getURL() . "'>" . $author_name . "</a>";

        if ($target_wall->getRealId() != $author->getRealId()) {
            $description_html .= "<br />" . tr('on_wall') . ": <img width='15px' src='" . $target_wall->getAvatarURL() . "'><a href='" . $target_wall->getURL() . "'>" . escape_html($target_wall->getCanonicalName()) . "</a>";
        }

        if ($this->isSigned()) {
            $signer = $this->getOwner(false);
            $description_html .= "<br />" . tr('sign_short') . ": <img width='15px' src='" . $signer->getAvatarURL() . "'><a href='" . $signer->getURL() . "'>" . escape_html($signer->getCanonicalName()) . "</a>";
        }

        if ($this->hasSource()) {
            $description_html .= "<br />" . tr('source') . ": " . escape_html($this->getSource());
        }

        $item = new \Bhaktaraz\RSSGenerator\Item();
        $item->title($title)
        ->url($url)
        ->guid($url)
        ->creator($author_name)
        ->pubDate($this->getPublicationTime()->timestamp())
        ->content(str_replace("\n", "<br />", $description_html));

        return $item;
    }

    public function getGeo(): ?object
    {
        if (!$this->getRecord()->geo) {
            return null;
        }

        return (object) json_decode($this->getRecord()->geo, true, JSON_UNESCAPED_UNICODE);
    }

    public function setGeo($encoded_object): void
    {
        $final_geo = $encoded_object['name'];
        $neutral_names = ["Россия", "Russia", "Росія", "Россія", "Украина", "Ukraine", "Україна", "Украіна"];
        foreach ($neutral_names as $name) {
            if (str_contains($final_geo, $name . ", ")) {
                $final_geo = str_replace($name . ", ", "", $final_geo);
            }
        }

        $encoded_object['name'] = ovk_proc_strtr($final_geo, 255);
        $encoded = json_encode($encoded_object);
        $this->stateChanges("geo", $encoded);
    }

    public function getLat(): ?float
    {
        return (float) $this->getRecord()->geo_lat ?? null;
    }

    public function getLon(): ?float
    {
        return (float) $this->getRecord()->geo_lon ?? null;
    }

    public function getVkApiGeo(): object
    {
        return (object) [
            'type'  => 'point',
            'coordinates' => $this->getLat() . ',' . $this->getLon(),
            'name' => $this->getGeo()->name,
        ];
    }
}
