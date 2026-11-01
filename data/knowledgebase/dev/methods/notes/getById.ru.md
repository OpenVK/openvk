OpenVK-KB-Heading: notes.getById

# notes.getById

Возвращает подробную информацию о заметке по её идентификатору и идентификатору владельца.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `note_id` | integer | **Обязательный параметр**. Идентификатор заметки. |
| `owner_id` | integer | **Обязательный параметр**. Идентификатор владельца заметки. |
| `need_wiki` | boolean | Флаг необходимости возврата разметки wiki. По умолчанию: `false`. |

### Результат

Возвращает объект заметки со следующими полями:
* `id` (integer) — идентификатор заметки;
* `owner_id` (integer) — идентификатор владельца;
* `title` (string) — заголовок заметки;
* `text` (string) — текст заметки;
* `date` (integer) — дата создания (Unix timestamp);
* `comments` (integer) — количество комментариев;
* `read_comments` (integer) — количество прочитанных комментариев;
* `view_url` (string) — прямая ссылка на страницу заметки.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Заметка не найдена, удалена или доступ к ней ограничен настройками приватности. |

### Пример запроса
```http
POST /method/notes.getById HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

note_id=1&owner_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "id": 1,
        "owner_id": 1,
        "title": "Моя первая заметка",
        "text": "Привет, мир!",
        "date": 1700000000,
        "comments": 0,
        "read_comments": 0,
        "view_url": "/notes1_1"
    }
}
```
