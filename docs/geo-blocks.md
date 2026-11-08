# Geo blocks

OpenVK can hide an entity (post, note, document, video, photo, audio, poll,
topic, profile or group) from selected countries without deleting it. The
feature is disabled by default.

## Configuration

```yaml
openvk:
    preferences:
        geo:
            enabled: true
            database: "/usr/share/GeoIP/GeoLite2-Country.mmdb"
            trustedProxies:
                - "127.0.0.1/32"
                - "10.0.0.0/8"
            headerCountry: "X-Geo-Country"
            defaultCountries: ["RU"]
            mediaCacheTtl: 300
```

The `.mmdb` file is not bundled. Install GeoLite2 or DB-IP Lite, point
`database` at it and update it periodically (weekly is enough). If the database
is missing or unreadable, OpenVK fails open: the visitor is treated as having an
unknown country.

`trustedProxies` must contain the CDN/reverse-proxy networks. Only those peers
may supply `X-Forwarded-For`, `X-Real-IP` or `X-Geo-Country`. Do not trust
these headers from arbitrary clients.

## Admin panel and CLI

Admins with the `admin.access` permission get `/admin/geo`. Paste a URL from a
demand (`/wall2_1`, `/id3`, `/club1`, ...) or use `type:id`, choose countries,
enter a reason and optionally an external demand reference. A post block can
also block its attachments. Unblocking a post revokes the attachment blocks
created with it.

Every block and unblock is recorded in `geo_blocks` with the actor, timestamp,
reason and optional external reference. Re-blocking creates a new row; the old
one stays as history.

CLI equivalents:

```bash
./openvkctl geo block /wall2_1 --countries=RU --reason="..." --ref="..."
./openvkctl geo block /wall2_1 --countries=RU --reason="..." --with-attachments
./openvkctl geo unblock /wall2_1 --countries=RU --reason="..."
./openvkctl geo list --country=RU
./openvkctl geo check /wall2_1 --country=RU
```

A direct page returns `451`; API methods return error `15`; media returns
`451`; feeds, search and other listings omit blocked entities.

## CDN and caching

Do not serve `/blob_*` directly from a static network share: the PHP check must
run. A cached response must be keyed by country, otherwise a response generated
for one country can be served to another. See
`install/automated/common/geo-cache.conf` for an nginx example.

There is no automatic cache purge. Blocking takes effect after the configured
cache TTL; keep it short (`mediaCacheTtl`, HTML is capped at 60 seconds when
geo blocks are enabled). Flush the old cache once when enabling the feature.

`uploads.mode: server` and `accelerated` bypass PHP. Geo blocks require
`basic` mode (or equivalent edge enforcement) for media.

## Deliberate limitations

- VPN and proxy visitors are not detected.
- Notifications are not filtered; private messages are not filtered. Media
  attachments are still served through `BlobPresenter` and are blocked there.
- A blocked profile's avatar and display name remain visible; its public
  content is hidden.
- Region-level blocks are not implemented.
