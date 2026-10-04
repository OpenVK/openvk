<?php

declare(strict_types=1);

namespace openvk\VKAPI\Handlers;

use openvk\Web\Models\Entities\Messages\StickerPack;
use openvk\Web\Models\Entities\Messages\Sticker;
use openvk\Web\Models\Repositories\Stickers as StickersRepo;
use openvk\Web\Models\Repositories\Users as UsersRepo;

final class Store extends VKAPIRequestHandler
{
    // Thanks gemini xd
    private static array $emojiKeywords = [
        "👋" => ["привет", "хай", "здравствуй", "здравствуйте", "ку", "салют", "йоу", "hello", "hi", "hey", "пока", "до свидания", "бай", "bye", "прощай"],
        "🤝" => ["привет", "договорились", "согласен", "по рукам", "друг", "уважение", "deal"],
        "🙋" => ["я", "привет", "здесь", "тут", "вопрос", "можно"],

        "😀" => ["улыбка", "смайл", "радость", "весело", "позитив", "smile", "happy"],
        "😃" => ["улыбка", "радость", "ура", "класс", "счастье"],
        "😄" => ["хаха", "смех", "смешно", "хех", "haha", "lol"],
        "😁" => ["ыыы", "улыбка", "доволен", "хихи", "ура"],
        "😆" => ["лол", "ржу", "смех", "хаха", "ахаха", "lol", "rofl"],
        "😅" => ["хех", "пот", "бывает", "упс", "неловко", "фух"],
        "😂" => ["слезы от смеха", "ржунимагу", "смешно", "хахаха", "ахахах", "lol", "lmao", "rofl"],
        "🤣" => ["пацталом", "ор", "ору", "ор выше гор", "rofl", "lol"],
        "😊" => ["мило", "улыбаюсь", "спасибо", "приятно", "милота", "cute", "blush"],
        "😇" => ["ангел", "святой", "я хороший", "невинен", "паинька", "angel"],
        "😉" => ["подмигивание", "миг", "намек", "секрет", "wink"],
        "😋" => ["вкусно", "ням", "аппетитно", "нямням", "tasty", "yummy"],
        "😛" => ["бебе", "язык", "дразню", "шалость"],
        "😜" => ["подмигнул", "дразнюсь", "дурачусь", "прикол"],
        "🤪" => ["безумие", "сумасшедший", "крейзи", "угараю", "crazy"],

        "❤️" => ["люблю", "любовь", "сердце", "сердечко", "обожаю", "чмок", "love", "heart"],
        "💖" => ["любовь", "блеск", "сердечко", "мило", "love"],
        "💕" => ["два сердца", "влюблен", "чувства", "люблю"],
        "😍" => ["влюблен", "красота", "красотка", "обожаю", "прелесть", "восторг", "love", "in love"],
        "😘" => ["целую", "чмок", "поцелуй", "люблю тебя", "kiss", "muah"],
        "🥰" => ["обожаю", "нежность", "умиление", "милота", "люблю"],
        "😻" => ["кот", "котик", "влюблен", "мило"],
        "💋" => ["поцелуй", "губы", "чмок", "kiss"],

        "😢" => ["грустно", "плачу", "печаль", "слеза", "жаль", "тоска", "sad", "cry"],
        "😭" => ["рыдаю", "слезы", "плач", "истерика", "обидно", "за что", "печально", "cry", "sob"],
        "🥺" => ["пожалуйста", "ну пожалуйста", "умоляю", "прости", "милый взгляд", "please"],
        "😞" => ["разочарование", "грусть", "эх", "увы", "эхх"],
        "😔" => ["печаль", "подавлен", "сожалею", "тоска"],
        "😩" => ["устал", "хватит", "надоело", "тяжело", "нет сил"],
        "😫" => ["сил нет", "устал", "сложно", "ужас"],

        "👍" => ["класс", "заебись", "супер", "отлично", "топ", "молодец", "красава", "плюс", "да", "ок", "хорошо", "согласен", "круто", "good", "cool", "like", "ok"],
        "👎" => ["дизлайк", "плохо", "отстой", "против", "фу", "не нравится", "bad", "dislike"],
        "👌" => ["ок", "окей", "идеально", "порядок", "все ок", "ok", "okay", "perfect"],
        "✌️" => ["мир", "победа", "два", "йоу", "peace"],
        "🤞" => ["удачи", "надеюсь", "скрестил пальцы", "хоть бы"],
        "👏" => ["браво", "аплодисменты", "хлопаю", "молодцы", "clap", "bravo"],
        "🙌" => ["ура", "слава богу", "празднуем", "руки вверх"],
        "🙏" => ["спасибо", "пожалуйста", "благодарю", "молю", "прости", "поклон", "спасибки", "thanks", "thank you", "please"],
        "💪" => ["сила", "мощь", "спорт", "сильный", "могу", "strong", "power"],
        "🔥" => ["огонь", "жара", "топ", "пушка", "бомба", "горячо", "fire", "hot", "lit"],
        "💯" => ["сотка", "на все сто", "факт", "база", "правда", "100"],

        "😡" => ["злость", "злой", "ярость", "бесит", "гнев", "раздражает", "angry"],
        "🤬" => ["мат", "бесит", "ненавижу", "черт", "капец", "пипец", "rage"],
        "👿" => ["демон", "черт", "злодей", "дьявол", "evil"],
        "💩" => ["какашка", "говно", "хрень", "фигня", "мусор", "shit", "poop"],
        "🤮" => ["тошнит", "блевать", "гадость", "фуу", "отвратительно", "sick"],

        // Thinking & Confusion
        "🤔" => ["хм", "думаю", "мысли", "задумался", "вопрос", "странно", "почему", "think", "hmm"],
        "🧐" => ["внимательно", "изучаю", "опа", "хмм", "интересно"],
        "🤨" => ["подозрительно", "сомневаюсь", "серьезно", "неужели", "чего"],
        "🤷" => ["не знаю", "хз", "без понятия", "пожимаю плечами", "idk", "shrug"],
        "😶" => ["молчу", "без слов", "тишина", "нет слов", "silent"],
        "🤐" => ["рот на замок", "молчу", "секрет", "тихо"],

        // Surprise & Fear
        "😱" => ["шок", "ужас", "кошмар", "страшно", "боюсь", "офигеть", "ого", "shock", "omg"],
        "😨" => ["страх", "боязнь", "жутко", "ой"],
        "😳" => ["смущение", "неловко", "ого", "ничего себе", "в шоке", "blush", "wow"],
        "🤯" => ["взрыв мозга", "офигеть", "мозг взорван", "mind blown"],

        // Sleep & Rest
        "😴" => ["спать", "спокойной ночи", "сон", "сонный", "баиньки", "доброй ночи", "sleep", "goodnight"],
        "🥱" => ["зеваю", "скучно", "спать охота", "устал"],
        "💤" => ["сплю", "храп", "сон", "zzz"],

        // Celebration
        "🎉" => ["праздник", "поздравляю", "ура", "пати", "вечеринка", "днюха", "congrats", "party"],
        "🥳" => ["празднуем", "ураа", "тусовка", "с днем рождения", "пати", "party", "happy birthday"],
        "🎂" => ["торт", "день рождения", "днюха", "с др", "birthday", "cake"],
        "🎁" => ["подарок", "презент", "сюрприз", "gift", "present"],
        "🍾" => ["шампанское", "бухаем", "праздник", "выпьем", "champagne"],
        "🍺" => ["пиво", "пивас", "бар", "по пиву", "beer"],
        "☕" => ["кофе", "чай", "утро", "доброе утро", "кофеек", "coffee", "tea"],

        // Money & Cool
        "😎" => ["крутой", "чилл", "стиль", "четко", "cool"],
        "🤑" => ["деньги", "богач", "бабло", "кэш", "прибыль", "money", "rich"],
    ];

