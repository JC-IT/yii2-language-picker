<?php

declare(strict_types=1);

namespace JCIT\i18n\exceptions;

use Throwable;
use yii\base\InvalidArgumentException;

class InvalidLanguageException extends InvalidArgumentException
{
    public function __construct(string $languageId, int $code = 0, ?Throwable $previous = null)
    {
        $message = 'Invalid language: ' . $languageId;

        parent::__construct($message, $code, $previous);
    }
}
