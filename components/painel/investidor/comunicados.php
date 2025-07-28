<?php
/**
 * Seção Comunicados – components/painel/investidor/comunicados.php
 * 
 * Versão simplificada sem destaque para conteúdo longo e link para single
 */
defined('ABSPATH') || exit;

// Busca últimos comunicados com cache
$comunicados = wp_cache_get('ultimos_comunicados', 's-invest-theme');

if (false === $comunicados) {
    $comunicados = get_posts([
        'post_type'      => 'comunicado',
        'posts_per_page' => 10,
        'post_status'    => 'publish',
        'orderby'        => 'menu_order date',
        'order'          => 'DESC',
        'no_found_rows'  => true,
    ]);
    
    wp_cache_set('ultimos_comunicados', $comunicados, 's-invest-theme', HOUR_IN_SECONDS);
}
?>

<h1 class="text-2xl font-semibold mb-6 pt-10">Comunicados</h1>

<?php if (!empty($comunicados)) : ?>
  <div x-data="{ open: null }" class="space-y-4 pb-10 main-content-mobile min-h-screen" role="region" aria-live="polite">

    <?php foreach ($comunicados as $com) : 
        setup_postdata($com);
        $id      = absint($com->ID);
        $title   = esc_html(get_the_title($com));
        $date    = esc_html(get_the_date('j \d\e F, Y', $com));
        $content = wp_kses_post(apply_filters('the_content', $com->post_content));
    ?>
      <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow duration-200">
        <button
          @click="open === <?php echo $id; ?> ? open = null : open = <?php echo $id; ?>"
          class="w-full px-6 py-4 flex justify-between items-center bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50"
          :aria-expanded="open === <?php echo $id; ?> ? 'true' : 'false'"
          aria-controls="comunicado-<?php echo $id; ?>"
        >
          <div class="text-left">
            <p class="text-gray-600 text-sm mb-1"><?php echo $date; ?></p>
            <p class="text-gray-800 font-medium"><?php echo $title; ?></p>
          </div>
          <svg
            class="w-5 h-5 text-gray-500 transform transition-transform duration-200"
            :class="{ 'rotate-90': open === <?php echo $id; ?> }"
            fill="none" stroke="currentColor" viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5l7 7-7 7" />
          </svg>
        </button>
        <div
          id="comunicado-<?php echo $id; ?>"
          x-show="open === <?php echo $id; ?>"
          x-collapse
          class="px-6 py-4 bg-white text-gray-700 prose max-w-none"
          role="region"
        >
          <?php echo $content; ?>
        </div>
      </div>
    <?php endforeach; 
    wp_reset_postdata(); ?>

  </div>

<?php else : ?>
  <div class="bg-gray-50 rounded-lg p-8 text-center">
    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
    </svg>
    <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhum comunicado encontrado</h3>
    <p class="mt-1 text-sm text-gray-500">Novos comunicados serão publicados aqui quando disponíveis.</p>
  </div>
<?php endif; ?>