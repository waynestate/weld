---
title: "Configuration | WELD docs"
description: "The configuration a WELD app reads: the environment variables, their defaults and where they live."
url: https://weld.apps.wayne.edu/docs/reference/configuration.html
llms: https://weld.apps.wayne.edu/llms.txt
generator: Squire agent-ready
---

# Configuration[#](https://weld.apps.wayne.edu/docs/reference/configuration.html#configuration)

This page lists every environment variable a WELD app reads, its default from `.env.example`, and what the sources say about it.

## Environment variables[#](https://weld.apps.wayne.edu/docs/reference/configuration.html#environment-variables)

| Name | Default | Notes |
| --- | --- | --- |
| `APP_NAME` | `WELD` | Renamed when you clone and rename; `VITE_APP_NAME` follows it through `${APP_NAME}`. |
| `APP_ENV` | `local` |  |
| `APP_KEY` | `(empty)` | Generate it with `php artisan key:generate`; `weld:doctor` checks the app key. |
| `APP_DEBUG` | `true` | The local default; keep `APP_DEBUG=false` in production (W26). |
| `APP_URL` | `http://localhost` | Set it to the real host (W17); Laravel trusts subdomains of `APP_URL` unless `subdomains: false`. |
| `APP_LOCALE` | `en` |  |
| `APP_FALLBACK_LOCALE` | `en` |  |
| `APP_FAKER_LOCALE` | `en_US` |  |
| `APP_MAINTENANCE_DRIVER` | `file` |  |
| `APP_MAINTENANCE_STORE` | `database (commented out)` |  |
| `PHP_CLI_SERVER_WORKERS` | `4` |  |
| `BCRYPT_ROUNDS` | `12` |  |
| `LOG_CHANNEL` | `stderr` | Everything goes to `php://stderr`; nothing under `storage/logs` (W14). |
| `LOG_STACK` | `single` |  |
| `LOG_DEPRECATIONS_CHANNEL` | `null` |  |
| `LOG_LEVEL` | `debug` |  |
| `DB_CONNECTION` | `sqlite` | Local development uses SQLite. |
| `DB_HOST` | `127.0.0.1 (commented out)` |  |
| `DB_PORT` | `3306 (commented out)` |  |
| `DB_DATABASE` | `laravel (commented out)` |  |
| `DB_USERNAME` | `root (commented out)` |  |
| `DB_PASSWORD` | `(empty, commented out)` |  |
| `ORACLE_USERNAME` | `(empty, commented out)` | Leave empty if the app does not use Oracle; nothing connects until set. On a server, set it in the app's environment settings, never in a file in git. |
| `ORACLE_PASSWORD` | `(empty, commented out)` | As `ORACLE_USERNAME`; on a server, set it in the app's environment settings, never in a file in git. |
| `ORACLE_TNS` | `(empty, commented out)` | An Easy Connect string (`host:port/service`) or a full descriptor; set this or the host, port and service trio. |
| `ORACLE_HOST` | `(empty, commented out)` | Set with `ORACLE_PORT` and `ORACLE_SERVICE_NAME` as the alternative to `ORACLE_TNS`. |
| `ORACLE_PORT` | `(empty, commented out)` | Part of the host, port and service alternative to `ORACLE_TNS`. |
| `ORACLE_SERVICE_NAME` | `(empty, commented out)` | Part of the host, port and service alternative to `ORACLE_TNS`. |
| `SESSION_DRIVER` | `database` | A shared store, even for one replica (W15). |
| `SESSION_LIFETIME` | `120` |  |
| `SESSION_ENCRYPT` | `false` |  |
| `SESSION_PATH` | `/` |  |
| `SESSION_DOMAIN` | `null` |  |
| `TRUSTED_PROXIES` | `172.18.0.1` | Which proxies Laravel trusts for `X-Forwarded-Proto` and the client address. Unset keeps `172.18.0.1` (Citadel's proxy); set but empty trusts none; `*` trusts every proxy; or a comma-separated list of IPs and CIDR ranges. Off Citadel, set your own proxy here and put the same list in `HELMET_PROXIES`. |
| `HELMET_PROXIES` | `172.18.0.1` | The address HA-Proxy connects from; the same list as `trustProxies` in `bootstrap/app.php`. Empty trusts nothing. A platform preset fills the list instead; when both are set, `HELMET_PROXIES` wins, and an unknown preset name throws at boot. |
| `HELMET_PRESET` | `citadel (commented out)` | Picks a platform preset; HELMET ships two, `citadel` and `docker`. An unknown preset name throws at boot. When both it and `HELMET_PROXIES` are set, `HELMET_PROXIES` wins. |
| `SESAME_DRIVER` | `fake` | SESAME owns sign-in. `fake` signs in locally without an identity provider and refuses to boot outside development environments; production sets `SESAME_DRIVER=cas` plus `SESAME_CAS_BASE_URL`. |
| `SESAME_FAKE_USERNAME` | `jdoe` |  |
| `SESAME_CAS_BASE_URL` | `(empty, commented out)` | Set with `SESAME_DRIVER=cas` in production. |
| `SESAME_CAS_SERVICE_URL` | `(empty, commented out)` |  |
| `BROADCAST_CONNECTION` | `log` |  |
| `FILESYSTEM_DISK` | `local` |  |
| `QUEUE_CONNECTION` | `database` | Run the queue worker where the platform allows one (W18). |
| `CACHE_STORE` | `database` | A shared cache, even for one replica (W15). |
| `CACHE_PREFIX` | `(empty, commented out)` |  |
| `MEMCACHED_HOST` | `127.0.0.1` |  |
| `REDIS_CLIENT` | `phpredis` |  |
| `REDIS_HOST` | `127.0.0.1` |  |
| `REDIS_PASSWORD` | `null` |  |
| `REDIS_PORT` | `6379` |  |
| `MAIL_MAILER` | `log` |  |
| `MAIL_SCHEME` | `null` |  |
| `MAIL_HOST` | `127.0.0.1` |  |
| `MAIL_PORT` | `2525` |  |
| `MAIL_USERNAME` | `null` |  |
| `MAIL_PASSWORD` | `null` |  |
| `MAIL_FROM_ADDRESS` | `hello@example.com` |  |
| `MAIL_FROM_NAME` | `${APP_NAME}` |  |
| `AWS_ACCESS_KEY_ID` | `(empty)` |  |
| `AWS_SECRET_ACCESS_KEY` | `(empty)` |  |
| `AWS_DEFAULT_REGION` | `us-east-1` |  |
| `AWS_BUCKET` | `(empty)` |  |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `false` |  |
| `VITE_APP_NAME` | `${APP_NAME}` | Follows `APP_NAME` through `${APP_NAME}`. |

## Trusted proxies and HELMET_PRESET[#](https://weld.apps.wayne.edu/docs/reference/configuration.html#trusted-proxies-and-helmet-preset)

HELMET owns the trusted proxies (W17, `Enforced` by HELMET 0.2): it trusts only the preset or listed proxies and drops `X-Forwarded-Port`. Pick a platform preset with `HELMET_PRESET`; HELMET 0.2 ships two, `citadel` and `docker`, and an unknown preset name throws at boot. On other platforms, list the front proxy's address in `HELMET_PROXIES` (it wins over `HELMET_PRESET`), and never trust a whole private range. Do not paste `trustProxies(at: '*')` from tutorials: an app that trusts all proxies lets a client forge `X-Forwarded-*` and the host used in callbacks. Laravel's own proxy list has its own variable; set `TRUSTED_PROXIES` to the same address (unset means Citadel's `172.18.0.1`). In WELD today, trusted proxies are a literal list in `bootstrap/app.php`.

## Keep .env out of version control[#](https://weld.apps.wayne.edu/docs/reference/configuration.html#keep-env-out-of-version-control)

Warning

`.env` never goes into version control. On a server, set values in the app's environment settings, never in a file in git.

## Related[#](https://weld.apps.wayne.edu/docs/reference/configuration.html#related)

- [Security](https://weld.apps.wayne.edu/docs/guides/security.html)
- [Build and supply chain](https://weld.apps.wayne.edu/docs/guides/build-and-supply-chain.html)
- [Running WELD off Citadel](https://weld.apps.wayne.edu/docs/running-off-citadel.html)
