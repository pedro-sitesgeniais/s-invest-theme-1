<?php
/**
 * CPT Manager - Sistema Unificado de Investimentos
 * Migrado do plugin sky-invest-panel
 * Versão: 3.0.0
 */

defined('ABSPATH') || exit;

class S_Invest_CPT_Manager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'register_post_types'], 5);
        add_action('init', [$this, 'register_taxonomies'], 6);
        add_action('admin_init', [$this, 'run_migration']);
        
        // Hooks de funcionalidade
        add_action('acf/save_post', [$this, 'auto_fill_investor_field'], 20);
        add_action('acf/save_post', [$this, 'update_investment_timestamp'], 20);
        
        // Colunas personalizadas
        add_filter('manage_investment_posts_columns', [$this, 'investment_columns']);
        add_action('manage_investment_posts_custom_column', [$this, 'investment_column_content'], 10, 2);
        add_filter('manage_aporte_posts_columns', [$this, 'aporte_columns']);
        add_action('manage_aporte_posts_custom_column', [$this, 'aporte_column_content'], 10, 2);
        
        // Ações rápidas
        add_filter('post_row_actions', [$this, 'investment_row_actions'], 10, 2);
        add_filter('post_row_actions', [$this, 'aporte_row_actions'], 10, 2);
        
        // Handlers de ações
        add_action('admin_action_duplicar_investment', [$this, 'duplicate_investment']);
        add_action('admin_action_duplicar_aporte', [$this, 'duplicate_aporte']);
        add_action('admin_action_marcar_vendido', [$this, 'mark_sold']);
        add_action('admin_action_recalcular_scp', [$this, 'recalculate_scp']);
        
        // Auto-preencher investment ao criar aporte
        add_action('admin_head-post-new.php', [$this, 'auto_select_investment']);
        
        // Notices
        add_action('admin_notices', [$this, 'admin_notices']);
    }
    
    /**
     * Verificar se deve registrar CPTs (evitar conflito com plugin)
     */
    private function should_register_cpts() {
        // Se o plugin sky-invest-panel estiver ativo, não registrar
        if (function_exists('is_plugin_active') && is_plugin_active('sky-invest-panel/sky-invest-panel.php')) {
            return false;
        }
        
        // Se os CPTs já estiverem registrados por outro plugin, não registrar
        if (post_type_exists('investment') && !get_option('s_invest_unified_migrated')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Registrar CPT Investimentos Unificado (Trade + SCP)
     */
    public function register_post_types() {
        if (!$this->should_register_cpts()) {
            return;
        }
        // CPT Investimentos
        register_post_type('investment', [
            'labels' => [
                'name' => 'Investimentos',
                'singular_name' => 'Investimento',
                'add_new_item' => 'Adicionar Novo Investimento',
                'edit_item' => 'Editar Investimento',
                'all_items' => 'Todos os Investimentos',
                'view_item' => 'Ver Investimento',
                'search_items' => 'Buscar Investimentos',
                'not_found' => 'Nenhum investimento encontrado',
                'menu_name' => 'Investimentos'
            ],
            'public' => true,
            'show_ui' => true,
            'menu_icon' => 'dashicons-chart-line',
            'menu_position' => 20,
            'supports' => ['title', 'editor', 'custom-fields', 'thumbnail'],
            'show_in_rest' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'investimentos'],
            'capability_type' => 'post',
            'show_in_admin_bar' => true,
        ]);
        
        // CPT Aportes
        register_post_type('aporte', [
            'labels' => [
                'name' => 'Aportes',
                'singular_name' => 'Aporte',
                'add_new_item' => 'Adicionar Novo Aporte',
                'edit_item' => 'Editar Aporte',
                'all_items' => 'Todos os Aportes',
                'view_item' => 'Ver Aporte',
                'search_items' => 'Buscar Aportes',
                'not_found' => 'Nenhum aporte encontrado',
                'menu_name' => 'Aportes'
            ],
            'public' => false,
            'show_ui' => true,
            'menu_icon' => 'dashicons-money-alt',
            'menu_position' => 21,
            'supports' => ['title', 'custom-fields'],
            'show_in_rest' => false,
            'capability_type' => 'post',
        ]);
        
        // CPT Comunicados
        register_post_type('comunicado', [
            'labels' => [
                'name' => 'Comunicados',
                'singular_name' => 'Comunicado',
                'add_new_item' => 'Adicionar Novo Comunicado',
                'edit_item' => 'Editar Comunicado',
                'all_items' => 'Todos os Comunicados',
                'view_item' => 'Ver Comunicado',
                'search_items' => 'Buscar Comunicados',
                'not_found' => 'Nenhum comunicado encontrado',
                'menu_name' => 'Comunicados'
            ],
            'public' => true,
            'show_ui' => true,
            'menu_icon' => 'dashicons-megaphone',
            'menu_position' => 22,
            'supports' => ['title', 'editor', 'custom-fields', 'thumbnail'],
            'show_in_rest' => true,
            'has_archive' => false,
            'rewrite' => ['slug' => 'comunicados'],
            'capability_type' => 'post',
        ]);
        
        // CPT FAQ
        register_post_type('faq', [
            'labels' => [
                'name' => 'FAQ',
                'singular_name' => 'FAQ',
                'add_new_item' => 'Adicionar Nova FAQ',
                'edit_item' => 'Editar FAQ',
                'all_items' => 'Todas as FAQs',
                'view_item' => 'Ver FAQ',
                'search_items' => 'Buscar FAQs',
                'not_found' => 'Nenhuma FAQ encontrada',
                'menu_name' => 'FAQ'
            ],
            'public' => true,
            'show_ui' => true,
            'menu_icon' => 'dashicons-editor-help',
            'menu_position' => 23,
            'supports' => ['title', 'editor', 'custom-fields'],
            'show_in_rest' => true,
            'has_archive' => true,
            'rewrite' => ['slug' => 'faq'],
            'capability_type' => 'post',
        ]);
    }
    
    /**
     * Registrar taxonomias
     */
    public function register_taxonomies() {
        if (!$this->should_register_cpts()) {
            return;
        }
        // Classe de Ativos (select único)
        register_taxonomy('tipo_produto', ['investment'], [
            'labels' => [
                'name' => 'Classe de Ativos',
                'singular_name' => 'Classe de Ativo',
                'add_new_item' => 'Adicionar Nova Classe',
                'edit_item' => 'Editar Classe',
                'menu_name' => 'Classes de Ativos',
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'classe-ativos'],
            'show_in_rest' => true,
            'meta_box_cb' => [$this, 'tipo_produto_single_select_metabox'],
        ]);
        
        // Impostos
        register_taxonomy('imposto', ['investment'], [
            'labels' => [
                'name' => 'Impostos',
                'singular_name' => 'Imposto',
                'add_new_item' => 'Adicionar Novo Imposto',
                'edit_item' => 'Editar Imposto',
                'menu_name' => 'Impostos',
            ],
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => ['slug' => 'impostos'],
            'show_in_rest' => true,
        ]);
    }
    
    /**
     * Metabox customizado para select único de tipo de produto
     */
    public function tipo_produto_single_select_metabox($post, $box) {
        $taxonomy = $box['args']['taxonomy'];
        $tax = get_taxonomy($taxonomy);
        $name = 'tax_input[' . $taxonomy . ']';
        $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => 0));
        $current = wp_get_object_terms($post->ID, $taxonomy, array('fields' => 'ids'));
        $current_id = !empty($current) ? $current[0] : 0;
        ?>
        <div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
            <div class="tabs-panel">
                <select name="<?php echo $name; ?>" style="width: 100%; margin-bottom: 10px;">
                    <option value="">Selecione uma classe de ativo</option>
                    <?php foreach ($terms as $term) : ?>
                        <option value="<?php echo $term->term_id; ?>" <?php selected($current_id, $term->term_id); ?>>
                            <?php echo esc_html($term->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p><em>Selecione apenas uma classe de ativo para este investimento.</em></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Migração automática de dados
     */
    public function run_migration() {
        if (get_option('s_invest_unified_migrated') === 'completed') {
            return;
        }
        
        // Criar termos da taxonomia se não existirem
        $classes_ativos = [
            'Private SCP' => 'private-scp',
            'Compra em Lote' => 'compra-em-lote', 
            'Land Bank' => 'land-bank'
        ];
        
        foreach ($classes_ativos as $nome => $slug) {
            if (!term_exists($slug, 'tipo_produto')) {
                wp_insert_term($nome, 'tipo_produto', ['slug' => $slug]);
            }
        }
        
        // Migrar posts existentes se necessário
        $this->migrate_existing_data();
        
        update_option('s_invest_unified_migrated', 'completed');
        update_option('s_invest_unified_migration_date', current_time('mysql'));
    }
    
    /**
     * Migrar dados existentes
     */
    private function migrate_existing_data() {
        $investments = get_posts([
            'post_type' => 'investment',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'fields' => 'ids'
        ]);
        
        foreach ($investments as $post_id) {
            // Verificar se já tem tipo_produto definido
            $existing_terms = get_the_terms($post_id, 'tipo_produto');
            if (!empty($existing_terms) && !is_wp_error($existing_terms)) {
                continue; // Já tem tipo definido
            }
            
            // Tentar migrar da taxonomia modalidade antiga
            $modalidades = get_the_terms($post_id, 'modalidade');
            if ($modalidades && !is_wp_error($modalidades)) {
                $modalidade_nome = strtolower($modalidades[0]->name);
                
                $novo_termo = '';
                switch ($modalidade_nome) {
                    case 'scp':
                        $novo_termo = 'private-scp';
                        break;
                    case 'trade':
                        $novo_termo = 'compra-em-lote';
                        break;
                    default:
                        $novo_termo = 'compra-em-lote';
                }
                
                $term = get_term_by('slug', $novo_termo, 'tipo_produto');
                if ($term) {
                    wp_set_object_terms($post_id, [$term->term_id], 'tipo_produto', false);
                }
            }
        }
    }
    
    /**
     * Auto-preencher campo investidor com usuário logado
     */
    public function auto_fill_investor_field($post_id) {
        if (get_post_type($post_id) === 'aporte' && !is_admin()) {
            $investidor_atual = get_field('rel_investidor', $post_id);
            if (empty($investidor_atual) && is_user_logged_in()) {
                update_field('rel_investidor', get_current_user_id(), $post_id);
            }
        }
    }
    
    /**
     * Atualizar timestamp de investimento
     */
    public function update_investment_timestamp($post_id) {
        if (get_post_type($post_id) !== 'investment') {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        $hora = current_time('H:i');
        update_field('horario_atualizacao', $hora, $post_id);
    }
    
    /**
     * Colunas personalizadas para investimentos
     */
    public function investment_columns($columns) {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['classe_ativo'] = 'Classe de Ativo';
                $new_columns['progresso'] = 'Progresso';
                $new_columns['valor_info'] = 'Valor/Status';
                $new_columns['investidores'] = 'Investidores';
            }
        }
        unset($new_columns['date']);
        return $new_columns;
    }
    
    public function investment_column_content($column, $post_id) {
        switch($column) {
            case 'classe_ativo':
                $termo = get_the_terms($post_id, 'tipo_produto');
                if ($termo && !is_wp_error($termo)) {
                    $nome = $termo[0]->name;
                    $colors = [
                        'Private SCP' => '#f093fb',
                        'Compra em Lote' => '#667eea', 
                        'Land Bank' => '#4facfe'
                    ];
                    $color = $colors[$nome] ?? '#718096';
                    echo "<span style='background: {$color}; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: bold;'>" . 
                         esc_html($nome) . "</span>";
                } else {
                    echo '—';
                }
                break;
                
            case 'progresso':
                if ($this->is_scp_investment($post_id)) {
                    $total_cotas = get_field('total_cotas', $post_id);
                    $cotas_vendidas = get_field('cotas_vendidas', $post_id);
                    if ($total_cotas > 0) {
                        $percentual = round(($cotas_vendidas / $total_cotas) * 100, 1);
                        echo "{$cotas_vendidas}/{$total_cotas}";
                        echo "<br><small style='color: #666;'>{$percentual}% vendido</small>";
                    } else {
                        echo '0/0';
                    }
                } else {
                    $status = get_field('status_investment') ?: 'ativo';
                    $labels = [
                        'ativo' => 'Ativo',
                        'vendido' => 'Vendido',
                        'pausado' => 'Pausado'
                    ];
                    echo $labels[$status] ?? 'Ativo';
                }
                break;
                
            case 'valor_info':
                if ($this->is_scp_investment($post_id)) {
                    $valor_cota = get_field('valor_cota', $post_id);
                    $cotas_vendidas = get_field('cotas_vendidas', $post_id);
                    $valor_captado = floatval($valor_cota) * floatval($cotas_vendidas);
                    echo 'R$ ' . number_format($valor_captado, 2, ',', '.');
                } else {
                    $total_captado = get_field('total_captado', $post_id);
                    echo 'R$ ' . number_format($total_captado ?: 0, 2, ',', '.');
                }
                break;
                
            case 'investidores':
                $aportes = get_posts([
                    'post_type' => 'aporte',
                    'posts_per_page' => -1,
                    'meta_query' => [['key' => 'investment_id', 'value' => $post_id]],
                    'fields' => 'ids'
                ]);
                $count = count($aportes);
                echo $count . ' aporte' . ($count != 1 ? 's' : '');
                break;
        }
    }
    
    /**
     * Colunas personalizadas para aportes
     */
    public function aporte_columns($columns) {
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['tipo_aporte'] = 'Tipo';
                $new_columns['investimento'] = 'Investimento';
                $new_columns['investidor'] = 'Investidor';
                $new_columns['valor_info'] = 'Valor/Status';
                $new_columns['status_geral'] = 'Status';
            }
        }
        unset($new_columns['date']);
        return $new_columns;
    }
    
    public function aporte_column_content($column, $post_id) {
        switch ($column) {
            case 'tipo_aporte':
                $investment_id = get_field('investment_id', $post_id);
                $tipo = 'Trade';
                
                if ($investment_id && $this->is_scp_investment($investment_id)) {
                    $tipo = 'SCP';
                }
                
                $colors = [
                    'Trade' => '#667eea',
                    'SCP' => '#f093fb',
                ];
                $color = $colors[$tipo] ?? '#718096';
                
                echo "<span style='background: {$color}; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: bold;'>" . 
                     esc_html($tipo) . "</span>";
                break;
                
            case 'investimento':
                $inv_id = get_field('investment_id', $post_id);
                if ($inv_id) {
                    $title = get_the_title($inv_id);
                    echo '<a href="' . get_edit_post_link($inv_id) . '">' . esc_html($title) . '</a>';
                    
                    if ($this->is_scp_investment($inv_id)) {
                        $nome_ativo = get_field('nome_ativo', $inv_id);
                        if ($nome_ativo) {
                            echo '<br><small style="color: #666;">' . esc_html($nome_ativo) . '</small>';
                        }
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'investidor':
                $user_id = get_field('investidor_id', $post_id);
                if ($user_id) {
                    $user = get_userdata($user_id);
                    if ($user) {
                        echo esc_html($user->display_name);
                        echo '<br><small style="color: #666;">' . esc_html($user->user_email) . '</small>';
                    }
                } else {
                    echo '—';
                }
                break;
                
            case 'valor_info':
                $investment_id = get_field('investment_id', $post_id);
                
                if ($investment_id && $this->is_scp_investment($investment_id)) {
                    $cotas = get_field('quantidade_cotas', $post_id);
                    $valor_aportado = get_field('valor_aportado', $post_id);
                    
                    echo '<strong>' . ($cotas ?: '0') . ' cotas</strong>';
                    if ($valor_aportado) {
                        echo '<br><small>R$ ' . number_format($valor_aportado, 2, ',', '.') . '</small>';
                    }
                } else {
                    $valor_compra = get_field('valor_compra', $post_id);
                    $valor_atual = get_field('valor_atual', $post_id);
                    
                    echo 'R$ ' . number_format($valor_compra ?: 0, 2, ',', '.');
                    
                    if ($valor_atual && $valor_atual != $valor_compra) {
                        $diferenca = $valor_atual - $valor_compra;
                        $cor = $diferenca >= 0 ? '#28a745' : '#dc3545';
                        echo '<br><small style="color: ' . $cor . ';">';
                        echo 'Atual: R$ ' . number_format($valor_atual, 2, ',', '.');
                        echo '</small>';
                    }
                }
                break;
                
            case 'status_geral':
                $investment_id = get_field('investment_id', $post_id);
                
                if ($investment_id && $this->is_scp_investment($investment_id)) {
                    $percentual = get_field('percentual_scp', $post_id);
                    $dividendo = get_field('dividendo_valor', $post_id);
                    
                    if ($percentual) {
                        echo '<strong>' . number_format($percentual, 2) . '%</strong>';
                    }
                    if ($dividendo > 0) {
                        echo '<br><small style="color: #28a745;">Div: R$ ' . number_format($dividendo, 2, ',', '.') . '</small>';
                    }
                } else {
                    $vendido = get_field('venda_status', $post_id);
                    if ($vendido) {
                        $data_venda = get_field('venda_data', $post_id);
                        echo '<span style="color: #28a745; font-weight: bold;">✓ Vendido</span>';
                        if ($data_venda) {
                            echo '<br><small>' . esc_html($data_venda) . '</small>';
                        }
                    } else {
                        echo '<span style="color: #007cba;">● Ativo</span>';
                    }
                }
                break;
        }
    }
    
    /**
     * Ações rápidas para investimentos
     */
    public function investment_row_actions($actions, $post) {
        if ($post->post_type === 'investment') {
            $add_url = admin_url('post-new.php?post_type=aporte&investment=' . $post->ID);
            $actions['add_aporte'] = '<a href="' . esc_url($add_url) . '" style="color: #0073aa;">+ Aporte</a>';
            
            $duplicate_url = wp_nonce_url(
                add_query_arg([
                    'action' => 'duplicar_investment',
                    'post' => $post->ID,
                ], admin_url('edit.php?post_type=investment')),
                'duplicar_investment_' . $post->ID
            );
            $actions['duplicate'] = '<a href="' . esc_url($duplicate_url) . '">Duplicar</a>';
        }
        return $actions;
    }
    
    /**
     * Ações rápidas para aportes
     */
    public function aporte_row_actions($actions, $post) {
        if ($post->post_type === 'aporte') {
            $investment_id = get_field('investment_id', $post->ID);
            
            // Botão de duplicar
            $url = wp_nonce_url(
                add_query_arg([
                    'action' => 'duplicar_aporte',
                    'post' => $post->ID,
                ], admin_url('edit.php?post_type=aporte')),
                'duplicar_aporte_' . $post->ID
            );
            $actions['duplicar_aporte'] = '<a href="' . esc_url($url) . '">Duplicar</a>';
            
            // Ações específicas por tipo
            if ($investment_id && $this->is_scp_investment($investment_id)) {
                $recalc_url = wp_nonce_url(
                    add_query_arg([
                        'action' => 'recalcular_scp',
                        'post' => $post->ID,
                    ], admin_url('edit.php?post_type=aporte')),
                    'recalcular_scp_' . $post->ID
                );
                $actions['recalcular_scp'] = '<a href="' . esc_url($recalc_url) . '" style="color: #0073aa;">🔄 Recalcular</a>';
            } else {
                $vendido = get_field('venda_status', $post->ID);
                
                if (!$vendido) {
                    $url = wp_nonce_url(
                        add_query_arg([
                            'action' => 'marcar_vendido',
                            'post' => $post->ID,
                        ], admin_url('edit.php?post_type=aporte')),
                        'marcar_vendido_' . $post->ID
                    );
                    $actions['marcar_vendido'] = '<a href="' . esc_url($url) . '" style="color: #d63384;">Marcar como Vendido</a>';
                } else {
                    $actions['status_vendido'] = '<span style="color: #28a745; font-weight: bold;">✓ Vendido</span>';
                }
            }
        }
        return $actions;
    }
    
    /**
     * Auto-preencher investment ao criar aporte
     */
    public function auto_select_investment() {
        global $typenow;
        
        if ($typenow === 'aporte' && isset($_GET['investment'])) {
            $investment_id = absint($_GET['investment']);
            ?>
            <script>
            jQuery(document).ready(function($) {
                setTimeout(function() {
                    $('select[name*="investment_id"]').val('<?php echo $investment_id; ?>').trigger('change');
                }, 1000);
            });
            </script>
            <?php
        }
    }
    
    /**
     * Handlers de ações administrativas
     */
    public function duplicate_investment() {
        if (!isset($_GET['post'])) wp_die('Investimento não especificado');
        
        $post_id = absint($_GET['post']);
        check_admin_referer('duplicar_investment_' . $post_id);
        
        if (!current_user_can('edit_posts', $post_id)) wp_die('Sem permissão');
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'investment') wp_die('Investimento inválido');

        $new_post = [
            'post_title' => $post->post_title . ' (Cópia)',
            'post_content' => $post->post_content,
            'post_status' => 'draft',
            'post_type' => 'investment',
            'post_author' => get_current_user_id(),
        ];
        
        $new_id = wp_insert_post($new_post);
        
        // Copiar metadados
        $exclude_meta = ['total_captado', 'cotas_vendidas'];
        foreach (get_post_meta($post_id) as $key => $values) {
            if (in_array($key, $exclude_meta)) continue;
            foreach ($values as $value) {
                update_post_meta($new_id, $key, maybe_unserialize($value));
            }
        }
        
        // Copiar taxonomias
        foreach (get_object_taxonomies('investment') as $tax) {
            wp_set_object_terms(
                $new_id,
                wp_get_object_terms($post_id, $tax, ['fields' => 'slugs']),
                $tax
            );
        }
        
        wp_redirect(admin_url('post.php?action=edit&post=' . $new_id . '&duplicated=1'));
        exit;
    }
    
    public function duplicate_aporte() {
        if (!isset($_GET['post'])) wp_die('Aporte não especificado');
        
        $post_id = absint($_GET['post']);
        check_admin_referer('duplicar_aporte_' . $post_id);
        
        if (!current_user_can('edit_posts', $post_id)) wp_die('Sem permissão');
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'aporte') wp_die('Aporte inválido');

        $new_post = [
            'post_title' => $post->post_title . ' (Cópia)',
            'post_status' => 'draft',
            'post_type' => 'aporte',
            'post_author' => get_current_user_id(),
        ];
        
        $new_id = wp_insert_post($new_post);
        
        // Copiar metadados (excluir alguns campos específicos)
        $exclude_meta = ['investidor_id', 'venda_status', 'venda_data', 'venda_valor'];
        foreach (get_post_meta($post_id) as $key => $values) {
            if (in_array($key, $exclude_meta)) continue;
            foreach ($values as $value) {
                update_post_meta($new_id, $key, maybe_unserialize($value));
            }
        }
        
        wp_redirect(admin_url('post.php?action=edit&post=' . $new_id . '&duplicated=1'));
        exit;
    }
    
    public function mark_sold() {
        if (!isset($_GET['post'])) wp_die('Aporte não especificado');
        
        $post_id = absint($_GET['post']);
        check_admin_referer('marcar_vendido_' . $post_id);
        
        if (!current_user_can('edit_posts', $post_id)) wp_die('Sem permissão');
        
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'aporte') wp_die('Aporte inválido');

        update_field('venda_status', true, $post_id);
        update_field('venda_data', date('d/m/Y'), $post_id);
        
        wp_redirect(admin_url('post.php?action=edit&post=' . $post_id . '&marcado_vendido=1'));
        exit;
    }
    
    public function recalculate_scp() {
        if (!isset($_GET['post'])) wp_die('Aporte não especificado');
        
        $post_id = absint($_GET['post']);
        check_admin_referer('recalcular_scp_' . $post_id);
        
        if (!current_user_can('edit_posts', $post_id)) wp_die('Sem permissão');
        
        // Forçar recálculo SCP
        do_action('s_invest_force_recalculate_scp', $post_id);
        
        wp_redirect(admin_url('post.php?action=edit&post=' . $post_id . '&recalculado=1'));
        exit;
    }
    
    /**
     * Notices para feedback
     */
    public function admin_notices() {
        if (!empty($_GET['duplicated']) && $_GET['duplicated'] == '1') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>✅ Item duplicado com sucesso!</strong></p>';
            echo '</div>';
        }
        
        if (!empty($_GET['marcado_vendido']) && $_GET['marcado_vendido'] == '1') {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>⚠️ Aporte marcado como vendido!</strong> Preencha os dados de venda.</p>';
            echo '</div>';
        }
        
        if (!empty($_GET['recalculado']) && $_GET['recalculado'] == '1') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>✅ Dados SCP recalculados com sucesso!</strong></p>';
            echo '</div>';
        }
    }
    
    /**
     * Helper function para verificar se é investimento SCP
     */
    private function is_scp_investment($investment_id) {
        $terms = get_the_terms($investment_id, 'tipo_produto');
        if ($terms && !is_wp_error($terms)) {
            return strtolower($terms[0]->slug) === 'private-scp';
        }
        return false;
    }
}

// Inicializar
S_Invest_CPT_Manager::get_instance();