OpenVK-KB-Heading: store.getStickersKeywords

# store.getStickersKeywords

Возвращает словарь соответствия эмодзи и ключевых слов стикерам для мгновенного предложения стикеров при наборе текста в сообщениях.

### Авторизация
Этот метод не требует обязательной авторизации.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `aliases` | integer | `1` — использовать встроенные текстовые синонимы к эмодзи (например, «привет», «хай» для эмодзи «👋»), `0` — только исходные эмодзи. По умолчанию: `1`. |
| `all_products` | integer | `1` — возвращать подсказки для всех наборов каталога, `0` — только для активных наборов пользователя. По умолчанию: `1`. |
| `need_stickers` | integer | `1` — возвращать полные структуры стикеров, `0` — только их числовые идентификаторы. По умолчанию: `1`. |
| `stickers_hash` | string | MD5-хеш текущей версии словаря на стороне клиента. |
| `products_hash` | string | MD5-хеш списка продуктов на стороне клиента. |
| `count` | integer | Лимит количества записей. По умолчанию: `0` (без ограничений). |
| `user_id` | integer | Идентификатор пользователя. |

### Результат

Возвращает объект с полями:
* `count` (integer) — количество групп подсказок в словаре;
* `dictionary` (array) — массив групп соответствия слов и стикеров;
* `base_url` (string) — базовый URL изображений стикеров платформы;
* `stickers_hash` (string) — хеш словаря для кеширования;
* `products_hash` (string) — хеш списка продуктов.

Каждая группа в массиве `dictionary` содержит:
* `words` (array) — список слов и эмодзи, активирующих подсказку;
* `user_stickers` (array) — массив доступных пользователю стикеров для этих слов;
* `promoted_stickers` (array) — массив рекомендуемых к покупке стикеров.

### Пример запроса
```http
POST /method/store.getStickersKeywords HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

aliases=1&need_stickers=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "dictionary": [
            {
                "words": [
                    "👋",
                    "привет",
                    "хай",
                    "здравствуй",
                    "hello",
                    "hi"
                ],
                "user_stickers": [
                    {
                        "id": 1,
                        "sticker_id": 1
                    }
                ],
                "promoted_stickers": []
            }
        ],
        "base_url": "https://openvk.instance/images/stickers/",
        "stickers_hash": "d41d8cd98f00b204e9800998ecf8427e",
        "products_hash": "7b8b965ad4bca0e41ab51de7b31363a1"
    }
}
```
