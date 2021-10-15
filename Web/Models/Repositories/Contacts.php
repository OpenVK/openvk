<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\Contact;
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class Contacts
{
    private $context;
    private $contacts;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->contacts   = $this->context->table("group_contacts");
    }

    function get(int $id): ?Contact
    {
        $ar = $this->clubs->get($id);
        return is_null($ar) ? NULL : new Contact($ar);
    }

    function getByClub(int $id): \Traversable
    {
        $contacts = $this->contacts->where("group", $id)->where("deleted", 0);
        return new Util\EntityStream("Contact", $contacts);
    }

    function getByClubAndUser(int $club, int $user): ?Contact
    {
        $contact = $this->contacts->where("group", $club)->where("user", $user)->fetch();
        return $this->get($contact);
    }
}