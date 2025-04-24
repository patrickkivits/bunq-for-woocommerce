<?php

function bunq_helper_get_current_url() {
	return ( isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http' ) . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

function bunq_helper_remove_url_parameter( $key, $url ) {
	return preg_replace( '~(\?|&)' . $key . '=[^&]*~', '$1', $url );
}
