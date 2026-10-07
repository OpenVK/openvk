OpenVK-KB-Heading: OpenVK API Overview

# OpenVK API Overview

OpenVK API is compatible with the VK API specification.

To call any API method, send a GET or POST request to:
`https://{domain}/method/{method_name}`

## Common Parameters

| Parameter | Type | Description |
| --- | --- | --- |
| `callback` | string | Sets `Content-Type` header to `application/javascript` and wraps JSON response into a function call (JSONP). |
| `forGodSakePleaseDoNotReportAboutMyOnlineActivity` | bool (0, 1) | Disables online activity update when executing methods. |
| `rss` | bool (0, 1) | If `1`, returns response in RSS format (supported for `wall.get` and `newsfeed.getGlobal`). |

## Tips & Notes

* Base API URL: `https://openvk.instance/method/`
* If a method is not yet covered in this documentation, refer to the official specification at https://dev.vk.com/ru/method
* To specify a community (group), pass its ID as a negative number.

## Errors

In case of an error, the API returns a response structured as follows:

```json
{
    "error": {
        "error_code": 5,
        "error_msg": "User authorization failed: invalid access_token.",
        "request_params": [
            {
                "key": "method",
                "value": "account.getInfo"
            },
            {
                "key": "oauth",
                "value": 1
            }
        ]
    }
}
```