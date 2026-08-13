<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use HTMLPurifier_Config;
use HTMLPurifier;
use HTMLPurifier_Filter;
use Parsedown;
use Chandler\Database\DatabaseConnection;
use openvk\Web\Models\RowModel;
use openvk\Web\Models\Repositories\{Users, Clubs, Notes};

class SecurityFilter extends HTMLPurifier_Filter
{
    public function preFilter($html, $config, $context)
    {
        $html = preg_replace_callback(
            '/<img[^>]*src\s*=\s*["\']([^"\']*)["\'][^>]*>/i',
            function ($matches) {
                $originalSrc = $matches[1];
                $src = $originalSrc;

                if (OPENVK_ROOT_CONF["openvk"]["preferences"]["notes"]["disableHotlinking"] ?? true) {
                    if (!str_contains($src, "/image.php?url=")) {
                        $src = '/image.php?url=' . base64_encode($originalSrc);
                    }
                }

                return str_replace($originalSrc, $src, $matches[0]);
            },
            $html
        );

        return $html;
    }
}

class Note extends Postable
{
    protected $tableName = "notes";

    public const FORMAT_HTML     = 0;
    public const FORMAT_MARKDOWN = 1;

    public const ACCESS_EVERYONE = 0;
    public const ACCESS_MEMBERS  = 1;
    public const ACCESS_ADMINS   = 2;

    private ?int $revisionEditorId = null;

    public function getOwnerId(): int
    {
        if ($this->getRecord()) {
            return (int) $this->getRecord()->owner;
        }

        return (int) ($this->changes["owner"] ?? 0);
    }

    public function getOwner(bool $real = false): RowModel
    {
        $oid = $this->getOwnerId();
        if ($oid < 0) {
            return (new Clubs())->get(abs($oid));
        }

        return (new Users())->get(abs($oid));
    }

    public function isClubNote(): bool
    {
        return $this->getOwnerId() < 0;
    }

    public function getClub(): ?Club
    {
        return $this->isClubNote() ? (new Clubs())->get(abs($this->getOwnerId())) : null;
    }

    public function getCreatedBy(): ?User
    {
        $record = $this->getRecord();
        $createdBy = $record ? ($record->created_by ?? null) : ($this->changes["created_by"] ?? null);
        if ($createdBy) {
            return (new Users())->get((int) $createdBy);
        }

        if (!$this->isClubNote()) {
            return (new Users())->get(abs($this->getOwnerId()));
        }

        return null;
    }

    public function getFormat(): int
    {
        $record = $this->getRecord();
        if ($record && isset($record->format)) {
            return (int) $record->format;
        }

        return (int) ($this->changes["format"] ?? ($this->isClubNote() ? self::FORMAT_MARKDOWN : self::FORMAT_HTML));
    }

    public function isMarkdown(): bool
    {
        return $this->getFormat() === self::FORMAT_MARKDOWN;
    }

    public function isMain(): bool
    {
        $record = $this->getRecord();
        if ($record && isset($record->is_main)) {
            return (bool) $record->is_main;
        }

        return (bool) ($this->changes["is_main"] ?? false);
    }

    public function getViewAccess(): int
    {
        $record = $this->getRecord();
        if ($record && isset($record->view_access)) {
            return (int) $record->view_access;
        }

        return (int) ($this->changes["view_access"] ?? self::ACCESS_EVERYONE);
    }

    public function getEditAccess(): int
    {
        $record = $this->getRecord();
        if ($record && isset($record->edit_access)) {
            return (int) $record->edit_access;
        }

        return (int) ($this->changes["edit_access"] ?? self::ACCESS_ADMINS);
    }

    public function keepsRevisions(): bool
    {
        $record = $this->getRecord();
        if ($record && isset($record->keep_revisions)) {
            return (bool) $record->keep_revisions;
        }

        return (bool) ($this->changes["keep_revisions"] ?? false);
    }

