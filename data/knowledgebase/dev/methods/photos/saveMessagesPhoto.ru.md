OpenVK-KB-Heading: photos.saveMessagesPhoto

# photos.saveMessagesPhoto

Сохраняет фотографию для отправки в личном сообщении после загрузки через адрес [photos.getMessagesUploadServer](/dev/methods/photos/getMessagesUploadServer).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos` или `messages`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `photo` | string | **Обязательный параметр.** Строка с метаданными загруженного изображения из ответа сервера загрузки. |
| `hash` | string | **Обязательный параметр.** Хеш-подпись из ответа сервера загрузки. |
| `server` | mixed | Номер сервера загрузки (параметр совместимости). |

### Результат

Возвращает массив, содержащий объект сохраненной фотографии сообщения:

```json
{
    "response": [
        {
            "id": 8,
            "pid": 8,
            "owner_id": 1,
            "user_id": 1,
            "album_id": -3,
            "aid": -3,
            "width": 1024,
            "height": 768,
            "text": "",
            "date": 1609459200,
            "access_key": "a1b2c3d4e5f6",
            "photo_75": "https://openvk.instance/photos/1_8_cropped/miniscule.jpeg",
            "photo_130": "https://openvk.instance/photos/1_8_cropped/tiny.jpeg",
            "photo_604": "https://openvk.instance/photos/1_8_cropped/normal.jpeg",
            "url": "https://openvk.instance/photos/1_8.jpeg"
        }
    ]
}
```

> **Примечание:** Для прикрепления полученной фотографии к сообщению через метод [messages.send](/dev/methods/messages/send) используйте строку вложения в формате `photo<owner_id>_<id>` (или с ключом доступа `photo<owner_id>_<id>_<access_key>`).

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `121` | `Incorrect hash` — Передан неверный хеш загрузки. |
| `129` | `Invalid image file` — Ошибка при обработке файла изображения. |

### Пример запроса
```http
POST /method/photos.saveMessagesPhoto HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

photo=1|msg_img_1|0&hash=d8a1c9e...&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": [
        {
            "id": 8,
            "pid": 8,
            "owner_id": 1,
            "user_id": 1,
            "album_id": -3,
            "aid": -3,
            "width": 1024,
            "height": 768,
            "text": "",
            "date": 1609459200,
            "access_key": "a1b2c3d4e5f6",
            "photo_75": "https://openvk.instance/photos/1_8_cropped/miniscule.jpeg",
            "photo_130": "https://openvk.instance/photos/1_8_cropped/tiny.jpeg",
            "photo_604": "https://openvk.instance/photos/1_8_cropped/normal.jpeg",
            "url": "https://openvk.instance/photos/1_8.jpeg"
        }
    ]
}
```
