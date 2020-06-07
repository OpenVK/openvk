<?php declare(strict_types=1);
namespace openvk\Web\Util;

class Bitmask
{
    protected $data;
    protected $length;
    protected $mapping;
    
    function __construct(int $data, int $length = 1, array $mapping = [])
    {
        $this->data    = str_pad(decbin($data), 63, "0", STR_PAD_RIGHT);
        $this->length  = $length;
        if((sizeof($mapping) - 1) > (64 / $length))
            throw new \OutOfRangeException("Mapping contains more keys than a bitmask can fit in itself.");
        else
            $this->mapping = $mapping;
    }
    
    private function getOffsetByKey(string $key): int
    {
        $offset = array_search($key, $this->mapping);
        if($offset === false)
            throw new \OutOfBoundsException("Key '$key' is not present in bitmask.");
        
        return $offset;
    }
    
    function toInteger(): int
    {
        return (int) bindec($this->data);
    }
    
    function __toString(): string
    {
        return (string) $this->toInteger();
    }
    
    function getNumberByOffset(int $offset): float
    {
        $offset *= $this->length;
        if($offset > (64 / $this->length))
            return (float) 'NaN';
        
        return (float) bindec(substr($this->data, $offset, $this->length));
    }
    
    function getBoolByOffset(int $offset): ?bool
    {
        if($this->length !== 1)
            return NULL;
        
        $number = $this->getNumberByOffset($offset);
        return is_nan($number) ? NULL : (bool) $number;
    }
    
    function setByOffset(int $offset, int $number): void
    {
        $offset *= $this->length;
        if(($offset + $this->length) > 64)
            throw new \OutOfRangeException("$offset is invalid offset. Bitmask length is 64 bits.");
        
        $this->data = substr_replace($this->data, str_pad(decbin($number), $this->length, "0", STR_PAD_LEFT), $offset, $this->length);
    }
    
    function set($key, int $data): Bitmask
    {
        if(gettype($key) === "string")
            $this->setByOffset($this->getOffsetByKey($key), $data);
        else if(gettype($key) === "int")
            $this->setByOffset($key, $data);
        else
            throw new TypeError("Key must be either offset (int) or a string index");
        
        return $this;
    }
    
    function get($key)
    {
        if(gettype($key) === "string")
            $key = $this->getOffsetByKey($key);
        else if(gettype($key) !== "int")
            throw new TypeError("Key must be either offset (int) or a string index");
        
        return $this->length === 1 ? $this->getBoolByOffset($key) : $this->getNumberByOffset($key);
    }
}
