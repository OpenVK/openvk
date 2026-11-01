OpenVK-KB-Heading: stickers.getFrom

# stickers.getFrom

Возвращает подробную информацию об указанном стикерпаке и полный список входящих в него стикеров.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `stickerpack_id` | integer | **Обязательный параметр.** Идентификатор стикерпака. |

### Результат

Возвращает объект стикерпака:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор стикерпака. |
| `name` | string | Название набора. |
| `title` | string | Заголовок набора. |
| `description` | string | Описание набора. |
| `slug` | string | Слаг набора. |
| `price` | integer | Стоимость в монетах. |
| `end_time` | integer | Срок окончания доступности или `0`. |
| `purchased` | integer | `1` — приобретен/установлен пользователем, `0` — нет. |
| `photo_128` | string | URL обложки 128x128 px. |
| `photo_256` | string | URL обложки 256x256 px. |
| `is_animated` | boolean | Анимированный ли набор. |
| `animation_url` | string/null | URL файла анимации (Lottie/TGS JSON). |
| `stickers_count` | integer | Количество стикеров. |
| `sticker_ids` | array | Массив идентификаторов стикеров. |
| `stickers` | array | Массив объектов стикеров набора. |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Sticker pack not found` / `Access denied` — Стикерпак не найден, удален или недоступен для просмотра. |

### Пример запроса
```http
POST /method/stickers.getFrom HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

stickerpack_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "id": 1,
        "name": "Котик Персик",
        "title": "Котик Персик",
        "description": "Милый рыжий кот",
        "slug": "peach",
        "price": 0,
        "end_time": 0,
        "purchased": 1,
        "photo_128": "https://openvk.instance/images/stickers/1/128b.png",
        "photo_256": "https://openvk.instance/images/stickers/1/256b.png",
        "is_animated": false,
        "animation_url": null,
        "stickers_count": 24,
        "sticker_ids": [1, 2, 3],
        "stickers": [
            {
                "sticker_id": 1,
                "is_allowed": true
            }
        ]
    }
}
```
