OpenVK-KB-Heading: ovk.test

# ovk.test

Тестовый метод для проверки связи с API, статуса авторизации и версии протокола.

### Авторизация
Метод является публичным, но возвращает статус авторизации, если передан `access_token`.

### Параметры
Метод не требует обязательных параметров. Для тестирования конкретного механизма авторизации может передаваться параметр `auth_mechanism` (по умолчанию `access_token`).

### Результат

Возвращает объект со следующими полями:
* `authorized` (boolean) — `true`, если запрос выполнен с валидным токеном авторизации пользователя, иначе `false`;
* `auth_with` (string) — используемый механизм авторизации (`access_token`, `cookie` и т.д.);
* `version` (string / float) — объявленная версия протокола VKAPI.

### Пример запроса
```http
POST /method/ovk.test HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "authorized": true,
        "auth_with": "access_token",
        "version": "5.138"
    }
}
```
