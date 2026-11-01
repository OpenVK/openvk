OpenVK-KB-Heading: users.search

# users.search

Returns a list of users matching search criteria.

### Authorization
This method requires user authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `q` | string | Search query string (user first or last name). |
| `fields` | string | Comma-separated list of profile fields to return. |
| `sex` | integer | Sex: `1` — female, `2` — male, `0` — any. Default is `0`. |
| `status` | integer | Relationship status code. |
| `city` | string | City name. |
| `hometown` | string | Hometown name. |
| `online` | integer | `1` — search only online users. Default is `0`. |
| `offset` | integer | Offset needed to return a specific subset of users. Default is `0`. |
| `count` | integer | Number of users to return. Default is `100`, maximum is `100`. |

### Result
Returns an object with `count` (total number of found users) and `items` (array of user objects).

### Example Response

```json
{
    "response": {
        "count": 1,
        "items": [
            {
                "id": 1,
                "first_name": "Pavel",
                "last_name": "Durov",
                "screen_name": "durov"
            }
        ]
    }
}
```
