OpenVK-KB-Heading: likes.add

# likes.add

Добавляет отметку «Мне нравится» к указанному объекту (записи на стене, комментарию, фотографии, видеозаписи или заметке).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `type` | string | **Обязательный параметр**. Тип объекта: `"post"` (запись на стене), `"comment"` (комментарий), `"photo"` (фотография), `"video"` (видеозапись), `"note"` (заметка). |
| `owner_id` | integer | **Обязательный параметр**. Идентификатор владельца объекта (пользователя или сообщества). |
| `item_id` | integer | **Обязательный параметр**. Идентификатор самого объекта. |

### Результат

Возвращает объект, содержащий поле:
* `likes` (integer) — общее количество отметок «Мне нравится» у объекта после добавления.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `2` | `Access to postable denied` — Доступ к объекту ограничен настройками приватности или черным списком. |
| `100` | `One of the parameters specified was missing or invalid: incorrect type` — Указан неподдерживаемый тип объекта. |
| `100` | `One of the parameters specified was missing or invalid: object not found` — Объект не найден или удален. |

### Пример запроса
```http
POST /method/likes.add HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

type=post&owner_id=1&item_id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "likes": 15
    }
}
```
