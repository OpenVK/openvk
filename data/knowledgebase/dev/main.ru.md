OpenVK-KB-Heading: Описание OpenVK API

# Описание OpenVK API

OpenVK API основан на API ВКонтакте для обеспечения совместимости. Если вы хотите улучшить API, ознакомьтесь с [этой страницей](https://github.com/openvk/openvk/blob/master/VKAPI/README.md).

Для вызова функции необходимо перейти по адресу `{ВАШ_ДОМЕН}/method/`, указав далее имя функции, например: `{ВАШ_ДОМЕН}/method/account.getProfileInfo`. Сервер вернет данные в формате JSON. Для отправки данных можно использовать методы GET или POST.

## Основные параметры

| Параметр | Тип | Описание |
| --- | --- | --- |
| `callback` | string | Устанавливает заголовок `Content-Type` в значение `application/javascript` и оборачивает JSON-ответ в вызов функции (JSONP). |
| `forGodSakePleaseDoNotReportAboutMyOnlineActivity` | bool (0, 1) | Отключает обновление онлайн-активности при вызове некоторых методов. |
| `rss` | bool (0, 1) | Если передана `1`, возвращает данные в формате RSS (работает для `wall.get` и `newsfeed.getGlobal`). |

## Советы и примечания

* Основной адрес API инстанса — `https://openvk.instance/method/`
* Если нужный вам метод отсутствует в документации, проверьте его описание на [https://dev.vk.com/ru/method](https://dev.vk.com/ru/method)
* Чтобы указать сообщество (группу), используйте отрицательный ID (с минусом перед числом).

## Ошибки

Если что-то пойдет не так, сервер вернет ошибку следующего вида:

```json
{
    "error_code": 28,
    "error_msg": "Invalid username or password",
    "request_params":
    [
        {
            "key": "grant_type",
            "value": "password"
        },
        {
            "key": "password",
            "value": "agreatpassword"
        },
        {
            "key": "username",
            "value": "cooluser@cock.li"
        },
        {
            "key": "method",
            "value": "internal.acquireToken"
        },
        {
            "key": "oauth",
            "value": 1
        }
    ]
}

```