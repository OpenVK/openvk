<?php

declare(strict_types=1);

namespace openvk\Web\Presenters;

use openvk\Web\Themes\Themepacks;
use openvk\Web\Models\Repositories\{Users, Managers, Clubs, Posts};
use openvk\Web\Util\Localizator;
use Chandler\Session\Session;

final class AboutPresenter extends OpenVKPresenter
{
    protected $banTolerant = true;
    protected $activationTolerant = true;
    protected $deactivationTolerant = true;

    public function renderIndex(): void
    {
        if (!is_null($this->user)) {
            if ($this->user->identity->getMainPage()) {
                $this->redirect("/feed");
            } else {
                $this->redirect($this->user->identity->getURL());
            }
        }

        if ($_SERVER['REQUEST_URI'] == "/id0") {
            $this->redirect("/");
        }

        $this->template->stats = (new Users())->getStatistics();
    }

    public function renderRules(): void
    {
        $this->pass("openvk!Support->knowledgeBaseArticle", "rules");
    }

    public function renderHelp(): void {}

    public function renderBB(): void {}

    public function renderTour(): void {}

    public function renderInvite(): void
    {
        $this->assertUserLoggedIn();
    }

    public function renderDonate(): void
    {
        $this->pass("openvk!Support->knowledgeBaseArticle", "donate");
    }

    public function renderPrivacy(): void
    {
        $this->pass("openvk!Support->knowledgeBaseArticle", "privacy");
    }

    public function renderVersion(): void
    {
        $this->template->themes = Themepacks::i()->getAllThemes();
        $this->template->languages = getLanguages();
    }

    public function renderAboutInstance(): void
    {
        $this->template->usersStats   = (new Users())->getStatistics();
        $this->template->clubsCount   = (new Clubs())->getCount();
        $this->template->postsCount   = (new Posts())->getCount();
        $this->template->popularClubs = [];
        $this->template->admins       = iterator_to_array((new Users())->getInstanceAdmins());
    }

    public function renderLanguage(): void
    {
        $this->template->languages = getLanguages();

        if (!is_null($_GET['lg'])) {
            $this->assertNoCSRF();
            setLanguage($_GET['lg']);
        }

        if (!is_null($_GET['jReturnTo'])) {
            $this->redirect(rawurldecode($_GET['jReturnTo']));
        }
    }

    public function renderExportJSLanguage($lg = null): void
    {
        $localizer = Localizator::i();
        $lang      = $lg;
        if (is_null($lg)) {
            $this->throwError(404, "Not found", "Language is not found");
        }
        header("Content-Type: application/javascript");
        echo "window.lang = " . json_encode($localizer->export($lang)) . ";"; # привет хардкод :DDD
        exit;
    }

    public function renderSandbox(): void
    {
        $this->template->languages = getLanguages();
    }

    public function renderRobotsTxt(): void
    {
        $text = "# robots.txt file for openvk\n"
        . "#\n"
        . "# this includes only those links that are not in any way\n"
        . "# covered from unauthorized persons (for example, due to\n"
        . "# lack of rights to access the admin panel)\n\n"
        . "User-Agent: *\n"
        . "Disallow: /albums/create\n"
        . "Disallow: /assets/packages/static/openvk/img/banned.jpg\n"
        . "Disallow: /assets/packages/static/openvk/img/camera_200.png\n"
        . "Disallow: /assets/packages/static/openvk/img/flags/\n"
        . "Disallow: /assets/packages/static/openvk/img/oof.apng\n"
        . "Disallow: /videos/upload\n"
        . "Disallow: /invite\n"
        . "Disallow: /groups_create\n"
        . "Disallow: /notifications\n"
        . "Disallow: /settings\n"
        . "Disallow: /edit\n"
        . "Disallow: /gifts\n"
        . "Disallow: /support\n"
        . "Disallow: /rpc\n"
        . "Disallow: /language\n"
        . "Disallow: /badbrowser.php\n"
        . "Disallow: /logout\n"
        . "Disallow: /away.php\n"
        . "Disallow: /im?\n"
        . "Disallow: *query=\n"
        . "Disallow: *?lg=\n"
        . "Disallow: *hash=\n"
        . "Disallow: *?jReturnTo=\n"
        . "Disallow: /method/*\n"
        . "Disallow: /token*";
        header("Content-Type: text/plain");
        exit($text);
    }

    public function renderHumansTxt(): void
    {
        # :D
        $this->redirect("https://github.com/openvk/openvk#readme");
    }

    public function renderAssetLinksJSON(): void
    {
        # Необходимо любому андроид приложению для автоматического разрешения принимать ссылки с этого сайта.
        # Не шарю как писать норм на php поэтому тут чутка на вайбкодил - искренне ваш, ZAZiOs.
        header("Content-Type: application/json");

        $data = [
            [
                "relation" => ["delegate_permission/common.handle_all_urls"],
                "target" => [
                    "namespace" => "android_app",
                    "package_name" => "oss.OpenVK.Native",
                    "sha256_cert_fingerprints" => [
                        "79:67:14:23:DC:6E:FA:49:64:1F:F1:81:0E:B0:A3:AE:6E:88:AB:0D:CF:BC:02:96:F3:6D:76:6B:82:94:D6:9C",
                    ],
                ],
            ],
        ];

        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit;
    }

    public function renderDev(): void
    {
        $this->redirect("https://docs.ovk.to/");
    }
}
