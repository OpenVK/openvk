<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use HTMLPurifier_Config;
use HTMLPurifier;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\{Clubs, Users, GroupPages};
use openvk\Web\Util\DateTime;
use Chandler\Database\DatabaseConnection;
use Parsedown;

class GroupPage extends RowModel
{
    protected $tableName = "group_pages";

    public const ACCESS_EVERYONE = 0;
    public const ACCESS_MEMBERS  = 1;
    public const ACCESS_ADMINS   = 2;

    public function getId(): int
    {
        return $this->getRecord()->id;
    }

    public function getVirtualId(): int
    {
        return (int) $this->getRecord()->virtual_id;
    }

    public function getGroupId(): int
    {
        $record = $this->getRecord();
        if ($record) {
            return (int) $record->group;
        }

        return (int) ($this->changes["group"] ?? 0);
    }

    public function getClub(): Club
    {
        return (new Clubs())->get($this->getGroupId());
    }

    public function getOwner(): ?User
    {
        return (new Users())->get((int) $this->getRecord()->owner);
    }

    public function getTitle(): string
    {
        return $this->getRecord()->title;
    }

    public function getSource(): string
    {
        return (string) $this->getRecord()->source;
    }

    public function isMain(): bool
    {
        return (bool) $this->getRecord()->is_main;
    }

    public function getViewAccess(): int
    {
        return (int) $this->getRecord()->view_access;
    }

    public function getEditAccess(): int
    {
        return (int) $this->getRecord()->edit_access;
    }

    public function isDeleted(): bool
    {
        return (bool) $this->getRecord()->deleted;
    }

    public function getPrettyId(): string
    {
        return "-" . $this->getGroupId() . "_" . $this->getVirtualId();
    }

    public function getURL(): string
    {
        return "/page-" . $this->getGroupId() . "_" . $this->getVirtualId();
    }

    public function getPublicationTime(): DateTime
    {
        return new DateTime((int) $this->getRecord()->created);
    }

    public function getEditTime(): ?DateTime
    {
        $edited = $this->getRecord()->edited;
        return $edited ? new DateTime((int) $edited) : null;
    }

    public function getEditor(): ?User
    {
        $revisions = DatabaseConnection::i()->getContext()->table("group_page_revisions")
            ->where("page", $this->getId())
            ->order("created DESC")
            ->limit(1)
            ->fetch();

        if (!$revisions) {
            return $this->getOwner();
        }

        return (new Users())->get((int) $revisions->editor);
    }

    protected function checkAccessLevel(int $level, ?User $user): bool
    {
        if ($level === self::ACCESS_EVERYONE) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $club = $this->getClub();
        if ($club->canBeModifiedBy($user)) {
            return true;
        }

        if ($level === self::ACCESS_ADMINS) {
            return false;
        }

        // ACCESS_MEMBERS
        return $club->getSubscriptionStatus($user);
    }

    public function canBeViewedBy(?User $user = null): bool
    {
        if ($this->isDeleted()) {
            return false;
        }

        $club = $this->getClub();
        if (!$club || $club->isBanned() || !$club->isPagesEnabled()) {
            return false;
        }

        return $this->checkAccessLevel($this->getViewAccess(), $user);
    }

    public function canBeEditedBy(?User $user = null): bool
    {
        if (!$user || $this->isDeleted()) {
            return false;
        }

        $club = $this->getClub();
        if (!$club || $club->isBanned() || !$club->isPagesEnabled()) {
            return false;
        }

        return $this->checkAccessLevel($this->getEditAccess(), $user);
    }

    public static function renderMarkdown(string $source, Club $club): string
    {
        $pages = new GroupPages();
        $processed = preg_replace_callback(
            '/\[\[([^\]|#]+)(?:\|([^\]]+))?\]\]/u',
            function (array $matches) use ($pages, $club): string {
                $target = trim($matches[1]);
                $label  = isset($matches[2]) ? trim($matches[2]) : $target;
                $page   = $pages->getByTitle($club, $target);

                if ($page) {
                    return "[" . str_replace(["[", "]"], ["\\[", "\\]"], $label) . "](" . $page->getURL() . ")";
                }

                $createUrl = "/pages-" . $club->getId() . "/create?title=" . rawurlencode($target);
                return "[" . str_replace(["[", "]"], ["\\[", "\\]"], $label) . "](" . $createUrl . ")";
            },
            $source
        );

        $html = (new Parsedown())->text($processed ?? $source);

        $html = preg_replace_callback(
            '/<a href="(\/pages-\d+\/create\?title=[^"]+)">/',
            static function (array $m): string {
                return '<a class="wiki-missing" href="' . $m[1] . '">';
            },
            $html
        );

        $config = HTMLPurifier_Config::createDefault();
        $config->set("Attr.AllowedClasses", ["wiki-missing", "underline"]);
        $config->set("Attr.DefaultInvalidImageAlt", "Unknown image");
        $config->set("AutoFormat.AutoParagraph", false);
        $config->set("AutoFormat.Linkify", true);
        $config->set("URI.Base", "//$_SERVER[SERVER_NAME]/");
        $config->set("URI.Munge", "/away.php?xinf=%n.%m:%r&css=%p&to=%s");
        $config->set("URI.MakeAbsolute", true);
        $config->set("HTML.Doctype", "XHTML 1.1");
        $config->set("HTML.TidyLevel", "heavy");
        $config->set("HTML.AllowedElements", [
            "div", "h1", "h2", "h3", "h4", "h5", "h6", "p", "i", "b", "em", "strong",
            "a", "del", "ins", "sup", "sub", "table", "thead", "tbody", "tr", "td", "th",
            "img", "ul", "ol", "li", "hr", "br", "blockquote", "cite", "span", "code", "pre",
        ]);
        $config->set("HTML.AllowedAttributes", [
            "table.summary", "td.abbr", "th.abbr", "a.href", "a.class", "a.title",
            "img.src", "img.alt", "img.style", "div.style", "div.title", "span.class", "p.class",
            "td.align", "th.align", "p.align", "div.align",
        ]);
        $config->set("CSS.AllowedProperties", [
            "float", "height", "width", "max-height", "max-width", "font-weight", "text-align",
        ]);

        return (new HTMLPurifier($config))->purify($html);
    }

