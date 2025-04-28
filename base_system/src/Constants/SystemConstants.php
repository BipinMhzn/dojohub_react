<?php

namespace BaseSystem\Constants;

use Symfony\Component\HttpFoundation\Response;

class SystemConstants
{
    public const string ERR_PASSWORD_INVALID_USER = "err_passwords_user";
    public const string ERR_INVALID_PASSWORD = "err_invalid_password";
    public const string ERR_PASSWORDS_TOKEN = "err_passwords_token";
    public const string ERR_PASSWORD_NOT_UPDATED = "err_password_not_updated";
    public const string ERR_INVALID_CREDENTIAL = 'err_invalid_credentials';
    public const string ERR_EMAIL_DOESNT_EXIST_IN_DATABASE = 'err_email_doesnt_exist_in_system';
    public const string ERR_MAIL_SERVER_AUTH_OR_CONF = "err_mail_server_auth_or_conf_error";
    public const string ERR_FORM_VALIDATION = "err_form_validation";
    public const string ERR_OLD_PASSWORD_MISMATCH = "err_old_password_mismatch";
    public const string ERR_INTERNAL_SERVER_ERROR = "err_internal_server";
    public const string ERR_UNAUTHORIZED = "err_unauthorized";
    public const string ERR_FORBIDDEN = "err_forbidden";
    public const string ERR_NOT_FOUND = "err_not_found";
    public const string ERR_BAD_REQUEST = "err_bad_request";
    public const string ERR_METHOD_NOT_ALLOWED = "err_method_not_allowed";
    public const string ERR_EXPECTATION_FAILED = "err_expectation_failed";
    public const string ERR_PRECONDITION_FAILED = "err_precondition_failed";
    public const string ERR_CONFLICT = "err_conflict";
    public const string ERR_USER_NOT_LOGGED_IN_OR_TOKEN_ABSENT = "err_user_not_logged_in_or_token_absent";
    public const string ERR_INVALID_OAUTH_CLIENT = 'err_invalid_client';
    public const string ERR_AUTH_TOKEN_ABSENT = 'err_auth_token_absent';
    public const string ERR_INVALID_AUTH_TOKEN = 'err_invalid_auth_token';
    public const string ERR_INVALID_CALLBACK_URL = 'err_invalid_callback_url';
    public const string ERR_TOKEN_EXPIRED = 'err_auth_token_expired';
    public const string ERR_TOO_MANY_LOGIN_ATTEMPT = 'err_too_many_bad_login_attempt';
    public const string ERR_RESOURCE_IN_USE = 'err_resource_in_use';
    public const string ERR_RESOURCE_NOT_FOUND = 'err_resource_not_found';
    public const string ERR_AUTHENTICATION_ERROR = 'err_authentication_error';
    public const string ERR_USER_NOT_REGISTERED = 'err_user_not_registered';
    public const string ERR_PASSWORDS_THROTTLED = 'err_passwords_throttled';
    public const string ERR_EDITOR_NULL = 'err_editor_null';

    /**
     * @param int $code
     * @return string
     */
    public static function getAppMsgCodeFromStatusCode(int $code): string
    {
        return match ($code) {
            Response::HTTP_BAD_REQUEST => static::ERR_BAD_REQUEST,
            Response::HTTP_UNAUTHORIZED => static::ERR_UNAUTHORIZED,
            Response::HTTP_FORBIDDEN => static::ERR_FORBIDDEN,
            Response::HTTP_NOT_FOUND => static::ERR_NOT_FOUND,
            Response::HTTP_METHOD_NOT_ALLOWED => static::ERR_METHOD_NOT_ALLOWED,
            Response::HTTP_EXPECTATION_FAILED => static::ERR_EXPECTATION_FAILED,
            Response::HTTP_PRECONDITION_FAILED => static::ERR_PRECONDITION_FAILED,
            default => static::ERR_INTERNAL_SERVER_ERROR,
        };
    }
}
