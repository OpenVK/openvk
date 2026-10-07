OpenVK-KB-Heading: account.getProfileInfo

# account.getProfileInfo

Returns current user profile information for editing or settings display.

### Authorization
This method requires user authorization (`access_token`).

### Parameters
This method does not accept any parameters.

### Result
Returns an object containing detailed user profile information:

| Field | Type | Description |
| --- | --- | --- |
| `id` | integer | User ID. |
| `first_name` | string | First name. |
| `last_name` | string | Last name. |
| `nickname` | string | Nickname. |
| `maiden_name` | string | *(Compatibility stub)* Maiden name (always returns empty string `""`). |
| `screen_name` | string | Short profile address (e.g. `durov` or `id1`). |
| `sex` | integer | Sex: `1` for female, `2` for male, `0` if unspecified. |
| `status` | string | Profile status text. |
| `bdate` | string | Birthday in `D.M.YYYY` format. |
| `bdate_visibility` | integer | Birthday visibility: `1` — full date, `2` — day and month only, `0` — hidden. |
| `home_town` | string | Hometown. |
| `country` | object | *(Compatibility stub)* Country object (hardcoded: `{"id": 1, "title": "Россия"}`). |
| `city` | object | *(Compatibility stub)* City object (hardcoded: `{"id": 1, "title": "—"}`). |
| `phone` | string | *(Compatibility stub)* Masked phone number (`"+420 ** *** 228"`). |
| `relation` | integer | Relationship status: `0` — not specified, `1` — single, `2` — in a relationship, `3` — engaged, `4` — married, `5` — it's complicated, `6` — actively searching, `7` — in love, `8` — in a civil union. |
| `relation_partner` | null | *(Compatibility stub)* Relationship partner (always `null`). |
| `is_verified` | boolean | Whether the profile is verified. |
| `verification_status` | string | Verification status string (`"verified"` or `"unverified"`). |
| `can_create_stickers` | boolean | Whether the user is allowed to create stickers. |
| `is_service_account` | boolean | *(Compatibility stub)* Whether this is a service account (always `false`). |
| `photo_200` | string | URL of the 200x200px profile avatar. |
| `name_request` | null | *(Compatibility stub)* Name change request info (always `null`). |
| `audio_status` | object | Currently broadcasted audio track object (if enabled). |

### Example Request
```http
POST /method/account.getProfileInfo HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "id": 1,
        "first_name": "Pavel",
        "last_name": "Durov",
        "nickname": "",
        "maiden_name": "",
        "screen_name": "durov",
        "sex": 2,
        "status": "VK",
        "bdate": "10.10.1984",
        "bdate_visibility": 1,
        "home_town": "Leningrad",
        "country": {
            "id": 1,
            "title": "Russia"
        },
        "city": {
            "id": 1,
            "title": "—"
        },
        "relation": 0,
        "relation_partner": null,
        "is_verified": true,
        "verification_status": "verified",
        "can_create_stickers": true,
        "is_service_account": false,
        "photo_200": "https://openvk.instance/assets/packages/static/openvk/img/camera_200.png",
        "phone": "+420 ** *** 228",
        "name_request": null
    }
}
```
