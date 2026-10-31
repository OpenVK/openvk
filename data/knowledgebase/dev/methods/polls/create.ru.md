OpenVK-KB-Heading: polls.create

# polls.create

Создает новый опрос у текущего пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `wall`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `question` | string | **Обязательный параметр.** Текст вопроса опроса. |
| `add_answers` | string | **Обязательный параметр.** JSON-массив строк с вариантами ответов (например, `["Да", "Нет"]`). |
| `disable_unvote` | boolean | `1` — запретить пользователям отзывать свой голос, `0` — разрешить. По умолчанию: `0`. |
| `is_anonymous` | boolean | `1` — анонимный опрос (список проголосовавших скрыт), `0` — открытый. По умолчанию: `0`. |
| `is_multiple` | boolean | `1` — разрешить выбор нескольких вариантов ответа, `0` — только один вариант. По умолчанию: `0`. |
| `end_date` | integer | Дата и время завершения опроса (Unix timestamp). Должна быть в будущем, но не более чем на 365 дней вперед. По умолчанию: `0` (бессрочный опрос). |

### Результат

Возвращает объект созданного опроса:

| Поле | Тип | Описание |
| --- | --- | --- |
| `id` | integer | Идентификатор опроса. |
| `poll_id` | integer | Идентификатор опроса (для совместимости). |
| `owner_id` | integer | Идентификатор владельца (создателя) опроса. |
| `author_id` | integer | Идентификатор автора опроса. |
| `question` | string | Текст вопроса. |
| `votes` | integer | Общее количество проголосовавших (при создании `0`). |
| `multiple` | boolean | Разрешен ли выбор нескольких вариантов. |
| `anonymous` | integer | `1` — анонимный опрос, `0` — публичный. |
| `disable_unvote` | boolean | Запрещен ли отзыв голоса. |
| `closed` | boolean | Завершен ли опрос. |
| `end_date` | integer | Время завершения опроса (Unix timestamp) или `0`. |
| `can_vote` | integer | `1`, если текущий пользователь может голосовать. |
| `can_share` | integer | `1`, если опрос можно прикрепить к записи или сообщению. |
| `answer_ids` | array | Массив идентификаторов вариантов, за которые проголосовал текущий пользователь. |
| `answers` | array | Массив объектов вариантов ответа. |

Каждый объект в массиве `answers` содержит:
* `id` (integer) — идентификатор варианта ответа;
* `text` (string) — текст варианта ответа;
* `votes` (integer) — число голосов;
* `rate` (float) — процент голосов от общего числа.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `51` | `Too many options` — Превышено максимальное число вариантов ответа. |
| `62` | `Invalid options` — Некорректный формат или пустой список вариантов ответа в `add_answers`. |
| `89` | `End date is too big` — Дата окончания опроса превышает допустимый максимум (1 год). |

### Пример запроса
```http
POST /method/polls.create HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

question=Какая%20ваша%20любимая%20ОС?&add_answers=["Linux","Windows","macOS"]&is_anonymous=0&access_token=YOUR_ACCESS_TOKEN&v=5.138
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
        "votes": 0,
        "multiple": false,
        "anonymous": 0,
        "disable_unvote": false,
        "closed": false,
        "end_date": 0,
        "can_vote": 1,
        "can_share": 1,
        "can_edit": 0,
        "can_report": 0,
        "is_board": 0,
        "created": 0,
        "answer_ids": [],
        "answers": [
            {
                "id": 1,
                "text": "Linux",
                "votes": 0,
                "rate": 0
            },
            {
                "id": 2,
                "text": "Windows",
                "votes": 0,
                "rate": 0
            },
            {
                "id": 3,
                "text": "macOS",
                "votes": 0,
                "rate": 0
            }
        ]
    }
}
```
