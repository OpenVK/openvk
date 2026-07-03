<?php
declare(strict_types=1);
namespace openvk\Web\Models\VK\Entities;
class Application
{
    private array $data;
    public function __construct(array $data) { $this->data = $data; }
    public function getId(): int { return (int) ($this->data["id"] ?? 0); }
    public function getTitle(): string { return $this->data["title"] ?? ""; }
    public function getName(): string { return $this->getTitle(); }
    public function getAvatarUrl(string $type): string { return $this->data["icon_200"] ?? $this->data["icon_150"] ?? $this->data["icon_75"] ?? ""; }
    public function getDescription(): string { return $this->data["description"] ?? ""; }
    public function getURL(): string
    {
        bdump($this->data);
        return $this->data["mobile_url"] ?? $this->data["site_url"] ?? $this->data["webview_url"] ?? "";
    }
    public function isDeleted(): bool { return false; }
    public function isEnabled(): bool { return true; }

    public function getOrigin(): string
    {
        $parsed = parse_url($this->getURL());

        return (
            ($parsed["scheme"] ?? "https") . "://"
            . ($parsed["host"] ?? "127.0.0.1") . ":"
            . ($parsed["port"] ?? "443")
        );
    }

    public function getOwner()
    {
        $oid = (int)$this->data['author_owner_id'];
        if ($oid > 0) return User::load($oid);
        if ($oid < 0) return Club::load(abs($oid));
        if ($oid == 0) {
            return User::load(100);
        }

        return null;
    }

    public function getPermissions($user): array
    {
        return [];
    }

    public function getNote()
    {
        return null;
    }

    public function __get(string $name): mixed { return $this->data[$name] ?? null; }
}
