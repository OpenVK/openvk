OpenVK-KB-Heading: users.get

# users.get

Returns detailed information about users.

### Authorization
This method can be called without authorization.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `user_ids` | string | User IDs or screen names separated by commas. Defaults to current user ID if authorized. |
| `fields` | string | Comma-separated list of profile fields to return (e.g. `photo_200`, `sex`, `bdate`, `city`, `country`, `status`, `online`, `verified`). |
| `name_case` | string | Case for declension of user name and surname: `nom` — nominative (default), `gen` — genitive, `dat` — dative, `acc` — accusative, `ins` — instrumental, `abl` — prepositional. |

### Result
Returns an array of user objects. Each basic user object contains:
* `id` — user ID;
* `first_name` — first name;
* `last_name` — last name.

### Example Response

```json
{
    "response": [
        {
            "id": 1,
            "first_name": "Pavel",
            "last_name": "Durov",
            "screen_name": "durov",
            "photo_200": "https://ovk.to/assets/packages/static/openvk/img/camera_200.png",
            "online": 1
        }
    ]
}
```
