<?php
declare(strict_types=1);
namespace openvk\Web\Models\VK\Entities;
class Comment extends VkEntity
{
    public function getText(): string { return $this->data["text"] ?? ""; }
    public function getDate(): int { return (int) ($this->data["date"] ?? 0); }
    public function getPublicationTime(): \openvk\Web\Util\DateTime { return new \openvk\Web\Util\DateTime($this->getDate()); }
    public function getEditTime(): ?\openvk\Web\Util\DateTime { return null; }
    public function getLikesCount(): int { return (int) (($this->data["likes"] ?? [])["count"] ?? 0); }
    public function hasLikeFrom($user): bool { return (bool) (($this->data["likes"] ?? [])["user_likes"] ?? false); }
    public function getTarget() { return null; } // stub
    public function getTargetURL(): string { return ""; }
    public function canBeDeletedBy($who): bool { return false; }
    public function canBeEditedBy($who): bool { return false; }
    public function getChildrenWithLayout(int $width): object { return (object) ["height" => 0, "width" => 0, "tiles" => [], "extras" => []]; }
    public function getRealId(): int { return $this->getId(); }
}