    private function formatProduct(StickerPack $pack, bool $extended = true): array
    {
        $server_url = ovk_scheme(true) . ($_SERVER["HTTP_HOST"] ?? "");
        $mainSticker = $pack->getMainSticker();

        $isAnimated = ($mainSticker && $mainSticker->getFormat($pack->getId()) === "lottie");
        $animUrl = $isAnimated ? $server_url . $mainSticker->getAnimationUrl($pack->getId()) : null;

        $user = $this->getUser();
        $isPurchased = $user ? $pack->isPurchasedBy($user) : false;
        $hasBought = $user ? $pack->hasBoughtBy($user) : false;
        $isActive = $isPurchased ? 1 : 0;
        $purchased = ($isPurchased || $hasBought) ? 1 : 0;

        $stickerIds = $pack->getStickerIds();
        $stickersList = [];
        foreach ($stickerIds as $sid) {
            $stickersList[] = [
                "sticker_id" => (int) $sid,
                "is_allowed" => true,
            ];
        }

        $thumb128 = $mainSticker ? ($server_url . $mainSticker->getImageUrl(128, (int) $pack->getId())) : "";
        $thumb256 = $mainSticker ? ($server_url . $mainSticker->getImageUrl(256, (int) $pack->getId())) : "";
        $thumb512 = $mainSticker ? ($server_url . $mainSticker->getImageUrl(512, (int) $pack->getId())) : "";

        $previews = [];
        foreach (array_slice($stickerIds, 0, 4) as $sid) {
            $previews[] = [
                "photo_128" => $server_url . "/images/stickers/{$sid}/128b.png",
                "photo_256" => $server_url . "/images/stickers/{$sid}/256b.png",
                "photo_512" => $server_url . "/images/stickers/{$sid}/512.png",
            ];
        }

        $price = (int) $pack->getPrice();
        $priceStr = $price > 0 ? "{$price} " . tr("coins") : "Бесплатно";

        return [
            "id"            => (int) $pack->getId(),
            "type"          => "stickers",
            "title"         => $pack->getName(),
            "name"          => $pack->getName(),
            "description"   => $pack->getDescription() ?? "",
            "author"        => $pack->getAuthor() ?? ($pack->getOwner() ? $pack->getOwner()->getCanonicalName() : ""),
            "purchased"     => $purchased,
            "active"        => $isActive,
            "promoted"      => 0,
            "purchase_date" => (int) $pack->getCreated(),
            "price"         => $price,
            "price_str"     => $priceStr,
            "photo_35"      => $thumb128,
            "photo_70"      => $thumb128,
            "photo_140"     => $thumb128,
            "photo_296"     => $thumb256,
            "photo_592"     => $thumb512,
            "photo_128"     => $thumb128,
            "photo_256"     => $thumb256,
            "photo_512"     => $thumb512,
            "base_url"      => $server_url . "/images/stickers/",
            "has_animation" => $isAnimated,
            "is_animated"   => $isAnimated,
            "animation_url" => $animUrl,
            "stickers_count" => count($stickerIds),
            "sticker_ids"   => $stickerIds,
            "stickers"      => $stickersList,
            "previews"      => $previews,
        ];
    }

