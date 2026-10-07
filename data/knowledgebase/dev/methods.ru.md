OpenVK-KB-Heading: Список методов API

# Список методов API

Для вызова любого метода необходимо отправить GET или POST запрос по адресу:
`https://{domain}/method/{method_name}`

## account

* **[account.ban](/dev/methods/account/ban)** — добавляет пользователя в черный список.
* **[account.unban](/dev/methods/account/unban)** — удаляет пользователя из черного списка.
* **[account.getBanned](/dev/methods/account/getBanned)** — возвращает список заблокированных пользователей.
* **[account.getBalance](/dev/methods/account/getBalance)** — возвращает баланс голосов (монет) текущего пользователя.
* **[account.getCounters](/dev/methods/account/getCounters)** — возвращает счетчики непрочитанных сообщений, уведомлений и заявок.
* **[account.getInfo](/dev/methods/account/getInfo)** — возвращает общую информацию о текущем аккаунте и настройках.
* **[account.getProfileInfo](/dev/methods/account/getProfileInfo)** — возвращает расширенную информацию о профиле пользователя.
* **[account.getOvkSettings](/dev/methods/account/getOvkSettings)** — возвращает специфичные настройки интерфейса OpenVK.
* **[account.getViewerId](/dev/methods/account/getViewerId)** — возвращает идентификатор текущего пользователя.
* **[account.getAppPermissions](/dev/methods/account/getAppPermissions)** — возвращает битовую маску настроек и прав приложения.
* **[account.saveProfileInfo](/dev/methods/account/saveProfileInfo)** — сохраняет основную информацию профиля.
* **[account.saveInterestsInfo](/dev/methods/account/saveInterestsInfo)** — сохраняет информацию об интересах и увлечениях.
* **[account.sendVotes](/dev/methods/account/sendVotes)** — передает голоса другому пользователю.
* **[account.setOnline](/dev/methods/account/setOnline)** — помечает текущего пользователя как online на 5 минут.
* **[account.setOffline](/dev/methods/account/setOffline)** — помечает текущего пользователя как offline.
* **[account.registerDevice](/dev/methods/account/registerDevice)** — регистрирует устройство для получения Push-уведомлений.
* **[account.unregisterDevice](/dev/methods/account/unregisterDevice)** — отменяет регистрацию устройства для Push-уведомлений.
* **[account.setSilenceMode](/dev/methods/account/setSilenceMode)** — настраивает беззвучный режим уведомлений.
* **[account.getPushSettings](/dev/methods/account/getPushSettings)** — возвращает настройки Push-уведомлений.
* **[account.get](/dev/methods/account/get)** — возвращает базовую структуру данных аккаунта.
* **[account.getMulti](/dev/methods/account/getMulti)** — возвращает информацию о сессии мультиаккаунта.
* **[account.getPrivacySettings](/dev/methods/account/getPrivacySettings)** — возвращает структуру настроек приватности.
* **[account.getContactList](/dev/methods/account/getContactList)** — возвращает телефонную книгу контактов.
* **[account.getHelpHints](/dev/methods/account/getHelpHints)** — возвращает подсказки справки.
* **[account.getBadgesSettings](/dev/methods/account/getBadgesSettings)** — возвращает настройки значков приложения.
* **[account.getToggles](/dev/methods/account/getToggles)** — возвращает состояние переключателей функций (feature toggles).

## audio

* **[audio.get](/dev/methods/audio/get)** — возвращает список аудиозаписей пользователя или сообщества.
* **[audio.search](/dev/methods/audio/search)** — возвращает результаты поиска по аудиозаписям.
* **[audio.add](/dev/methods/audio/add)** — копирует аудиозапись на страницу пользователя или сообщества.

## users

* **[users.get](/dev/methods/users/get)** — возвращает расширенную информацию о пользователях.
* **[users.search](/dev/methods/users/search)** — возвращает список пользователей в соответствии с заданным критерием поиска.
