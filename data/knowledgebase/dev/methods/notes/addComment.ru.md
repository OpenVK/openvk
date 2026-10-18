OpenVK-KB-Heading: notes.addComment

# notes.addComment

Добавляет комментарий к заметке. Метод является псевдонимом (алиасом) для метода [notes.createComment](/dev/methods/notes/createComment) и поддерживает альтернативное наименование параметра `text`.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`). Метод выполняет действие записи.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `note_id` | integer | **Обязательный параметр**. Идентификатор заметки. |
| `owner_id` | integer | **Обязательный параметр**. Идентификатор владельца заметки. |
| `message` | string | Текст комментария (может быть передан в параметре `text`). |
| `text` | string | Альтернативное наименование для текста комментария. |
| `attachments` | string | Медиавложения. |

### Результат

В API версии 5.0 и выше возвращает идентификатор созданного комментария (`integer`).

В устаревших версиях API (до версии 5.0) возвращает объект `{"cid": <comment_id>}`.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Заметка не найдена, удалена или доступ к комментированию ограничен. |
| `100` | `Required parameter 'message' missing.` — Не передан текст комментария. |

### Пример запроса
```http
POST /method/notes.addComment HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

note_id=1&owner_id=1&text=Комментарий&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 43
}
```
