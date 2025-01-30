<?php

declare(strict_types=1);

namespace openvk\Web\Util;

class Bitmask
{
    protected $data;
    protected $length;
    protected $mapping;

    public function __construct(int $data, int $length = 1, array $mapping = [])
    {
        $this->data    = str_pad(decbin($data), 63, "0", STR_PAD_RIGHT);
        $this->length  = $length;
        if ((sizeof($mapping) - 1) > (64 / $length)) {
            throw new \OutOfRangeException("Mapping contains more keys than a bitmask can fit in itself.");
        } else {
            $this->mapping = $mapping;
        }
    }

    private function getOffsetByKey(string $key): int
    {
        $offset = array_search($key, $this->mapping);
        if ($offset === false) {
            throw new \OutOfBoundsException("Key '$key' is not present in bitmask.");
        }

        return $offset;
    }

    public function toInteger(): int
    {
        return (int) bindec($this->data);
    }

    public function __toString(): string
    {
        return (string) $this->toInteger();
    }

    public function getNumberByOffset(int $offset): float
    {
        $offset *= $this->length;
        if ($offset > (64 / $this->length)) {
            return (float) 'NaN';
        }

        return (float) bindec(substr($this->data, $offset, $this->length));
    }

    public function getBoolByOffset(int $offset): ?bool
    {
        if ($this->length !== 1) {
            return null;
        }

        $number = $this->getNumberByOffset($offset);
        return is_nan($number) ? null : (bool) $number;
    }

    public function setByOffset(int $offset, int $number): void
    {
        $offset *= $this->length;
        if (($offset + $this->length) > 64) {
            throw new \OutOfRangeException("$offset is invalid offset. Bitmask length is 64 bits.");
        }

        $this->data = substr_replace($this->data, str_pad(decbin($number), $this->length, "0", STR_PAD_LEFT), $offset, $this->length);
    }

    public function set($key, int $data): Bitmask
    {
        if (gettype($key) === "string") {
            $this->setByOffset($this->getOffsetByKey($key), $data);
        } elseif (gettype($key) === "int") {
            $this->setByOffset($key, $data);
        } else {
            throw new TypeError("Key must be either offset (int) or a string index");
        }

        return $this;
    }

    public function get($key)
    {
        if (gettype($key) === "string") {
            $key = $this->getOffsetByKey($key);
        } elseif (gettype($key) !== "int") {
            throw new TypeError("Key must be either offset (int) or a string index");
        }

        return $this->length === 1 ? $this->getBoolByOffset($key) : $this->getNumberByOffset($key);
    }
}
