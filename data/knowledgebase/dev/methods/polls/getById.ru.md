OpenVK-KB-Heading: polls.getById

# polls.getById

Возвращает подробную информацию об опросе по его идентификатору.

### Авторизация
Этот метод не требует обязательной авторизации, но при передаче `access_token` возвращает персональный статус голосования текущего пользователя (`answer_ids`, `can_vote`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `poll_id` | integer | **Обязательный параметр.** Идентификатор опроса. |
| `extended` | boolean | `1` — возвращать профиль создателя опроса в массиве `profiles`, `0` — нет. По умолчанию: `0`. |
| `fields` | string | Список дополнительных полей профилей через запятую (при `extended=1`). По умолчанию: `sex,screen_name,photo_50,photo_100,online_info,online`. |

### Результат

Возвращает объект опроса:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор опроса. |
| `poll_id` | integer | Идентификатор опроса (для совместимости). |
| `owner_id` | integer | Идентификатор владельца (создателя) опроса. |
| `author_id` | integer | Идентификатор автора опроса. |
| `question` | string | Текст вопроса опроса. |
| `votes` | integer | Общее количество проголосовавших. |
| `multiple` | boolean | Разрешен ли выбор нескольких вариантов. |
| `anonymous` | integer | `1` — анонимный опрос, `0` — публичный. |
| `disable_unvote` | boolean | Запрещен ли отзыв голоса. |
| `closed` | boolean | Завершен ли опрос. |
| `end_date` | integer | Время окончания опроса (Unix timestamp) или `0`. |
| `can_vote` | integer | `1`, если текущий пользователь может проголосовать. |
| `can_share` | integer | `1`, если опрос доступен для публикации. |
| `answer_ids` | array | Идентификаторы вариантов ответов, выбранных текущим пользователем. |
| `answers` | array | Массив объектов вариантов ответов. |
| `profiles` | array | Массив профилей авторов/проголосовавших (при `extended=1`). |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `100` | `One of the parameters specified was missing or invalid: poll_id is incorrect` — Опрос с указанным `poll_id` не найден. |

### Пример запроса
```http
POST /method/polls.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

poll_id=1&extended=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "id": 1,
        "poll_id": 1,
        "owner_id": 1,
        "author_id": 1,
        "question": "Какая ваша любимая ОС?",
        "votes": 10,
        "multiple": false,
        "anonymous": 0,
        "disable_unvote": false,
        "closed": false,
        "end_date": 0,
        "can_vote": 0,
        "can_share": 1,
        "can_edit": 0,
        "can_report": 0,
        "is_board": 0,
        "created": 0,
        "answer_ids": [1],
        "answers": [
            {
                "id": 1,
                "text": "Linux",
                "votes": 7,
                "rate": 70
            },
            {
                "id": 2,
                "text": "Windows",
                "votes": 2,
                "rate": 20
            },
            {
                "id": 3,
                "text": "macOS",
                "votes": 1,
                "rate": 10
            }
        ],
        "profiles": [
            {
                "id": 1,
                "first_name": "Павел",
                "last_name": "Дуров"
            }
        ]
    }
}
```
