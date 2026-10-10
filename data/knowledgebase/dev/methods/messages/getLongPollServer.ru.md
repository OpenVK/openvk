OpenVK-KB-Heading: messages.getLongPollServer

# messages.getLongPollServer

Возвращает параметры подключения к LongPoll серверу для получения мгновенных уведомлений и событий в режиме реального времени.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `need_pts` | integer | `1` — возвращать параметр `pts` для отслеживания состояния событий. По умолчанию: `0`. |
| `lp_version` | integer | Версия протокола LongPoll (например, `2`, `3`, `19`). По умолчанию: `2`. |
| `use_ssl` | integer | `1` — использовать HTTPS адрес сервера. По умолчанию: `0`. |
| `group_id` | integer | Идентификатор сообщества (для ботов сообществ). |

### Результат

Возвращает объект со следующими полями:
* `server` (string) — адрес LongPoll сервера;
* `key` (string) — секретный ключ сессии;
* `ts` (integer) — номер начальной временной метки / события;
* `pts` (integer, опционально) — номер последнего события (при `need_pts=1`).

### Пример запроса
```http
POST /method/messages.getLongPollServer HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

need_pts=1&lp_version=3&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "key": "4f9a0c7e2b1d",
        "server": "openvk.instance/lp",
        "ts": 1696680000,
        "pts": 450
    }
}
```
