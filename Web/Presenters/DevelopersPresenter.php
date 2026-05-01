<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use Parsedown;
use Chandler\Session\Session;

final class DevelopersPresenter extends OpenVKPresenter
{
    protected $banTolerant = true;
    protected $activationTolerant = true;
    protected $deactivationTolerant = true;
    protected $presenterName = "dev";

    public function renderIndex(): void {}

    public function renderDevelopersArticle(string $name): void
    {
        $name = ltrim($name, '/');

        if (empty($name) || $name == "elopers") {
            $this->redirect("/dev");
        }

        $lang = Session::i()->get("lang", "ru");
        $base = OPENVK_ROOT . "/data/knowledgebase/dev";
        if (file_exists("$base/$name.$lang.md")) {
            $file = "$base/$name.$lang.md";
        } elseif (file_exists("$base/$name.md")) {
            $file = "$base/$name.md";
        } else {
            $this->notFound();
        }

        $lines = file($file);
        if (!preg_match("%^OpenVK-KB-Heading: (.+)$%", $lines[0], $matches)) {
            $heading = "Article $name";
        } else {
            $heading = $matches[1];
            array_shift($lines);
        }

        $content = implode($lines);

        $parser = new Parsedown();
        $this->template->heading = $heading;
        $this->template->content = $parser->text($content);

        $this->template->devMenu = [
            ["name" => "dsb_main",      "href" => "main",            "type" => "menu"],
            ["name" => "dsb_methods",   "href" => "methods",         "type" => "menu"],
            ["name" => "dsb_m_account", "href" => "methods/account", "type" => "submenu"],
            ["name" => "dsb_m_audio",   "href" => "methods/audio",   "type" => "submenu"],
            ["name" => "dsb_models",    "href" => "models",          "type" => "menu"],
        ];
        $this->template->currentSection = $name;
    }
}
