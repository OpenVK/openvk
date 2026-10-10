OpenVK-KB-Heading: account.getInfo

# account.getInfo

Returns information about the current user account and settings.

### Authorization
This method requires user authorization (`access_token`).

### Parameters
This method does not accept any parameters.

### Result
Returns an object containing account settings and information:

| Field | Type | Description |
| --- | --- | --- |
| `2fa_required` | integer | `1` if two-factor authentication is enabled, `0` otherwise. |
| `country` | string | *(Compatibility stub)* ISO country code (defaults to `"CZ"`). |
| `eu_user` | boolean | *(Compatibility stub)* Whether user is in EU (always `false`). |
| `https_required` | integer | *(Compatibility stub)* Whether HTTPS is required (always `1`). |
| `phone` | string | *(Compatibility stub)* User's phone number (always `""`). |
| `link_redirects` | string | *(Compatibility stub)* Link redirect preferences (always `"{}"`). |
| `intro` | integer | *(Compatibility stub)* Completed onboarding bitmask (always `0`). |
| `community_comments` | boolean | *(Compatibility stub)* Community comments allowed (always `false`). |
| `is_live_streaming_enabled` | boolean | *(Compatibility stub)* Live streaming enabled (always `false`). |
| `is_new_live_streaming_enabled` | boolean | *(Compatibility stub)* New live streaming enabled (always `false`). |
| `lang` | integer | *(Compatibility stub)* Interface language ID (always `1`). |
| `no_wall_replies` | integer | *(Compatibility stub)* Wall comments disabled (always `0`). |
| `own_posts_default` | integer | *(Compatibility stub)* Own posts only by default (always `0`). |
| `music_available` | boolean | `true` if music section is accessible for the user. |

### Example Request
```http
POST /method/account.getInfo HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "2fa_required": 0,
        "country": "CZ",
        "eu_user": false,
        "https_required": 1,
        "phone": "",
        "link_redirects": "{}",
        "intro": 0,
        "community_comments": false,
        "is_live_streaming_enabled": false,
        "is_new_live_streaming_enabled": false,
        "lang": 1,
        "no_wall_replies": 0,
        "own_posts_default": 0,
        "music_available": true
    }
}
```
