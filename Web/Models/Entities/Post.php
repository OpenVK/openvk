<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use Chandler\Database\DatabaseConnection as DB;
use openvk\Web\Models\Repositories\{Clubs, Users};
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Entities\Notifications\LikeNotification;

class Post extends Postable
{
    protected $tableName = "posts";
    protected $upperNodeReferenceColumnName = "wall";

    private function setLikeRecursively(bool $liked, User $user, int $depth): void
    {
        $searchData = [
            "origin" => $user->getId(),
            "model"  => static::class,
            "target" => $this->getRecord()->id,
        ];

        if((sizeof(DB::i()->getContext()->table("likes")->where($searchData)) > 0) !== $liked) {
            if($this->getOwner(false)->getId() !== $user->getId() && !($this->getOwner() instanceof Club) && !$this instanceof Comment)
                (new LikeNotification($this->getOwner(false), $this, $user))->emit();

            parent::setLike($liked, $user);
        }

        if($depth < ovkGetQuirk("wall.repost-liking-recursion-limit"))
            foreach($this->getChildren() as $attachment)
                if($attachment instanceof Post)
                    $attachment->setLikeRecursively($liked, $user, $depth + 1);
    }
    
    /**
     * May return fake owner (group), if flags are [1, (*)]
     * 
     * @param bool $honourFlags - check flags
     */
    function getOwner(bool $honourFlags = true, bool $real = false): RowModel
    {
        if($honourFlags && $this->isPostedOnBehalfOfGroup()) {
            if($this->getRecord()->wall < 0)
                return (new Clubs)->get(abs($this->getRecord()->wall));
        }
        
        return parent::getOwner($real);
    }
    
    function getPrettyId(): string
    {
        return $this->getRecord()->wall . "_" . $this->getVirtualId();
    }
    
    function getTargetWall(): int
    {
        return $this->getRecord()->wall;
    }

    function getWallOwner()
    {
        $w = $this->getRecord()->wall;
        if($w < 0)
            return (new Clubs)->get(abs($w));

        return (new Users)->get($w);
    }
    
    function getRepostCount(): int
    {
        return sizeof(
            $this->getRecord()
                 ->related("attachments.attachable_id")
                 ->where("attachable_type", get_class($this))
        );
    }
    
    function isPinned(): bool
    {
        return (bool) $this->getRecord()->pinned;
    }

    function hasSource(): bool
    {
        return $this->getRecord()->source != NULL;
    }

    function getSource(bool $format = false)
    {
        $orig_source = $this->getRecord()->source;
        if(!str_contains($orig_source, "https://") && !str_contains($orig_source, "http://"))
            $orig_source = "https://" . $orig_source;

        if(!$format)
            return $orig_source;
        
        return $this->formatLinks($orig_source);
    }

    function setSource(string $source)
    {
        $result = check_copyright_link($source);

        $this->stateChanges("source", $source);
    }

    function getVkApiCopyright(): object
    {
        return (object)[
            'id'   => 0,
            'link' => $this->getSource(false),
            'name' => $this->getSource(false),
            'type' => 'link',
        ];
    }
    
    function isAd(): bool
    {
        return (bool) $this->getRecord()->ad;
    }
    
    function isPostedOnBehalfOfGroup(): bool
    {
        return ($this->getRecord()->flags & 0b10000000) > 0;
    }
    
    function isSigned(): bool
    {
        return ($this->getRecord()->flags & 0b01000000) > 0;
    }

    function isDeactivationMessage(): bool
    {
        return (($this->getRecord()->flags & 0b00100000) > 0) && ($this->getRecord()->owner > 0);
    }
    
    function isUpdateAvatarMessage(): bool
    {
        return (($this->getRecord()->flags & 0b00010000) > 0) && ($this->getRecord()->owner > 0);
    }

    function isExplicit(): bool
    {
        return (bool) $this->getRecord()->nsfw;
    }
    
    function isDeleted(): bool
    {
        return (bool) $this->getRecord()->deleted;
    }
    
    function getOwnerPost(): int
    {
        return $this->getOwner(false)->getId();
    }

    function getPlatform(bool $forAPI = false): ?string
    {
        $platform = $this->getRecord()->api_source_name;
        if($forAPI) {
            switch ($platform) {
                case 'openvk_refresh_android':
                case 'openvk_legacy_android':
                    return 'android';
                    break;

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

                case NULL:
                    return NULL;
                    break;
                
                default:
                    return 'api';
                    break;
            }
        } else {
            return $platform;
        }
    }

