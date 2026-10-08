OpenVK-KB-Heading: notes.add

# notes.add

Создает новую заметку у текущего пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`). Метод выполняет действие записи.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `title` | string | **Обязательный параметр**. Заголовок заметки. |
| `text` | string | Текст заметки. |

### Результат

Возвращает идентификатор созданной заметки (`integer`).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `100` | `Required parameter 'title' missing.` — Не передан обязательный параметр `title`. |

### Пример запроса
```http
POST /method/notes.add HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

title=Моя%20первая%20заметка&text=Привет%2C%20мир!&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
