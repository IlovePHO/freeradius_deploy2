<?php

namespace App\Model\Validation;
use Cake\Validation\Validation;

class LifeseedValidation extends Validation
{
    public static function regexFormat($check) {
        // Note: It is possible that the behavior is different from
        //       that of rlm_perl in freeRADIUS because of the validation check
        //       by php's perl compatible regular expression function.

        // Compile the regular expression by preg_match and check for warnings.
        // Suppress output to logs, etc. by adding "@" to preg_match.
        $pattern = sprintf("/%s/", $check);
        @preg_match($pattern, "");

        // Return false if an error occurs when building the regular expression.
        $error = error_get_last();
        if (isset($error)) {
            if ($error["file"] == __FILE__) {
                error_clear_last();
                return false;
            }
        }

        // Returns true if no error.
        return true;
    }

    public static function ipv4Format($check) {
        if (preg_match("/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}".
                       "([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/", $check)) {
            return true;
        }
        return false;
    }

    public static function fqdnFormat($check) {
        if (filter_var($check, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return true;
        }
        return false;
    }

    public static function oauthCredentialFormat($check) {
        $credential = json_decode($check);
        if (is_object($credential)) {
            // Check for Google OAuth credentials.
            if (isset($credential->web) &&
                isset($credential->web->client_id) &&
                isset($credential->web->project_id) &&
                isset($credential->web->auth_uri) &&
                isset($credential->web->token_uri) &&
                isset($credential->web->auth_provider_x509_cert_url) &&
                isset($credential->web->client_secret) &&
                isset($credential->web->redirect_uris)) {
                return true;
            }
            return false;
        }
        return false;
    }
}
