<?php
/**
 * Painel Administrativo - Dashboard Principal
 * Componente do Sistema Unificado
 * Versão: 3.0.0
 */

defined('ABSPATH') || exit;

// Verificar permissão
if (!S_Invest_Roles_Manager::user_can_access_admin_panel()) {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded-lg">Acesso negado. Você não tem permissão para acessar esta área.</div>';
    return;
}

// Obter estatísticas
$stats = [
    'total_investments' => wp_count_posts('investment')->publish,
    'total_aportes' => wp_count_posts('aporte')->publish,
    'total_investors' => count(get_users(['role' => 'investidor'])),
    'pending_users' => count(get_users(['meta_query' => [['key' => 'email_confirmed', 'value' => false]]])),
];

// Calcular volume total
global $wpdb;
$total_volume = $wpdb->get_var("
    SELECT SUM(CAST(meta_value AS DECIMAL(15,2)))
    FROM {$wpdb->postmeta} pm
    JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    WHERE pm.meta_key = 'valor_total'
    AND p.post_type = 'investment'
    AND p.post_status = 'publish'
") ?: 0;

// Investimentos recentes
$recent_investments = get_posts([
    'post_type' => 'investment',
    'posts_per_page' => 5,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Aportes recentes
$recent_aportes = get_posts([
    'post_type' => 'aporte',
    'posts_per_page' => 5,
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Investidores recentes
$recent_investors = get_users([
    'role' => 'investidor',
    'number' => 5,
    'orderby' => 'registered',
    'order' => 'DESC'
]);
?>

<div class="admin-dashboard fade-in" x-data="adminDashboard()">
    
    <!-- Header com Título e Ações Rápidas -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Dashboard Administrativo</h1>
            <p class="text-gray-600">Visão geral do sistema de investimentos</p>
        </div>
        
        <div class="flex flex-wrap gap-2 mt-4 lg:mt-0">
            <a href="<?php echo admin_url('post-new.php?post_type=investment'); ?>" 
               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-800 transition-colors">
                <i class="fas fa-plus mr-2"></i>
                Novo Investimento
            </a>
            <a href="<?php echo admin_url('post-new.php?post_type=comunicado'); ?>" 
               class="inline-flex items-center px-4 py-2 bg-secondary text-white rounded-lg hover:bg-secondary/80 transition-colors">
                <i class="fas fa-megaphone mr-2"></i>
                Novo Comunicado
            </a>
            <button @click="exportData()" 
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-download mr-2"></i>
                Exportar
            </button>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <!-- Total de Investimentos -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-chart-line text-xl text-blue-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900"><?php echo $stats['total_investments']; ?></div>
                    <div class="text-sm text-gray-600">Investimentos Ativos</div>
                </div>
            </div>
            <div class="mt-4">
                <a href="<?php echo admin_url('edit.php?post_type=investment'); ?>" 
                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    Ver todos →
                </a>
            </div>
        </div>

        <!-- Total de Aportes -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-green-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-money-bill-wave text-xl text-green-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900"><?php echo $stats['total_aportes']; ?></div>
                    <div class="text-sm text-gray-600">Aportes Realizados</div>
                </div>
            </div>
            <div class="mt-4">
                <a href="<?php echo admin_url('edit.php?post_type=aporte'); ?>" 
                   class="text-green-600 hover:text-green-800 text-sm font-medium">
                    Ver todos →
                </a>
            </div>
        </div>

        <!-- Total de Investidores -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-users text-xl text-purple-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900"><?php echo $stats['total_investors']; ?></div>
                    <div class="text-sm text-gray-600">Investidores Ativos</div>
                    <?php if ($stats['pending_users'] > 0): ?>
                        <div class="text-xs text-orange-600 mt-1">
                            <?php echo $stats['pending_users']; ?> pendente(s)
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="mt-4">
                <a href="<?php echo admin_url('users.php?page=s-invest-users'); ?>" 
                   class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                    Gerenciar →
                </a>
            </div>
        </div>

        <!-- Volume Total -->
        <div class="bg-white rounded-2xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-yellow-500/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-dollar-sign text-xl text-yellow-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <div class="text-2xl font-bold text-gray-900">
                        R$ <?php echo number_format($total_volume, 0, ',', '.'); ?>
                    </div>
                    <div class="text-sm text-gray-600">Volume Total</div>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-gray-500 text-sm">Valor captado total</span>
            </div>
        </div>

    </div>

    <!-- Seções Principais -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Investimentos Recentes -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Investimentos Recentes</h2>
                    <a href="<?php echo admin_url('edit.php?post_type=investment'); ?>" 
                       class="text-primary hover:text-primary-800 text-sm font-medium">
                        Ver todos
                    </a>
                </div>
            </div>
            <div class="p-6">
                <?php if (empty($recent_investments)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-chart-line text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Nenhum investimento encontrado</p>
                        <a href="<?php echo admin_url('post-new.php?post_type=investment'); ?>" 
                           class="inline-flex items-center mt-4 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-800 transition-colors">
                            <i class="fas fa-plus mr-2"></i>
                            Criar Primeiro Investimento
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_investments as $investment): ?>
                            <?php 
                            $type_terms = get_the_terms($investment->ID, 'tipo_produto');
                            $type_name = $type_terms && !is_wp_error($type_terms) ? $type_terms[0]->name : 'N/A';
                            $valor_total = get_field('valor_total', $investment->ID);
                            ?>
                            <div class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">
                                        <a href="<?php echo get_edit_post_link($investment->ID); ?>" 
                                           class="hover:text-primary transition-colors">
                                            <?php echo esc_html($investment->post_title); ?>
                                        </a>
                                    </div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                     <?php echo $type_name === 'Private SCP' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?>">
                                            <?php echo esc_html($type_name); ?>
                                        </span>
                                        <?php if ($valor_total): ?>
                                            <span class="ml-2">R$ <?php echo number_format($valor_total, 0, ',', '.'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500">
                                        <?php echo human_time_diff(strtotime($investment->post_date), current_time('timestamp')) . ' atrás'; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Aportes Recentes -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Aportes Recentes</h2>
                    <a href="<?php echo admin_url('edit.php?post_type=aporte'); ?>" 
                       class="text-primary hover:text-primary-800 text-sm font-medium">
                        Ver todos
                    </a>
                </div>
            </div>
            <div class="p-6">
                <?php if (empty($recent_aportes)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-money-bill-wave text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500">Nenhum aporte encontrado</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($recent_aportes as $aporte): ?>
                            <?php 
                            $investment_id = get_field('investment_id', $aporte->ID);
                            $investment_title = $investment_id ? get_the_title($investment_id) : 'N/A';
                            $investor_id = get_field('investidor_id', $aporte->ID);
                            $investor = $investor_id ? get_userdata($investor_id) : null;
                            $valor = get_field('valor_compra', $aporte->ID) ?: get_field('valor_aportado', $aporte->ID);
                            ?>
                            <div class="flex items-center justify-between py-3 border-b border-gray-50 last:border-0">
                                <div class="flex-1">
                                    <div class="font-medium text-gray-900">
                                        <a href="<?php echo get_edit_post_link($aporte->ID); ?>" 
                                           class="hover:text-primary transition-colors">
                                            <?php echo esc_html($aporte->post_title); ?>
                                        </a>
                                    </div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        <span><?php echo esc_html($investment_title); ?></span>
                                        <?php if ($investor): ?>
                                            <span class="ml-2">• <?php echo esc_html($investor->display_name); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <?php if ($valor): ?>
                                        <div class="font-medium text-gray-900">
                                            R$ <?php echo number_format($valor, 2, ',', '.'); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-sm text-gray-500">
                                        <?php echo human_time_diff(strtotime($aporte->post_date), current_time('timestamp')) . ' atrás'; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Investidores Recentes -->
    <?php if (!empty($recent_investors)): ?>
    <div class="mt-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Investidores Recentes</h2>
                    <a href="<?php echo admin_url('users.php?page=s-invest-users'); ?>" 
                       class="text-primary hover:text-primary-800 text-sm font-medium">
                        Gerenciar todos
                    </a>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <?php foreach ($recent_investors as $investor): ?>
                        <?php 
                        $email_confirmed = get_user_meta($investor->ID, 'email_confirmed', true);
                        $avatar_url = get_avatar_url($investor->ID, ['size' => 64]);
                        ?>
                        <div class="text-center">
                            <div class="relative inline-block mb-2">
                                <img src="<?php echo esc_url($avatar_url); ?>" 
                                     alt="<?php echo esc_attr($investor->display_name); ?>"
                                     class="w-12 h-12 rounded-full mx-auto">
                                <?php if ($email_confirmed): ?>
                                    <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-white">
                                        <i class="fas fa-check text-white text-xs"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-yellow-500 rounded-full border-2 border-white">
                                        <i class="fas fa-clock text-white text-xs"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="font-medium text-gray-900 text-sm">
                                <?php echo esc_html($investor->display_name); ?>
                            </div>
                            <div class="text-xs text-gray-500">
                                <?php echo human_time_diff(strtotime($investor->user_registered), current_time('timestamp')) . ' atrás'; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
function adminDashboard() {
    return {
        loading: false,
        
        exportData() {
            if (this.loading) return;
            
            this.loading = true;
            
            // Implementar exportação via AJAX
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'export_investment_data',
                    'nonce': '<?php echo wp_create_nonce('export_data'); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Simular download (implementar conforme necessário)
                    alert('Exportação iniciada! O arquivo será baixado em breve.');
                } else {
                    alert('Erro na exportação: ' + data.data);
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao exportar dados');
            })
            .finally(() => {
                this.loading = false;
            });
        }
    }
}
</script>