OpenVK-KB-Heading: Методы store

# Методы store

Секция **store** содержит методы для работы с каталогом магазина цифровых товаров платформы (стикерпаков), витриной, покупкой, активацией товаров и управлением подсказками/избранным.

## Список методов

### Каталог и товары
* **[store.getProducts](/dev/methods/store/getProducts)** — возвращает список товаров (стикерпаков) с фильтрацией по статусу покупки и активности.
* **[store.getStockItems](/dev/methods/store/getStockItems)** — возвращает витрину товаров магазина (популярные, бесплатные, все).
* **[store.getStickersKeywords](/dev/methods/store/getStickersKeywords)** — возвращает словарь сопоставления эмодзи и ключевых слов со стикерами.
* **[store.buy](/dev/methods/store/buy)** — приобретает товар (стикерпак) за монеты с баланса пользователя.
* **[store.activateProduct](/dev/methods/store/activateProduct)** — активирует товар (добавляет в быстрый доступ на клавиатуре).
* **[store.deactivateProduct](/dev/methods/store/deactivateProduct)** — деактивирует товар (скрывает из быстрого доступа).

### Избранное и недавнее
* **[store.getFavoriteStickers](/dev/methods/store/getFavoriteStickers)** — возвращает список избранных стикеров пользователя.
* **[store.addFavoriteSticker](/dev/methods/store/addFavoriteSticker)** — добавляет стикер в избранное.
* **[store.removeFavoriteSticker](/dev/methods/store/removeFavoriteSticker)** — удаляет стикер из избранного.
* **[store.getRecentStickers](/dev/methods/store/getRecentStickers)** — возвращает список недавно использованных стикеров.
* **[store.addRecentSticker](/dev/methods/store/addRecentSticker)** — добавляет стикер в недавние.
* **[store.clearRecentStickers](/dev/methods/store/clearRecentStickers)** — очищает список недавно использованных стикеров.
