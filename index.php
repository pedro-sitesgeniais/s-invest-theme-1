<?php
/**
 * Template Index otimizado - Compatível com Elementor
 * S-Invest Theme v2.0
 */

get_header();

$is_elementor_context = (
    class_exists('\Elementor\Plugin') && 
    (
        \Elementor\Plugin::$instance->editor->is_edit_mode() ||
        \Elementor\Plugin::$instance->preview->is_preview_mode() ||
        isset($_GET['elementor-preview'])
    )
);

if ($is_elementor_context) {
    while (have_posts()): 
        the_post();
        the_content();
    endwhile;
} else {
    ?>
    <main id="primary" class="site-main" role="main">
        <?php if (have_posts()): ?>
            <?php while (have_posts()): the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <header class="entry-header">
                        <?php the_title('<h1 class="entry-title">', '</h1>'); ?>
                    </header>
                    
                    <div class="entry-content">
                        <?php the_content(); ?>
                    </div>
                    
                    <?php if (comments_open() || get_comments_number()): ?>
                        <footer class="entry-footer">
                            <?php comments_template(); ?>
                        </footer>
                    <?php endif; ?>
                </article>
            <?php endwhile; ?>
            
            <?php the_posts_navigation(); ?>
            
        <?php else: ?>
            <section class="no-results not-found">
                <header class="page-header">
                    <h1 class="page-title">Nada encontrado</h1>
                </header>
                
                <div class="page-content">
                    <p>Parece que não conseguimos encontrar o que você está procurando. Tente uma busca.</p>
                    <?php get_search_form(); ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <?php
}

get_footer();
?>