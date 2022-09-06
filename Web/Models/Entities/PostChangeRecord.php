<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;
use openvk\Web\Models\RowModel;
use openvk\Web\Util\DateTime;
use openvk\Web\Models\Entities\{User, Club, Post};
use openvk\Web\Models\Repositories\{Users, Clubs, Posts};
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class PostChangeRecord extends RowModel
{
    protected $tableName = "posts_changes";

    function getId(): int
    {
        return $this->getRecord()->id;
    }

    function getWid(): int
    {
        return $this->getRecord()->wall_id;
    }

    function getAuthorType(): string
    {
        return $this->getWid() < 0 ? "club" : "user";
    }

    function getVid(): int
    {
        return $this->getRecord()->virtual_id;
    }

    function getPost(): ?Post
    {
        return (new Posts)->getPostById($this->getWid(), $this->getVid());
    }

    function getAuthor()
    {
        if ($this->getAuthorType() === "club")
            return (new Clubs)->get($this->getWid());

        return (new Users)->get($this->getWid());
    }

    function getNewContent(): ?string
    {
        return $this->getRecord()->newContent;
    }

    function getCreationDate(): DateTime
    {
        return new DateTime($this->getRecord()->created);
    }

    function canBeApplied(): bool
    {
        return $this->getPost()->getChangeId() != $this->getId();
    }
}
