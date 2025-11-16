<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Repositories\Users as UsersRepo;
use openvk\Web\Models\Repositories\Gifts as GiftsRepo;
use openvk\Web\Models\Entities\Notifications\GiftNotification;

final class Gifts extends VKAPIRequestHandler
{
    public function get(int $user_id = 0, int $count = 10, int $offset = 0)
    {
        # There is no extended :)

        $this->requireUser();

        $server_url = ovk_scheme(true) . $_SERVER["HTTP_HOST"];

        if ($user_id < 1) {
            $user_id = $this->getUser()->getId();
        }

        $user = (new UsersRepo())->get($user_id);

        if (!$user || $user->isDeleted()) {
            $this->fail(15, "Access denied");
        }

        if (!$user->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $gift_item = [];
        $user_gifts = array_slice(iterator_to_array($user->getGifts(1, $count)), $offset, $count);

        foreach ($user_gifts as $gift) {
            $gift_item[] = [
                "from_id"   => $gift->anon == true ? 0 : $gift->sender->getId(),
                "message"   => $gift->caption == null ? "" : $gift->caption,
                "date"      => $gift->sent->timestamp(),
                "gift"      => [
                    "id"          => $gift->gift->getId(),
                    "thumb_256"   => $server_url . $gift->gift->getImage(2),
                    "thumb_96"    => $server_url . $gift->gift->getImage(2),
                    "thumb_48"    => $server_url . $gift->gift->getImage(2),
                ],
            ];
        }

        return $gift_item;
    }

    public function send(int $user_ids, int $gift_id, string $message = "", int $privacy = 0)
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        if (!OPENVK_ROOT_CONF['openvk']['preferences']['commerce']) {
            $this->fail(-105, "Commerce is disabled on this instance");
        }

        if (\openvk\Web\Util\EventRateLimiter::i()->tryToLimit($this->getUser(), "gifts.send", false)) {
            $this->failTooOften();
        }

        $user = (new UsersRepo())->get((int) $user_ids); # FAKE прогноз погоды (в данном случае user_ids)

        if (!$user || $user->isDeleted()) {
            $this->fail(15, "Access denied");
        }

        if (!$user->canBeViewedBy($this->getUser())) {
            $this->fail(15, "Access denied");
        }

        $gift  = (new GiftsRepo())->get($gift_id);

        if (!$gift) {
            $this->fail(15, "Invalid gift");
        }

        $price = $gift->getPrice();
        $coinsLeft = $this->getUser()->getCoins() - $price;

        if (!$gift->canUse($this->getUser())) {
            return (object)
            [
                "success"         => 0,
                "user_ids"        => $user_ids,
                "error"           => "You don't have any more of these gifts.",
            ];
        }

        if ($coinsLeft < 0) {
            return (object)
            [
                "success"         => 0,
                "user_ids"        => $user_ids,
                "error"           => "You don't have enough voices.",
            ];
        }

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
            "withdraw_votes"  => $price,
        ];
    }

    public function getCategories(bool $extended = false, int $page = 1)
    {
        $this->requireUser();

        $cats = (new GiftsRepo())->getCategories($page);
        $categ = [];
        $i = 0;
        $server_url = ovk_scheme(true) . $_SERVER["HTTP_HOST"];

        if (!OPENVK_ROOT_CONF['openvk']['preferences']['commerce']) {
            $this->fail(-105, "Commerce is disabled on this instance");
        }

        foreach ($cats as $cat) {
            $categ[$i] = [
                "name"        => $cat->getName(),
                "description" => $cat->getDescription(),
                "id"          => $cat->getId(),
                "thumbnail"   => $server_url . $cat->getThumbnailURL(),
            ];

            if ($extended == true) {
                $categ[$i]["localizations"] = [];
                foreach (getLanguages() as $lang) {
                    $code = $lang["code"];
                    $categ[$i]["localizations"][$code] =
                    [
                        "name"    => $cat->getName($code),
                        "desc"    => $cat->getDescription($code),
                    ];
                }
            }
            $i++;
        }

        return $categ;
    }

    public function getGiftsInCategory(int $id, int $page = 1)
    {
        $this->requireUser();

        if (!OPENVK_ROOT_CONF['openvk']['preferences']['commerce']) {
            $this->fail(-105, "Commerce is disabled on this instance");
        }

        $gift_category = (new GiftsRepo())->getCat($id);

        if (!$gift_category) {
            $this->fail(15, "Category not found");
        }

        $gifts_list = $gift_category->getGifts($page);
        $gifts = [];

        foreach ($gifts_list as $gift) {
            $gifts[] = [
                "name"         => $gift->getName(),
                "image"        => $gift->getImage(2),
                "usages_left"  => (int) $gift->getUsagesLeft($this->getUser()),
                "price"        => $gift->getPrice(),
                "is_free"      => $gift->isFree(),
            ];
        }

        return $gifts;
    }
}
