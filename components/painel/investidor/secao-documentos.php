<?php
/**
 * Seção Documentos - Versão Final Corrigida
 * components/painel/investidor/secao-documentos.php
 */
defined('ABSPATH') || exit;

// Verifica se o usuário está logado
if (!is_user_logged_in()) {
    wp_redirect(home_url('/acessar'));
    exit;
}

// Contexto com validação
$painel = in_array(sanitize_key($_GET['painel'] ?? ''), ['investidor', 'admin'], true) 
          ? sanitize_key($_GET['painel']) 
          : 'investidor';

$inv_id = absint($_GET['id'] ?? 0);

// URL base corrigida para documentos SEM ID
$documentos_url = esc_url(add_query_arg([
    'painel' => $painel,
    'secao' => 'documentos'
], home_url('/painel/')));

// Se não tiver ID, mostra a lista geral
if (!$inv_id) {
    $investment_data = [
        'title' => __('Todos os Documentos', 's-invest-theme'),
        'docs'  => []
    ];
    $back_link = esc_url(add_query_arg(['painel' => $painel], home_url('/painel/')));
} 
// Se tiver ID, mostra documentos específicos
else {
    if (!get_post_status($inv_id)) {
        echo '<p class="text-red-600" role="alert">' . esc_html__('Investimento não encontrado.', 's-invest-theme') . '</p>';
        return;
    }

    $investment_data = [
        'title' => get_the_title($inv_id),
        'docs'  => get_field('documentos', $inv_id) ?: []
    ];
    $back_link = $documentos_url; // Volta para lista geral
}
?>

<div class="max-w-4xl mx-auto space-y-6" role="region" aria-label="<?php esc_attr_e('Documentos', 's-invest-theme'); ?>">

    <!-- CABEÇALHO COM BOTÃO VOLTAR SEMPRE VISÍVEL -->
    <header class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold" id="documents-heading">
            <?php echo esc_html($investment_data['title']); ?>
        </h1>
        <a href="<?php echo $back_link; ?>"
           class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center gap-2"
           aria-label="<?php esc_attr_e('Voltar', 's-invest-theme'); ?>">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
            <?php esc_html_e('Voltar', 's-invest-theme'); ?>
        </a>
    </header>

    <!-- LISTA DE DOCUMENTOS -->
    <section class="bg-white p-6 rounded-2xl shadow">
        <?php if ($inv_id) : ?>
            <!-- DOCUMENTOS ESPECÍFICOS -->
            <?php if (!empty($investment_data['docs'])) : ?>
                <ul class="space-y-2" aria-labelledby="documents-heading">
                    <?php foreach ($investment_data['docs'] as $doc) : 
                        $title = esc_html($doc['title'] ?? '');
                        $url = esc_url($doc['url']['url'] ?? '');
                        $file_type = wp_check_filetype($url);
                    ?>
                        <li class="flex flex-col sm:flex-row sm:items-center justify-between py-3 border-b last:border-b-0">
                            <div class="mb-2 sm:mb-0">
                                <span class="text-gray-700 block"><?php echo $title; ?></span>
                                <?php if ($file_type['ext']) : ?>
                                    <span class="text-xs text-gray-500">
                                        <?php echo esc_html(strtoupper($file_type['ext'])); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($url) : ?>
                                <a href="<?php echo $url; ?>"
                                   target="_blank" rel="noopener noreferrer"
                                   class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 inline-flex items-center justify-center min-w-[120px]"
                                   <?php if ($file_type['ext']) echo 'download="' . esc_attr(sanitize_title($title) . '.' . $file_type['ext']) . '"'; ?>>
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    <?php esc_html_e('Baixar', 's-invest-theme'); ?>
                                </a>
                            <?php else : ?>
                                <span class="text-gray-500" aria-hidden="true">—</span>
                                <span class="sr-only"><?php esc_html_e('Documento indisponível', 's-invest-theme'); ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="text-gray-600" role="status">
                    <?php esc_html_e('Nenhum documento cadastrado para este investimento.', 's-invest-theme'); ?>
                </p>
            <?php endif; ?>
        <?php else : ?>
            <!-- LISTA GERAL DE INVESTIMENTOS COM DOCUMENTOS -->
            <?php
            $investments = get_posts([
                'post_type' => 'investimento',
                'posts_per_page' => -1,
                'meta_query' => [
                    [
                        'key' => 'documentos',
                        'compare' => 'EXISTS'
                    ]
                ]
            ]);
            
            if (!empty($investments)) : ?>
                <ul class="space-y-4">
                    <?php foreach ($investments as $investment) : 
                        $docs = get_field('documentos', $investment->ID);
                        if (empty($docs)) continue;
                    ?>
                        <li class="border-b pb-4 last:border-b-0">
                            <a href="<?php echo esc_url(add_query_arg([
                                'id' => $investment->ID
                            ], $documentos_url)); ?>" 
                               class="flex justify-between items-center hover:bg-gray-50 p-3 rounded-lg">
                                <span class="font-medium"><?php echo esc_html($investment->post_title); ?></span>
                                <span class="text-sm text-gray-500">
                                    <?php printf(esc_html__('%d documentos', 's-invest-theme'), count($docs)); ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <p class="text-gray-600"><?php esc_html_e('Nenhum documento disponível.', 's-invest-theme'); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>