OpenVK-KB-Heading: likes.isLiked

# likes.isLiked

Проверяет, находится ли указанный объект в списке отметок «Мне нравится» заданного пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `user_id` | integer | **Обязательный параметр**. Идентификатор проверяемого пользователя. |
| `type` | string | **Обязательный параметр**. Тип объекта: `"post"` (запись на стене), `"comment"` (комментарий), `"photo"` (фотография), `"video"` (видеозапись), `"note"` (заметка). |
| `owner_id` | integer | **Обязательный параметр**. Идентификатор владельца объекта (пользователя или сообщества). |
| `item_id` | integer | **Обязательный параметр**. Идентификатор самого объекта. |

### Результат

Возвращает объект со следующими полями:
* `liked` (integer) — `1`, если пользователь поставил лайк объекту (или если у пользователя включена приватность скрытия лайков), `0` — если нет;
* `copied` (integer) — `1`, если пользователь сделал репост (или если включена приватность), `0` — если нет.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access denied` — Профиль указанного пользователя недоступен текущему пользователю. |
| `100` | `One of the parameters specified was missing or invalid: user not found` — Пользователь не найден или удален. |
| `100` | `One of the parameters specified was missing or invalid: incorrect type` — Указан некорректный тип объекта. |
| `100` | `One of the parameters specified was missing or invalid: object not found` — Объект не найден или удален. |
| `665` | `Access to postable denied` — Доступ к объекту ограничен для текущего пользователя. |

### Пример запроса
```http
POST /method/likes.isLiked HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

user_id=1&type=post&owner_id=1&item_id=42&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "liked": 1,
        "copied": 0
    }
}
```
