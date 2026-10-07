OpenVK-KB-Heading: Mobile and Standalone Apps

# Mobile and Standalone Apps

This section covers the development of client applications (mobile apps, desktop clients, bots, CLI tools) interacting with the OpenVK API without needing a dedicated public web server.

---

## Authorization Methods

Several authorization flows are available to obtain an `access_token`:

### 1. Direct Password Authorization (Direct Auth / Password Flow)

The fastest and most straightforward method for mobile and console applications.

To obtain a token, send an HTTPS GET or POST request to the `/token` endpoint:

```http
POST /token HTTP/1.1
Host: openvk.instance
Content-Type: application/x-www-form-urlencoded

username=user@example.com&password=your_password&grant_type=password&client_name=MyClient
```

#### Request Parameters
| Parameter | Type | Required | Description |
| --- | --- | --- | --- |
| `grant_type` | string | **Yes** | Always `"password"`. |
| `username` | string | **Yes** | User email address or phone number. |
| `password` | string | **Yes** | User password. |
| `client_name` | string | No | Application name (displayed in online status and wall posts). |
| `client_id` | integer | No | Client ID from the `clients.xml` registry for platform detection. |
| `code` | string | No | One-time 2FA code (TOTP or backup code) if two-factor authentication is enabled. |
| `accepts_stale` | integer | No | `1` — reuse a previously issued active token for this `client_name` (requires explicit `client_name`). |

#### Successful Response Example
```json
{
    "access_token": "a1b2c3d4e5f6...",
    "expires_in": 0,
    "user_id": 1,
    "is_stale": false,
    "secret": "super_secret_value"
}
```

> **Note:** `expires_in: 0` indicates that the token is perpetual and remains valid until the user changes password or revokes sessions in account settings.

---

### 2. OAuth 2.0 Authorization (Implicit Flow)

Recommended for applications with GUI and embedded browser (WebView).

1. Open the authorization URL in browser or WebView:
   ```
   https://openvk.instance/authorize?client_name=MyClient&redirect_uri=https://openvk.instance/blank.html&response_type=token&display=page
   ```

2. After user approval, the browser is redirected to:
   ```
   https://openvk.instance/blank.html#access_token=a1b2c3d4e5f6...&expires_in=0&user_id=1
   ```

3. Your application intercepts the redirect URL and parses the `access_token` from the hash fragment (`#`).

#### Additional `/authorize` Parameters
* `response_type` — `"token"` (returns in URL hash) or `"php"` (returns in GET query parameters).
* `revoke=1` — Revoke previous active token before issuing a new one.
* `display` — Display style (`"page"`, `"popup"`, `"mobile"`).

---

### 3. Roaming Mode (Testing)

When logged in via a browser session on OpenVK, you can make API requests without a separate token by adding `auth_mechanism=roaming`:

```http
GET /method/users.get?v=5.80&auth_mechanism=roaming HTTP/1.1
Host: openvk.instance
Cookie: chandler_session=...
```

---

## Passing Access Token in API Requests

The `access_token` can be passed in two ways:

1. **HTTP Authorization Header (Recommended):**
   ```http
   GET /method/account.getProfileInfo?v=5.80 HTTP/1.1
   Host: openvk.instance
   Authorization: Bearer a1b2c3d4e5f6...
   ```

2. **GET/POST Parameter `access_token` or `sid`:**
   ```http
   GET /method/account.getProfileInfo?v=5.80&access_token=a1b2c3d4e5f6... HTTP/1.1
   Host: openvk.instance
   ```

---

## Client Registry and Client IDs

OpenVK includes a built-in client registry (`clients.xml`) for detecting applications and displaying custom badges:

### OpenVK Clients
| Client ID | Tag | Client Name | Platform |
| --- | --- | --- | --- |
| `10001` | `openvk_legacy_android` | OpenVK Legacy | Android |
| `10002` | `openvk_refresh_android` | OpenVK Refresh | Android |
| `10003` | `openvk_flux_android` | OpenVK Flux | Android |
| `10004` | `openvk_native` | OpenVK Native | Android |
| `10005` | `openvk_ios` | OpenVK for iOS | iOS |
| `10006` | `vk4me` | VK4ME | J2ME |
| `10008` | `Matcha` | Matcha | Android |

### VK Compatible Clients
| Client ID | Tag | Client Name | Platform |
| --- | --- | --- | --- |
| `2274003` | `vk_android` | VK for Android | Android |
| `2685278` | `Kate Mobile` | Kate Mobile | Android |
| `3034484` | `vk_ipad` | VK for iPad | iOS |
| `3140623` | `vk_iphone` | VK for iPhone | iOS |
| `3502561` | `vk_windows_8` | VK for Windows 8 | Windows |
| `3680547` | `vk_ios` | VK for iOS | iOS |
| `3697615` | `vk_windows` | VK for Windows | Windows |
| `4083558` | `VFeed` | VFeed | iOS |
| `5027722` | `vk_wphone` | VK for Windows Phone | Windows Phone |
| `5030499` | `vk_messenger` | VK Messenger | Desktop |
| `6146827` | `vk_me` | VK Me | Android |

---

## Token Revocation and Security

Users can invalidate all issued tokens at any time via:
**Settings** &rarr; **Security** &rarr; **"End all sessions"**.