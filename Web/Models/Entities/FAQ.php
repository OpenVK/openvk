<?php

declare(strict_types=1);

namespace openvk\Web\Models\Entities;

use Parsedown;
use Symfony\Component\Yaml\Yaml;

class FAQ
{
    private $faqDir = OPENVK_ROOT . "/data/knowledgebase/faq";

    public function getIssues(string $lang, bool $admin, bool $showDisabled = false): array
    {
        $issues = chandler_parse_yaml($this->faqDir . "/known-issues.yml");
        
        foreach ($issues as $i => $issue) {
            if (!$issue["enabled"] && (!$admin || !$showDisabled)) {
                unset($issues[$i]);
                continue;
            }
            
            if (!$admin and $issue["for_admin"]) {
                unset($issues[$i]);
                continue;
            }

            $issues[$i]["title"]       = $issue["title"][$lang] ?? $issue["title"][getDefaultLanguage()];
            $issues[$i]["description"] = $issue["description"][$lang] ?? $issue["description"][getDefaultLanguage()];
            $issues[$i]["id"]          = $i;
        }

        return $issues;
    }

    public function getRawIssue(string $name): array
    {
        $issues = chandler_parse_yaml($this->faqDir . "/known-issues.yml");
        return [
            "title"       => $issues[$name]["title"],
            "description" => $issues[$name]["description"],
            "id"          => $name,
            "for_admin"   => $issues[$name]["for_admin"],
            "enabled"     => $issues[$name]["enabled"]
        ];
    }

    public function getTopicList(string $lang, bool $admin, bool $showDisabled = false): array
    {
        $topics = [];

        foreach (chandler_parse_yaml($this->faqDir . "/topics.yml") as $url => $topic) {
            if (!$topic["enabled"] && (!$admin || !$showDisabled)) {
                continue;
            }

            if (!$admin && $topic["for_admin"]) {
                continue;
            }
            
            $topics[$url] = [
                "url"        => $url,
                "title"      => $topic["title"][$lang] ?? $topic["title"][getDefaultLanguage()],
                "icon"       => $topic["icon"],
                "for_admin"  => $topic["for_admin"],
                "translated" => isset($topic["title"][$lang]),
                "enabled"    => $topic["enabled"],
            ];
        }

        return $topics;
    }

    public function getTopicPreviews(string $lang, bool $admin): array
    {
        $topics = [];

        foreach (chandler_parse_yaml($this->faqDir . "/topics.yml") as $name => $topic) {      
            if (!$topic["enabled"]) {
                continue;
            }    

            if (!$admin and $topic["for_admin"]) {
                continue;
            }

            if (file_exists("$this->faqDir/$name.$lang.md")) {
                $file = "$this->faqDir/$name.$lang.md";
            } elseif (file_exists($this->faqDir . "/" . $name . "." . getDefaultLanguage() . ".md")) {
                $file = $this->faqDir . "/" . $name . "." . getDefaultLanguage() . ".md";
            } else {
                continue;
            }

            $lines = file($file);
            $index = 1;
            $questions = [];

            foreach ($lines as $line) {
                if ($index === 4) {
                    break;
                }

                if (strpos($line, "# ") === 0) {
                    $questions[] = [
                        "title" => substr($line, 2),
                        "index" => $index
                    ];
                    ++$index;
                }
            }

            $topics[] = [
                "url"        => $name,
                "title"      => $topic["title"][$lang] ?? $topic["title"][getDefaultLanguage()],
                "icon"       => $topic["icon"],
                "for_admin"  => $topic["for_admin"],
                "translated" => file_exists("$this->faqDir/$name.$lang.md"),
                "questions"  => $questions
            ];
        }

        return $topics;
    }