    function getPlatformDetails(): array
    {
        $clients = simplexml_load_file(OPENVK_ROOT . "/data/clients.xml");

        foreach($clients as $client) {
            if($client['tag'] == $this->getPlatform()) {
                return [
                    "tag"  => $client['tag'],
                    "name" => $client['name'],
                    "url"  => $client['url'],
                    "img"  => $client['img']
                ];
                break;
            }
        }

        return [
            "tag"  => $this->getPlatform(),
            "name" => NULL,
            "url"  => NULL,
            "img"  => NULL
        ];
    }

    function getPostSourceInfo(): array
    {
        $post_source = ["type" => "vk"];
        if($this->getPlatform(true) !== NULL) {
            $post_source = [
                "type" => "api",
                "platform" => $this->getPlatform(true)
            ];
        }

        if($this->isUpdateAvatarMessage())
            $post_source['data'] = 'profile_photo';
        
        return $post_source;
    }

    function getVkApiType(): string
    {
        $type = 'post';
        if($this->getSuggestionType() != 0)
            $type = 'suggest';

        return $type;
    }
    
    function pin(): void
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
    
    function unpin(): void
    {
        $this->stateChanges("pinned", false);
        $this->save();
    }
    
    function canBePinnedBy(User $user): bool
    {
        if($this->getTargetWall() < 0)
            return (new Clubs)->get(abs($this->getTargetWall()))->canBeModifiedBy($user);
        
        return $this->getTargetWall() === $user->getId();
    }
    
    function canBeDeletedBy(User $user): bool
    {
        if($this->getTargetWall() < 0 && !$this->getWallOwner()->canBeModifiedBy($user) && $this->getWallOwner()->getWallType() != 1 && $this->getSuggestionType() == 0)
            return false;
        
        return $this->getOwnerPost() === $user->getId() || $this->canBePinnedBy($user);
    }
    
    function setContent(string $content): void
    {
        if(ctype_space($content))
            throw new \LengthException("Content length must be at least 1 character (not counting whitespaces).");
        else if(iconv_strlen($content) > OPENVK_ROOT_CONF["openvk"]["preferences"]["wall"]["postSizes"]["maxSize"])
            throw new \LengthException("Content is too large.");
        
        $this->stateChanges("content", $content);
    }

    function toggleLike(User $user): bool
    {
        $liked = parent::toggleLike($user);

        if($this->getOwner(false)->getId() !== $user->getId() && !($this->getOwner() instanceof Club) && !$this instanceof Comment)
            (new LikeNotification($this->getOwner(false), $this, $user))->emit();

        foreach($this->getChildren() as $attachment)
            if($attachment instanceof Post)
                $attachment->setLikeRecursively($liked, $user, 2);

        return $liked;
    }

    function setLike(bool $liked, User $user): void
    {
        $this->setLikeRecursively($liked, $user, 1);
    }
    
    function deletePost(): void 
    {
        $this->setDeleted(1);
        $this->unwire();
        $this->save();
    }

    function canBeViewedBy(?User $user = NULL): bool
    {
        if($this->isDeleted()) {
            return false;
        }
        
        return $this->getWallOwner()->canBeViewedBy($user);
    }
    
    function getSuggestionType()
    {
        return $this->getRecord()->suggested;
    }
  
    function toNotifApiStruct()
    {
        $res = (object)[];
        
        $res->id      = $this->getVirtualId();
        $res->to_id   = $this->getOwner() instanceof Club ? $this->getOwner()->getId() * -1 : $this->getOwner()->getId();
        $res->from_id = $res->to_id;
        $res->date    = $this->getPublicationTime()->timestamp();
        $res->text    = $this->getText(false);
        $res->attachments = []; # todo

        $res->copy_owner_id = NULL; # todo
        $res->copy_post_id  = NULL; # todo

        return $res;
    }
    
    function canBeEditedBy(?User $user = NULL): bool
    {
        if(!$user)
            return false;

        if($this->isDeactivationMessage() || $this->isUpdateAvatarMessage())
            return false;

        if($this->getTargetWall() > 0)
            return $this->getPublicationTime()->timestamp() + WEEK > time() && $user->getId() == $this->getOwner(false)->getId();
        else {
            if($this->isPostedOnBehalfOfGroup())
                return $this->getWallOwner()->canBeModifiedBy($user);
            else
                return $user->getId() == $this->getOwner(false)->getId();
        }

        return $user->getId() == $this->getOwner(false)->getId();
    }
    
    use Traits\TRichText;
}
