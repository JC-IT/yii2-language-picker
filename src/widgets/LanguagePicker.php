<?php

declare(strict_types=1);

namespace JCIT\i18n\widgets;

use JCIT\i18n\repositories\LanguageRepository;
use yii\base\Widget;
use yii\helpers\Html;

class LanguagePicker extends Widget
{
    public string $activeLanguage;
    /** @var array<string, mixed> */
    public array $activeLanguageOptions = ['class' => ['language-picker-country-active']];
    public string $languageQueryParam = '_lang';
    /** @var array<string, mixed> */
    public array $linkOptions = ['class' => []];
    /** @var array<string, mixed> */
    public array $options = [];

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private LanguageRepository $languageRepository,
        array $config = []
    ) {
        parent::__construct($config);
    }

    protected function getAppLanguage(): string
    {
        return \Yii::$app->language;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getRequestQueryParams(): array
    {
        return \Yii::$app->request->queryParams;
    }

    /**
     * @return array<int|string, int|string>
     */
    public function getSetLanguageUrl(string $languageId): array
    {
        return array_merge_recursive([''], array_diff_key($this->getRequestQueryParams(), [$this->languageQueryParam => true]), [$this->languageQueryParam => $languageId]);
    }

    public function init(): void
    {
        parent::init();

        if (!isset($this->activeLanguage)) {
            $this->activeLanguage = $this->getAppLanguage();
        }

        if (!isset($this->options['id'])) {
            $this->options['id'] = $this->getId();
        }
    }

    public function isLanguageActive(string $languageId): bool
    {
        return $languageId === $this->activeLanguage;
    }

    public function run(): string
    {
        $result = parent::run();

        $languages = $this->languageRepository->retrieveActiveOptions();

        $options = $this->options;
        Html::addCssClass($options, ['language-picker']);

        $linkOptions = $this->linkOptions;
        Html::addCssClass($linkOptions, 'language-picker-country');

        return $result . $this->render(
            'languagePicker',
            [
                'linkOptions' => $linkOptions,
                'languages' => $languages,
                'options' => $options,
                'widget' => $this,
            ]
        );
    }
}
