OpenVK-KB-Heading: account.getOvkSettings

# account.getOvkSettings

Returns OpenVK-specific user interface and display preferences for the current user.

### Authorization
This method requires user authorization (`access_token`).

### Parameters
This method does not accept any parameters.

### Result
Returns an object with OpenVK preferences:

| Field | Type | Description |
| --- | --- | --- |
| `avatar_style` | integer | Avatar style preference: `0` for square, `1` for round. |
| `style` | string | Interface theme identifier (e.g. `classic`, `2016`). |
| `show_rating` | boolean | Whether to display the rating bar on the profile (`true` / `false`). |
| `nsfw_tolerance` | integer | NSFW content tolerance level. |
| `post_view` | string | Wall display mode: `"microblog"` or `"old"` style. |
| `main_page` | string | Default landing page upon opening: `"my_page"` or `"news"`. |

### Example Request
```http
POST /method/account.getOvkSettings HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

access_token=YOUR_ACCESS_TOKEN&v=5.138
```

### Example Response
```json
{
    "response": {
        "avatar_style": 0,
        "style": "classic",
        "show_rating": true,
        "nsfw_tolerance": 0,
        "post_view": "microblog",
        "main_page": "my_page"
    }
}
```
