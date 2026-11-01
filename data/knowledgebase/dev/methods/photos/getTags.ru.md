OpenVK-KB-Heading: photos.getTags

# photos.getTags

Возвращает список отметок пользователей на фотографии.

> **Примечание:** В текущей версии OpenVK функциональность отметок людей на фото не реализована, метод всегда возвращает пустой массив.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `owner_id` | integer | Идентификатор владельца фотографии. По умолчанию: `0`. |
| `photo_id` | integer | Идентификатор фотографии. |
| `pid` | integer | Альтернативный идентификатор фотографии. |

### Результат

Возвращает пустой массив `[]`.

### Пример запроса
```http
POST /method/photos.getTags HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

owner_id=1&photo_id=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": []
}
```
