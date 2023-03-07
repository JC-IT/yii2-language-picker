<?php

declare(strict_types=1);

namespace JCIT\i18n\components;

use Closure;
use JCIT\i18n\exceptions\InvalidLanguageException;
use JCIT\i18n\repositories\LanguageRepository;
use yii\base\Application as BaseApplication;
use yii\base\BootstrapInterface;
use yii\base\Component;
use yii\base\Event;
use yii\db\ActiveRecord;
use yii\web\Application as WebApplication;
use yii\web\Cookie;

class LanguagePicker extends Component implements BootstrapInterface
{
    private BaseApplication $_app;
    /** @var array<int, string>  */
    private array $_languages;
    /**
     * Function to execute after changing the language of the site.
     */
    public Closure $callback;
    public string $cookieDomain = '';
    public ?int $cookieExpireDays = 30;
    public string $cookieName = '_language';
    public string $queryParam = '_lang';
    /**
     * Query param for executing a page in a specific language only once
     */
    public string $queryParamOnce = '_langOnce';
    public ?string $userProperty = 'language';

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private LanguageRepository $languageRepository,
        $config = []
    ) {
        parent::__construct($config);
    }

    public function addQueryOnceParam(array $url, string $language): array
    {
        if (!$this->isValidLanguage($language)) {
            throw new InvalidLanguageException($language);
        }

        $url[$this->queryParamOnce] = $language;

        return $url;
    }

    public function addQueryParam(array $url, string $language): array
    {
        if (!$this->isValidLanguage($language)) {
            throw new InvalidLanguageException($language);
        }

        $url[$this->queryParam] = $language;

        return $url;
    }

    public function bootstrap($app): void
    {
        if ($app instanceof WebApplication) {
            $app->on(WebApplication::EVENT_BEFORE_REQUEST, function (Event $event) use ($app) {
                $request = $app->request;
                $response = $app->response;

                // Case where query once parameter is set
                if (!is_null($language = $request->get($this->queryParamOnce))) {
                    /** @var string $language */
                    if ($this->isValidLanguage($language)) {
                        $this->saveLanguageIntoApp($language);
                        $this->saveLanguageIntoCallback($language);

                        if ($request->isAjax) {
                            $this->redirect();
                        }
                    }
                // Case where query parameter is set
                } elseif (!is_null($language = $request->get($this->queryParam))) {
                    /** @var string $language */
                    if ($this->isValidLanguage($language)) {
                        $this->saveLanguageIntoApp($language);
                        $this->saveLanguageIntoCallback($language);
                        $this->saveLanguageIntoCookie($language);
                        $this->saveLanguageIntoUser($language);

                        if ($request->isAjax) {
                            $this->redirect();
                        }
                    }
                }
                // Case where the user has a language value
                elseif (!$app->user->isGuest
                    && isset($this->userProperty)
                    && $app->user->identity instanceof ActiveRecord
                    && $app->user->identity->hasProperty($this->userProperty)
                    && isset($app->user->identity->{$this->userProperty}) /** @phpstan-ignore-line */
                    && $this->isValidLanguage($app->user->identity->{$this->userProperty}) /** @phpstan-ignore-line */
                ) {
                    $language = $app->user->identity->{$this->userProperty}; /** @phpstan-ignore-line */
                    $this->saveLanguageIntoApp($language);
                }
                // Case where a cookie is available
                elseif (
                    isset($request->cookieValidationKey) /** @phpstan-ignore-line */
                    && $request->cookies->has($this->cookieName)
                ) {
                    if ($this->isValidLanguage($language = $request->cookies->getValue($this->cookieName))) {
                        $this->saveLanguageIntoApp($language); /** @phpstan-ignore-line */
                    } else {
                        $response->cookies->remove($this->cookieName);
                    }
                }
                // Lastly detect language from headers
                else {
                    $language = $this->detectLanguage();
                    if (!is_null($language)) {
                        $this->saveLanguageIntoApp($language);
                    }
                }
            });
        }
    }

    private function getApp(): BaseApplication
    {
        if (!isset($this->_app)) {
            $this->_app = \Yii::$app;
        }

        return $this->_app;
    }

    public function detectLanguage(): ?string
    {
        if (($app = $this->getApp()) instanceof WebApplication) {
            $acceptableLanguages = $app->request->getAcceptableLanguages();
            foreach ($acceptableLanguages as $language) {
                if ($this->isValidLanguage($language)) {
                    return $language;
                }
            }

            foreach ($acceptableLanguages as $language) {
                $pattern = preg_quote(substr($language, 0, 2), '/');
                foreach ($this->getLanguages() as $languageId) {
                    if (preg_match('/^' . $pattern . '/', $languageId) !== false) {
                        return $languageId;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array<int|string, string>
     */
    private function getLanguages(): array
    {
        if (!isset($this->_languages)) {
            $this->_languages = $this->languageRepository->retrieveActiveLanguageIds();
        }

        return $this->_languages;
    }

    private function isValidLanguage(mixed $language): bool
    {
        return is_string($language) && in_array($language, $this->getLanguages(), true);
    }

    private function redirect(): void
    {
        if (($app = $this->getApp()) instanceof WebApplication) {
            $request = $app->request;
            $redirect = $request->absoluteUrl == $request->referrer ? '/' : ($request->referrer ?? '/');

            $app->response->redirect($redirect);
        }
    }

    public function saveLanguageIntoApp(string $language): void
    {
        if (!$this->isValidLanguage($language)) {
            throw new InvalidLanguageException($language);
        }

        $app = $this->getApp();
        $app->language = $language;
    }

    public function saveLanguageIntoCallback(string $language): void
    {
        if (!$this->isValidLanguage($language)) {
            throw new InvalidLanguageException($language);
        }

        if (isset($this->callback)) {
            call_user_func($this->callback, $language);
        }
    }

    public function saveLanguageIntoCookie(string $language): void
    {
        if (!$this->isValidLanguage($language)) {
            throw new InvalidLanguageException($language);
        }

        $app = $this->getApp();
        if (
            isset($this->cookieExpireDays)
            && $app instanceof WebApplication
            && isset($app->request->cookieValidationKey)
        ) {
            $cookie = \Yii::createObject(Cookie::class, [[
                    'name' => $this->cookieName,
                    'domain' => $this->cookieDomain,
                    'value' => $language,
                    'expire' => time() + 86400 * $this->cookieExpireDays,
            ]]);

            $app->response->cookies->add($cookie);
        }
    }

    public function saveLanguageIntoUser(string $language): void
    {
        if (!$this->isValidLanguage($language)) {
            throw new InvalidLanguageException($language);
        }

        $app = $this->getApp();
        if (
            $app instanceof WebApplication
            && !$app->user->isGuest
            && isset($this->userProperty)
            && $app->user->identity instanceof ActiveRecord
            && $app->user->identity->hasProperty($this->userProperty)
        ) {
            $identity = $app->user->identity;
            $identity->updateAttributes(['language' => $language]);
        }
    }
}
