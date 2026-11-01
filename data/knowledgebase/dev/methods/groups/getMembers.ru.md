OpenVK-KB-Heading: groups.getMembers

# groups.getMembers

Возвращает список участников (подписчиков) сообщества.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `group_id` | integer | **Обязательный параметр**. Идентификатор сообщества. |
| `fields` | string | Список дополнительных полей профилей участников через запятую (например, `sex,bdate,city,country,photo_50`). |
| `offset` | integer | Смещение относительно начала списка участников. По умолчанию: `0`. |
| `count` | integer | Количество участников, которое необходимо вернуть. По умолчанию: `10`. |

### Результат

Возвращает объект с полями:
* `count` (integer) — общее число участников сообщества;
* `items` (array) — массив объектов пользователей [User](/dev/models/user).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Доступ к просмотру сообщества ограничен. |

### Пример запроса
```http
POST /method/groups.getMembers HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=1&count=2&fields=sex,bdate&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 150,
        "items": [
            {
                "id": 1,
                "first_name": "Павел",
                "last_name": "Дуров",
                "sex": 2,
                "bdate": "10.10.1984"
            }
        ]
    }
}
```
