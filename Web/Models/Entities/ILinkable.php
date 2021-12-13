<?php declare(strict_types=1);
namespace openvk\Web\Models\Entities;

interface ILinkable {
    public function getOVKLink(): string;
}
