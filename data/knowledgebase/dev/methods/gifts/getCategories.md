OpenVK-KB-Heading: gifts.getCategories

# gifts.getCategories

Returns the list of gift categories in the catalog.

### Authorization
This method requires user authorization (`access_token`).

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `extended` | boolean | `1` to return localized category names and descriptions for all supported platform languages, `0` for default data only. Default: `0`. |
| `page` | integer | Category page number. Default: `1`. |

### Result

Returns an array of gift category objects. Each object contains:
| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | Category ID. |
| `name` | string | Category title. |
| `description` | string | Category description. |
| `thumbnail` | string | Category thumbnail image URL. |
| `localizations` | object | Object mapping language codes to translated names and descriptions (if `extended=1`). |

### Possible Errors

| Code | Description |
| --- | --- |
| `-105` | `Commerce is disabled on this instance` — Commerce / votes system is disabled on this instance. |
| `5` | `User authorization failed: no access_token passed.` — User authorization failed. |

### Request Example
```http
POST /method/gifts.getCategories HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

extended=1&page=1&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Response Example
```json
{
    "response": [
        {
            "id": 1,
            "name": "Birthday",
            "description": "Birthday gifts",
            "thumbnail": "https://openvk.instance/assets/packages/static/openvk/img/gift_cats/1.png",
            "localizations": {
                "ru": {
                    "name": "День рождения",
                    "desc": "Подарки ко дню рождения"
                },
                "en": {
                    "name": "Birthday",
                    "desc": "Birthday gifts"
                }
            }
        }
    ]
}
```
