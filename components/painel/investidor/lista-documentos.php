<?php
/**
 * Lista Geral de Documentos
 * components/painel/investidor/lista-documentos.php
 */
return; // Impede acesso direto ao arquivo
defined('ABSPATH') || exit;

// Sua lógica para listar todos os investimentos com documentos
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
?>

<div class="max-w-4xl mx-auto space-y-6 main-content-mobile min-h-screen">
    <h1 class="text-2xl font-semibold"><?php esc_html_e('Meus Documentos', 's-invest-theme'); ?></h1>
    
    <?php if (!empty($investments)) : ?>
        <div class="bg-white p-6 rounded-2xl shadow">
            <ul class="space-y-4">
                <?php foreach ($investments as $investment) : 
                    $docs = get_field('documentos', $investment->ID);
                    if (empty($docs)) continue;
                ?>
                    <li class="border-b pb-4 last:border-b-0">
                        <a href="<?php echo esc_url(add_query_arg(['id' => $investment->ID], $panel_url)); ?>" 
                           class="flex justify-between items-center hover:bg-gray-50 p-3 rounded-lg">
                            <span class="font-medium"><?php echo esc_html($investment->post_title); ?></span>
                            <span class="text-sm text-gray-500">
                                <?php printf(esc_html__('%d documentos', 's-invest-theme'), count($docs)); ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php else : ?>
        <p class="text-gray-600"><?php esc_html_e('Nenhum documento disponível.', 's-invest-theme'); ?></p>
    <?php endif; ?>
</div>