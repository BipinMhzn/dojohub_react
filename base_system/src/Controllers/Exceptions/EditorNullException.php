<?php

namespace BaseSystem\Controllers\Exceptions;

use BaseSystem\Constants\SystemConstants;

/**
 * Class EditorNullException
 * @package base_system\Exceptions
 */
class EditorNullException extends \RuntimeException
{
    protected $code = 500;

    protected $message = SystemConstants::ERR_EDITOR_NULL;
}
