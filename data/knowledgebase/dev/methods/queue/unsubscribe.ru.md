OpenVK-KB-Heading: queue.unsubscribe

# queue.unsubscribe

Отписывает пользователя от очередей событий.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `queue_id` | string | Идентификатор очереди событий. |
| `queue_ids` | string | Список идентификаторов очередей событий, перечисленных через запятую. |

### Результат

Возвращает `1`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |

### Пример запроса
```http
POST /method/queue.unsubscribe HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

queue_id=im1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
