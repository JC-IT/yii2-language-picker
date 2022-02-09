<?php

declare(strict_types=1);

namespace JCIT\i18n\repositories;

use lajax\translatemanager\models\Language;
use yii\db\ActiveQuery;

class LanguageRepository
{
    /**
     * @return array<int, string>
     */
    public function retrieveActiveLanguageIds(): array
    {
        return $this->find()->andWhere(['status' => Language::STATUS_ACTIVE])->select('language_id')->column();
    }

    /**
     * @return array<string, string>
     */
    public function retrieveActiveOptions(): array
    {
        return $this->find()->andWhere(['status' => Language::STATUS_ACTIVE])->indexBy('language_id')->select('country')->column();
    }

    public function find(): ActiveQuery
    {
        return Language::find();
    }
}
