OpenVK-KB-Heading: notifications.getIgnoredSources

# notifications.getIgnoredSources

Возвращает список источников, уведомления от которых скрыты (игнорируются) пользователем.

> **Примечание (заглушка совместимости):** В текущей версии OpenVK метод является заглушкой для совместимости и возвращает пустой список.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `offset` | integer | Смещение выборки. По умолчанию: `0`. |
| `count` | integer | Количество возвращаемых записей. По умолчанию: `0`. |
| `fields` | string | Дополнительные поля профилей и сообществ. |

### Результат

Возвращает объект со следующими полями:
* `count` (integer) — количество источников (всегда `0`);
* `items` (array) — пустой массив `[]`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/notifications.getIgnoredSources HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 0,
        "items": []
    }
}
```
