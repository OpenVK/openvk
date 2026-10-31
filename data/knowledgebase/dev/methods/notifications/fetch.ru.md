OpenVK-KB-Heading: notifications.fetch

# notifications.fetch

Получает поток новых событий уведомлений через внутренний брокер уведомлений (NotificationBroker) на основе идентификатора последнего полученного события.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `last_id` | string | Идентификатор последнего полученного события (курсор потока). По умолчанию: `"0"`. |

### Результат

Возвращает объект со следующими полями:
* `items` (array) — массив новых объектов уведомлений;
* `profiles` (array) — массив профилей пользователей;
* `groups` (array) — массив сообществ;
* `new_lastId` (string) — новый идентификатор последнего события (если были новые события);
* `next_last_id` (string) — следующий курсор для опроса.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `1981` | `Internal error during event processing` — Внутренняя ошибка обработки событий брокером. |

### Пример запроса
```http
POST /method/notifications.fetch HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

last_id=1700000000&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "items": [],
        "profiles": [],
        "groups": [],
        "next_last_id": "1700000000"
    }
}
```
