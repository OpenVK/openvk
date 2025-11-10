<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use Chandler\Session\Session;
use Parsedown;

final class DevelopersPresenter extends OpenVKPresenter
{
    public function renderMain(): void
    {
        $this->template->_template = "Developers/Main.xml";
        $this->template->responseTime = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2);
    }

    public function renderStandalone(): void
    {
        $this->template->_template = "Developers/Standalone.xml";
    }

    public function noPage(): void
    {
        $this->template->_template = "Developers/NoPage.xml";
    }

    public function parseMarkdown(string $path)
    {
        if (!file_exists("$path")) {
            $this->noPage();
			return [];
        }
		$dataArray = array();

        $lines = file($path);
        if (!preg_match("%^OpenVK-KB-Heading: (.+)$%", $lines[0], $matches)) {
            $heading = "Article $name";
        } else {
            $heading = $matches[1];
            array_shift($lines);
        }

        $content = implode($lines);

        $parser = new Parsedown();

		$dataArray['heading'] = $heading;
		$dataArray['content'] = $parser->text($content);
		return $dataArray;
    }

    public function renderDevelopersBaseArticle(string $name): void
    {
        $lang = Session::i()->get("lang", "ru");
        $base = OPENVK_ROOT . "/data/developers";
        if (file_exists("$base/$name.$lang.md")) {
            $file = "$base/$name.$lang.md";
        } elseif (file_exists("$base/$name.md")) {
            $file = "$base/$name.md";
        } else {
            $this->noPage();
			return;
        }

		$parsedMd = $this->parseMarkdown($file);

        $this->template->articlename = $name;
        $this->template->heading = $parsedMd['heading'];
        $this->template->content = $parsedMd['content'];
    }
}