OpenVK-KB-Heading: account.saveProfileInfo

# account.saveProfileInfo

Edits basic profile information of the current user.

### Authorization
This method requires user authorization (`access_token`). Rate limits for write actions apply.

### Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `first_name` | string | User's first name. When updating name, changes apply immediately and return a `name_request` object with `"success"` status for compatibility with VK clients. |
| `last_name` | string | User's last name. |
| `screen_name` | string | Short profile domain address (e.g. `durov`). |
| `sex` | integer | Sex: `1` for female, `2` for male. |
| `relation` | integer | Relationship status: `0` — unspecified, `1` — single, `2` — in a relationship, `3` — engaged, `4` — married, `5` — it's complicated, `6` — actively searching, `7` — in love, `8` — in a civil union. |
| `bdate` | string | Birthday (in `DD.MM.YYYY` or `YYYY-MM-DD` format). |
| `bdate_visibility` | integer | Birthday visibility: `1` — full date, `2` — day and month only, `0` — hidden (hiding birthday is not supported in OpenVK and returns error `946`). |
| `home_town` | string | Hometown. |
| `status` | string | Profile status text. |
| `telegram` | string | Telegram username or link. |

### Result
Returns an object containing:

| Field | Type | Description |
| --- | --- | --- |
| `changed` | integer | `1` if profile information was changed, `0` otherwise. |
| `name_request` | object\|null | *(Optional)* Name change request emulation object (`id`, `status`, `first_name`, `last_name`). |

### Errors

| Code | Message | Description |
| --- | --- | --- |
| `100` | `invalid value of bdate.` | Invalid date of birth value or format. |
| `1260` | `Invalid screen name` | Invalid short profile domain address. |
| `946` | `Hiding date of birth is not implemented.` | Attempting to hide date of birth (`bdate_visibility = 0`). |

### Example Request
```http
POST /method/account.saveProfileInfo HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

status=Working+on+OpenVK&screen_name=durov&access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "changed": 1
    }
}
```
