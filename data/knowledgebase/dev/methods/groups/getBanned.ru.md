OpenVK-KB-Heading: groups.getBanned

# groups.getBanned

Возвращает список пользователей, заблокированных в сообществе (находящихся в черном списке).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами администратора сообщества.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `group_id` | integer | **Обязательный параметр**. Идентификатор сообщества. |
| `fields` | string | Список дополнительных полей профилей пользователей через запятую (например, `sex,bdate,city,country,photo_50`). |
| `offset` | integer | Смещение относительно начала списка заблокированных пользователей. По умолчанию: `0`. |
| `count` | integer | Количество пользователей, которое необходимо вернуть. По умолчанию: `20`. |

### Результат

Возвращает объект с полями:
* `count` (integer) — общее количество пользователей в черном списке сообщества;
* `items` (array) — массив объектов пользователей [User](/dev/models/user).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Текущий пользователь не является администратором сообщества. |

### Пример запроса
```http
POST /method/groups.getBanned HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&count=10&fields=sex,bdate&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 5,
                "first_name": "Спамер",
                "last_name": "Иванов",
                "sex": 2,
                "bdate": "1.1.2000"
            }
        ]
    }
}
```
