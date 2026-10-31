OpenVK-KB-Heading: photos.delete

# photos.delete

Удаляет одну или несколько фотографий.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | Идентификатор владельца фотографии. По умолчанию: ID текущего пользователя. |
| `photo_id` | integer | Идентификатор фотографии (для удаления одной фотографии). |
| `photos` | string | Список идентификаторов фотографий через запятую в формате `owner_id_photo_id` (максимум 10 штук). Если передан, параметр `photo_id` игнорируется. |

### Результат

Возвращает `1` в случае успешного удаления фотографий.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `-78` | `Photos count must not exceed limit` — Превышен лимит на количество удаляемых фотографий (более 10). |

### Пример запроса
```http
POST /method/photos.delete HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&photo_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": 1
}
```
