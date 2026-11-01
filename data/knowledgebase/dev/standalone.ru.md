OpenVK-KB-Heading: Mobile и Standalone приложения

# Mobile и Standalone приложения

Раздел посвящен разработке клиентских приложений (мобильных, десктопных, CLI-утилит, консольных ботов), которые работают с OpenVK API без необходимости иметь собственный публичный веб-сервер.

---

## Способы авторизации

Для получения ключа доступа (`access_token`) поддерживается несколько механизмов авторизации:

### 1. Прямая авторизация по логину и паролю (Password Flow / Direct Auth)

Самый быстрый и удобный способ для мобильных и консольных клиентов.

Для получения токена отправьте HTTPS GET или POST запрос на эндпоинт `/token`:

```http
POST /token HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

username=user@example.com&password=your_password&grant_type=password&client_name=MyClient
```

#### Параметры запроса
| Параметр | Тип | Обязательный | Описание |
| --- | --- | --- | --- |
| `grant_type` | string | **Да** | Всегда значение `"password"`. |
| `username` | string | **Да** | E-mail или номер телефона аккаунта. |
| `password` | string | **Да** | Пароль пользователя. |
| `client_name` | string | Нет | Имя вашего приложения (отображается в статусе онлайн и в записях). |
| `client_id` | integer | Нет | Идентификатор клиента из реестра `clients.xml` (для распознавания платформы). |
| `code` | string | Нет | Одноразовый код двухфакторной аутентификации (TOTP или резервный код), если 2FA включена. |
| `accepts_stale` | integer | Нет | `1` — переиспользовать ранее выданный токен для данного `client_name` (работает только с явно указанным `client_name`). |

#### Пример успешного ответа
```json
{
    "access_token": "a1b2c3d4e5f6...",
    "expires_in": 0,
    "user_id": 1,
    "is_stale": false,
    "secret": "super_secret_value"
}
```

> **Примечание:** `expires_in: 0` означает, что выданный токен бессрочный и действует до смены пароля или ручного отзыва сессий в настройках безопасности.

---

### 2. Авторизация через OAuth 2.0 (Implicit Flow)

Подходит для приложений с графическим интерфейсом и встроенным браузером (WebView).

1. Откройте в браузере или WebView страницу авторизации:
   ```
   https://openvk.instance/authorize?client_name=MyClient&redirect_uri=https://openvk.instance/blank.html&response_type=token&display=page
   ```

2. После подтверждения прав пользователь будет перенаправлен на адрес:
   ```
   https://openvk.instance/blank.html#access_token=a1b2c3d4e5f6...&expires_in=0&user_id=1
   ```

3. Ваше приложение перехватывает URL перенаправления и извлекает `access_token` из хэш-фрагмента (`#`).

#### Дополнительные параметры `/authorize`
* `response_type` — `"token"` (возврат в URL-хеше) или `"php"` (возврат в GET-параметрах).
* `revoke=1` — принудительно отозвать предыдущий токен перед выпуском нового.
* `display` — тип отображения окна (`"page"`, `"popup"`, `"mobile"`).

---

### 3. Режим Roaming (для тестирования)

Если вы авторизованы в браузере на сайте OpenVK, вы можете выполнять запросы к API без получения токена, добавив параметр `auth_mechanism=roaming`:

```http
GET /method/users.get?v=5.80&auth_mechanism=roaming HTTP/1.1
Host: openvk.instance
Cookie: chandler_session=...
```

---

## Использование токена в запросах к API

Полученный `access_token` можно передавать:

1. **HTTP-заголовок Authorization (Рекомендуется):**
   ```http
   GET /method/account.getProfileInfo?v=5.80 HTTP/1.1
   Host: openvk.instance
   Authorization: Bearer a1b2c3d4e5f6...
   ```

2. **GET/POST параметр `access_token` или `sid`:**
   ```http
   GET /method/account.getProfileInfo?v=5.80&access_token=a1b2c3d4e5f6... HTTP/1.1
   Host: openvk.instance
   ```

---

## Реестр клиентов и Client ID

OpenVK содержит встроенный реестр клиентов (`clients.xml`) для распознавания приложений и отображения их иконок:

### Клиенты OpenVK
| Client ID | Тег | Название клиента | Платформа |
| --- | --- | --- | --- |
| `10001` | `openvk_legacy_android` | OpenVK Legacy | Android |
| `10002` | `openvk_refresh_android` | OpenVK Refresh | Android |
| `10003` | `openvk_flux_android` | OpenVK Flux | Android |
| `10004` | `openvk_native` | OpenVK Native | Android |
| `10005` | `openvk_ios` | OpenVK for iOS | iOS |
| `10006` | `vk4me` | VK4ME | J2ME |
| `10008` | `Matcha` | Matcha | Android |

### Совместимость с клиентами VK
| Client ID | Тег | Название клиента | Платформа |
| --- | --- | --- | --- |
| `2274003` | `vk_android` | VK for Android | Android |
| `2685278` | `Kate Mobile` | Kate Mobile | Android |
| `3034484` | `vk_ipad` | VK for iPad | iOS |
| `3140623` | `vk_iphone` | VK for iPhone | iOS |
| `3502561` | `vk_windows_8` | VK for Windows 8 | Windows |
| `3680547` | `vk_ios` | VK for iOS | iOS |
| `3697615` | `vk_windows` | VK for Windows | Windows |
| `4083558` | `VFeed` | VFeed | iOS |
| `5027722` | `vk_wphone` | VK for Windows Phone | Windows Phone |
| `5030499` | `vk_messenger` | VK Messenger | Desktop |
| `6146827` | `vk_me` | VK Me | Android |

---

## Отзыв токенов и безопасность

Пользователь в любой момент может прекратить действие всех выданных токенов через веб-интерфейс:
**Настройки** &rarr; **Безопасность** &rarr; кнопка **«Завершить все сеансы»**.
