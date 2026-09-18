<?php
/**
 * Regenerate languages/bunq-for-woocommerce.pot from the plugin's own PHP files (vendor/ is skipped).
 *
 *   php .github/scripts/make-pot.php
 *
 * Kept deliberately small so no extra tooling (wp-cli i18n) is needed. Only string literals passed as the first
 * argument of __(), _e(), esc_html__(), esc_html_e(), esc_attr__() and esc_attr_e() are picked up, and a
 * "translators:" comment directly before the call is copied as an extracted comment.
 */

$root = dirname(__DIR__, 2);
$domain = 'bunq-for-woocommerce';
$functions = ['__', '_e', 'esc_html__', 'esc_html_e', 'esc_attr__', 'esc_attr_e'];

$files = array_merge(
    [$root.'/bunq-for-woocommerce.php', $root.'/uninstall.php'],
    glob($root.'/includes/*.php')
);

$entries = [];

foreach ($files as $file) {
    $tokens = token_get_all(file_get_contents($file));
    $relative = substr($file, strlen($root) + 1);
    $comment = null;

    for ($i = 0, $count = count($tokens); $i < $count; $i++) {
        $token = $tokens[$i];

        if (is_array($token) && $token[0] === T_COMMENT && stripos($token[1], 'translators:') !== false) {
            $comment = trim(preg_replace('~^/\*\s*|\s*\*/$~', '', $token[1]));
            continue;
        }

        if (!is_array($token) || $token[0] !== T_STRING || !in_array($token[1], $functions, true)) {
            continue;
        }

        // Expect: FUNCTION ( 'msgid' , 'domain' )
        $j = $i + 1;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
        if (!isset($tokens[$j]) || $tokens[$j] !== '(') continue;
        $j++;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
        if (!isset($tokens[$j]) || !is_array($tokens[$j]) || $tokens[$j][0] !== T_CONSTANT_ENCAPSED_STRING) continue;

        $msgid = substr($tokens[$j][1], 1, -1);
        $msgid = $tokens[$j][1][0] === '"' ? stripcslashes($msgid) : str_replace(["\\'", '\\\\'], ["'", '\\'], $msgid);
        $line = $tokens[$j][2];

        if (!isset($entries[$msgid])) {
            $entries[$msgid] = ['references' => [], 'comments' => []];
        }
        $entries[$msgid]['references'][] = $relative.':'.$line;
        if ($comment !== null) {
            $entries[$msgid]['comments'][] = $comment;
        }
        $comment = null;
    }
}

$escape = function ($string) {
    return '"'.addcslashes($string, "\0..\37\"\\").'"';
};

$out = <<<POT
# Copyright (C) Patrick Kivits
# This file is distributed under the GPLv2 or later.
msgid ""
msgstr ""
"Project-Id-Version: bunq for WooCommerce\\n"
"Report-Msgid-Bugs-To: https://github.com/patrickkivits/bunq-for-woocommerce/issues\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"
"X-Domain: {$domain}\\n"


POT;

foreach ($entries as $msgid => $entry) {
    foreach (array_unique($entry['comments']) as $comment) {
        $out .= '#. '.$comment."\n";
    }
    $out .= '#: '.implode(' ', array_unique($entry['references']))."\n";
    $out .= 'msgid '.$escape($msgid)."\n";
    $out .= "msgstr \"\"\n\n";
}

file_put_contents($root.'/languages/'.$domain.'.pot', $out);
echo count($entries).' strings written to languages/'.$domain.".pot\n";
