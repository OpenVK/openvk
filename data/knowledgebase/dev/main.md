OpenVK-KB-Heading: OpenVK API description
# OpenVK API description

OpenVK API is based on VKontakte's API for compatibility. If you want to improve the API, then read [this page](https://github.com/openvk/openvk/blob/master/VKAPI/README.md).

To call the function, you need to go to `{YOUR_DOMAIN}/method/` URL, and then, the function name, for example: `{YOUR_DOMAIN}/method/Account.getProfileInfo`. The server will return JSON data. You can use GET or POST to send the Data.

🔰 above the function name means it requires authorization.

You can read about authorization [there](authorization.md).

## Main params

|Name|Value|Description|
|--|--|--|
|`callback`|string|Sets `Content-Type` header to `application/javascript` and wraps json response into function call. This will allow to bypass CORS limits. Not working with `auth_mechanism`=`roaming`.|
|`forGodSakePleaseDoNotReportAboutMyOnlineActivity`|bool (0, 1)|Do not calls online on some methods|
|`rss`|bool (0, 1)|If 1, returns data in RSS format (works only with wall.get and newsfeed.getGlobal)|

## Tips

- Main instance API URL is `https://ovk.to/method/`

- If there is no description of the method you need, check it on https://dev.vk.com/ru/method

- Don't try to use `execute`, it's not supported yet.

- To set group, add minus to id

## Error

If something goes wrong, the server will return you an error like this:

```json
{
    "error_code": 28,
    "error_msg": "Invalid username or password",
    "request_params":
    [
        {
            "key": "grant_type",
            "value": "password"
        },
        {
            "key": "password",
            "value": "agreatpassword"
        },
        {
            "key": "username",
            "value": "cooluser@cock.li"
        },
        {
            "key": "method",
            "value": "internal.acquireToken"
        },
        {
            "key": "oauth",
            "value": 1
        }
    ]
}
```
