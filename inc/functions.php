<?php

// Requête API (request)
function request($url)
{
	$response = wp_remote_get($url, ['timeout' => 10]);

	if (is_wp_error($response)) {
		error_log('WordPress HTTP API Error: ' . $response->get_error_message());
		return [];
	}

	$http_code = wp_remote_retrieve_response_code($response);
	if ($http_code !== 200) {
		error_log("HTTP Error: $http_code");
		return [];
	}

	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body, true);

	if (json_last_error() !== JSON_ERROR_NONE) {
		error_log('JSON Decode Error: ' . json_last_error_msg());
		return [];
	}

	return $data;
}

// Rafraîchissement du jeton
function refreshToken($token = null)
{
	// 1. Récupération du token actif ou permanent
	if (empty($token)) {
		$token = get_option('instagram_access_token');

		if (empty($token)) {
			$token = get_option('token_permanent_instagram');
			if (!empty($token)) {
				update_option('instagram_access_token', $token);
			}
		}
	}

	if (empty($token)) {
		error_log('❌ Impossible de rafraîchir : aucun token fourni ou disponible.');
		return false;
	}

	$last_update = get_option('instagram_last_update', 0);
	$current_time = time();

	// 2. Vérification : est-ce qu'on doit rafraîchir ?
	if (($current_time - $last_update) <= DAY_IN_SECONDS) {
		return true; // Pas besoin de rafraîchir
	}

	// 3. Appel à l'API pour rafraîchir le token
	$url = "https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token={$token}";
	$response = request($url);

	if (empty($response['access_token'])) {
		error_log('❌ Erreur lors du rafraîchissement du token : réponse invalide.');
		return false;
	}

	$new_token = $response['access_token'];

	// 4. Vérification que le nouveau token correspond au bon compte
	$expected_username = get_option('instagram_expected_username'); // option à définir manuellement si tu veux verrouiller le compte
	$user_data = request("https://graph.instagram.com/me?fields=username&access_token={$new_token}");

	if (!empty($user_data['username'])) {
		if (!empty($expected_username) && strtolower($user_data['username']) !== strtolower($expected_username)) {
			error_log("❌ Le token récupéré appartient à un autre compte Instagram (@{$user_data['username']}) au lieu de @{$expected_username}");
			return false;
		}

		// 5. Tout est bon : on sauvegarde
		update_option('instagram_access_token', $new_token);
		update_option('instagram_last_update', $current_time);
		error_log("✅ Token Instagram mis à jour avec succès pour @{$user_data['username']}.");
		return true;
	} else {
		error_log('❌ Impossible de vérifier le compte lié au nouveau token.');
		return false;
	}
}


// Cron WordPress pour un rafraîchissement quotidien
function spritz_schedule_instagram_token_refresh()
{
	if (!wp_next_scheduled('spritz_instagram_token_refresh_event')) {
		wp_schedule_event(time(), 'daily', 'spritz_instagram_token_refresh_event');
	}
}
add_action('wp', 'spritz_schedule_instagram_token_refresh');

// Action liée au cron
add_action('spritz_instagram_token_refresh_event', function () {
	refreshToken(); // Automatique
});

// Rafraîchissement manuel depuis l’admin
function spritz_maybe_force_refresh_from_admin()
{
	if (is_admin() && current_user_can('manage_options') && isset($_GET['refresh_token']) && $_GET['refresh_token'] === '1') {
		$refreshed = refreshToken();
		add_action('admin_notices', function () use ($refreshed) {
			echo '<div class="notice notice-' . ($refreshed ? 'success' : 'error') . ' is-dismissible"><p>Rafraîchissement du token ' . ($refreshed ? 'réussi ✅' : 'échoué ❌') . '.</p></div>';
		});
	}
}
add_action('admin_init', 'spritz_maybe_force_refresh_from_admin');

// Récupération du flux Instagram
function instagramFeed($token = null)
{
	if (is_null($token)) {
		$token = get_option('instagram_access_token');
	}

	// Si token toujours vide, essayer de rafraîchir avec celui de secours
	if (empty($token)) {
		refreshToken(); // essaie de le générer automatiquement
		$token = get_option('instagram_access_token');
	}

	if (empty($token)) {
		error_log('Instagram token is missing.');
		return [];
	}

	// Toujours tenter un rafraîchissement automatique si nécessaire
	refreshToken($token);

	$url = "https://graph.instagram.com/me/media?fields=id,username,permalink,timestamp,caption,media_url,media_type&access_token=$token";
	$response = request($url);

	if (!empty($response['data']) && is_array($response['data'])) {
		return $response['data'];
	}

	$http_code = wp_remote_retrieve_response_code(wp_remote_get($url));
	$error_message = isset($response['error']['message']) ? $response['error']['message'] : 'Erreur inconnue';

	$error_details = sprintf(
		"Cher %s,\n\nLa mise à jour du flux Instagram pour le site %s a échoué.\n\nDétails :\n- Code HTTP : %s\n- Message d'erreur : %s\n- URL : %s\n",
		get_option('blogname'),
		home_url(),
		$http_code,
		$error_message,
		$url
	);

	error_log("Instagram Feed Error: $error_details");

	if (function_exists('wp_mail')) {
		wp_mail(get_option('admin_email'), 'Erreur dans le flux Instagram', nl2br($error_details));
	}

	return [];
}

function truncate_caption($caption, $limit = 150, $permalink = '')
{
	if (strlen($caption) <= $limit) {
		return esc_html($caption);
	}

	$truncated = mb_substr($caption, 0, $limit);
	$truncated = rtrim($truncated, " \t\n\r\0\x0B.") . '…';


	return wp_kses_post($truncated);
}