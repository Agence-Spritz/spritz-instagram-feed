<?php
/*
Plugin Name: Spritz Instagram Feed
Plugin URI: http://www.agence-spritz.com/
Description: Plugin permettant la récupération du flux Instagram, son affichage direct sur le site par le biais d'un shortcode.
Version: 2.1
Author: Agence Spritz
Author URI: http://www.agence-spritz.com/
License: GPLv2
*/

if (!defined('ABSPATH')) {
	exit;
}

// Définitions des constantes
define('SPRITZ_INSTAGRAMFEED_VERSION', '2.1');
define('SPRITZ_INSTAGRAMFEED_PLUGIN_ABSPATH', dirname(__FILE__));
define('SPRITZ_INSTAGRAMFEED_PLUGIN_URL', plugin_dir_url(__FILE__));

// Chargement des fichiers nécessaires
if (file_exists(SPRITZ_INSTAGRAMFEED_PLUGIN_ABSPATH . '/inc/functions.php')) {
	require_once SPRITZ_INSTAGRAMFEED_PLUGIN_ABSPATH . '/inc/functions.php';
}

if (file_exists(SPRITZ_INSTAGRAMFEED_PLUGIN_ABSPATH . '/shortcodes/instagram-shortcode.php')) {
	require_once SPRITZ_INSTAGRAMFEED_PLUGIN_ABSPATH . '/shortcodes/instagram-shortcode.php';
}

// Enregistrement des options
add_action('admin_init', 'spritz_insta_register_settings');
function spritz_insta_register_settings()
{
	register_setting('spritz_insta_options', 'instagram_access_token');
	register_setting('spritz_insta_options', 'instagram_expected_username');

	register_setting('spritz_insta_options', 'limit_instagram');
}

// Chargement des scripts pour l'administration
add_action('admin_enqueue_scripts', 'spritz_insta_admin_scripts');
function spritz_insta_admin_scripts()
{
	wp_enqueue_style(
		'spritz-insta-admin-css',
		SPRITZ_INSTAGRAMFEED_PLUGIN_URL . 'src/css/admin_styles.css',
		[],
		SPRITZ_INSTAGRAMFEED_VERSION,
		'all'
	);
}

// Scripts pour le front
function spritz_instagram_enqueue_styles()
{

	wp_enqueue_script('jquery');
	wp_enqueue_script(
		'spritz-insta-admin-js',
		SPRITZ_INSTAGRAMFEED_PLUGIN_URL . 'src/js/main.js',
		['jquery'],
		SPRITZ_INSTAGRAMFEED_VERSION,
		true
	);
	// Charger la feuille de style front-end
	wp_enqueue_style(
		'spritz-instagram-styles',
		plugin_dir_url(__FILE__) . 'src/css/styles.css',
		[],
		SPRITZ_INSTAGRAMFEED_VERSION, // Utiliser la version du plugin pour forcer la mise à jour
		'all'
	);
}
add_action('wp_enqueue_scripts', 'spritz_instagram_enqueue_styles');


// Création du menu dans le tableau de bord
add_action('admin_menu', 'spritz_insta_menu');
function spritz_insta_menu()
{
	add_menu_page(
		'Configuration générale',
		'Spritz Instagram Feed',
		'manage_options',
		'spritz-instagram-feed',
		'spritz_insta_settings_page',
		'dashicons-instagram',
		80
	);
}

// Page d'administration du plugin
function spritz_insta_settings_page()
{
?>
	<div class="wrap">
		<h2>Spritz Instagram Feed - Configuration générale</h2>
		<form method="post" action="options.php">
			<?php
			settings_fields('spritz_insta_options');
			do_settings_sections('spritz_insta_options');
			?>
			<table class="form-table">
				<tr>
					<div class="alert alert-primary" role="alert">
						<strong>Etape 1</strong> - obtenir son Token : https://docs.uxthemes.com/article/433-instagram-api-token/<br />
						<strong>Etape 2</strong> - utiliser le shortcode [instagram_feed] dans votre page
					</div>
				</tr>
				<tr>
					<th scope="row"><label for="instagram_expected_username">Nom d'utilisateur Instagram attendu</label></th>
					<td>
						<input type="text" id="instagram_expected_username" name="instagram_expected_username"
							value="<?php echo esc_attr(get_option('instagram_expected_username', '')); ?>" />
						<p class="description">Ce champ permet de vérifier que le token appartient bien à ce compte Instagram. Ne mets pas l'@.</p>
					</td>
				</tr>

				<tr>
					<th scope="row"><label for="instagram_access_token">Token permanent Instagram</label></th>
					<td>
						<input type="text" id="instagram_access_token" name="instagram_access_token"
							value="<?php echo esc_attr(get_option('instagram_access_token', '')); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="limit_instagram">Nombre de posts à afficher</label></th>
					<td>
						<input type="number" id="limit_instagram" name="limit_instagram"
							value="<?php echo esc_attr(get_option('limit_instagram', 5)); ?>" min="1" />
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" class="button-primary" value="Enregistrer les modifications" />
			</p>

			<p>
				<a href="<?php echo admin_url('admin.php?page=spritz-instagram-feed&refresh_token=1'); ?>" class="button button-secondary">
					🔁 Forcer le rafraîchissement du token
				</a>
			</p>
			<p style="font-size: 12px; color: #666;">
				Dernière mise à jour du token :
				<?php
				$last = get_option('instagram_last_update');
				echo $last ? date_i18n('d/m/Y H:i', $last) : 'jamais';
				?>
				<?php
				$active_token = get_option('instagram_access_token');
				if ($active_token) {
					$user_info = request("https://graph.instagram.com/me?fields=username&access_token={$active_token}");
					if (!empty($user_info['username'])) {
						echo '<p><strong>Utilisateur Instagram actif :</strong> @' . esc_html($user_info['username']) . '</p>';
					}
				}
				?>

			</p>
			<p style="font-size: 12px; color: #666;">
				Compte attendu : <strong>@<?php echo esc_html(get_option('instagram_expected_username', 'non défini')); ?></strong>
			</p>

		</form>
	</div>
<?php
}
