OpenVK-KB-Heading: polls.getVoters

# polls.getVoters

Возвращает список пользователей, проголосовавших за указанный вариант ответа в открытом (неанонимном) опросе.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `wall`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `poll_id` | integer | **Обязательный параметр.** Идентификатор опроса. |
| `answer_ids` | integer | **Обязательный параметр.** Идентификатор варианта ответа, для которого нужно получить список проголосовавших. |
| `offset` | integer | Смещение относительно начала списка проголосовавших. По умолчанию: `0`. |
| `count` | integer | Количество возвращаемых пользователей. По умолчанию: `6`. |

### Результат

Возвращает массив объектов результатов голосования:

```json
{
    "response": [
        {
            "answer_id": 1,
            "users": {
                "items": [
                    {
                        "id": 1,
                        "first_name": "Павел",
                        "last_name": "Дуров",
                        "photo_50": "https://openvk.instance/avatars/1_50.jpeg",
                        "photo_100": "https://openvk.instance/avatars/1_100.jpeg",
                        "photo_200": "https://openvk.instance/avatars/1_200.jpeg"
                    }
                ]
            }
        }
    ]
}
```

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Опрос не найден или является анонимным (просмотр проголосовавших запрещен). |

### Пример запроса
```http
POST /method/polls.getVoters HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

poll_id=1&answer_ids=1&count=10&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": [
        {
            "answer_id": 1,
            "users": {
                "items": [
                    {
                        "id": 1,
                        "first_name": "Павел",
                        "last_name": "Дуров",
                        "photo_50": "https://openvk.instance/avatars/1_50.jpeg",
                        "photo_100": "https://openvk.instance/avatars/1_100.jpeg",
                        "photo_200": "https://openvk.instance/avatars/1_200.jpeg"
                    }
                ]
            }
        }
    ]
}
```
