<?php
/**
 * Arquivo: archive.php
 * Função: Integrar blog/categorias ao Elementor e delegar CPT customizados para seus arquivos.
 */

if ( ! defined( 'ABSPATH' ) ) exit;
// Identifica o post type atual

// Se for o CPT investment, carregue o template específico (garante que Elementor NÃO sobreponha)
if ( is_post_type_archive('investmentos') ) {
    include( locate_template('archive-investment.php') );
    return;
}

// Caso seja categoria, tag, autor, ou post do blog, delega para o Elementor Pro
if ( function_exists('elementor_theme_do_location') && elementor_theme_do_location('archive') ) {
    // Elementor Pro vai renderizar
    return;
}

// Fallback: caso Elementor Pro não esteja ativo, use o loop padrão
get_header();
if ( have_posts() ) : ?>
    <div class="container">
        <?php while ( have_posts() ) : the_post(); ?>
            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
            <div><?php the_excerpt(); ?></div>
        <?php endwhile; ?>
    </div>
<?php else : ?>
    <p><?php esc_html_e('Nada encontrado.', 's-invest-theme-1'); ?></p>
<?php endif;
get_footer();
