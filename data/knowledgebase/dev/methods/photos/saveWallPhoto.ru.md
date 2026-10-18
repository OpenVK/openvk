OpenVK-KB-Heading: photos.saveWallPhoto

# photos.saveWallPhoto

Сохраняет фотографию для последующего прикрепления к записи на стене после загрузки через адрес [photos.getWallUploadServer](/dev/methods/photos/getWallUploadServer).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `photo` | string | **Обязательный параметр.** Строка с параметрами загруженного изображения, полученная в ответе сервера загрузки. |
| `hash` | string | **Обязательный параметр.** Хеш-подпись, полученная в ответе сервера загрузки. |
| `group_id` | integer | Идентификатор сообщества, если фото загружалось для стены сообщества. По умолчанию: `0`. |
| `caption` | string | Текст описания к фотографии. |
| `server` | integer | Номер сервера загрузки (параметр совместимости). |
| `user_id` | integer | Идентификатор пользователя (параметр совместимости). |
| `wallpost` | integer | Параметр совместимости. По умолчанию: `1`. |

### Результат

Возвращает массив, содержащий объект сохраненной фотографии:

```json
{
    "response": [
        {
            "id": 5,
            "pid": 5,
            "owner_id": 1,
            "user_id": 1,
            "album_id": 2,
            "aid": 2,
            "width": 1280,
            "height": 960,
            "text": "Фотография для стены",
            "date": 1609459200,
            "access_key": "",
            "photo_75": "https://openvk.instance/photos/1_5_cropped/miniscule.jpeg",
            "photo_130": "https://openvk.instance/photos/1_5_cropped/tiny.jpeg",
            "photo_604": "https://openvk.instance/photos/1_5_cropped/normal.jpeg",
            "url": "https://openvk.instance/photos/1_5.jpeg"
        }
    ]
}
```

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `8` | `group_id doesn't match` — Переданный `group_id` не совпадает с данными сессии загрузки. |
| `10` | `Invalid image` — Изображение не найдено во временном хранилище. |
| `121` | `Incorrect hash` — Передан неверный хеш загрузки. |
| `129` | `Invalid image file` — Ошибка при обработке файла изображения. |

### Пример запроса
```http
POST /method/photos.saveWallPhoto HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

group_id=0&photo=1|xyz789|0&hash=d8a1c9e...&caption=Фотография%20на%20стену&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": [
        {
            "id": 5,
            "pid": 5,
            "owner_id": 1,
            "user_id": 1,
            "album_id": 2,
            "aid": 2,
            "width": 1280,
            "height": 960,
            "text": "Фотография на стену",
            "date": 1609459200,
            "access_key": "",
            "photo_75": "https://openvk.instance/photos/1_5_cropped/miniscule.jpeg",
            "photo_130": "https://openvk.instance/photos/1_5_cropped/tiny.jpeg",
            "photo_604": "https://openvk.instance/photos/1_5_cropped/normal.jpeg",
            "url": "https://openvk.instance/photos/1_5.jpeg"
        }
    ]
}
```