    public function getText(): string
    {
        $club = $this->getClub();
        if (is_null($this->getRecord())) {
            return self::renderMarkdown($this->changes["source"] ?? "", $club);
        }

        $cached = $this->getRecord()->cached_html;
        if (!$cached) {
            $cached = self::renderMarkdown($this->getSource(), $club);
            $this->changes["cached_html"] = $cached;
            parent::save(false);
        }

        return $cached;
    }

    private ?int $revisionEditorId = null;

    public function getSourceByteSize(): int
    {
        return strlen($this->getSource());
    }

    public function delete(bool $softly = true): void
    {
        if (!$softly) {
            parent::delete(false);
            return;
        }

        $this->setDeleted(1);
        $this->setIs_Main(0);
        parent::save(false);
    }

    public function save(?bool $log = false): void
    {
        $isNew = is_null($this->getRecord());
        $record = $this->getRecord();
        $groupId = $record ? (int) $record->group : (int) ($this->changes["group"] ?? 0);
        if (!$groupId) {
            throw new \LogicException("Can't persist page without group");
        }

        // Drop non-column keys before any persistence
        $editorId = $this->revisionEditorId;
        unset($this->changes["_revision_editor"]);
        $this->revisionEditorId = null;

        $titleChanged = isset($this->changes["title"])
            && (!$record || (string) $this->changes["title"] !== (string) $record->title);
        $sourceChanged = isset($this->changes["source"])
            && (!$record || (string) $this->changes["source"] !== (string) $record->source);

        // If values were set but identical, drop them so parent::save is a no-op
        if (isset($this->changes["title"]) && !$titleChanged && !$isNew) {
            unset($this->changes["title"]);
        }
        if (isset($this->changes["source"]) && !$sourceChanged && !$isNew) {
            unset($this->changes["source"]);
        }

        $contentChanged = $isNew || $titleChanged || $sourceChanged;

        if ($isNew) {
            if (!isset($this->changes["created"])) {
                $this->changes["created"] = time();
            }
            $count = sizeof(DatabaseConnection::i()->getContext()->table("group_pages")->where("group", $groupId));
            $this->changes["virtual_id"] = $count + 1;
            if (!isset($this->changes["view_access"])) {
                $this->changes["view_access"] = self::ACCESS_EVERYONE;
            }
            if (!isset($this->changes["edit_access"])) {
                $this->changes["edit_access"] = self::ACCESS_ADMINS;
            }
        } elseif ($contentChanged) {
            // Assign directly: stateChanges() probes ActiveRow and may fail on cached schema
            $this->changes["edited"] = time();
            $this->changes["cached_html"] = null;
        }

        if (!$isNew && !$contentChanged && empty($this->changes)) {
            return;
        }

        $revTitle = $contentChanged
            ? (string) ($this->changes["title"] ?? ($record->title ?? ""))
            : null;
        $revSource = $contentChanged
            ? (string) ($this->changes["source"] ?? ($record->source ?? ""))
            : null;

        parent::save($log);

        if ($contentChanged && $editorId !== null) {
            DatabaseConnection::i()->getContext()->table("group_page_revisions")->insert([
                "page"    => $this->getId(),
                "editor"  => (int) $editorId,
                "title"   => $revTitle,
                "source"  => $revSource,
                "created" => time(),
            ]);
        }
    }

    public function setRevisionEditor(int $userId): void
    {
        $this->revisionEditorId = $userId;
    }

    public function makeMain(): void
    {
        DatabaseConnection::i()->getContext()->table("group_pages")
            ->where([
                "group"   => $this->getGroupId(),
                "is_main" => 1,
                "deleted" => 0,
            ])
            ->update(["is_main" => 0]);

        $this->changes["is_main"] = 1;
        parent::save(false);
    }
}
