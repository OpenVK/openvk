OpenVK-KB-Heading: notes.delete

# notes.delete

Удаляет заметку текущего пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`). Метод выполняет действие записи.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `note_id` | integer | **Обязательный параметр**. Идентификатор заметки. |

### Результат

Возвращает `1` в случае успешного удаления.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Заметка не найдена, уже удалена или принадлежит другому пользователю. |

### Пример запроса
```http
POST /method/notes.delete HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

note_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
