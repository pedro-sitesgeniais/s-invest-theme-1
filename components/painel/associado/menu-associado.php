<?php
/**
 * Menu Lateral do Painel do Associado
 * Local: components/painel/associado/menu-associado.php
 */

defined( 'ABSPATH' ) || exit;

// Seção ativa
$current_secao = isset( $_GET['secao'] )
    ? sanitize_text_field( wp_unslash( $_GET['secao'] ) )
    : 'dashboard';

/**
 * Imprime um link de menu para o painel do associado.
 *
 * @param string $secao Slug da seção (ex: "dashboard", "investimentos" etc).
 * @param string $label Texto exibido.
 * @param string $icon  Nome do ícone FontAwesome (sem o prefixo "fa-").
 */
function sit_menu_link_associado( $secao, $label, $icon ) {
    global $current_secao;

    $is_active = ( $current_secao === $secao );
    $classes   = 'flex items-center gap-3 px-4 py-2 rounded hover:bg-gray-700 transition';
    if ( $is_active ) {
        $classes .= ' bg-gray-700 font-semibold';
    }

    // Gera URL mantendo ?painel=associado
    $url = add_query_arg(
        [
            'painel' => 'associado',
            'secao'   => $secao,
        ],
        get_permalink()
    );

    printf(
        '<a href="%1$s" class="%2$s"%3$s>
            <i class="fas fa-%4$s w-5"></i>
            <span>%5$s</span>
        </a>',
        esc_url( $url ),
        esc_attr( $classes ),
        $is_active ? ' aria-current="page"' : '',
        esc_attr( $icon ),
        esc_html( $label )
    );
}
?>

<nav role="navigation" aria-label="Menu do Associado" class="flex-1 overflow-y-auto space-y-1 text-sm text-white">
    <?php
    // Dashboard
    sit_menu_link_associado( 'dashboard',     'Dashboard',              'tachometer-alt' );
    // Investimentos publicados
    sit_menu_link_associado( 'investimentos', 'Investimentos Publicados','folder-open'    );
    // Lista de investidores
    sit_menu_link_associado( 'investidores',  'Investidores',           'users'           );
    // Aportes cadastrados
    sit_menu_link_associado( 'aportes',       'Aportes',                'coins'           );
    ?>
</nav>
