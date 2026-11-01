OpenVK-KB-Heading: groups.getById

# groups.getById

Возвращает подробную информацию о сообществах по их идентификаторам или коротким адресам (`screen_name`).

### Авторизация
Метод может вызываться как с авторизацией пользователя, так и без нее.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `group_ids` | string | Список идентификаторов или коротких имен сообществ через запятую (например, `1,2,apiclub`). |
| `group_id` | string | Идентификатор или короткое имя сообщества (используется, если не передан `group_ids`). |
| `fields` | string | Список дополнительных полей сообществ через запятую (например, `description,members_count,status,site,can_post`). |
| `offset` | integer | Смещение относительно начала списка сообществ. По умолчанию: `0`. |
| `count` | integer | Количество сообществ, которое необходимо вернуть (максимум `500`). По умолчанию: `500`. |

### Результат

Возвращает массив объектов сообществ [Group](/dev/models/group). Если сообщество не найдено или было удалено, возвращается объект-заглушка с `"name": "DELETED"`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `100` | `One of the parameters specified was missing or invalid: group_ids is undefined` — Не указан параметр `group_ids` или `group_id`. |

### Пример запроса
```http
POST /method/groups.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_ids=1,apiclub&fields=description,members_count&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": [
        {
            "id": 1,
            "name": "Официальная группа OpenVK",
            "screen_name": "openvk",
            "is_closed": 0,
            "type": "group",
            "is_admin": 1,
            "is_member": 1,
            "description": "Сообщество разработчиков и пользователей OpenVK",
            "members_count": 150,
            "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
            "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
            "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
        }
    ]
}
```
