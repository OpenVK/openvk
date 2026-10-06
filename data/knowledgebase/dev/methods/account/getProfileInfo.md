OpenVK-KB-Heading: account.getProfileInfo

# account.getProfileInfo

Returns current user profile information for editing or settings display.

### Authorization
This method requires user authorization.

### Parameters
This method does not accept any parameters.

### Result
Returns an object containing user profile details:

* `id` — user ID;
* `first_name` — first name;
* `last_name` — last name;
* `nickname` — nickname;
* `screen_name` — screen name (e.g. `durov` or `id1`);
* `sex` — sex: `1` for female, `2` for male, `0` if not specified;
* `status` — profile text status;
* `bdate` — birthday in `D.M.YYYY` or `D.M` format;
* `bdate_visibility` — birthday privacy mode;
* `home_town` — hometown;
* `relation` — marital status code;
* `is_verified` — verified badge status (`true`/`false`);
* `photo_200` — 200x200px profile avatar URL.

### Example Response

```json
{
    "response": {
        "id": 1,
        "first_name": "Pavel",
        "last_name": "Durov",
        "nickname": "",
        "screen_name": "durov",
        "sex": 2,
        "status": "VK",
        "bdate": "10.10.1984",
        "bdate_visibility": 1,
        "home_town": "Leningrad",
        "is_verified": true,
        "photo_200": "https://ovk.to/assets/packages/static/openvk/img/camera_200.png"
    }
}
```
