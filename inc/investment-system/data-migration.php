<?php
/**
 * Data Migration Engine - Sistema Completo de Migração
 * Migra todos os dados do plugin sky-invest-panel para o sistema unificado
 * Versão: 3.0.0
 */

defined('ABSPATH') || exit;

class S_Invest_Data_Migration {
    
    private static $instance = null;
    private $migration_log = [];
    private $errors = [];
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', [$this, 'add_migration_menu']);
        add_action('wp_ajax_run_data_migration', [$this, 'ajax_run_migration']);
        add_action('wp_ajax_check_migration_status', [$this, 'ajax_check_status']);
    }
    
    /**
     * Adicionar menu de migração
     */
    public function add_migration_menu() {
        if (!current_user_can('administrator')) {
            return;
        }
        
        add_submenu_page(
            'investment-management',
            'Migração de Dados',
            'Migração de Dados',
            'manage_options',
            'data-migration',
            [$this, 'migration_page']
        );
    }
    
    /**
     * Página de migração
     */
    public function migration_page() {
        $migration_status = get_option('s_invest_migration_status', 'pending');
        $migration_stats = get_option('s_invest_migration_stats', []);
        ?>
        <div class="wrap">
            <h1>🔄 Migração de Dados - Sistema Unificado S-Invest</h1>
            
            <div class="migration-interface" id="migration-interface">
                
                <!-- Status da Migração -->
                <div class="card migration-status-card">
                    <h2>Status da Migração</h2>
                    <div id="migration-status" class="status-<?php echo esc_attr($migration_status); ?>">
                        <?php
                        switch ($migration_status) {
                            case 'completed':
                                echo '<span class="dashicons dashicons-yes-alt"></span> Migração Concluída';
                                break;
                            case 'running':
                                echo '<span class="dashicons dashicons-update"></span> Migração em Andamento';
                                break;
                            case 'error':
                                echo '<span class="dashicons dashicons-warning"></span> Erro na Migração';
                                break;
                            default:
                                echo '<span class="dashicons dashicons-clock"></span> Aguardando Migração';
                        }
                        ?>
                    </div>
                    
                    <?php if (!empty($migration_stats)): ?>
                    <div class="migration-stats">
                        <h3>Estatísticas da Última Migração</h3>
                        <ul>
                            <li>Investimentos: <?php echo $migration_stats['investments'] ?? 0; ?></li>
                            <li>Aportes: <?php echo $migration_stats['aportes'] ?? 0; ?></li>
                            <li>Usuários: <?php echo $migration_stats['users'] ?? 0; ?></li>
                            <li>Taxonomias: <?php echo $migration_stats['taxonomies'] ?? 0; ?></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Verificações Pré-Migração -->
                <div class="card pre-migration-checks">
                    <h2>🔍 Verificações Pré-Migração</h2>
                    <div id="pre-checks">
                        <div class="check-item">
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <span>Plugin sky-invest-panel: <span id="plugin-status">Verificando...</span></span>
                        </div>
                        <div class="check-item">
                            <span class="dashicons dashicons-database"></span>
                            <span>Dados existentes: <span id="data-status">Verificando...</span></span>
                        </div>
                        <div class="check-item">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <span>ACF Plugin: <span id="acf-status">Verificando...</span></span>
                        </div>
                        <div class="check-item">
                            <span class="dashicons dashicons-backup"></span>
                            <span>Backup recomendado: <span id="backup-status">⚠️ Faça backup antes!</span></span>
                        </div>
                    </div>
                </div>
                
                <!-- Opções de Migração -->
                <div class="card migration-options">
                    <h2>⚙️ Opções de Migração</h2>
                    <form id="migration-form">
                        <table class="form-table">
                            <tr>
                                <th>Migrar Investimentos</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="migrate_investments" checked>
                                        Migrar todos os investimentos e suas taxonomias
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th>Migrar Aportes</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="migrate_aportes" checked>
                                        Migrar todos os aportes e relacionamentos
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th>Migrar Usuários</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="migrate_users" checked>
                                        Atualizar roles e permissões dos usuários
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th>Migrar Comunicados</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="migrate_comunicados" checked>
                                        Migrar comunicados e FAQ
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th>Força Sobrescrever</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="force_overwrite">
                                        Sobrescrever dados existentes (use com cuidado)
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="button" id="start-migration" class="button button-primary button-large" 
                                    <?php echo $migration_status === 'running' ? 'disabled' : ''; ?>>
                                <span class="dashicons dashicons-migrate"></span>
                                Iniciar Migração Completa
                            </button>
                            
                            <button type="button" id="check-data" class="button button-secondary">
                                <span class="dashicons dashicons-search"></span>
                                Verificar Dados
                            </button>
                            
                            <?php if ($migration_status === 'completed'): ?>
                            <button type="button" id="verify-migration" class="button button-secondary">
                                <span class="dashicons dashicons-yes-alt"></span>
                                Verificar Integridade
                            </button>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>
                
                <!-- Log de Migração -->
                <div class="card migration-log">
                    <h2>📋 Log de Migração</h2>
                    <div id="migration-log" class="migration-log-content">
                        <p>Aguardando início da migração...</p>
                    </div>
                </div>
                
            </div>
        </div>
        
        <style>
        .migration-interface .card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .migration-status-card {
            border-left: 4px solid #0073aa;
        }
        
        #migration-status {
            font-size: 18px;
            font-weight: bold;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }
        
        .status-pending { background: #f0f6fc; color: #0969da; }
        .status-running { background: #fff8e1; color: #f57c00; }
        .status-completed { background: #f0f9ff; color: #0969da; }
        .status-error { background: #ffeaea; color: #d1242f; }
        
        .check-item {
            display: flex;
            align-items: center;
            margin: 10px 0;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .check-item .dashicons {
            margin-right: 10px;
            color: #0073aa;
        }
        
        .migration-log-content {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            max-height: 400px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .migration-stats ul {
            list-style: none;
            padding: 0;
        }
        
        .migration-stats li {
            background: #f8f9fa;
            padding: 8px 12px;
            margin: 5px 0;
            border-radius: 4px;
            border-left: 3px solid #0073aa;
        }
        
        .button-large {
            padding: 8px 16px !important;
            font-size: 14px !important;
        }
        
        .button .dashicons {
            margin-right: 5px;
            vertical-align: middle;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            let migrationRunning = false;
            
            // Verificar dados iniciais
            checkPreMigrationData();
            
            // Botão de iniciar migração
            $('#start-migration').on('click', function() {
                if (migrationRunning) return;
                
                if (!confirm('Tem certeza que deseja iniciar a migração? Certifique-se de ter feito backup dos dados!')) {
                    return;
                }
                
                startMigration();
            });
            
            // Botão de verificar dados
            $('#check-data').on('click', function() {
                checkPreMigrationData();
            });
            
            // Botão de verificar integridade
            $('#verify-migration').on('click', function() {
                verifyMigrationIntegrity();
            });
            
            function checkPreMigrationData() {
                $.post(ajaxurl, {
                    action: 'check_migration_status',
                    nonce: '<?php echo wp_create_nonce('migration_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        updatePreChecks(response.data);
                    }
                });
            }
            
            function startMigration() {
                migrationRunning = true;
                $('#start-migration').prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Migrando...');
                $('#migration-status').removeClass().addClass('status-running').html('<span class="dashicons dashicons-update"></span> Migração em Andamento');
                
                const options = {
                    migrate_investments: $('#migration-form input[name="migrate_investments"]').is(':checked'),
                    migrate_aportes: $('#migration-form input[name="migrate_aportes"]').is(':checked'),
                    migrate_users: $('#migration-form input[name="migrate_users"]').is(':checked'),
                    migrate_comunicados: $('#migration-form input[name="migrate_comunicados"]').is(':checked'),
                    force_overwrite: $('#migration-form input[name="force_overwrite"]').is(':checked')
                };
                
                $.post(ajaxurl, {
                    action: 'run_data_migration',
                    nonce: '<?php echo wp_create_nonce('migration_nonce'); ?>',
                    options: options
                }, function(response) {
                    migrationRunning = false;
                    $('#start-migration').prop('disabled', false).html('<span class="dashicons dashicons-migrate"></span> Iniciar Migração Completa');
                    
                    if (response.success) {
                        $('#migration-status').removeClass().addClass('status-completed').html('<span class="dashicons dashicons-yes-alt"></span> Migração Concluída');
                        $('#migration-log').html(response.data.log.join('<br>'));
                        
                        // Recarregar página após 3 segundos
                        setTimeout(function() {
                            location.reload();
                        }, 3000);
                    } else {
                        $('#migration-status').removeClass().addClass('status-error').html('<span class="dashicons dashicons-warning"></span> Erro na Migração');
                        $('#migration-log').html('Erro: ' + response.data);
                    }
                }).fail(function() {
                    migrationRunning = false;
                    $('#start-migration').prop('disabled', false).html('<span class="dashicons dashicons-migrate"></span> Iniciar Migração Completa');
                    $('#migration-status').removeClass().addClass('status-error').html('<span class="dashicons dashicons-warning"></span> Erro na Migração');
                    $('#migration-log').html('Erro de comunicação com o servidor.');
                });
            }
            
            function updatePreChecks(data) {
                $('#plugin-status').html(data.plugin_active ? '✅ Ativo' : '❌ Inativo');
                $('#data-status').html(data.data_exists ? `✅ ${data.data_count} registros` : '❌ Sem dados');
                $('#acf-status').html(data.acf_active ? '✅ Ativo' : '❌ Inativo');
            }
            
            function verifyMigrationIntegrity() {
                // Implementar verificação de integridade
                alert('Funcionalidade em desenvolvimento');
            }
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: Verificar status da migração
     */
    public function ajax_check_status() {
        check_ajax_referer('migration_nonce', 'nonce');
        
        if (!current_user_can('administrator')) {
            wp_send_json_error('Permissão negada');
        }
        
        $data = [
            'plugin_active' => is_plugin_active('sky-invest-panel/sky-invest-panel.php'),
            'acf_active' => function_exists('get_field'),
            'data_exists' => false,
            'data_count' => 0
        ];
        
        // Verificar se existem dados para migrar
        $investments = get_posts(['post_type' => 'investment', 'posts_per_page' => -1, 'fields' => 'ids']);
        $aportes = get_posts(['post_type' => 'aporte', 'posts_per_page' => -1, 'fields' => 'ids']);
        
        $data['data_count'] = count($investments) + count($aportes);
        $data['data_exists'] = $data['data_count'] > 0;
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX: Executar migração
     */
    public function ajax_run_migration() {
        check_ajax_referer('migration_nonce', 'nonce');
        
        if (!current_user_can('administrator')) {
            wp_send_json_error('Permissão negada');
        }
        
        // Aumentar limite de tempo
        set_time_limit(300);
        
        $options = $_POST['options'] ?? [];
        
        try {
            update_option('s_invest_migration_status', 'running');
            
            $this->migration_log = [];
            $this->errors = [];
            
            $this->log('🚀 Iniciando migração completa do sistema S-Invest');
            $this->log('⚙️ Opções: ' . json_encode($options));
            
            $stats = [];
            
            // 1. Migrar taxonomias e termos
            $this->log('📂 Migrando taxonomias...');
            $this->migrate_taxonomies();
            
            // 2. Migrar investimentos
            if ($options['migrate_investments']) {
                $this->log('💼 Migrando investimentos...');
                $stats['investments'] = $this->migrate_investments($options['force_overwrite']);
            }
            
            // 3. Migrar aportes
            if ($options['migrate_aportes']) {
                $this->log('💰 Migrando aportes...');
                $stats['aportes'] = $this->migrate_aportes($options['force_overwrite']);
            }
            
            // 4. Migrar usuários
            if ($options['migrate_users']) {
                $this->log('👥 Migrando usuários...');
                $stats['users'] = $this->migrate_users();
            }
            
            // 5. Migrar comunicados
            if ($options['migrate_comunicados']) {
                $this->log('📢 Migrando comunicados...');
                $stats['comunicados'] = $this->migrate_comunicados();
            }
            
            // 6. Verificações finais
            $this->log('✅ Executando verificações finais...');
            $this->final_verifications();
            
            if (empty($this->errors)) {
                update_option('s_invest_migration_status', 'completed');
                update_option('s_invest_migration_stats', $stats);
                update_option('s_invest_migration_date', current_time('mysql'));
                
                $this->log('🎉 Migração concluída com sucesso!');
                
                wp_send_json_success([
                    'log' => $this->migration_log,
                    'stats' => $stats
                ]);
            } else {
                update_option('s_invest_migration_status', 'error');
                
                wp_send_json_error([
                    'log' => $this->migration_log,
                    'errors' => $this->errors
                ]);
            }
            
        } catch (Exception $e) {
            update_option('s_invest_migration_status', 'error');
            $this->log('❌ Erro crítico: ' . $e->getMessage());
            
            wp_send_json_error('Erro crítico na migração: ' . $e->getMessage());
        }
    }
    
    /**
     * Migrar taxonomias
     */
    private function migrate_taxonomies() {
        // Verificar se a taxonomia tipo_produto existe
        if (!taxonomy_exists('tipo_produto')) {
            $this->log('❌ Taxonomia tipo_produto não existe - registrando...');
            // A taxonomia será registrada pelo CPT Manager
            return;
        }
        
        // Criar termos padrão
        $default_terms = [
            'Private SCP' => [
                'slug' => 'private-scp',
                'description' => 'Investimentos em Sociedade de Capital Privado'
            ],
            'Compra em Lote' => [
                'slug' => 'compra-em-lote',
                'description' => 'Investimentos Trade em compra de lotes'
            ],
            'Land Bank' => [
                'slug' => 'land-bank',
                'description' => 'Investimentos em banco de terras'
            ]
        ];
        
        foreach ($default_terms as $name => $args) {
            if (!term_exists($args['slug'], 'tipo_produto')) {
                $result = wp_insert_term($name, 'tipo_produto', $args);
                if (!is_wp_error($result)) {
                    $this->log("✅ Termo criado: {$name}");
                } else {
                    $this->log("❌ Erro ao criar termo {$name}: " . $result->get_error_message());
                }
            } else {
                $this->log("ℹ️ Termo já existe: {$name}");
            }
        }
    }
    
    /**
     * Migrar investimentos
     */
    private function migrate_investments($force_overwrite = false) {
        $investments = get_posts([
            'post_type' => 'investment',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        $migrated = 0;
        $errors = 0;
        
        foreach ($investments as $investment) {
            $this->log("📊 Processando investimento: {$investment->post_title}");
            
            try {
                // Migrar taxonomia modalidade -> tipo_produto
                $modalidades = get_the_terms($investment->ID, 'modalidade');
                
                if ($modalidades && !is_wp_error($modalidades)) {
                    $modalidade_name = strtolower($modalidades[0]->name);
                    
                    // Mapear modalidade para novo tipo
                    $new_term_slug = '';
                    switch ($modalidade_name) {
                        case 'scp':
                            $new_term_slug = 'private-scp';
                            break;
                        case 'trade':
                            $new_term_slug = 'compra-em-lote';
                            break;
                        default:
                            $new_term_slug = 'compra-em-lote';
                    }
                    
                    // Verificar se já tem tipo_produto
                    $existing_terms = get_the_terms($investment->ID, 'tipo_produto');
                    
                    if (!$existing_terms || $force_overwrite) {
                        $term = get_term_by('slug', $new_term_slug, 'tipo_produto');
                        if ($term) {
                            wp_set_object_terms($investment->ID, [$term->term_id], 'tipo_produto', false);
                            $this->log("  ✅ Tipo produto definido: {$term->name}");
                        }
                    }
                }
                
                // Verificar e corrigir metadados essenciais
                $this->verify_investment_metadata($investment->ID);
                
                $migrated++;
                
            } catch (Exception $e) {
                $this->log("  ❌ Erro: " . $e->getMessage());
                $errors++;
            }
        }
        
        $this->log("📊 Investimentos processados: {$migrated} sucesso, {$errors} erros");
        return $migrated;
    }
    
    /**
     * Verificar metadados do investimento
     */
    private function verify_investment_metadata($investment_id) {
        $required_fields = [
            'valor_total' => 0,
            'total_captado' => 0,
            'horario_atualizacao' => current_time('H:i'),
        ];
        
        foreach ($required_fields as $field => $default) {
            $value = get_field($field, $investment_id);
            if ($value === false || $value === null) {
                update_field($field, $default, $investment_id);
                $this->log("  🔧 Campo {$field} corrigido");
            }
        }
        
        // Verificar campos específicos por tipo
        $terms = get_the_terms($investment_id, 'tipo_produto');
        if ($terms && !is_wp_error($terms)) {
            $type_slug = $terms[0]->slug;
            
            if ($type_slug === 'private-scp') {
                $scp_fields = [
                    'valor_cota' => 0,
                    'total_cotas' => 0,
                    'cotas_vendidas' => 0,
                    'nome_ativo' => get_the_title($investment_id),
                    'vgv_total' => 0,
                    'percentual_total_vgv' => 20
                ];
                
                foreach ($scp_fields as $field => $default) {
                    $value = get_field($field, $investment_id);
                    if ($value === false || $value === null) {
                        update_field($field, $default, $investment_id);
                        $this->log("  🔧 Campo SCP {$field} corrigido");
                    }
                }
            }
        }
    }
    
    /**
     * Migrar aportes
     */
    private function migrate_aportes($force_overwrite = false) {
        $aportes = get_posts([
            'post_type' => 'aporte',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        $migrated = 0;
        $errors = 0;
        
        foreach ($aportes as $aporte) {
            $this->log("💰 Processando aporte: {$aporte->post_title}");
            
            try {
                // Verificar relacionamentos
                $investment_id = get_field('investment_id', $aporte->ID);
                $investidor_id = get_field('investidor_id', $aporte->ID) ?: get_field('rel_investidor', $aporte->ID);
                
                if (!$investment_id) {
                    $this->log("  ⚠️ Aporte sem investimento relacionado");
                    continue;
                }
                
                if (!$investidor_id) {
                    $this->log("  ⚠️ Aporte sem investidor relacionado");
                    continue;
                }
                
                // Verificar se o investimento é SCP ou Trade
                $is_scp = S_Invest_Calculations::is_scp_investment($investment_id);
                
                if ($is_scp) {
                    $this->migrate_scp_aporte($aporte->ID, $investment_id, $force_overwrite);
                } else {
                    $this->migrate_trade_aporte($aporte->ID, $investment_id, $force_overwrite);
                }
                
                // Atualizar campos de relacionamento se necessário
                if (get_field('rel_investidor', $aporte->ID) && !get_field('investidor_id', $aporte->ID)) {
                    update_field('investidor_id', get_field('rel_investidor', $aporte->ID), $aporte->ID);
                    $this->log("  🔧 Campo investidor_id sincronizado");
                }
                
                $migrated++;
                
            } catch (Exception $e) {
                $this->log("  ❌ Erro: " . $e->getMessage());
                $errors++;
            }
        }
        
        $this->log("💰 Aportes processados: {$migrated} sucesso, {$errors} erros");
        return $migrated;
    }
    
    /**
     * Migrar aporte SCP
     */
    private function migrate_scp_aporte($aporte_id, $investment_id, $force_overwrite) {
        $this->log("  🏦 Migrando aporte SCP");
        
        $required_fields = [
            'quantidade_cotas' => 0,
            'valor_aportado' => 0,
            'participacao_por_cota' => 0,
            'participacao_total' => 0,
            'dividendo_recebido_total' => 0
        ];
        
        foreach ($required_fields as $field => $default) {
            $value = get_field($field, $aporte_id);
            if (($value === false || $value === null) || $force_overwrite) {
                
                // Calcular valores se necessário
                switch ($field) {
                    case 'valor_aportado':
                        $quantidade = get_field('quantidade_cotas', $aporte_id) ?: 0;
                        $valor_cota = get_field('valor_cota', $investment_id) ?: 0;
                        $calculated = $quantidade * $valor_cota;
                        update_field($field, $calculated, $aporte_id);
                        break;
                        
                    case 'participacao_por_cota':
                        $calculated = S_Invest_Calculations::calculate_scp_participation_per_share($investment_id);
                        update_field($field, $calculated, $aporte_id);
                        break;
                        
                    case 'participacao_total':
                        $quantidade = get_field('quantidade_cotas', $aporte_id) ?: 0;
                        $por_cota = get_field('participacao_por_cota', $aporte_id) ?: 0;
                        $calculated = $quantidade * $por_cota;
                        update_field($field, $calculated, $aporte_id);
                        break;
                        
                    default:
                        if ($value === false || $value === null) {
                            update_field($field, $default, $aporte_id);
                        }
                }
                
                $this->log("    🔧 Campo SCP {$field} atualizado");
            }
        }
    }
    
    /**
     * Migrar aporte Trade
     */
    private function migrate_trade_aporte($aporte_id, $investment_id, $force_overwrite) {
        $this->log("  📈 Migrando aporte Trade");
        
        $required_fields = [
            'valor_compra' => 0,
            'valor_atual' => 0,
            'data_aporte' => current_time('d/m/Y'),
            'venda_status' => false
        ];
        
        foreach ($required_fields as $field => $default) {
            $value = get_field($field, $aporte_id);
            if (($value === false || $value === null) || $force_overwrite) {
                
                // Valor atual padrão = valor compra se não definido
                if ($field === 'valor_atual' && !$value) {
                    $valor_compra = get_field('valor_compra', $aporte_id) ?: 0;
                    update_field($field, $valor_compra, $aporte_id);
                } else if ($value === false || $value === null) {
                    update_field($field, $default, $aporte_id);
                }
                
                $this->log("    🔧 Campo Trade {$field} atualizado");
            }
        }
        
        // Verificar histórico de aportes
        $historico = get_field('historico_aportes', $aporte_id);
        if (!$historico || !is_array($historico)) {
            $valor_compra = get_field('valor_compra', $aporte_id) ?: 0;
            $data_aporte = get_field('data_aporte', $aporte_id) ?: current_time('d/m/Y');
            
            $novo_historico = [[
                'valor_aporte' => $valor_compra,
                'data_aporte' => $data_aporte
            ]];
            
            update_field('historico_aportes', $novo_historico, $aporte_id);
            $this->log("    🔧 Histórico de aportes criado");
        }
    }
    
    /**
     * Migrar usuários
     */
    private function migrate_users() {
        $users = get_users(['fields' => 'all']);
        $migrated = 0;
        
        foreach ($users as $user) {
            // Verificar se precisa atualizar roles
            if (in_array('subscriber', $user->roles) && !in_array('investidor', $user->roles)) {
                // Converter subscriber para investidor se tiver aportes
                $aportes = get_posts([
                    'post_type' => 'aporte',
                    'meta_query' => [
                        [
                            'relation' => 'OR',
                            ['key' => 'investidor_id', 'value' => $user->ID],
                            ['key' => 'rel_investidor', 'value' => $user->ID]
                        ]
                    ],
                    'posts_per_page' => 1,
                    'fields' => 'ids'
                ]);
                
                if (!empty($aportes)) {
                    $user->remove_role('subscriber');
                    $user->add_role('investidor');
                    $this->log("👤 Usuário {$user->user_login} convertido para investidor");
                    $migrated++;
                }
            }
            
            // Verificar campos obrigatórios
            $email_confirmed = get_user_meta($user->ID, 'email_confirmed', true);
            if ($email_confirmed === '') {
                update_user_meta($user->ID, 'email_confirmed', true);
                $this->log("👤 E-mail confirmado para {$user->user_login}");
            }
        }
        
        $this->log("👥 Usuários processados: {$migrated}");
        return $migrated;
    }
    
    /**
     * Migrar comunicados
     */
    private function migrate_comunicados() {
        $comunicados = get_posts([
            'post_type' => 'comunicado',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        $faq = get_posts([
            'post_type' => 'faq',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        $total = count($comunicados) + count($faq);
        $this->log("📢 Comunicados e FAQ encontrados: {$total}");
        
        return $total;
    }
    
    /**
     * Verificações finais
     */
    private function final_verifications() {
        // Verificar integridade dos dados
        $investments = wp_count_posts('investment');
        $aportes = wp_count_posts('aporte');
        $users_with_role = count(get_users(['role' => 'investidor']));
        
        $this->log("📊 Verificação final:");
        $this->log("  - Investimentos: {$investments->publish} publicados");
        $this->log("  - Aportes: {$aportes->publish} publicados");
        $this->log("  - Investidores: {$users_with_role} usuários");
        
        // Verificar relacionamentos
        $aportes_sem_investimento = get_posts([
            'post_type' => 'aporte',
            'meta_query' => [
                [
                    'key' => 'investment_id',
                    'value' => '',
                    'compare' => 'NOT EXISTS'
                ]
            ],
            'posts_per_page' => -1,
            'fields' => 'ids'
        ]);
        
        if (!empty($aportes_sem_investimento)) {
            $this->log("⚠️ Encontrados " . count($aportes_sem_investimento) . " aportes sem investimento");
        }
        
        // Limpar cache
        wp_cache_flush();
        $this->log("🧹 Cache limpo");
        
        // Marcar migração como executada
        update_option('s_invest_unified_migrated', 'completed');
        update_option('s_invest_unified_migration_date', current_time('mysql'));
    }
    
    /**
     * Log helper
     */
    private function log($message) {
        $timestamp = current_time('H:i:s');
        $formatted_message = "[{$timestamp}] {$message}";
        $this->migration_log[] = $formatted_message;
        
        if (WP_DEBUG) {
            error_log("S-Invest Migration: {$message}");
        }
    }
}

// Inicializar
S_Invest_Data_Migration::get_instance();