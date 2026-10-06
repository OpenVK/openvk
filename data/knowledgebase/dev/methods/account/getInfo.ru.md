OpenVK-KB-Heading: account.getInfo

# account.getInfo

Возвращает информацию о текущем аккаунте.

### Авторизация
Для вызова этого метода Ваше приложение должно иметь права с битовой маской `account` или быть авторизованным пользователем.

### Параметры
Параметры отсутствуют.

### Результат
Возвращает объект, содержащий настройки аккаунта:

* `2fa_required` — `1`, если включена двухфакторная аутентификация.
* `country` — код страны пользователя.
* `https_required` — `1`, если требуется подключение по HTTPS.
* `intro` — битовая маска пройденного введения.
* `lang` — идентификатор языка интерфейса.
* `music_available` — `true`, если доступен раздел музыки.
* `own_posts_default` — `1`, если по умолчанию на стене показываются только свои записи.
* `no_wall_replies` — `1`, если отключены комментарии на стене.

### Пример ответа

```json
{
    "response": {
        "2fa_required": 0,
        "country": "RU",
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
