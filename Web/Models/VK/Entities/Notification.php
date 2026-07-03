<?php
declare(strict_types=1);
namespace openvk\Web\Models\VK\Entities;
class Notification
{
    private array $data;
    public function __construct(array $data) { $this->data = $data; }

    public function getId(): int { return (int) ($this->data["id"] ?? 0); }

    // Returns the user/group that caused the notification
    public function getModel(int $type = 0): ?object
    {
        $profiles = $this->data["_profiles"] ?? [];
        $groups   = $this->data["_groups"] ?? [];
        $feedback = $this->data["feedback"] ?? [];

        // Try to get from feedback items (owners of the feedback)
        $items = $feedback["items"] ?? [];
        if (!empty($items)) {
            $fromId = $items[0]["from_id"] ?? 0;
            if ($fromId > 0) {
                foreach ($profiles as $p) {
                    if (($p["id"] ?? 0) === $fromId) return new User($p);
                }
            } elseif ($fromId < 0) {
                foreach ($groups as $g) {
                    if (($g["id"] ?? 0) === abs($fromId)) return new Club($g);
                }
            }
        }

        // Fallback: first profile
        if (!empty($profiles)) return new User($profiles[0]);

        return null;
    }

    public function getTemplatePath(): string
    {
        // Use a generic notification template
        return __DIR__ . "/../../Presenters/templates/Notification/components/feedback.latte";
    }

    public function getDate(): int { return (int) ($this->data["date"] ?? 0); }
    public function getType(): string { return $this->data["type"] ?? ""; }
    public function getRawData(): array { return $this->data; }
    public function __get(string $name): mixed { return $this->data[$name] ?? null; }
}
