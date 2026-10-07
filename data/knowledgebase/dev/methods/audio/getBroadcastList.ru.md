OpenVK-KB-Heading: audio.getBroadcastList

# audio.getBroadcastList

Возвращает список друзей или сообществ текущего пользователя, транслирующих музыку в статус.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `audio` и `friends`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `filter` | string | Типы возвращаемых объектов: `"all"` — все, `"friends"` — только друзья, `"groups"` — только сообщества. По умолчанию: `"all"`. |
| `active` | integer | `1` — возвращать только активные трансляции. По умолчанию: `0`. |
| `hash` | string | Хэш авторизации потока (опционально). |

### Результат

Возвращает объект со следующими полями:
* `count` (integer) — количество объектов в списке;
* `items` (array) — массив объектов пользователей или сообществ, где поле `status_audio` содержит объект транслируемой аудиозаписи.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `8` | `Invalid filter {filter}` — Недопустимое значение фильтра. |

### Пример запроса
```http
POST /method/audio.getBroadcastList HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

filter=friends&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 2,
                "first_name": "Николай",
                "last_name": "Дуров",
                "status_audio": {
                    "id": 1,
                    "owner_id": 1,
                    "artist": "Rick Astley",
                    "title": "Never Gonna Give You Up",
                    "duration": 213,
                    "url": "https://openvk.instance/storage/01/hash.mp3"
                }
            }
        ]
    }
}
```
