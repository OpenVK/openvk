OpenVK-KB-Heading: Подключение сайтов и OAuth

# Подключение сайтов и OAuth

OpenVK поддерживает авторизацию пользователей на внешних сайтах и веб-сервисах через протокол **OAuth 2.0**.

С помощью OAuth вы можете реализовать вход через OpenVK на своем сайте, получать данные профиля пользователя и выполнять разрешенные действия через API.

---

## Процесс авторизации (OAuth 2.0 Web Flow)

### 1. Перенаправление пользователя на страницу авторизации

Чтобы запросить авторизацию у пользователя, перенаправьте его браузер на адрес `/authorize`:

```http
GET /authorize?client_name=MyWebsite&redirect_uri=https://example.com/oauth_callback&response_type=php HTTP/1.1
Host: openvk.instance
```

#### Параметры запроса `/authorize`
| Параметр | Тип | Обязательный | Описание |
| --- | --- | --- | --- |
| `client_name` | string | **Да** | Название вашего сайта или сервиса (отображается пользователю в окне подтверждения). |
| `client_id` | integer | Нет | Идентификатор клиента из реестра OpenVK (если используется `client_id`, `client_name` указывать необязательно). |
| `redirect_uri` | string | **Да** | HTTPS/HTTP URL страницы на вашем сайте, куда будет перенаправлен пользователь с токеном. |
| `response_type` | string | Нет | Формат возврата токена: `"php"` (в GET-параметрах запроса, по умолчанию) или `"token"` (в URL-хеше). |
| `revoke` | integer | Нет | `1` — принудительно отозвать старый токен и выпустить новый. |
| `prefers_postMessage` | integer | Нет | `1` — для всплывающих окон (popup). Отправляет токен через `window.opener.postMessage`. |
| `accepts_stale` | integer | Нет | `1` — использовать существующий активный токен пользователя для данного `client_name`, если он уже был создан ранее. |

---

### 2. Подтверждение доступа пользователем

Пользователь видит окно с запросом: «Приложение **MyWebsite** запрашивает доступ к вашему аккаунту».

После нажатия кнопки «Разрешить» сервер OpenVK перенаправляет пользователя на указанный `redirect_uri`.

---

### 3. Получение токена

#### При `response_type=php` (Серверная обработка)
Сервер OpenVK перенаправит пользователя с токеном в GET-параметрах URL:
```
https://example.com/oauth_callback?access_token=a1b2c3d4e5f6...&expires_in=0&user_id=1
```

Ваш бэкенд извлекает `access_token` из параметров запроса и сохраняет его для работы с API.

#### При `response_type=token` (Клиентская / SPA обработка)
Сервер перенаправит пользователя с токеном в фрагменте URL (после знака `#`):
```
https://example.com/oauth_callback#access_token=a1b2c3d4e5f6...&expires_in=0&user_id=1
```

Ваш JavaScript в браузере считывает токен из `window.location.hash`:
```javascript
const hash = window.location.hash.substring(1);
const params = new URLSearchParams(hash);
const accessToken = params.get("access_token");
const userId = params.get("user_id");
```

---

### 4. Авторизация через всплывающее окно (Popup + postMessage)

Если авторизация открывается во всплывающем окне (`window.open`), передайте параметр `prefers_postMessage=1`:

```javascript
// Открытие окна авторизации
const authWindow = window.open(
    "https://openvk.instance/authorize?client_name=MyWebsite&redirect_uri=https://example.com/callback&response_type=php&prefers_postMessage=1",
    "openvk_auth",
    "width=600,height=450"
);

// Получение токена через postMessage
window.addEventListener("message", function(event) {
    if (event.origin === "https://openvk.instance" || event.origin === "https://example.com") {
        if (event.data && event.data.access_token) {
            console.log("Получен токен:", event.data.access_token);
            console.log("User ID:", event.data.user_id);
            if (authWindow) authWindow.close();
        }
    }
});
```

---

## Вызов методов API от имени пользователя

Получив `access_token`, ваш сайт может запрашивать данные профиля и выполнять другие действия через OpenVK API:

```http
GET /method/users.get?user_ids=1&fields=photo_200,sex,bdate&v=5.80 HTTP/1.1
Host: openvk.instance
Authorization: Bearer a1b2c3d4e5f6...
```
