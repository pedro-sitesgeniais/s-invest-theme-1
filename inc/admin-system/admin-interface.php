<?php
/**
 * Admin Interface - Interface Administrativa Customizada
 * Sistema Unificado de Administração
 * Versão: 3.0.0
 */

defined('ABSPATH') || exit;

class S_Invest_Admin_Interface {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hooks principais
        add_action('admin_init', [$this, 'redirect_non_admin_users']);
        add_action('admin_menu', [$this, 'customize_admin_menu'], 999);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_head', [$this, 'admin_custom_styles']);
        add_action('admin_footer', [$this, 'admin_custom_scripts']);
        
        // Customizações da interface
        add_filter('admin_footer_text', [$this, 'custom_admin_footer']);
        add_filter('update_footer', [$this, 'custom_version_footer'], 11);
        add_action('wp_dashboard_setup', [$this, 'customize_dashboard']);
        
        // Menu personalizado para associados
        add_action('admin_menu', [$this, 'add_investment_management_menu']);
        
        // AJAX handlers
        add_action('wp_ajax_get_investment_stats', [$this, 'ajax_get_investment_stats']);
        add_action('wp_ajax_export_investment_data', [$this, 'ajax_export_investment_data']);
        add_action('wp_ajax_bulk_investment_action', [$this, 'ajax_bulk_investment_action']);
    }
    
    /**
     * Redirecionar usuários não-admin para painel customizado
     */
    public function redirect_non_admin_users() {
        // Não redirecionar durante AJAX ou processos críticos
        if (defined('DOING_AJAX') || defined('DOING_CRON') || defined('XMLRPC_REQUEST')) {
            return;
        }
        
        $user = wp_get_current_user();
        if (!$user || !$user->exists()) {
            return;
        }
        
        // Verificar se é uma página crítica do admin
        $current_page = $_GET['page'] ?? '';
        $current_screen = get_current_screen();
        $critical_pages = ['admin-ajax.php', 'async-upload.php'];
        $critical_screens = ['upload', 'media'];
        
        if (in_array(basename($_SERVER['SCRIPT_NAME']), $critical_pages)) {
            return;
        }
        
        if ($current_screen && in_array($current_screen->base, $critical_screens)) {
            return;
        }
        
        // Administradores têm acesso total ao wp-admin
        if (in_array('administrator', $user->roles)) {
            return;
        }
        
        // Associados têm acesso limitado ao wp-admin
        if (in_array('associado', $user->roles)) {
            // Permitir apenas páginas específicas para associados
            $allowed_pages = [
                'index.php', // Dashboard
                'edit.php', // Posts (investimentos, aportes, etc.)
                'post-new.php', // Novo post
                'post.php', // Editar post
                'upload.php', // Mídia
                'media-upload.php', // Upload de mídia
                'profile.php', // Perfil
                'users.php', // Se tem permissão para ver usuários
            ];
            
            $current_file = basename($_SERVER['SCRIPT_NAME']);
            
            // Verificar se é uma página permitida ou relacionada aos CPTs
            if (in_array($current_file, $allowed_pages)) {
                return;
            }
            
            // Permitir se é página de CPT que o associado pode gerenciar
            if ($current_file === 'edit.php') {
                $post_type = $_GET['post_type'] ?? 'post';
                $allowed_post_types = ['investment', 'aporte', 'comunicado', 'faq'];
                
                if (in_array($post_type, $allowed_post_types)) {
                    return;
                }
            }
            
            // Se chegou até aqui, redirecionar para o painel
            wp_safe_redirect(home_url('/painel/?painel=associado'));
            exit;
        }
        
        // Investidores não têm acesso ao wp-admin
        if (in_array('investidor', $user->roles)) {
            wp_safe_redirect(home_url('/painel/?painel=investidor'));
            exit;
        }
    }
    
    /**
     * Customizar menu do admin para associados
     */
    public function customize_admin_menu() {
        $user = wp_get_current_user();
        
        // Apenas para associados (admins mantêm menu completo)
        if (!in_array('associado', $user->roles) || in_array('administrator', $user->roles)) {
            return;
        }
        
        // Remover menus desnecessários para associados
        remove_menu_page('edit-comments.php');
        remove_menu_page('themes.php');
        remove_menu_page('plugins.php');
        remove_menu_page('tools.php');
        remove_menu_page('options-general.php');
        
        // Manter apenas menus relevantes
        // Dashboard, Posts (investimentos), Mídia, Usuários (se tiver permissão), Perfil
        
        // Customizar labels dos menus existentes
        global $menu, $submenu;
        
        // Renomear "Posts" para "Conteúdo" se houver
        foreach ($menu as $key => $item) {
            if ($item[2] === 'edit.php') {
                $menu[$key][0] = 'Conteúdo do Site';
                break;
            }
        }
    }
    
    /**
     * Adicionar menu de gestão de investimentos
     */
    public function add_investment_management_menu() {
        $user = wp_get_current_user();
        
        // Apenas para usuários com permissão de gerenciar investimentos
        if (!current_user_can('manage_investments')) {
            return;
        }
        
        // Menu principal
        add_menu_page(
            'Gestão de Investimentos',
            'Investimentos',
            'manage_investments',
            'investment-management',
            [$this, 'investment_management_page'],
            'dashicons-chart-line',
            3
        );
        
        // Submenus
        add_submenu_page(
            'investment-management',
            'Dashboard de Investimentos',
            'Dashboard',
            'manage_investments',
            'investment-management',
            [$this, 'investment_management_page']
        );
        
        add_submenu_page(
            'investment-management',
            'Relatórios',
            'Relatórios',
            'view_financial_reports',
            'investment-reports',
            [$this, 'investment_reports_page']
        );
        
        add_submenu_page(
            'investment-management',
            'Gerenciar Investidores',
            'Investidores',
            'manage_investors',
            'manage-investors',
            [$this, 'manage_investors_page']
        );
        
        add_submenu_page(
            'investment-management',
            'Configurações',
            'Configurações',
            'manage_investment_system',
            'investment-settings',
            [$this, 'investment_settings_page']
        );
    }
    
    /**
     * Página principal de gestão de investimentos
     */
    public function investment_management_page() {
        ?>
        <div class="wrap">
            <h1>Dashboard de Investimentos</h1>
            
            <div id="investment-dashboard" class="investment-admin-dashboard">
                
                <!-- Cards de estatísticas -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="dashicons dashicons-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="total-investments">
                                <?php echo $this->get_total_investments(); ?>
                            </div>
                            <div class="stat-label">Total de Investimentos</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="dashicons dashicons-money-alt"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="total-aportes">
                                <?php echo $this->get_total_aportes(); ?>
                            </div>
                            <div class="stat-label">Total de Aportes</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="dashicons dashicons-groups"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="total-investors">
                                <?php echo $this->get_total_investors(); ?>
                            </div>
                            <div class="stat-label">Investidores Ativos</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="dashicons dashicons-money"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-number" id="total-volume">
                                R$ <?php echo number_format($this->get_total_volume(), 2, ',', '.'); ?>
                            </div>
                            <div class="stat-label">Volume Total</div>
                        </div>
                    </div>
                </div>
                
                <!-- Gráficos e dados recentes -->
                <div class="dashboard-content">
                    
                    <!-- Ações rápidas -->
                    <div class="quick-actions">
                        <h2>Ações Rápidas</h2>
                        <div class="action-buttons">
                            <a href="<?php echo admin_url('post-new.php?post_type=investment'); ?>" class="button button-primary">
                                <i class="dashicons dashicons-plus-alt"></i>
                                Novo Investimento
                            </a>
                            <a href="<?php echo admin_url('post-new.php?post_type=aporte'); ?>" class="button button-secondary">
                                <i class="dashicons dashicons-money-alt"></i>
                                Novo Aporte
                            </a>
                            <a href="<?php echo admin_url('post-new.php?post_type=comunicado'); ?>" class="button button-secondary">
                                <i class="dashicons dashicons-megaphone"></i>
                                Novo Comunicado
                            </a>
                            <button id="export-data" class="button button-secondary">
                                <i class="dashicons dashicons-download"></i>
                                Exportar Dados
                            </button>
                        </div>
                    </div>
                    
                    <!-- Investimentos recentes -->
                    <div class="recent-investments">
                        <h2>Investimentos Recentes</h2>
                        <?php $this->render_recent_investments(); ?>
                    </div>
                    
                    <!-- Aportes recentes -->
                    <div class="recent-aportes">
                        <h2>Aportes Recentes</h2>
                        <?php $this->render_recent_aportes(); ?>
                    </div>
                    
                </div>
                
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Atualizar estatísticas via AJAX
            $('#export-data').on('click', function() {
                const button = $(this);
                button.prop('disabled', true).text('Exportando...');
                
                $.post(ajaxurl, {
                    action: 'export_investment_data',
                    nonce: '<?php echo wp_create_nonce('export_data'); ?>'
                }, function(response) {
                    if (response.success) {
                        // Criar download
                        const link = document.createElement('a');
                        link.href = response.data.url;
                        link.download = response.data.filename;
                        link.click();
                    } else {
                        alert('Erro ao exportar dados: ' + response.data);
                    }
                }).always(function() {
                    button.prop('disabled', false).html('<i class="dashicons dashicons-download"></i> Exportar Dados');
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * Página de relatórios
     */
    public function investment_reports_page() {
        ?>
        <div class="wrap">
            <h1>Relatórios de Investimentos</h1>
            
            <div class="reports-container">
                <div class="report-filters">
                    <h3>Filtros</h3>
                    <form id="report-filters-form">
                        <table class="form-table">
                            <tr>
                                <th><label for="date-from">Data Inicial</label></th>
                                <td><input type="date" id="date-from" name="date_from" value="<?php echo date('Y-m-01'); ?>"></td>
                            </tr>
                            <tr>
                                <th><label for="date-to">Data Final</label></th>
                                <td><input type="date" id="date-to" name="date_to" value="<?php echo date('Y-m-d'); ?>"></td>
                            </tr>
                            <tr>
                                <th><label for="investment-type">Tipo de Investimento</label></th>
                                <td>
                                    <select id="investment-type" name="investment_type">
                                        <option value="">Todos</option>
                                        <option value="private-scp">Private SCP</option>
                                        <option value="compra-em-lote">Compra em Lote</option>
                                        <option value="land-bank">Land Bank</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        <button type="submit" class="button button-primary">Gerar Relatório</button>
                    </form>
                </div>
                
                <div id="report-results" class="report-results">
                    <!-- Resultados do relatório serão carregados aqui -->
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Página de gerenciamento de investidores
     */
    public function manage_investors_page() {
        // Redirecionar para a página existente do tema
        wp_safe_redirect(admin_url('users.php?page=s-invest-users'));
        exit;
    }
    
    /**
     * Página de configurações
     */
    public function investment_settings_page() {
        ?>
        <div class="wrap">
            <h1>Configurações do Sistema de Investimentos</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('s_invest_settings');
                do_settings_sections('s_invest_settings');
                ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Taxa Padrão SCP (%)</th>
                        <td>
                            <input type="number" name="s_invest_default_scp_rate" 
                                   value="<?php echo esc_attr(get_option('s_invest_default_scp_rate', '20')); ?>" 
                                   step="0.01" min="0" max="100" />
                            <p class="description">Taxa padrão de participação no VGV para investimentos SCP</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">E-mail de Notificações</th>
                        <td>
                            <input type="email" name="s_invest_notification_email" 
                                   value="<?php echo esc_attr(get_option('s_invest_notification_email', get_option('admin_email'))); ?>" 
                                   class="regular-text" />
                            <p class="description">E-mail para receber notificações do sistema</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Período Mínimo Trade (meses)</th>
                        <td>
                            <input type="number" name="s_invest_min_trade_period" 
                                   value="<?php echo esc_attr(get_option('s_invest_min_trade_period', '12')); ?>" 
                                   min="1" max="120" />
                            <p class="description">Período mínimo padrão para investimentos Trade</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Cache de Dados (horas)</th>
                        <td>
                            <select name="s_invest_cache_duration">
                                <option value="1" <?php selected(get_option('s_invest_cache_duration', '1'), '1'); ?>>1 hora</option>
                                <option value="6" <?php selected(get_option('s_invest_cache_duration', '1'), '6'); ?>>6 horas</option>
                                <option value="12" <?php selected(get_option('s_invest_cache_duration', '1'), '12'); ?>>12 horas</option>
                                <option value="24" <?php selected(get_option('s_invest_cache_duration', '1'), '24'); ?>>24 horas</option>
                            </select>
                            <p class="description">Duração do cache dos dados de investimentos</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
            
            <hr>
            
            <h2>Ferramentas de Sistema</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Limpar Cache</th>
                    <td>
                        <button type="button" id="clear-cache" class="button button-secondary">
                            Limpar Todo o Cache
                        </button>
                        <p class="description">Remove todos os dados em cache do sistema</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Recalcular Dados SCP</th>
                    <td>
                        <button type="button" id="recalc-scp" class="button button-secondary">
                            Recalcular Todos os SCPs
                        </button>
                        <p class="description">Força o recálculo de todos os dados SCP</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">Verificar Integridade</th>
                    <td>
                        <button type="button" id="check-integrity" class="button button-secondary">
                            Verificar Integridade dos Dados
                        </button>
                        <p class="description">Verifica a consistência dos dados do sistema</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#clear-cache').on('click', function() {
                if (confirm('Tem certeza que deseja limpar todo o cache?')) {
                    $(this).text('Limpando...').prop('disabled', true);
                    // Implementar AJAX para limpar cache
                    location.reload();
                }
            });
            
            $('#recalc-scp').on('click', function() {
                if (confirm('Tem certeza que deseja recalcular todos os dados SCP? Isso pode levar alguns minutos.')) {
                    $(this).text('Recalculando...').prop('disabled', true);
                    // Implementar AJAX para recalcular SCP
                }
            });
            
            $('#check-integrity').on('click', function() {
                $(this).text('Verificando...').prop('disabled', true);
                // Implementar AJAX para verificar integridade
            });
        });
        </script>
        <?php
    }
    
    /**
     * Customizar dashboard
     */
    public function customize_dashboard() {
        $user = wp_get_current_user();
        
        // Para associados, adicionar widgets customizados
        if (in_array('associado', $user->roles)) {
            wp_add_dashboard_widget(
                'investment_overview',
                'Visão Geral dos Investimentos',
                [$this, 'dashboard_investment_overview']
            );
            
            wp_add_dashboard_widget(
                'recent_activity',
                'Atividade Recente',
                [$this, 'dashboard_recent_activity']
            );
        }
        
        // Remover widgets desnecessários para associados
        if (in_array('associado', $user->roles) && !in_array('administrator', $user->roles)) {
            remove_meta_box('dashboard_primary', 'dashboard', 'side');
            remove_meta_box('dashboard_secondary', 'dashboard', 'side');
            remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
            remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
        }
    }
    
    /**
     * Widget de visão geral dos investimentos
     */
    public function dashboard_investment_overview() {
        $stats = [
            'total_investments' => $this->get_total_investments(),
            'total_aportes' => $this->get_total_aportes(),
            'total_volume' => $this->get_total_volume(),
            'active_investors' => $this->get_total_investors()
        ];
        
        ?>
        <div class="investment-overview-widget">
            <div class="stats-row">
                <div class="stat">
                    <strong><?php echo $stats['total_investments']; ?></strong>
                    <span>Investimentos</span>
                </div>
                <div class="stat">
                    <strong><?php echo $stats['total_aportes']; ?></strong>
                    <span>Aportes</span>
                </div>
            </div>
            <div class="stats-row">
                <div class="stat">
                    <strong>R$ <?php echo number_format($stats['total_volume'], 0, ',', '.'); ?></strong>
                    <span>Volume Total</span>
                </div>
                <div class="stat">
                    <strong><?php echo $stats['active_investors']; ?></strong>
                    <span>Investidores</span>
                </div>
            </div>
            
            <div class="widget-actions">
                <a href="<?php echo admin_url('edit.php?post_type=investment'); ?>" class="button">Ver Investimentos</a>
                <a href="<?php echo admin_url('edit.php?post_type=aporte'); ?>" class="button">Ver Aportes</a>
            </div>
        </div>
        <?php
    }
    
    /**
     * Widget de atividade recente
     */
    public function dashboard_recent_activity() {
        $recent_investments = get_posts([
            'post_type' => 'investment',
            'posts_per_page' => 3,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        $recent_aportes = get_posts([
            'post_type' => 'aporte',
            'posts_per_page' => 3,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        ?>
        <div class="recent-activity-widget">
            <h4>Investimentos Recentes</h4>
            <ul>
                <?php foreach ($recent_investments as $investment): ?>
                    <li>
                        <a href="<?php echo get_edit_post_link($investment->ID); ?>">
                            <?php echo esc_html($investment->post_title); ?>
                        </a>
                        <span class="date"><?php echo human_time_diff(strtotime($investment->post_date), current_time('timestamp')) . ' atrás'; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <h4>Aportes Recentes</h4>
            <ul>
                <?php foreach ($recent_aportes as $aporte): ?>
                    <li>
                        <a href="<?php echo get_edit_post_link($aporte->ID); ?>">
                            <?php echo esc_html($aporte->post_title); ?>
                        </a>
                        <span class="date"><?php echo human_time_diff(strtotime($aporte->post_date), current_time('timestamp')) . ' atrás'; ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Apenas nas páginas do sistema de investimentos
        if (strpos($hook, 'investment-') !== false || 
            strpos($hook, 'manage-investors') !== false ||
            get_current_screen()->post_type === 'investment' ||
            get_current_screen()->post_type === 'aporte') {
            
            wp_enqueue_script('jquery');
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js', [], '4.4.9', true);
            
            // CSS customizado
            wp_add_inline_style('wp-admin', $this->get_admin_css());
        }
    }
    
    /**
     * Estilos customizados do admin
     */
    public function admin_custom_styles() {
        echo '<style>' . $this->get_admin_css() . '</style>';
    }
    
    /**
     * Scripts customizados do admin
     */
    public function admin_custom_scripts() {
        ?>
        <script>
        // Scripts customizados para melhorar UX do admin
        jQuery(document).ready(function($) {
            // Adicionar confirmação para ações críticas
            $('.delete a').on('click', function(e) {
                if (!confirm('Tem certeza que deseja excluir este item?')) {
                    e.preventDefault();
                }
            });
            
            // Melhorar feedback visual
            $('.button-primary').on('click', function() {
                const button = $(this);
                if (button.closest('form').length > 0) {
                    button.prop('disabled', true).text('Processando...');
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Customizar footer do admin
     */
    public function custom_admin_footer($text) {
        $screen = get_current_screen();
        
        if (in_array($screen->post_type, ['investment', 'aporte', 'comunicado', 'faq']) ||
            strpos($screen->id, 'investment-') !== false) {
            return 'Sistema de Investimentos S-Invest | Desenvolvido por <a href="https://sitesgeniais.com.br" target="_blank">Sites Geniais</a>';
        }
        
        return $text;
    }
    
    /**
     * Customizar versão do footer
     */
    public function custom_version_footer($text) {
        return 'Versão 3.0.0';
    }
    
    /**
     * ==============================================
     * MÉTODOS AUXILIARES PARA ESTATÍSTICAS
     * ==============================================
     */
    
    private function get_total_investments() {
        return wp_count_posts('investment')->publish;
    }
    
    private function get_total_aportes() {
        return wp_count_posts('aporte')->publish;
    }
    
    private function get_total_investors() {
        $users = get_users(['role' => 'investidor', 'fields' => 'ID']);
        return count($users);
    }
    
    private function get_total_volume() {
        global $wpdb;
        
        $result = $wpdb->get_var("
            SELECT SUM(CAST(meta_value AS DECIMAL(15,2)))
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE pm.meta_key = 'valor_total'
            AND p.post_type = 'investment'
            AND p.post_status = 'publish'
        ");
        
        return floatval($result);
    }
    
    private function render_recent_investments() {
        $investments = get_posts([
            'post_type' => 'investment',
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        if (empty($investments)) {
            echo '<p>Nenhum investimento encontrado.</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Título</th><th>Tipo</th><th>Status</th><th>Data</th><th>Ações</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($investments as $investment) {
            $type_terms = get_the_terms($investment->ID, 'tipo_produto');
            $type_name = $type_terms && !is_wp_error($type_terms) ? $type_terms[0]->name : 'N/A';
            
            echo '<tr>';
            echo '<td><strong><a href="' . get_edit_post_link($investment->ID) . '">' . esc_html($investment->post_title) . '</a></strong></td>';
            echo '<td>' . esc_html($type_name) . '</td>';
            echo '<td><span class="status-active">Ativo</span></td>';
            echo '<td>' . get_the_date('d/m/Y H:i', $investment->ID) . '</td>';
            echo '<td><a href="' . get_edit_post_link($investment->ID) . '" class="button button-small">Editar</a></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    private function render_recent_aportes() {
        $aportes = get_posts([
            'post_type' => 'aporte',
            'posts_per_page' => 5,
            'post_status' => 'publish',
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        
        if (empty($aportes)) {
            echo '<p>Nenhum aporte encontrado.</p>';
            return;
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Título</th><th>Investimento</th><th>Investidor</th><th>Valor</th><th>Data</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($aportes as $aporte) {
            $investment_id = get_field('investment_id', $aporte->ID);
            $investment_title = $investment_id ? get_the_title($investment_id) : 'N/A';
            
            $investor_id = get_field('investidor_id', $aporte->ID);
            $investor = $investor_id ? get_userdata($investor_id) : null;
            $investor_name = $investor ? $investor->display_name : 'N/A';
            
            $valor = get_field('valor_compra', $aporte->ID) ?: get_field('valor_aportado', $aporte->ID);
            
            echo '<tr>';
            echo '<td><a href="' . get_edit_post_link($aporte->ID) . '">' . esc_html($aporte->post_title) . '</a></td>';
            echo '<td>' . esc_html($investment_title) . '</td>';
            echo '<td>' . esc_html($investor_name) . '</td>';
            echo '<td>R$ ' . number_format(floatval($valor), 2, ',', '.') . '</td>';
            echo '<td>' . get_the_date('d/m/Y H:i', $aporte->ID) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * CSS customizado para o admin
     */
    private function get_admin_css() {
        return '
            .investment-admin-dashboard { margin-top: 20px; }
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .stat-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; display: flex; align-items: center; }
            .stat-icon { margin-right: 15px; font-size: 24px; color: #0073aa; }
            .stat-number { font-size: 24px; font-weight: bold; color: #23282d; }
            .stat-label { font-size: 12px; color: #646970; text-transform: uppercase; }
            .dashboard-content { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
            .quick-actions, .recent-investments, .recent-aportes { background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; }
            .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
            .action-buttons .button { display: flex; align-items: center; }
            .action-buttons .dashicons { margin-right: 5px; }
            .investment-overview-widget .stats-row { display: flex; justify-content: space-between; margin-bottom: 15px; }
            .investment-overview-widget .stat { text-align: center; }
            .investment-overview-widget .stat strong { display: block; font-size: 18px; color: #0073aa; }
            .investment-overview-widget .stat span { font-size: 12px; color: #646970; }
            .widget-actions { margin-top: 15px; display: flex; gap: 10px; }
            .recent-activity-widget h4 { margin-top: 15px; margin-bottom: 10px; font-size: 14px; }
            .recent-activity-widget ul { margin: 0; }
            .recent-activity-widget li { display: flex; justify-content: space-between; align-items: center; padding: 5px 0; border-bottom: 1px solid #f0f0f1; }
            .recent-activity-widget .date { font-size: 11px; color: #646970; }
            .status-active { color: #00a32a; font-weight: bold; }
            @media (max-width: 768px) {
                .stats-grid { grid-template-columns: 1fr; }
                .dashboard-content { grid-template-columns: 1fr; }
                .action-buttons { flex-direction: column; }
            }
        ';
    }
    
    /**
     * ==============================================
     * AJAX HANDLERS
     * ==============================================
     */
    
    public function ajax_get_investment_stats() {
        check_ajax_referer('investment_stats', 'nonce');
        
        if (!current_user_can('view_financial_reports')) {
            wp_send_json_error('Permissão negada');
        }
        
        $stats = [
            'total_investments' => $this->get_total_investments(),
            'total_aportes' => $this->get_total_aportes(),
            'total_volume' => $this->get_total_volume(),
            'active_investors' => $this->get_total_investors()
        ];
        
        wp_send_json_success($stats);
    }
    
    public function ajax_export_investment_data() {
        check_ajax_referer('export_data', 'nonce');
        
        if (!current_user_can('export_data')) {
            wp_send_json_error('Permissão negada');
        }
        
        // Implementar exportação de dados
        // Por ora, retornar sucesso
        wp_send_json_success([
            'url' => '#',
            'filename' => 'investments_export_' . date('Y-m-d') . '.csv'
        ]);
    }
    
    public function ajax_bulk_investment_action() {
        check_ajax_referer('bulk_action', 'nonce');
        
        if (!current_user_can('manage_investments')) {
            wp_send_json_error('Permissão negada');
        }
        
        // Implementar ações em massa
        wp_send_json_success('Ação executada com sucesso');
    }
}

// Inicializar
S_Invest_Admin_Interface::get_instance();