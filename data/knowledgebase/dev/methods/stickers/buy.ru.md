OpenVK-KB-Heading: stickers.buy

# stickers.buy

Приобретает или активирует указанный стикерпак для текущего пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `stickerpack_id` | integer | **Обязательный параметр.** Идентификатор приобретаемого стикерпака. |

### Результат

Возвращает объект с полями:

| Поле | Тип | Описание |
| --- | --- | --- |
| `success` | integer | Всегда `1` при успешной покупке/активации. |
| `pack_id` | integer | Идентификатор приобретенного стикерпака. |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Sticker pack not found` / `Sticker not available` — Стикерпак не найден или недоступен для покупки. |
| `15` | `Cannot purchase this pack` — Недостаточно монет на балансе или набор уже активирован. |

### Пример запроса
```http
POST /method/stickers.buy HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

stickerpack_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "success": 1,
        "pack_id": 1
    }
}
```
