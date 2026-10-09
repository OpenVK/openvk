<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities\Relationships;

use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\Applications;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Util\DateTime;

class VoteChangeInfo extends RowModel
{
    public const ACTION_SEND = 0;
    public const ACTION_APP_BUY = 1;
    public const ACTION_APP_WITHDRAW = 2;
    public const ACTION_STICKERS_WITHDRAW = 3;
    public const ACTION_TON = 4;
    public const ACTION_GIFT = 5;
    public const ACTION_STICKERPACK_BUY = 6;
    public const ACTION_VOUNCHER_USE = 7;
    public const ACTION_RATING_CHANGE = 8;

    protected $tableName = "votes_changes";

    public function getOwner(): RowModel
    {
        $oid = (int) $this->getRecord()->owner_id;

        return get_entity_by_id($oid);
    }

    public function getInitiator(): RowModel
    {
        $oid = (int) $this->getRecord()->caused_by;
        $type = (int) $this->getRecord()->caused_by_type;

        if ($type == 1) {
            return (new Applications())->get($oid);
        } else {
            return get_entity_by_id($oid);
        }
    }

    public function getOldValue(): float
    {
        return $this->getRecord()->old_value;
    }

    public function getNewValue(): float
    {
        return $this->getRecord()->new_value;
    }

    public function getDifference(): float
    {
        return $this->getNewValue() - $this->getOldValue();
    }

    public function getPublicationTime(): DateTime
    {
        return new DateTime($this->getRecord()->created_at);
    }

    public function getAction(): int
    {
        return $this->getRecord()->action;
    }

    public function toVkApiStruct(): object
    {
        return (object) [
            "initiator"  => $this->getInitiator()->getRealId(),
            "created_at" => $this->getPublicationTime()->timestamp(),
            "old_value"  => $this->getOldValue(),
            "new_value"  => $this->getNewValue(),
            "action"     => $this->getRecord()->action,
        ];
    }

    public function getExtendedKey(): string
    {
        return "profiles";
    }

    public static function getHistory(RowModel $from)
    {
        $payload = [];
        $selection = DatabaseConnection::i()->getContext()->table("votes_changes")->where("owner_id", $from->getRealId());

        foreach ($selection as $item) {
            $payload[] = new VoteChangeInfo($item);
        }

        return $payload;
    }

    public static function sendAction(RowModel $from, ?RowModel $to = null, float $diff = 0.0, ?int $action = null): bool
    {
        // Sender

        $out = new VoteChangeInfo();
        $out->setOwner_id($from->getRealId());
        $out->setCreated_at(time());

        if ($to) {
            $out->setCaused_by($to->getRealId());
        }

        $out->setOld_value($from->getCoins());
        $out->setNew_value($from->getCoins() - $diff);
        if ($action) {
            $out->setAction($action);
        }

        $out->save();

        // Receiver

        if ($to) {
            $out2 = new VoteChangeInfo();
            $out2->setOwner_id($to->getRealId());
            $out2->setCreated_at(time());
            $out2->setCaused_by($from->getRealId());
            $out2->setOld_value($to->getCoins());
            $out2->setNew_value($to->getCoins() + $diff);
            if ($action) {
                $out2->setAction($action);
            }

            $out2->save();
        }

        return true;
    }
}