    public function getProducts(
        string $type = "stickers",
        string $filters = "",
        int $extended = 1,
        int $count = 50,
        int $offset = 0,
        $product_ids = "",
        int $user_id = 0
    ): object|array {
        $repo = new StickersRepo();
        $targetUser = $this->getUser();
        if ($user_id > 0) {
            $targetUser = (new UsersRepo())->get($user_id) ?? $targetUser;
        }

        $items = [];
        $totalCount = 0;

        $filterList = array_filter(array_map('trim', explode(',', strtolower($filters))));
        $hasProductIds = !empty($product_ids);

        if ($hasProductIds) {
            $ids = is_array($product_ids) ? $product_ids : array_map('intval', explode(',', (string) $product_ids));
            foreach ($ids as $id) {
                $pack = $repo->getPack($id);
                if ($pack && !$pack->isDeleted() && $pack->isAvailable()) {
                    $items[] = $this->formatProduct($pack, (bool) $extended);
                }
            }
            $totalCount = count($items);
        } elseif (in_array("purchased", $filterList, true)) {
            if (!$targetUser) {
                $this->requireUser();
            }
            $boughtIds = $repo->getBoughtPackIds($targetUser);
            $totalCount = count($boughtIds);
            $slice = array_slice($boughtIds, $offset, $count);
            foreach ($slice as $id) {
                $pack = $repo->getPack($id);
                if ($pack && !$pack->isDeleted()) {
                    $items[] = $this->formatProduct($pack, (bool) $extended);
                }
            }
        } elseif (in_array("active", $filterList, true) || empty($filters)) {
            if (!$targetUser) {
                $this->requireUser();
            }
            $activePacks = iterator_to_array($repo->getMyPacks($targetUser, 1, PHP_INT_MAX));
            $totalCount = count($activePacks);
            $slice = array_slice($activePacks, $offset, $count);
            foreach ($slice as $pack) {
                $items[] = $this->formatProduct($pack, (bool) $extended);
            }
        } else {
            $allPacks = iterator_to_array($repo->getPacks(1, PHP_INT_MAX, $totalCount, "all"));
            $slice = array_slice($allPacks, $offset, $count);
            foreach ($slice as $pack) {
                $items[] = $this->formatProduct($pack, (bool) $extended);
            }
        }

        return (object) [
            "count" => $totalCount,
            "items" => $items,
        ];
    }

