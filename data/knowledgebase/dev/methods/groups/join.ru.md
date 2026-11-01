OpenVK-KB-Heading: groups.join

# groups.join

Позволяет текущему пользователю вступить в группу или подписаться на публичную страницу.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `groups`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `group_id` | integer | **Обязательный параметр**. Идентификатор сообщества. |

### Результат

Возвращает `1` в случае успешного вступления или подписки.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `9` | `Flood control: action too fast or too frequent.` — Слишком частая подписка на сообщества. |

### Пример запроса
```http
POST /method/groups.join HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
