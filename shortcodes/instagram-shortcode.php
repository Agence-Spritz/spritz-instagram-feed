<?php
// [instagram_feed limit=""]
function display_instagram_feed($atts)
{
	// Récupération des attributs avec une limite par défaut
	$atts = shortcode_atts(['limit' => 4], $atts, 'instagram_feed');
	$limit = (int) $atts['limit'];
	$feed = instagramFeed(); // Appel pour récupérer le flux enrichi
	// Déterminer la classe Bootstrap en fonction de la limite
	$col_class = 'col-12'; // Par défaut, une colonne sur mobile
	if ($limit >= 4) {
		$col_class .= ' col-sm-6 col-md-4 col-lg-3'; // 4 colonnes ou plus
	} elseif ($limit === 3) {
		$col_class .= ' col-sm-6 col-md-4'; // 3 colonnes
	} elseif ($limit === 2) {
		$col_class .= ' col-sm-6'; // 2 colonnes
	}

	// Appel du template avec les données
	spritz_get_template('spritz-instagram-feed', 'feed-list.php', [
		'feed'      => $feed,
		'limit'     => $limit,
		'col_class' => $col_class
	]);
}
add_shortcode('instagram_feed', 'display_instagram_feed');