    public function getStockItems(
        string $type = "stickers",
        string $section = "",
        int $extended = 1,
        int $count = 50,
        int $offset = 0,
        string $merchant = ""
    ): object {
        $repo = new StickersRepo();
        $totalCount = 0;
        $page = (int) floor($offset / max($count, 1)) + 1;

        $secParam = match ($section) {
            "free" => "free",
            "all", "catalog" => "all",
            default => "popular",
        };
        $packs = iterator_to_array($repo->getPacks(1, PHP_INT_MAX, $totalCount, $secParam));

        $slice = array_slice($packs, $offset, $count);
        $items = [];

        foreach ($slice as $pack) {
            $product = $this->formatProduct($pack, (bool) $extended);
            $price = $pack->getPrice();
            $priceStr = $price > 0 ? "{$price} " . tr("coins") : "Бесплатно";

            $items[] = [
                "product"       => $product,
                "description"   => $pack->getDescription() ?? "",
                "author"        => $pack->getAuthor() ?? ($pack->getOwner() ? $pack->getOwner()->getCanonicalName() : ""),
                "price"         => $price,
                "price_str"     => $priceStr,
                "can_purchase"  => 1,
                "free"          => $price === 0 ? 1 : 0,
                "is_new"        => (time() - $pack->getCreated() < 30 * 86400) ? 1 : 0,
            ];
        }

        return (object) [
            "count" => count($packs),
            "items" => $items,
        ];
    }

