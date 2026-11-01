OpenVK-KB-Heading: Методы секции account

# Методы секции account

Секция **account** содержит методы для управления настройками аккаунта текущего пользователя, изменения профиля, работы с черным списком, балансом голосов и уведомлениями.

### Профиль и информация
* **[account.getProfileInfo](/dev/methods/account/getProfileInfo)** — возвращает расширенную информацию о профиле текущего пользователя.
* **[account.saveProfileInfo](/dev/methods/account/saveProfileInfo)** — сохраняет основные данные профиля (имя, фамилия, пол, дата рождения, статус и др.).
* **[account.saveInterestsInfo](/dev/methods/account/saveInterestsInfo)** — сохраняет интересы и увлечения (музыка, фильмы, книги и др.).
* **[account.getInfo](/dev/methods/account/getInfo)** — возвращает общую информацию об аккаунте и настройках.
* **[account.getOvkSettings](/dev/methods/account/getOvkSettings)** — возвращает специфичные настройки интерфейса OpenVK (тема, стиль аватарок, режим стены).
* **[account.getViewerId](/dev/methods/account/getViewerId)** — возвращает идентификатор текущего пользователя.
* **[account.getAppPermissions](/dev/methods/account/getAppPermissions)** — возвращает битовую маску прав приложения.
* **[account.get](/dev/methods/account/get)** — возвращает базовую структуру данных аккаунта.
* **[account.getMulti](/dev/methods/account/getMulti)** — возвращает информацию о текущем мультиаккаунте.

### Статус и активность
* **[account.setOnline](/dev/methods/account/setOnline)** — помечает пользователя как онлайн на 5 минут.
* **[account.setOffline](/dev/methods/account/setOffline)** — переводит статус пользователя в оффлайн.
* **[account.getCounters](/dev/methods/account/getCounters)** — возвращает счетчики непрочитанных сообщений, уведомлений и заявок в друзья.

### Черный список
* **[account.ban](/dev/methods/account/ban)** — добавляет пользователя в черный список.
* **[account.unban](/dev/methods/account/unban)** — удаляет пользователя из черного списка.
* **[account.getBanned](/dev/methods/account/getBanned)** — возвращает список заблокированных пользователей.

### Баланс и переводы
* **[account.getBalance](/dev/methods/account/getBalance)** — возвращает баланс голосов (монет) текущего пользователя.
* **[account.sendVotes](/dev/methods/account/sendVotes)** — переводит голоса другому пользователю.

### Уведомления и устройства
* **[account.registerDevice](/dev/methods/account/registerDevice)** — регистрирует устройство для получения Push-уведомлений.
* **[account.unregisterDevice](/dev/methods/account/unregisterDevice)** — отменяет регистрацию устройства.
* **[account.setSilenceMode](/dev/methods/account/setSilenceMode)** — настраивает беззвучный режим для уведомлений.
* **[account.getPushSettings](/dev/methods/account/getPushSettings)** — возвращает настройки Push-уведомлений.

### Приватность и вспомогательные методы
* **[account.getPrivacySettings](/dev/methods/account/getPrivacySettings)** — возвращает настройки приватности.
* **[account.getContactList](/dev/methods/account/getContactList)** — возвращает телефонную книгу контактов.
* **[account.getHelpHints](/dev/methods/account/getHelpHints)** — возвращает подсказки справки.
* **[account.getBadgesSettings](/dev/methods/account/getBadgesSettings)** — возвращает настройки значков.
* **[account.getToggles](/dev/methods/account/getToggles)** — возвращает активные переключатели функций (feature toggles).