    public function getName(): string
    {
        if ($this->getRecord()) {
            return (string) $this->getRecord()->name;
        }

        return (string) ($this->changes["name"] ?? "");
    }

    /** Alias used by wiki-style templates. */
    public function getTitle(): string
    {
        return $this->getName();
    }

    public function getEditor(): ?User
    {
        $revision = DatabaseConnection::i()->getContext()->table("note_revisions")
            ->where("note", $this->getId())
            ->order("created DESC")
            ->limit(1)
            ->fetch();

        if (!$revision) {
            return $this->getCreatedBy();
        }

        return (new Users())->get((int) $revision->editor);
    }

    public function getPreview(int $length = 25): string
    {
        return ovk_proc_strtr(strip_tags($this->getSource()), $length);
    }

    public function getSource(): string
    {
        if ($this->getRecord()) {
            return (string) $this->getRecord()->source;
        }

        return (string) ($this->changes["source"] ?? "");
    }

    public function getURL(): string
    {
        if ($this->isClubNote()) {
            return "/note-" . abs($this->getOwnerId()) . "_" . $this->getVirtualId();
        }

        return "/note" . $this->getOwnerId() . "_" . $this->getVirtualId();
    }

    public function getListURL(): string
    {
        if ($this->isClubNote()) {
            return "/notes-" . abs($this->getOwnerId());
        }

        return "/notes" . $this->getOwnerId();
    }

    protected function renderHTML(?string $content = null): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set("Attr.AllowedClasses", []);
        $config->set("Attr.DefaultInvalidImageAlt", "Unknown image");
        $config->set("AutoFormat.AutoParagraph", true);
        $config->set("AutoFormat.Linkify", true);
        $config->set("URI.Base", "//$_SERVER[SERVER_NAME]/");
        $config->set("URI.Munge", "/away.php?xinf=%n.%m:%r&css=%p&to=%s");
        $config->set("URI.MakeAbsolute", true);
        $config->set("HTML.Doctype", "XHTML 1.1");
        $config->set("HTML.TidyLevel", "heavy");
        $config->set("HTML.AllowedElements", [
            "div", "h3", "h4", "h5", "h6", "p", "i", "b", "a", "del", "ins", "sup", "sub",
            "table", "thead", "tbody", "tr", "td", "th", "img", "ul", "ol", "li", "hr", "br",
            "acronym", "blockquote", "cite", "span",
        ]);
        $config->set("HTML.AllowedAttributes", [
            "table.summary", "td.abbr", "th.abbr", "a.href", "img.src", "img.alt", "img.style",
            "div.style", "div.title", "div.align", "span.class", "p.class", "p.align",
        ]);
        $config->set("CSS.AllowedProperties", [
            "float", "height", "width", "max-height", "max-width", "font-weight", "text-align",
        ]);
        $config->set("Attr.AllowedClasses", ["underline"]);
        $config->set("Filter.Custom", [new SecurityFilter()]);

        $source = $content;
        if (!$source) {
            $source = $this->getSource();
            if ($source === "" && is_null($this->getRecord()) && !isset($this->changes["source"])) {
                throw new \LogicException("Can't render note without content set.");
            }
        }

