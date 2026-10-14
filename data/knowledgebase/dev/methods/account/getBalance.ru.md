OpenVK-KB-Heading: account.getBalance

# account.getBalance

Возвращает баланс текущего пользователя в голосах (монетах).

### Авторизация
Для вызова этого метода требуется авторизация пользователя (`access_token`).

### Параметры
Метод не принимает параметров.

### Результат
Возвращает объект с балансом пользователя:

| Поле | Тип | Описание |
| --- | --- | --- |
| `votes` | integer | Количество голосов (монет) на балансе пользователя. |

### Возможные ошибки

| Код | Сообщение | Описание |
| --- | --- | --- |
| `-105` | `Commerce is disabled on this instance` | Коммерция и голоса отключены в настройках сервера. |

### Пример запроса
```http
POST /method/account.getBalance HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Пример ответа
```json
{
    "response": {
        "votes": 100
    }
}
```
