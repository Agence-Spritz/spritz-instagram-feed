<?php
// [instagram_feed limit=""]
function display_instagram_feed($atts)
{
	// Récupération des attributs avec une limite par défaut
	$atts = shortcode_atts(['limit' => 4], $atts, 'instagram_feed');
	$limit = (int) $atts['limit'];
	$feed = instagramFeed(); // Appel pour récupérer le flux enrichi

	// Si le flux est vide, afficher un message
	if (empty($feed)) {
		echo '<p>Le flux Instagram est actuellement indisponible. Veuillez réessayer plus tard.</p>';
		return;
	}

	// Déterminer la classe Bootstrap en fonction de la limite
	$col_class = 'col-12'; // Par défaut, une colonne sur mobile
	if ($limit >= 4) {
		$col_class .= ' col-sm-6 col-md-4 col-lg-3'; // 4 colonnes ou plus
	} elseif ($limit === 3) {
		$col_class .= ' col-sm-6 col-md-4'; // 3 colonnes
	} elseif ($limit === 2) {
		$col_class .= ' col-sm-6'; // 2 colonnes
	}

	// Début de la grille Bootstrap
	echo '<div class="container">';
	echo '<div class="instagram-feed row">';
	$count = 0;

	foreach ($feed as $post) {
		// Filtrer pour inclure uniquement les images
		if ($count >= $limit || !isset($post['media_type']) || $post['media_type'] !== 'IMAGE') {
			continue;
		}

		if (isset($post['media_url'], $post['permalink'])) {
?>
			<div class="<?php echo esc_attr($col_class); ?> md-margin-30px-bottom">
				<div class="blog-post h-100">
					<div class="blog-post-images overflow-hidden position-relative cover-background lazy" data-bg="<?php echo esc_url($post['media_url']); ?>">
					</div>
					<div class="post-details padding-20px-all md-padding-15px-all">
						<div class="picto-reseau d-flex justify-content-start">
							<i class="fab fa-instagram icon-medium text-black margin-10px-bottom"></i>
						</div>
						<p class="post-title text-extra-medium text-dark width-100 d-block margin-15px-bottom">
							<?php echo truncate_caption($post['caption'] ?? '', 160, $post['permalink']); ?>
						</p>

					</div>
					<div class="author padding-20px-all">
						<a href="<?php echo esc_url($post['permalink']); ?>" aria-label="<?php echo truncate_caption($post['caption'], 40); ?>" target="_blank" rel="noreferrer noopener" class="post-title text-small margin-15px-top">
							<span>En savoir plus</span> <i class="ti-arrow-down icon-extra-small"></i>
						</a>
					</div>
				</div>
			</div>
<?php
			$count++;
		}
	}

	echo '</div>'; // Fermeture de .row
	echo '</div>'; // Fermeture de .container
}
add_shortcode('instagram_feed', 'display_instagram_feed');

?>