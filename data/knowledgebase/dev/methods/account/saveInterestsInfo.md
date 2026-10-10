OpenVK-KB-Heading: account.saveInterestsInfo

# account.saveInterestsInfo

Edits interests, activities, and hobby details of the current user.

### Authorization
This method requires user authorization (`access_token`). Rate limits for write actions apply.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `interests` | string | Activities and interests. |
| `fav_music` | string | Favorite music. |
| `fav_films` | string | Favorite movies. |
| `fav_shows` | string | Favorite TV shows. |
| `fav_books` | string | Favorite books. |
| `fav_quote` | string | Favorite quotes. |
| `fav_games` | string | Favorite games. |
| `about` | string | About me. |

### Result
Returns an object indicating the result of the update:

| Field | Type | Description |
| --- | --- | --- |
| `changed` | integer | `1` if profile data was updated, `0` if no changes occurred. |

### Example Request
```http
POST /method/account.saveInterestsInfo HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

interests=Programming%2C+music&fav_music=Synthwave&about=Developer&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "changed": 1
    }
}
```
