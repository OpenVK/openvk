<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Models\Entities\{User, Club};
use openvk\Web\Models\Search\{InvalidSectionError, SearchState};
use Chandler\Database\DatabaseConnection;

final class SearchPresenter extends OpenVKPresenter
{
    public function renderIndex(): void
    {
        $this->assertUserLoggedIn();

        $query     = $this->queryParam("q") ?? "";
        $section   = $this->queryParam("section") ?? "users";
        $order     = $this->queryParam("order") ?? "id";
        $invert    = (int) ($this->queryParam("invert") ?? 0) == 1;
        $page      = (int) ($this->queryParam("p") ?? 1);

        $state = new SearchState($this->user->identity, $query, $section, $order, $invert);
        $state->buildParamsFromRequest($_REQUEST);
        $data = null;

        try {
            $data = $state->execute($page, OPENVK_DEFAULT_PER_PAGE);
        } catch (InvalidSectionError $e) {
            $this->throwError(400, "Bad Request", "Invalid search entity $section.");
        }

        # https://youtu.be/pSAWM5YuXx8

        $this->template->order    = $order;
        $this->template->invert   = $invert;
        $this->template->data     = $data[0];
        $this->template->count    = $data[1];
        $this->template->section  = $section;
        $this->template->page     = $page;
        $this->template->perPage  = OPENVK_DEFAULT_PER_PAGE;
        $this->template->query    = $query;
        $this->template->atSearch = true;
        $this->template->ref = $this->queryParam("ref");

        $this->template->paginatorConf = (object) [
            "page"      => $page,
            "count"     => $data[1],
            "amount"    => $data[2],
            "perPage"   => $this->template->perPage,
            "atTop"     => false,
            "atBottom"  => false,
            "tidy"      => true,
            "space"     => 6,
            'pageCount' => ceil($count / $this->template->perPage),
        ];
        $this->template->extendedPaginatorConf = clone $this->template->paginatorConf;
        $this->template->extendedPaginatorConf->space = 11;
        $this->template->paginatorConf->atTop = true;
    }
}
