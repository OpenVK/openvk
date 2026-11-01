OpenVK-KB-Heading: photos.save

# photos.save

Сохраняет фотографии после их успешной загрузки на сервер через адрес, полученный методом [photos.getUploadServer](/dev/methods/photos/getUploadServer).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `photos_list` | string | **Обязательный параметр.** JSON-строка со списком загруженных файлов, полученная в ответе сервера загрузки. |
| `hash` | string | **Обязательный параметр.** Хеш-подпись, полученная в ответе сервера загрузки. |
| `album_id` | integer | Идентификатор альбома, в который сохраняются фотографии. По умолчанию: `0` (сохранение без альбома). |
| `caption` | string | Текст описания к сохраненным фотографиям. |

### Результат

Возвращает объект с полями:
* `count` (integer) — количество успешно сохраненных фотографий;
* `items` (array) — массив объектов сохраненных фотографий.

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `15` | `Access: Album can't be 'written' by user` — Нет прав на добавление фотографий в указанный альбом. |
| `121` | `Incorrect hash` — Передан неверный хеш загрузки. |
| `129` | `Invalid image file` — Ошибка обработки загруженного файла изображения. |
| `404` | `Invalid album` — Указанный альбом не найден. |

### Пример запроса
```http
POST /method/photos.save HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

album_id=1&photos_list=[{"keyholder":"1","resource":"a1b2c3"}]&hash=d8a1c9e...&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "pid": 1,
                "owner_id": 1,
                "user_id": 1,
                "album_id": 1,
                "aid": 1,
                "width": 1920,
                "height": 1080,
                "text": "Загруженное фото",
                "date": 1609459200,
                "access_key": "",
                "photo_75": "https://openvk.instance/photos/1_1_cropped/miniscule.jpeg",
                "photo_130": "https://openvk.instance/photos/1_1_cropped/tiny.jpeg",
                "photo_604": "https://openvk.instance/photos/1_1_cropped/normal.jpeg",
                "url": "https://openvk.instance/photos/1_1.jpeg"
            }
        ]
    }
}
```
