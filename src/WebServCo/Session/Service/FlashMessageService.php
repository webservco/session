<?php

declare(strict_types=1);

namespace WebServCo\Session\Service;

use Override;
use WebServCo\Session\Contract\FlashMessageServiceInterface;
use WebServCo\Session\Contract\SessionStoreInterface;
use WebServCo\Session\Model\DataTransfer\FlashMessage;
use WebServCo\Session\Model\Enum\FlashMessageType;

use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function is_float;
use function is_int;
use function is_string;

/**
 * Flash messages: the result of an action, shown once, on the next page the user gets.
 *
 * A controller adds one before its redirect, and the layout takes them for the next HTML response, full page or htmx
 * partial. Stored in the session as type, key and parameters, and translated when shown.
 *
 * Adding one starts the session; reading never does.
 */
final class FlashMessageService implements FlashMessageServiceInterface
{
    private const string FIELD_KEY = 'key';
    private const string FIELD_PARAMETERS = 'parameters';
    private const string FIELD_TYPE = 'type';

    private const string SESSION_KEY = 'flash_messages';

    public function __construct(private SessionStoreInterface $sessionStore)
    {
    }

    /**
     * @param array<string,float|int|string> $parameters
     */
    #[Override]
    public function add(FlashMessageType $type, string $key, array $parameters = []): bool
    {
        $messages = $this->sessionStore->getArray(self::SESSION_KEY);
        $message = [self::FIELD_TYPE => $type->value, self::FIELD_KEY => $key, self::FIELD_PARAMETERS => $parameters];

        if (in_array($message, $messages, true)) {
            return false;
        }

        $messages[] = $message;

        return $this->sessionStore->setArray(self::SESSION_KEY, $messages);
    }

    /**
     * @return list<\WebServCo\Session\Model\DataTransfer\FlashMessage>
     */
    #[Override]
    public function take(): array
    {
        $items = $this->sessionStore->getArray(self::SESSION_KEY);
        if ($items === []) {
            return [];
        }

        $this->sessionStore->remove(self::SESSION_KEY);

        return array_values(
            array_filter(
                array_map($this->createFlashMessage(...), $items),
                static function (?FlashMessage $message): bool {
                    return $message !== null;
                },
            ),
        );
    }

    /**
     * A stored item as a message; null when it isn't one.
     */
    private function createFlashMessage(mixed $item): ?FlashMessage
    {
        if (!is_array($item)) {
            return null;
        }

        $type = FlashMessageType::tryFrom($this->getText($item, self::FIELD_TYPE));
        $key = $this->getText($item, self::FIELD_KEY);
        $parameters = $item[self::FIELD_PARAMETERS] ?? null;
        if ($type === null || $key === '' || !is_array($parameters)) {
            return null;
        }

        $validParameters = $this->getValidParameters($parameters);

        return count($validParameters) === count($parameters)
            ? new FlashMessage($type, $key, $validParameters)
            : null;
    }

    /**
     * A text field of a stored item; empty when it is missing or isn't text.
     *
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $item
     */
    private function getText(array $item, string $field): string
    {
        return isset($item[$field]) && is_string($item[$field])
            ? $item[$field]
            : '';
    }

    /**
     * The parameters with a text name and a value a message can have.
     *
     * @phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
     * @param array<mixed> $parameters
     * @return array<string,float|int|string>
     */
    private function getValidParameters(array $parameters): array
    {
        $result = [];
        foreach (array_keys($parameters) as $name) {
            $value = $this->getParameterValue($parameters[$name]);
            if (!is_string($name) || $value === null) {
                continue;
            }

            $result[$name] = $value;
        }

        return $result;
    }

    /**
     * A value a message parameter can have (ICU: text or number); null for any other.
     */
    private function getParameterValue(mixed $value): float|int|string|null
    {
        return is_string($value) || is_int($value) || is_float($value)
            ? $value
            : null;
    }
}
