OpenVK-KB-Heading: queue.subscribe

# queue.subscribe

Подписывает авторизованного пользователя на одну или несколько очередей событий и возвращает адрес сервера очередей, ключи доступа и текущий timestamp.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `queue_id` | string | Идентификатор очереди событий. |
| `queue_ids` | string | Список идентификаторов очередей событий, перечисленных через запятую. |
| `ts` | integer | Временная метка (timestamp) для начала отслеживания событий. По умолчанию: `0` (текущее время). |

### Результат

Возвращает объект с параметрами подключения:
* `base_url` (string) — базовый URL сервера очередей (`/queue`);
* `queues` (array) — массив объектов очередей:
  * `queue_id` (string) — идентификатор очереди;
  * `id` (string) — идентификатор очереди;
  * `name` (string) — имя очереди;
  * `base_url` (string) — URL сервера очередей;
  * `key` (string) — секретный ключ доступа к очереди событий;
  * `ts` (string) — строковый timestamp;
  * `timestamp` (integer) — числовой timestamp;
  * `wait` (integer) — таймаут ожидания LongPoll (25 секунд);
  * `events` (array) — массив событий.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/queue.subscribe HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

queue_id=im1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "base_url": "https://openvk.instance/queue",
        "queues": [
            {
                "queue_id": "im1",
                "id": "im1",
                "name": "im1",
                "base_url": "https://openvk.instance/queue",
                "key": "a1b2c3d4e5f67890a1b2c3d4e5f67890",
                "ts": "1700000000",
                "timestamp": 1700000000,
                "wait": 25,
                "events": []
            }
        ]
    }
}
```
