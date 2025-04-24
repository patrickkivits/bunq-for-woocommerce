<?php

function bunq_oauth2_get_provider( $clientId, $clientSecret, $redirectUri, $testmode ) {
	return new \League\OAuth2\Client\Provider\GenericProvider(
		array(
			'clientId'                => $clientId,
			'clientSecret'            => $clientSecret,
			'redirectUri'             => $redirectUri,
			'urlAuthorize'            => $testmode ? 'https://oauth.sandbox.bunq.com/auth' : 'https://oauth.bunq.com/auth',
			'urlAccessToken'          => $testmode ? 'https://api-oauth.sandbox.bunq.com/v1/token' : 'https://api.oauth.bunq.com/v1/token',
			'urlResourceOwnerDetails' => null,
		)
	);
}

function bunq_oauth2_get_authorization_url( $clientId, $clientSecret, $redirectUri, $testmode ) {
	$provider = bunq_oauth2_get_provider( $clientId, $clientSecret, $redirectUri, $testmode );

	$authorizationUrl = $provider->getAuthorizationUrl();

	$_SESSION['oauth2state'] = $provider->getState();

	return $authorizationUrl;
}

function bunq_oauth2_get_access_token( $clientId, $clientSecret, $redirectUri, $testmode ) {
	$provider = bunq_oauth2_get_provider( $clientId, $clientSecret, $redirectUri, $testmode );

	// Check given state against previously stored one to mitigate CSRF attack
	if ( empty( $_GET['state'] ) || ( isset( $_SESSION['oauth2state'] ) && $_GET['state'] !== $_SESSION['oauth2state'] ) ) {

		if ( isset( $_SESSION['oauth2state'] ) ) {
			unset( $_SESSION['oauth2state'] );
		}

		return false;
	} else {
		$data           = array(
			'grant_type'    => 'authorization_code',
			'code'          => $_GET['code'],
			'redirect_uri'  => $redirectUri,
			'client_id'     => $clientId,
			'client_secret' => $clientSecret,
		);
		$http_client    = $provider->getHttpClient();
		$token_response = $http_client->request(
			'POST',
			$provider->getBaseAccessTokenUrl( $data ),
			array(
				'query'   => $data,
				'headers' => array(
					'X-Bunq-Client-Request-Id' => wp_generate_uuid4(),
				),
			)
		);

		$bodyDecoded = json_decode( $token_response->getBody()->getContents() );

		return $bodyDecoded->access_token;
	}
}