        return (new HTMLPurifier($config))->purify($source);
    }

    public static function renderMarkdown(string $source, ?Club $club = null, ?User $userOwner = null): string
    {
        $notes = new Notes();
        $ownerId = $club ? -$club->getId() : ($userOwner ? $userOwner->getId() : 0);
        $createBase = $club
            ? "/notes-" . $club->getId() . "/create"
            : "/notes/create";

        $processed = preg_replace_callback(
            '/\[\[([^\]|#]+)(?:\|([^\]]+))?\]\]/u',
            function (array $matches) use ($notes, $ownerId, $club, $createBase): string {
                $target = trim($matches[1]);
                $label  = isset($matches[2]) ? trim($matches[2]) : $target;
                $note   = $ownerId !== 0 ? $notes->getByTitle($ownerId, $target) : null;

                if ($note) {
                    return "[" . str_replace(["[", "]"], ["\\[", "\\]"], $label) . "](" . $note->getURL() . ")";
                }

                $createUrl = $createBase . "?title=" . rawurlencode($target);
                return "[" . str_replace(["[", "]"], ["\\[", "\\]"], $label) . "](" . $createUrl . ")";
            },
            $source
        );

        $html = (new Parsedown())->text($processed ?? $source);

        $html = preg_replace_callback(
            '/<(t[dh])(\s[^>]*)?\sstyle="text-align:\s*(left|center|right);?"([^>]*)>/i',
            static function (array $m): string {
                $rest = ($m[2] ?? "") . ($m[4] ?? "");
                $rest = preg_replace('/\sstyle="[^"]*"/i', "", $rest) ?? $rest;
                return "<{$m[1]} align=\"{$m[3]}\"{$rest}>";
            },
            $html
        ) ?? $html;

        $html = preg_replace_callback(
            '/<a href="(\/notes(?:-\d+)?\/create\?title=[^"]+)">/',
            static function (array $m): string {
                return '<a class="wiki-missing" href="' . $m[1] . '">';
            },
            $html
        );

        $config = HTMLPurifier_Config::createDefault();
        $config->set("Attr.AllowedClasses", ["wiki-missing", "underline", "wiki_md_table"]);
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
            "table.summary", "table.class", "td.abbr", "th.abbr", "a.href", "a.class", "a.title",
            "img.src", "img.alt", "img.style", "div.style", "div.title", "span.class", "p.class",
            "td.align", "th.align", "td.style", "th.style", "p.align", "div.align",
        ]);
        $config->set("CSS.AllowedProperties", [
            "float", "height", "width", "max-height", "max-width", "font-weight", "text-align",
        ]);
        $config->set("Filter.Custom", [new SecurityFilter()]);

        $html = (new HTMLPurifier($config))->purify($html);
        return preg_replace('/<table\b(?![^>]*\bclass=)/i', '<table class="wiki_md_table"', $html) ?? $html;
    }

    public function getText(): string
    {
        if ($this->isMarkdown()) {
            if (is_null($this->getRecord())) {
                return self::renderMarkdown(
                    $this->getSource(),
                    $this->getClub(),
                    $this->isClubNote() ? null : ($this->getCreatedBy() ?? null)
                );
            }

            $cached = $this->getRecord()->cached_content;
            if (!$cached) {
                $cached = self::renderMarkdown(
                    $this->getSource(),
                    $this->getClub(),
                    $this->isClubNote() ? null : (new Users())->get(abs($this->getOwnerId()))
                );
                $this->changes["cached_content"] = $cached;
                parent::save(false);
            }

            return $cached;
        }

        if (is_null($this->getRecord())) {
            return $this->renderHTML();
        }

        $cached = $this->getRecord()->cached_content;
        if (!$cached) {
            $cached = $this->renderHTML();
            $this->setCached_Content($cached);
            parent::save(false);
        }

        return $this->renderHTML($cached);
    }

    private function checkAccessLevel(int $level, ?User $user): bool
    {
        if ($level === self::ACCESS_EVERYONE) {
            return true;
        }

        if (!$user) {
            return false;
        }

        $club = $this->getClub();
        if (!$club) {
            return false;
        }

        if ($level === self::ACCESS_ADMINS) {
            return $club->canBeModifiedBy($user);
        }

        // members
        return $club->getSubscriptionStatus($user)
            || $club->canBeModifiedBy($user);
    }

    public function canBeViewedBy(?User $user = null): bool
    {
        if ($this->isDeleted()) {
            return false;
        }

        if ($this->isClubNote()) {
            $club = $this->getClub();
            if (!$club || $club->isBanned() || !$club->isPagesEnabled()) {
                return false;
            }

            return $this->checkAccessLevel($this->getViewAccess(), $user);
        }

        $owner = $this->getOwner();
        if (!($owner instanceof User) || $owner->isDeleted()) {
            return false;
        }

        return $owner->getPrivacyPermission("notes.read", $user) && $owner->canBeViewedBy($user);
    }

    public function canBeEditedBy(?User $user = null): bool
    {
        if (!$user || $this->isDeleted()) {
            return false;
        }

        if ($this->isClubNote()) {
            $club = $this->getClub();
            if (!$club || $club->isBanned() || !$club->isPagesEnabled()) {
                return false;
            }

            return $this->checkAccessLevel($this->getEditAccess(), $user);
        }

        return $this->canBeModifiedBy($user);
    }

    public function setRevisionEditor(int $userId): void
    {
        $this->revisionEditorId = $userId;
    }

    public function makeMain(): void
    {
        if (!$this->isClubNote()) {
            return;
        }

        DatabaseConnection::i()->getContext()->table("notes")
            ->where([
                "owner"   => $this->getOwnerId(),
                "is_main" => 1,
                "deleted" => 0,
            ])
            ->update(["is_main" => 0]);

        $this->changes["is_main"] = 1;
        parent::save(false);
    }

    public function delete(bool $softly = true): void
    {
        if (!$softly) {
            parent::delete(false);
            return;
        }

        $this->setDeleted(1);
        if ($this->isClubNote()) {
            $this->changes["is_main"] = 0;
        }
        parent::save(false);
    }

    public function save(?bool $log = false): void
    {
        $isNew = is_null($this->getRecord());
        $record = $this->getRecord();

        $editorId = $this->revisionEditorId;
        unset($this->changes["_revision_editor"]);
        $this->revisionEditorId = null;

        $nameChanged = isset($this->changes["name"])
            && (!$record || (string) $this->changes["name"] !== (string) $record->name);
        $sourceChanged = isset($this->changes["source"])
            && (!$record || (string) $this->changes["source"] !== (string) $record->source);

        if (isset($this->changes["name"]) && !$nameChanged && !$isNew) {
            unset($this->changes["name"]);
        }
        if (isset($this->changes["source"]) && !$sourceChanged && !$isNew) {
            unset($this->changes["source"]);
        }

        $contentChanged = $isNew || $nameChanged || $sourceChanged;

        if ($isNew) {
            if (!isset($this->changes["format"])) {
                $this->changes["format"] = $this->isClubNote() ? self::FORMAT_MARKDOWN : self::FORMAT_HTML;
            }
            if ($this->isClubNote()) {
                if (!isset($this->changes["view_access"])) {
                    $this->changes["view_access"] = self::ACCESS_EVERYONE;
                }
                if (!isset($this->changes["edit_access"])) {
                    $this->changes["edit_access"] = self::ACCESS_ADMINS;
                }
            }
        } elseif ($contentChanged) {
            $this->changes["edited"] = time();
            $this->changes["cached_content"] = null;
        }

        $revTitle = $contentChanged
            ? (string) ($this->changes["name"] ?? ($record->name ?? ""))
            : null;
        $revSource = $contentChanged
            ? (string) ($this->changes["source"] ?? ($record->source ?? ""))
            : null;

        $keepRevisions = $this->keepsRevisions();
        if (isset($this->changes["keep_revisions"])) {
            $keepRevisions = (bool) $this->changes["keep_revisions"];
        }

        parent::save($log);

        if ($contentChanged && $editorId !== null && $keepRevisions) {
            DatabaseConnection::i()->getContext()->table("note_revisions")->insert([
                "note"    => $this->getId(),
                "editor"  => (int) $editorId,
                "title"   => $revTitle,
                "source"  => $revSource,
                "created" => time(),
            ]);
        }
    }

    public function toVkApiStruct(): object
    {
        $res = (object) [];

        $res->id       = $this->getVirtualId();
        $res->owner_id = $this->getOwnerId();
        $res->title    = $this->getName();
        $res->text     = $this->getText();
        $res->date     = $this->getPublicationTime()->timestamp();
        $res->comments = $this->getCommentsCount();
        $res->view_url = $this->getURL();

        return $res;
    }
}
