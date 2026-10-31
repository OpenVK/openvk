OpenVK-KB-Heading: Store methods

# Store methods

The **store** section contains methods for managing the digital goods store catalog (sticker packs), showcases, purchasing, activation, keyword suggestions, and favorite/recent stickers.

## Method List

### Catalog and Products
* **[store.getProducts](/dev/methods/store/getProducts)** — returns a list of products (sticker packs) filtered by purchase and active status.
* **[store.getStockItems](/dev/methods/store/getStockItems)** — returns showcase store items (popular, free, all).
* **[store.getStickersKeywords](/dev/methods/store/getStickersKeywords)** — returns dictionary mapping emojis and keywords to stickers.
* **[store.buy](/dev/methods/store/buy)** — purchases a product (sticker pack) using user coins.
* **[store.activateProduct](/dev/methods/store/activateProduct)** — activates a product (adds to quick access in sticker keyboard).
* **[store.deactivateProduct](/dev/methods/store/deactivateProduct)** — deactivates a product (hides from quick access).

### Favorites and Recent
* **[store.getFavoriteStickers](/dev/methods/store/getFavoriteStickers)** — returns user's favorite stickers.
* **[store.addFavoriteSticker](/dev/methods/store/addFavoriteSticker)** — adds a sticker to favorites.
* **[store.removeFavoriteSticker](/dev/methods/store/removeFavoriteSticker)** — removes a sticker from favorites.
* **[store.getRecentStickers](/dev/methods/store/getRecentStickers)** — returns recently used stickers.
* **[store.addRecentSticker](/dev/methods/store/addRecentSticker)** — adds a sticker to recent list.
* **[store.clearRecentStickers](/dev/methods/store/clearRecentStickers)** — clears recent stickers history.
