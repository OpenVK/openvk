<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Gifts;

use Nette\Database\Table\ActiveRow;
use openvk\Web\Models\Entities\{Gift, User};
use openvk\Web\Util\DateTime;

class SentGift 
{
    private $relation;
    public $shortName = "gift";

    function __construct(ActiveRow $relation, Gift $gift)
    {
        $this->relation = $relation;
        $this->gift = $gift;
    }

    function canBeViewedBy(?User $user = null): bool
    {
        return true;
    }

    public function toApiAttachment(User $user): object
    {
        return (object) [
            "type"  => "gift",
            "gift" => $this->toVkApiStruct($user),
        ];
    }

    public function toVkApiStruct(User $user): object
    {
        $server_url = ovk_scheme(true) . $_SERVER["HTTP_HOST"];
        $relation = $this->relation;

        return (object) [
            "id"        => $relation->id,
            "message"   => $relation->comment == null ? "" : $relation->comment,
            "date"      => (new DateTime($relation->sent))->timestamp(),
            "privacy"   => $relation->anonymous == 1 ? 1 : 0,
            "gift"      => [
                "id"          => $this->gift->getId(),
                "thumb_256"   => $server_url . $this->gift->getImage(2),
                "thumb_96"    => $server_url . $this->gift->getImage(2),
                "thumb_48"    => $server_url . $this->gift->getImage(2),
            ],
        ];
    }

    public function delete()
    {
        $this->relation->update([
            "deleted" => 1,
        ]);
    }
}
