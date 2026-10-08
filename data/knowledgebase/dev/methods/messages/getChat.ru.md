OpenVK-KB-Heading: messages.getChat

# messages.getChat

Возвращает информацию о групповой беседе (название, администратор, список участников, обложка).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `chat_id` | integer | Идентификатор беседы (число `1...N`). |
| `chat_ids` | string | Список идентификаторов бесед через запятую. |
| `fields` | string | Дополнительные поля профилей участников. |
| `name_case` | string | Падеж для склонения имени и фамилии участников. |

---

### Результат

#### API версии 5.0 и выше (v >= 5.0)
Возвращает объект **[Chat](/dev/models/chat)** (или массив объектов при передаче `chat_ids`):

```json
{
    "response": {
        "type": "chat",
        "id": 1,
        "title": "Разработчики OpenVK",
        "admin_id": 1,
        "users": [1, 2, 3],
        "members_count": 3,
        "photo_50": "https://openvk.instance/assets/packages/static/openvk/img/camera_50.png",
        "photo_100": "https://openvk.instance/assets/packages/static/openvk/img/camera_100.png",
        "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png"
    }
}
```

#### Устаревшие версии API (v < 5.0)
Возвращает объект со свойствами `chat_id` и `users`:
```json
{
    "response": {
        "chat_id": 1,
        "type": "chat",
        "title": "Разработчики OpenVK",
        "admin_id": 1,
        "users": [1, 2, 3]
    }
}
```

### Пример запроса
```http
POST /method/messages.getChat HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

chat_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```
