# webservco/session

A PHP component/library. Please see [webservco/sandbox](https://webservco.github.io/sandbox/) for an example project using these types of components.

PHP sessions with cookie settings and lifetime taken from the configuration.

- Requires: the `COOKIE_*` and `SESSION_*` configuration keys below
- Related: `webservco/dependency-container` (`ServiceContainer::getSessionService()`), `webservco/configuration`

---

## Installation

```shell
composer require webservco/session
```

---

## Usage

The service container creates the session service from the configuration. The `webservco/sandbox` project starts the session in its authentication middleware.

```php
// \WebServCo\Session\Contract\SessionServiceInterface
$sessionService = $applicationDependencyContainer->getServiceContainer()->getSessionService();
$sessionService->start();

// Login: a new session ID for the same data, before storing the user.
$sessionService->regenerateId();
$sessionService->setSessionDataItem('userId', $userId);
$data = $sessionService->getSessionData();
$sessionService->unsetSessionDataItem('userId');

// Logout.
$sessionService->destroy();
```

To start a session only when the browser already has one (a PSR-7 request):

```php
if (array_key_exists($sessionService->getCookieName(), $request->getCookieParams())) {
    $sessionService->start($storagePath);
}
```

Read values from `getSessionData()` with `webservco/data` to get proper types.

Without the container:

```php
use WebServCo\Session\Factory\CookieConfigurationFactory;
use WebServCo\Session\Factory\SessionServiceFactory;

$sessionServiceFactory = new SessionServiceFactory(
    new CookieConfigurationFactory($configurationGetter),
    $configurationGetter,
);
$sessionService = $sessionServiceFactory->createSessionService();
```

---

## Contracts

| Interface | Implementation | Purpose |
| --- | --- | --- |
| `Contract\SessionServiceInterface` | `Service\SessionService` | Start, read, write and destroy the session; renew its ID; name its cookie. |
| `Contract\SessionServiceFactoryInterface` | `Factory\SessionServiceFactory` | Creates the service from the configuration (`SESSION_*` keys and `COOKIE_NAME_SESSION`). |
| `Contract\CookieServiceInterface` | `Service\CookieService` | Set and remove (both with a `CookieConfiguration`), read cookies. |
| `Contract\CookieConfigurationFactoryInterface` | `Factory\CookieConfigurationFactory` | Reads the other `COOKIE_*` keys. |

Namespaces are relative to `WebServCo\Session\`.

---

## Configuration

```ini
[Session]

; 60 * 60 * 24 * 30
COOKIE_LIFETIME = 2592000
COOKIE_PATH = '/'
COOKIE_DOMAIN = ''
COOKIE_SECURE = true
COOKIE_HTTP_ONLY = true
COOKIE_SAME_SITE = 'Lax'
; Name of the session cookie. Optional, default: 'webservco'.
COOKIE_NAME_SESSION = 'webservco'
; 60 * 60 * 24 * 30
SESSION_EXPIRE = 2592000
; Throw if a custom storage path can't be used.
SESSION_STRICT_STORAGE_PATH = true
```

---

## Notes

In CLI, `start()` returns `false` without starting anything. Code that runs in both places has to cope with a session that never started. It also returns `false` if the session is already running.

`start()` sets two things in code, whatever `php.ini` says:

- No cache limiter: PHP sends no cache headers of its own (`Cache-Control`, `Expires`, `Pragma`). The application sends what fits, for example `Cache-Control: private, no-cache` on every response to a request with a session.
- Strict mode (`session.use_strict_mode`): a session ID the server didn't create is refused, and the session starts with a new ID.

`regenerateId()` gives the session a new ID and deletes the old session. Call it when the user's privileges change, at login, against session fixation. Like the other methods that use the session, it throws when the session isn't started.

The session cookie name comes from `COOKIE_NAME_SESSION`, or `webservco` if the key is missing or empty (`getCookieName()`). `SESSION_EXPIRE` sets the garbage collection lifetime. On a shared server, another application's garbage collection can delete your sessions early. A storage path of your own avoids that. `start()` accepts that path as an optional argument.

---

## Development

```shell
# Start project.
ddev start
# Install dependencies.
ddev c u
# Run all validations.
ddev c c
# Run unit tests.
ddev c test:dox
```

---

## License

[MIT](LICENSE.md)