    public function getTopic(string $name, string $lang, bool $admin): array
    {
        $topic = [];

        $topics = chandler_parse_yaml($this->faqDir . "/topics.yml");
        if (!isset($topics[$name])) {
            return $topic;
        }

        if (!$topics[$name]["enabled"]) {
            return $topic;
        } 

        if (!$admin and $topics[$name]["for_admin"]) {
            return $topic;
        }

        if (file_exists("$this->faqDir/$name.$lang.md")) {
            $file = "$this->faqDir/$name.$lang.md";
        } elseif (file_exists($this->faqDir . "/" . $name . "." . getDefaultLanguage() . ".md")) {
            $file = $this->faqDir . "/" . $name . "." . getDefaultLanguage() . ".md";
        } else {
            return $topic;
        }

        $content = [];
        $lines   = file($file, FILE_IGNORE_NEW_LINES);
        $id      = 1;

        foreach ($lines as $line) {
            if (strpos($line, "# ") === 0) {
                $content[] = [
                    'id' => $id++,
                    'q'  => substr($line, 2),
                    'a'  => '',
                ];
            } elseif (!empty($content)) {
                $last = count($content) - 1;
                $content[$last]['a'] .= $line . "\n";
            }
        }

        foreach ($content as &$item) {
            $item['a'] = (new Parsedown())->text(trim($item['a']));
        }

        $topic = [
            "title"     => $topics[$name]["title"][$lang] ?? $topics[$name]["title"][getDefaultLanguage()],
            "url"       => $name,
            "icon"      => $topics[$name]["icon"],
            "for_admin" => $topics[$name]["for_admin"],
            "content"   => $content
        ];

        return $topic;
    }

    public function getRawTopic(string $name): array
    {
        $topics = chandler_parse_yaml($this->faqDir . "/topics.yml");
        $questions = [];

        foreach ($topics[$name]["title"] as $lang => $s) {
            $lines = file("$this->faqDir/$name.$lang.md", FILE_IGNORE_NEW_LINES);
            foreach ($lines as $line) {
                if (strpos($line, "# ") === 0) {
                    $questions[$lang][] = [
                        'q'  => substr($line, 2),
                        'a'  => '',
                    ];
                }  elseif (!empty($questions[$lang])) {
                    $questions[$lang][count($questions[$lang]) - 1]['a'] .= $line . "\n";
                }
            }
            
        }    
        
        return [
            "title"     => $topics[$name]["title"],
            "url"       => $name,
            "icon"      => $topics[$name]["icon"],
            "for_admin" => $topics[$name]["for_admin"],
            "enabled"   => $topics[$name]["enabled"],
            "content"   => $questions
        ];
    }

    public function createTopic(array $topic, array $questions): void
    {
        foreach ($questions as $lang => $q) {
            $content = [];
            foreach ($q as $i) {
                $a = [];
                foreach (explode("\n", trim($i["a"])) as $s) {
                    if (trim($s) === "") {
                        $a[] = "<br>";
                        continue;
                    }
                    $a[] = $s;
                }
                $content[] = '# ' . trim($i["q"]) . "\n" . implode("\n", $a);
            }
            file_put_contents($this->faqDir . "/" .  array_key_first($topic) . ".{$lang}.md", implode("\n\n", $content));
        }
        $topics = chandler_parse_yaml($this->faqDir . "/topics.yml");
        $topics[array_key_first($topic)] = array_values($topic)[0];
        file_put_contents($this->faqDir . "/topics.yml", "\n" . preg_replace('/\n(?=\S)/', "\n\n", Yaml::dump($topics, 4, 4)));
    }

    public function disableTopic($name): int
    {
        $topics = chandler_parse_yaml($this->faqDir . "/topics.yml");
        $result = -1;
        if (isset($topics[$name])) {
            $topics[$name]["enabled"] = !$topics[$name]["enabled"];
            if (file_put_contents($this->faqDir . "/topics.yml", "\n" . preg_replace('/\n(?=\S)/', "\n\n", Yaml::dump($topics, 4, 4))) !== false) {
                $result = $topics[$name]["enabled"] ? 1 : 0;
            }
        }
        return $result;
    }

    public function deleteTopic($name): bool
    {
        $topics = chandler_parse_yaml($this->faqDir . "/topics.yml");
        if (isset($topics[$name])) {
            foreach ($topics[$name]["title"] as $lang => $t) {
                unlink($this->faqDir . "/" . $name . ".{$lang}.md");
            }
            unset($topics[$name]);
        } else {
            return false;
        }
        return file_put_contents($this->faqDir . "/topics.yml", "\n" . preg_replace('/\n(?=\S)/', "\n\n", Yaml::dump($topics, 4, 4))) !== false;
    }

    
}
