OpenVK-KB-Heading: photos.saveOwnerPhoto

# photos.saveOwnerPhoto

Сохраняет главную фотографию (аватар) пользователя или сообщества после загрузки через адрес [photos.getOwnerPhotoUploadServer](/dev/methods/photos/getOwnerPhotoUploadServer).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`) с правами доступа `photos`.

### Параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `photo` | string | **Обязательный параметр.** Строка с параметрами загруженного изображения, полученная в ответе сервера загрузки. |
| `hash` | string | **Обязательный параметр.** Хеш-подпись, полученная в ответе сервера загрузки. |

### Результат

Возвращает объект с полями:
| Поле | Тип | Описание |
| --- | --- | --- |
| `photo_hash` | null | Зарезервированное поле. |
| `photo_src` | string | URL загруженной и установленной главной фотографии. |

### Возможные ошибки

| Код | Описание |
| --- | --- |
| `5` | `User authorization failed: no access_token passed.` — Пользователь не авторизован. |
| `10` | `Invalid image` — Файл изображения не найден во временном хранилище. |
| `121` | `Incorrect hash` — Передан неверный хеш загрузки. |
| `129` | `Invalid image file` — Ошибка при обработке файла изображения. |

### Пример запроса
```http
POST /method/photos.saveOwnerPhoto HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

photo=1|a1b2c3|0&hash=d8a1c9e...&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "photo_hash": null,
        "photo_src": "https://openvk.instance/photos/1_1.jpeg"
    }
}
```
