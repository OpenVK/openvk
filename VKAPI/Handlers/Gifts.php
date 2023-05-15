<?php declare(strict_types=1);
namespace openvk\VKAPI\Handlers;
use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Repositories\Gifts as GiftsRepo;
use openvk\Web\Models\Entities\Notifications\GiftNotification;

final class Gifts extends VKAPIRequestHandler
{
    function get(int $user_id, int $count = 100, int $offset = 0)
    {
        $this->requireUser();

        $i = 0;

        $i+=$offset;

        $user = (new UsersRepo)->get($user_id);
        if(!$user || $user->isDeleted())
            $this->fail(177, "Invalid user");

        $gift_item = [];

        $userGifts = $user->getGifts(1, $count, false);

        if(sizeof($userGifts) < 0) {
            return NULL;
        }

        foreach($userGifts as $gift) {
            if($i < $count) {
                $gift_item[] = [
                    "id"        => $i,
                    "from_id"   => $gift->anon == true ? 0 : $gift->sender->getId(),
                    "message"   => $gift->caption == NULL ? "" : $gift->caption,
                    "date"      => $gift->sent->timestamp(),
                    "gift"      => [
                        "id"          => $gift->gift->getId(),
                        "thumb_256"   => $gift->gift->getImage(2),
                        "thumb_96"    => $gift->gift->getImage(2),
                        "thumb_48"    => $gift->gift->getImage(2)
                    ],
                    "privacy"   => 0
                ];
            }
            $i+=1;
        }

        return $gift_item;
    }

    function send(int $user_ids, int $gift_id, string $message = "", int $privacy = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $user = (new UsersRepo)->get((int) $user_ids);
        if(!$user || $user->isDeleted())
            $this->fail(177, "Invalid user");
        
        $gift  = (new GiftsRepo)->get($gift_id);
        $price = $gift->getPrice();
        $coinsLeft = $this->getUser()->getCoins() - $price;

        if(!$gift->canUse($this->getUser()))
            return (object)
            [
                "success"         => 0,
                "user_ids"        => $user_ids,
                "error"           => "You don't have any more of these gifts."
            ];

        if($coinsLeft < 0)
            return (object)
            [
                "success"         => 0,
                "user_ids"        => $user_ids,
                "error"           => "You don't have enough voices."
            ];

        $user->gift($this->getUser(), $gift, $message);
        $gift->used();

        $this->getUser()->setCoins($coinsLeft);
        $this->getUser()->save();

        $notification = new GiftNotification($user, $this->getUser(), $gift, $message);
        $notification->emit();

        return (object)
        [
            "success"         => 1,
            "user_ids"        => $user_ids,
            "withdraw_votes"  => $price
        ];
    }

    function delete()
    {
        $this->requireUser();
        $this->willExecuteWriteAction();
        # тожэ заглушка
        return 0;
    }

    # этих методов не было в ВК, но я их добавил чтобы можно было отобразить список подарков
    function getCategories(bool $extended = false, int $count = 10, int $offset = 0)
    {
        $cats = (new GiftsRepo)->getCategories(1, $count);
        $categ = [];
        $i = 1;

        foreach($cats as $cat) {
            if($i > $count) 
                break;
            if($i > $offset) {
                $categ[] = [
                    "name"        => $cat->getName(),
                    "description" => $cat->getDescription(),
                    "id"          => $cat->getId(),
                    "thumbnail"   => $cat->getThumbnailURL(),
                    "localizations" => $extended == true ?
                    [
                        "en"    => [
                            "name"    => $cat->getName("en"),
                            "desc"    => $cat->getDescription("en"),
                        ],
                        "ru"    => [
                            "name"    => $cat->getName("ru"),
                            "desc"    => $cat->getDescription("ru"),
                        ],
                        "uk"  => [
                            "name"    => $cat->getName("uk"),
                            "desc"    => $cat->getDescription("uk")
                        ],
                        "by"  => [
                            "name"    => $cat->getName("by"),
                            "desc"    => $cat->getDescription("by")
                        ],
                        "by_lat"  => [
                            "name"    => $cat->getName("by_lat"),
                            "desc"    => $cat->getDescription("by_lat")
                        ],
                        "pl"  => [
                            "name"    => $cat->getName("pl"),
                            "desc"    => $cat->getDescription("pl")
                        ],
                        "de"  => [
                            "name"    => $cat->getName("de"),
                            "desc"    => $cat->getDescription("de")
                        ],
                        "hy"  => [
                            "name"    => $cat->getName("hy"),
                            "desc"    => $cat->getDescription("hy")
                        ],
                        "sr_cyr"  => [
                            "name"    => $cat->getName("sr_cyr"),
                            "desc"    => $cat->getDescription("sr_cyr")
                        ],
                        "sr_lat"  => [
                            "name"    => $cat->getName("sr_lat"),
                            "desc"    => $cat->getDescription("sr_lat")
                        ],
                        "tr"  => [
                            "name"    => $cat->getName("tr"),
                            "desc"    => $cat->getDescription("tr")
                        ],
                        "kk"  => [
                            "name"    => $cat->getName("kk"),
                            "desc"    => $cat->getDescription("kk")
                        ],
                        "ru_old"  => [
                            "name"    => $cat->getName("ru_old"),
                            "desc"    => $cat->getDescription("ru_old")
                        ],
                        "eo"  => [
                            "name"    => $cat->getName("eo"),
                            "desc"    => $cat->getDescription("eo")
                        ],
                        "ru_sov"  => [
                            "name"    => $cat->getName("ru_sov"),
                            "desc"    => $cat->getDescription("ru_sov")
                        ],
                        "udm"  => [
                            "name"    => $cat->getName("udm"),
                            "desc"    => $cat->getDescription("udm")
                        ],
                        "id"  => [
                            "name"    => $cat->getName("id"),
                            "desc"    => $cat->getDescription("id")
                        ],
                        "qqx"  => [
                            "name"    => $cat->getName("qqx"),
                            "desc"    => $cat->getDescription("qqx")
                        ],
                    ] : NULL];
            } else {
                $i++;
            }
        }
        
        return $categ;
    }

    function getGiftsInCategory(int $id, int $count = 10, int $offset = 0)
    {
        $this->requireUser();
        if(!(new GiftsRepo)->getCat($id))
            $this->fail(177, "Category not found");

        $giftz = ((new GiftsRepo)->getCat($id))->getGifts(1, $count);
        $gifts = [];
        $i = 1;

        foreach($giftz as $gift) {
            if($i > $count) 
                break;
            if($i > $offset) {
                $gifts[] = [
                    "name"         => $gift->getName(),
                    "image"        => $gift->getImage(2),
                    "usages_left"  => (int)$gift->getUsagesLeft($this->getUser()),
                    "price"        => $gift->getPrice(), # голосов
                    "is_free"      => $gift->isFree()
                ];
            } else {
                $i++;
            }
        }

        return $gifts;
    }
}
