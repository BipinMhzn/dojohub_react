<?php


use BaseSystem\Constants\SystemConstants;

return [
    SystemConstants::ERR_INVALID_PASSWORD => "Invalid password or password doesn't match",
    SystemConstants::ERR_PASSWORD_INVALID_USER => "The given email doesn't exist in our system.",
    SystemConstants::ERR_PASSWORDS_TOKEN => "The password reset token has expired or is invalid.",
    SystemConstants::ERR_TOO_MANY_LOGIN_ATTEMPT => "Too many wrong login attempts were made, please try again after some time",
    SystemConstants::ERR_INVALID_CREDENTIAL => "Invalid username/password combination.",
    SystemConstants::ERR_EMAIL_DOESNT_EXIST_IN_DATABASE => "Sorry, that email doesn't exist in our system.",
    SystemConstants::ERR_MAIL_SERVER_AUTH_OR_CONF => "E-mail couldn't be sent at this time. Please try again later.",
    SystemConstants::ERR_FORM_VALIDATION => "Please correct the errors and try again.",
    SystemConstants::ERR_OLD_PASSWORD_MISMATCH => "The old password doesn't match. Please try again.",
    SystemConstants::ERR_INTERNAL_SERVER_ERROR => "There was an error during the process. We've received and documented the error. We'll be fixing it soon. Thank you for your patience.",
    SystemConstants::ERR_UNAUTHORIZED => "Unauthorized access. Make sure you are logged in.",
    SystemConstants::ERR_FORBIDDEN => "You are not authorized to perform this action.",
    SystemConstants::ERR_NOT_FOUND => "The item/page you were looking for cannot be found.",
    SystemConstants::ERR_BAD_REQUEST => "That was a bad request.",
    SystemConstants::ERR_PRECONDITION_FAILED => "Validation failed.",
    SystemConstants::ERR_CONFLICT => "Sorry, the action you request conflicts with current system settings.",
    SystemConstants::ERR_EXPECTATION_FAILED => "Sorry, we need more information to perform that action.",
    SystemConstants::ERR_USER_NOT_LOGGED_IN_OR_TOKEN_ABSENT => "Please submit the form to access that page or action.",
    SystemConstants::ERR_INVALID_OAUTH_CLIENT => "We cannot process the request at this time. Sorry for the inconvenience.",
    SystemConstants::ERR_METHOD_NOT_ALLOWED => "Invalid method call.",
    SystemConstants::ERR_AUTH_TOKEN_ABSENT => "Authentication token is not present in the request.",
    SystemConstants::ERR_INVALID_AUTH_TOKEN => 'Invalid token given.',
    SystemConstants::ERR_TOKEN_EXPIRED => "The token has expired.",
    SystemConstants::ERR_RESOURCE_IN_USE => "The resource you are trying to modify is in use and therefore cannot be modified.",
    SystemConstants::ERR_RESOURCE_NOT_FOUND => "The resource you are trying to modify doesn't exist",
    SystemConstants::ERR_PASSWORD_NOT_UPDATED => "You first need to update your password.",
    SystemConstants::ERR_AUTHENTICATION_ERROR => "Please authenticate with correct credentials.",
    SystemConstants::ERR_USER_NOT_REGISTERED => "Please contact the organization manager to fully utilize this application.",
    SystemConstants::ERR_PASSWORDS_THROTTLED => 'Please wait for few minutes before retrying.',
    SystemConstants::ERR_INVALID_CALLBACK_URL => 'Invalid callback URL, contact administration for registration',
    SystemConstants::ERR_EDITOR_NULL => 'Editor model not found.',
];
