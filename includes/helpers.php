<?php

function bunq_helper_get_current_url()
{
    $scheme = (function_exists('is_ssl') && is_ssl()) || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

    return $scheme . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

function bunq_helper_remove_url_parameter($key, $url) {
    return remove_query_arg($key, $url);
}

/**
 * Whether a URL points at a host that only resolves locally (development sites), so bunq cannot call it.
 *
 * @param string $url
 * @return bool
 */
function bunq_helper_is_local_url($url)
{
    $host = strtolower(trim((string) wp_parse_url($url, PHP_URL_HOST), '[]'));

    if ($host === '' || in_array($host, array('localhost', '127.0.0.1', '::1'), true)) {
        return true;
    }

    foreach (array('.localhost', '.local', '.test') as $suffix) {
        if (substr($host, -strlen($suffix)) === $suffix) {
            return true;
        }
    }

    return false;
}

/**
 * Log a message to the WooCommerce log (WooCommerce > Status > Logs, source "bunq")
 * and to the PHP error log when WP_DEBUG is enabled.
 *
 * @param string|Throwable $message
 * @param string $level One of the WC_Log_Levels constants (error, warning, info, debug).
 */
function bunq_helper_log($message, $level = 'error')
{
    if ($message instanceof Throwable) {
        $message = get_class($message) . ': ' . $message->getMessage() . ' in ' . $message->getFile() . ':' . $message->getLine();
    }

    if (function_exists('wc_get_logger')) {
        wc_get_logger()->log($level, $message, array('source' => 'bunq'));
    }

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[bunq for WooCommerce] ' . $message);
    }
}

/**
 * Format a throwable into a short, human readable message for the admin UI.
 *
 * @param Throwable $throwable
 * @return string
 */
function bunq_helper_format_error(Throwable $throwable)
{
    $message = trim($throwable->getMessage());

    if ($message === '') {
        $message = get_class($throwable);
    }

    return $message;
}
