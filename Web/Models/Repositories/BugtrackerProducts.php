<?php declare(strict_types=1);
namespace openvk\Web\Models\Repositories;
use openvk\Web\Models\Entities\{BugtrackerProduct};
use Nette\Database\Table\ActiveRow;
use Chandler\Database\DatabaseConnection;

class BugtrackerProducts
{
    private $context;
    private $products;
    
    function __construct()
    {
        $this->context = DatabaseConnection::i()->getContext();
        $this->products   = $this->context->table("bt_products");
    }

    private function toProduct(?ActiveRow $ar)
    {
        return is_null($ar) ? NULL : new BugtrackerProduct($ar);
    }

    function get(int $id): ?BugtrackerProduct
    {
        return $this->toProduct($this->products->get($id));
    }

    function getAll(int $page = 1): \Traversable
    {
        foreach($this->products->order("id ASC")->page($page, 5) as $product)
            yield new BugtrackerProduct($product);
    }

    function getOpen(int $page = 1): \Traversable
    {
        foreach($this->products->where(["closed" => 0])->order("id ASC")->page($page, 5) as $product)
            yield new BugtrackerProduct($product);
    }

    function getCount(): ?int
    {
        return sizeof($this->products->where(["closed" => 0]));
    }
}