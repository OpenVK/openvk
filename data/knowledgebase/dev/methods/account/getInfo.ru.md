OpenVK-KB-Heading: account.getInfo

# account.getInfo

Возвращает информацию о текущем аккаунте и настройках пользователя.

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Метод не принимает параметров.

### Результат
Возвращает объект, содержащий настройки аккаунта:

| Поле | Тип | Описание |
| --- | --- | --- |
| `2fa_required` | integer | `1`, если включена двухфакторная аутентификация, иначе `0`. |
| `country` | string | *(Заглушка совместимости)* Двухбуквенный код страны (по умолчанию `"CZ"`). |
| `eu_user` | boolean | *(Заглушка совместимости)* Находится ли пользователь в ЕС (всегда `false`). |
| `https_required` | integer | *(Заглушка совместимости)* `1`, если требуется HTTPS (всегда `1`). |
| `phone` | string | *(Заглушка совместимости)* Номер телефона (всегда `""`). |
| `link_redirects` | string | *(Заглушка совместимости)* Настройки перенаправлений (всегда `"{}"`). |
| `intro` | integer | *(Заглушка совместимости)* Битовая маска вводного руководства (всегда `0`). |
| `community_comments` | boolean | *(Заглушка совместимости)* Комментарии от сообществ (всегда `false`). |
| `is_live_streaming_enabled` | boolean | *(Заглушка совместимости)* Прямые трансляции (всегда `false`). |
| `is_new_live_streaming_enabled` | boolean | *(Заглушка совместимости)* Новый формат трансляций (всегда `false`). |
| `lang` | integer | *(Заглушка совместимости)* Идентификатор языка (всегда `1`). |
| `no_wall_replies` | integer | *(Заглушка совместимости)* Отключение комментариев на стене (всегда `0`). |
| `own_posts_default` | integer | *(Заглушка совместимости)* Режим показа только своих записей (всегда `0`). |
| `music_available` | boolean | `true`, если пользователю доступен раздел музыки. |

### Пример запроса
```http
POST /method/account.getInfo HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "2fa_required": 0,
        "country": "CZ",
        "eu_user": false,
        "https_required": 1,
        "phone": "",
        "link_redirects": "{}",
        "intro": 0,
        "community_comments": false,
        "is_live_streaming_enabled": false,
        "is_new_live_streaming_enabled": false,
        "lang": 1,
        "no_wall_replies": 0,
        "own_posts_default": 0,
        "music_available": true
    }
}
```
