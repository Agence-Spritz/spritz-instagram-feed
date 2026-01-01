<?php

/**
 * Template for Instagram Feed list
 * 
 * Available variables:
 * @var array  $feed      The enriched feed data
 * @var int    $limit     Number of posts to display
 * @var string $col_class Bootstrap column class
 */

if (!defined('ABSPATH')) exit;

if (empty($feed)) {
    echo '<p>Le flux Instagram est actuellement indisponible. Veuillez réessayer plus tard.</p>';
    return;
}
?>

<div class="container">
    <div class="instagram-feed row">
        <?php
        $count = 0;
        foreach ($feed as $post):
            // Filtrer pour inclure uniquement les images
            if ($count >= $limit || !isset($post['media_type']) || $post['media_type'] !== 'IMAGE') {
                continue;
            }

            if (isset($post['media_url'], $post['permalink'])): ?>
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
            endif;
        endforeach;
        ?>
    </div>
</div>