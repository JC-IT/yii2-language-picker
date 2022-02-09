<?php

declare(strict_types=1);

use JCIT\i18n\widgets\LanguagePicker;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;

/**
 * @var array<string, string> $languages
 * @var array<string, mixed> $linkOptions
 * @var array<string, mixed> $options
 * @var View $this
 * @var LanguagePicker $widget
 */

$options = $options ?? [];

echo Html::beginTag('div', $options);

foreach ($languages as $languageId => $countryCode) {
    $country = country($countryCode);

    $individualLinkOptions = $linkOptions;
    if ($widget->isLanguageActive($languageId)) {
        $individualLinkOptions = ArrayHelper::merge($individualLinkOptions, $widget->activeLanguageOptions);
    }

    echo Html::a(
        is_array($country) ? $countryCode : ($country->getFlag() ?? $countryCode),
        $widget->getSetLanguageUrl($languageId),
        $individualLinkOptions
    );
}

echo Html::endTag('div');