    public function getStickersKeywords(
        int $aliases = 1,
        int $all_products = 1,
        int $need_stickers = 1,
        string $stickers_hash = "",
        string $products_hash = "",
        int $count = 0,
        int $user_id = 0
    ): object {
        $repo = new StickersRepo();
        $user = $this->getUser();
        if ($user_id > 0) {
            $user = (new UsersRepo())->get($user_id) ?? $user;
        }

        $packs = [];
        if ($user) {
            $packs = iterator_to_array($repo->getMyPacks($user, 1, PHP_INT_MAX));
        }

        if (empty($packs) || $all_products === 1) {
            $allAvailable = iterator_to_array($repo->getPacks(1, 100));
            $existingIds = array_map(fn($p) => $p->getId(), $packs);
            foreach ($allAvailable as $p) {
                if (!in_array($p->getId(), $existingIds, true)) {
                    $packs[] = $p;
                }
            }
        }

        $stickerMap = [];
        $wordMap = [];
        foreach ($packs as $pack) {
            foreach ($pack->getStickers(-1) as $sticker) {
                $sid = $sticker->getId();
                if ($need_stickers === 1 && !isset($stickerMap[$sid])) {
                    $stickerMap[$sid] = $sticker->toVkApiStruct($user, $pack->getId());
                }

                $rawEmoji = $sticker->getEmoji();
                if ($rawEmoji === "") {
                    continue;
                }

                $emojis = \Emoji\detect_emoji($rawEmoji);
                $emojiChars = !empty($emojis) ? array_column($emojis, "emoji") : [$rawEmoji];

                foreach ($emojiChars as $eChar) {
                    $eChar = trim($eChar);
                    if ($eChar === "") {
                        continue;
                    }

                    if (!isset($wordMap[$eChar])) {
                        $wordMap[$eChar] = [];
                    }
                    if (!in_array($sid, $wordMap[$eChar], true)) {
                        $wordMap[$eChar][] = $sid;
                    }

                    if ($aliases === 1 && isset(self::$emojiKeywords[$eChar])) {
                        foreach (self::$emojiKeywords[$eChar] as $kw) {
                            $kw = mb_strtolower(trim($kw));
                            if (!isset($wordMap[$kw])) {
                                $wordMap[$kw] = [];
                            }
                            if (!in_array($sid, $wordMap[$kw], true)) {
                                $wordMap[$kw][] = $sid;
                            }
                        }
                    }
                }
            }
        }

        $stickerGroups = [];
        foreach ($wordMap as $word => $sids) {
            sort($sids);
            $key = implode(",", $sids);
            if (!isset($stickerGroups[$key])) {
                $stickerGroups[$key] = [
                    "words"         => [],
                    "user_stickers" => $sids,
                ];
            }
            $stickerGroups[$key]["words"][] = $word;
        }

        $dictionary = [];
        foreach ($stickerGroups as $group) {
            $userStickers = [];
            foreach ($group["user_stickers"] as $sid) {
                $sidInt = (int) $sid;
                if ($need_stickers === 1 && isset($stickerMap[$sidInt])) {
                    $item = $stickerMap[$sidInt];
                    $item["sticker_id"] = $sidInt;
                    $item["id"] = $sidInt;
                    $userStickers[] = $item;
                } else {
                    $userStickers[] = [
                        "id"         => $sidInt,
                        "sticker_id" => $sidInt,
                    ];
                }
            }

            $dictionary[] = [
                "words"             => array_values(array_map('strval', $group["words"])),
                "user_stickers"     => $userStickers,
                "promoted_stickers" => [],
            ];
        }

        $server_url = ovk_scheme(true) . ($_SERVER["HTTP_HOST"] ?? "");

        return (object) [
            "count"         => count($dictionary),
            "dictionary"    => $dictionary,
            "base_url"      => $server_url . "/images/stickers/",
            "stickers_hash" => md5(json_encode($dictionary)),
            "products_hash" => md5(json_encode(array_keys($dictionary))),
        ];
    }

    public function activateProduct(int $product_id = 0, string $type = "stickers"): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $repo = new StickersRepo();
        $pack = $repo->getPack($product_id);
        if (!$pack) {
            $this->fail(15, "Product not found");
        }

        if ($pack->getPrice() === 0 || $pack->hasBoughtBy($this->getUser())) {
            $pack->buy($this->getUser());
        }

        return (object) ["success" => 1];
    }

    public function deactivateProduct(int $product_id = 0, string $type = "stickers"): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $repo = new StickersRepo();
        $pack = $repo->getPack($product_id);
        if (!$pack) {
            $this->fail(15, "Product not found");
        }

        $pack->hideFromQuickAccess($this->getUser());

        return (object) ["success" => 1];
    }

    public function buy(int $product_id = 0, int $stickerpack_id = 0): object
    {
        $this->requireUser();
        $this->willExecuteWriteAction();

        $id = $product_id > 0 ? $product_id : $stickerpack_id;
        $repo = new StickersRepo();
        $pack = $repo->getPack($id);

        if (!$pack) {
            $this->fail(15, "Sticker pack not found");
        }

        if (!$pack->isAvailable()) {
            $this->fail(15, "Sticker not available");
        }

        if (!$pack->buy($this->getUser())) {
            $this->fail(15, "Cannot purchase this pack");
        }

        return (object) [
            "success"    => 1,
            "product_id" => $pack->getId(),
            "pack_id"    => $pack->getId(),
        ];
    }

    public function getFavoriteStickers(): object
    {
        return (object) [
            "count" => 0,
            "items" => [],
        ];
    }

    public function addFavoriteSticker(int $sticker_id = 0): object
    {
        return (object) ["success" => 1];
    }

    public function removeFavoriteSticker(int $sticker_id = 0): object
    {
        return (object) ["success" => 1];
    }

    public function getRecentStickers(): object
    {
        return (object) [
            "count" => 0,
            "items" => [],
        ];
    }

    public function addRecentSticker(int $sticker_id = 0): object
    {
        return (object) ["success" => 1];
    }

    public function clearRecentStickers(): object
    {
        return (object) ["success" => 1];
    }
}
