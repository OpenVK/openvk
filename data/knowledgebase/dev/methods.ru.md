OpenVK-KB-Heading: Список методов API

# Список методов API

Для вызова любого метода необходимо отправить GET или POST запрос по адресу:
`https://{domain}/method/{method_name}`

---

## account

* **[account.getInfo](/dev/methods/account/getInfo)** — возвращает информацию о текущем аккаунте.
* **[account.setOnline](/dev/methods/account/setOnline)** — помечает текущего пользователя как online.
* **[account.changePassword](/dev/methods/account/changePassword)** — изменяет пароль текущего пользователя.

---

## audio

* **[audio.get](/dev/methods/audio/get)** — возвращает список аудиозаписей пользователя или сообщества.
* **[audio.search](/dev/methods/audio/search)** — возвращает результаты поиска по аудиозаписям.
* **[audio.add](/dev/methods/audio/add)** — копирует аудиозапись на страницу пользователя или сообщества.

---

## users

* **[users.get](/dev/methods/users/get)** — возвращает расширенную информацию о пользователях.
* **[users.search](/dev/methods/users/search)** — возвращает список пользователей в соответствии с заданным критерием поиска.
