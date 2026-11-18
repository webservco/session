# webservco/session

A PHP component/library. Please see [webservco/sandbox](https://webservco.github.io/sandbox/) for an example project using these types of components.

PHP sessions with cookie settings and lifetime taken from the configuration; a session store started only when needed, a CSRF token and flash messages.

- Requires: the `COOKIE_*` and `SESSION_*` configuration keys below
- Related: `webservco/dependency-container` (`ServiceContainer::getSessionService()`), `webservco/configuration`, `webservco/data` (the store reads its values with it), `webservco/middleware` (the session and CSRF middleware), `webservco/i18n` (translates the flash messages)

---

## Installation

```shell
composer require webservco/session
```

---

## Usage

### Session service

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

To start a session only when something needs it, use the [session store](#session-store) on top of it.

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

### Session store

A visitor who only reads pages needs no session. `SessionStore` starts one only when something needs it:

- `resume()` starts it when the request sends the session cookie (the session middleware calls it, see [Wiring](#wiring));
- a write (`setString()`, `setArray()`) starts it;
- a read (`getString()`, `getArray()`) or a removal (`remove()`) never does: without a session, every value is missing.

Values are text, or arrays of scalars and arrays. Never objects: a session that holds one can't be read once its class changes.

```php
use WebServCo\Session\Service\SessionStore;

// \WebServCo\Session\Contract\SessionStoreInterface
$sessionStore = new SessionStore(
    $applicationDependencyContainer->getDataExtractionContainer()->getStrictArrayNonEmptyDataExtractionService(),
    $applicationDependencyContainer->getServiceContainer()->getSessionService(),
    // The directory of the session files (see "Wiring").
    $projectPath . 'var/sessions',
);

// A request that sends the session cookie (a PSR-7 request); the session middleware does this.
$sessionStore->resume($request);

// Starts the session if needed.
$sessionStore->setString('theme', 'dark');
$sessionStore->setArray('filters', ['year' => 1969, 'formats' => ['LP', 'EP']]);

// Null without a session or without the value.
$theme = $sessionStore->getString('theme');
// Empty without a session, without the value, or when it isn't an array; check the items.
$filters = $sessionStore->getArray('filters');

$sessionStore->remove('filters');
$sessionStore->isStarted();
```

A response to a request with a session, started before or during it, depends on that session. The session middleware sends `Cache-Control: private, no-cache` with it.

### CSRF token

One token per session, 64 hexadecimal characters, created when a form first needs it. A form carries it in a hidden field (`CsrfTokenServiceInterface::FIELD_NAME`, `csrf_token`), a request sent with JavaScript in a header (`HEADER_NAME`, `X-CSRF-Token`). The CSRF middleware checks it on every request that can change state.

```php
use WebServCo\Session\Contract\CsrfTokenServiceInterface;
use WebServCo\Session\Service\CsrfTokenService;

// \WebServCo\Session\Contract\CsrfTokenServiceInterface
$csrfTokenService = new CsrfTokenService($sessionStore);

// A form: starts the session and creates the token when needed.
// <input type="hidden" name="csrf_token" value="...">
$formToken = $csrfTokenService->getToken();

// The layout, once the page is rendered: the token of a session that exists, or null. Never starts a session.
// With htmx, every request of the page sends it: <body hx-headers='{"X-CSRF-Token":"..."}'>.
$sessionToken = $csrfTokenService->getSessionToken();
if ($sessionToken !== null) {
    $htmxHeaders = json_encode([CsrfTokenServiceInterface::HEADER_NAME => $sessionToken], JSON_THROW_ON_ERROR);
}

// The check (the CSRF middleware does it): false without a session or without a token; `hash_equals()`.
$isValid = $csrfTokenService->isValid($requestToken);

// Login: a new session ID, then a new token.
$sessionService->regenerateId();
$csrfTokenService->renewToken();
```

### Flash messages

The result of an action, shown once, on the next page the user gets. A controller adds one before its redirect, and the layout takes the waiting messages for every HTML response (full page or partial) and shows them. A JSON response or a redirect leaves them waiting.

A message is stored in the session as its type, the translation key of its text and the parameters, and translated when it is shown, in the language of that page. Adding one starts the session; taking them never does. The same message waiting twice is stored once.

The four types (`FlashMessageType`: `Success`, `Info`, `Warning`, `Danger`) have the names of the contextual colours of Bootstrap and Tabler (`alert-success`) as values. The values are stored in the session.

```php
use WebServCo\Session\Model\Enum\FlashMessageType;
use WebServCo\Session\Service\FlashMessageService;

// \WebServCo\Session\Contract\FlashMessageServiceInterface
$flashMessageService = new FlashMessageService($sessionStore);

// A controller, before its redirect. Write the key in full, so that a catalog check finds it.
$flashMessageService->add(FlashMessageType::Success, 'records.saved', ['title' => $title]);

// The layout: the waiting messages, in the order they were added, removed from the session.
foreach ($flashMessageService->take() as $message) {
    // \WebServCo\Session\Model\DataTransfer\FlashMessage
    $colour = $message->type->value;
    $text = $translator->translate($message->key, $message->parameters);
}
```

A stored item that isn't a message (for example, written by an older version) is dropped.

### Wiring

A project gives:

- The directory of the session files, the store's third argument: one of its own, writable by the web server, out of git and out of backups. On a shared server, it also keeps the garbage collection of other applications away from these sessions (see "Notes").
- The middleware of `webservco/middleware`: `WebServCo\Middleware\Service\Session\SessionMiddleware` resumes the session of a request that sends the cookie, and sends `Cache-Control: private, no-cache` with a response that depends on a session; `WebServCo\Middleware\Service\Security\CsrfMiddleware` checks the token of every request that isn't GET, HEAD or OPTIONS. That component's README gives their order in the stack and what a project gives the CSRF one.
- The layout: the hidden field in every form, the header of requests sent with JavaScript (`hx-headers`), the flash messages.

---

## Contracts

| Interface | Implementation | Purpose |
| --- | --- | --- |
| `Contract\SessionServiceInterface` | `Service\SessionService` | Start, read, write and destroy the session; renew its ID; name its cookie. |
| `Contract\SessionServiceFactoryInterface` | `Factory\SessionServiceFactory` | Creates the service from the configuration (`SESSION_*` keys and `COOKIE_NAME_SESSION`). |
| `Contract\CookieServiceInterface` | `Service\CookieService` | Set and remove (both with a `CookieConfiguration`), read cookies. |
| `Contract\CookieConfigurationFactoryInterface` | `Factory\CookieConfigurationFactory` | Reads the other `COOKIE_*` keys. |
| `Contract\SessionStoreInterface` | `Service\SessionStore` | The session, started only when needed: text and array values; resumed when the request sends the cookie. |
| `Contract\CsrfTokenServiceInterface` | `Service\CsrfTokenService` | The CSRF token of the session: create, check, renew; the names of its field and header. |
| `Contract\FlashMessageServiceInterface` | `Service\FlashMessageService` | Add a flash message (`Model\Enum\FlashMessageType`), take the waiting ones once (`Model\DataTransfer\FlashMessage`). |

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

So in CLI, a write to the session store throws (`OutOfRangeException`, the session isn't started), and its reads find no value. Tests give the store an in-memory `SessionServiceInterface`, as this component's tests do (`tests/Unit/WebServCo/Session/Service/InMemorySessionService.php`).

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
# Run unit tests, with coverage (needs Xdebug, which DDEV leaves off).
ddev xdebug on && ddev c test:dox; ddev xdebug off
```

---

## License

[MIT](LICENSE.md)
