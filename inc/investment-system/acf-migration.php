<?php
/**
 * Migração dos campos ACF para Sistema Nativo
 */

defined('ABSPATH') || exit;

class S_Invest_ACF_Migration {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'init']);
    }
    
    public function init() {
        // AJAX para migração
        add_action('wp_ajax_migrate_investment_from_acf', [$this, 'ajax_migrate_from_acf']);
        add_action('wp_ajax_bulk_migrate_investments', [$this, 'ajax_bulk_migrate']);
        add_action('wp_ajax_scan_investments', [$this, 'ajax_scan_investments']);
        add_action('wp_ajax_validate_migration', [$this, 'ajax_validate_migration']);
        
        // Admin page para migração
        add_action('admin_menu', [$this, 'add_migration_page']);
    }
    
    /**
     * Adicionar página de migração
     */
    public function add_migration_page() {
        add_submenu_page(
            'edit.php?post_type=investment',
            'Migração ACF → Nativo',
            'Migração ACF',
            'manage_options',
            's-invest-acf-migration',
            [$this, 'migration_page']
        );
    }
    
    /**
     * Página de migração
     */
    public function migration_page() {
        ?>
        <div class="wrap">
            <h1>Migração ACF → Sistema Nativo</h1>
            
            <div id="migration-app" x-data="migrationManager()">
                <!-- Status Overview -->
                <div class="card">
                    <h2>Status da Migração</h2>
                    <div class="migration-stats">
                        <div class="stat-box">
                            <h3 x-text="stats.total">0</h3>
                            <p>Total de Investimentos</p>
                        </div>
                        <div class="stat-box">
                            <h3 x-text="stats.acf">0</h3>
                            <p>Usando ACF</p>
                        </div>
                        <div class="stat-box">
                            <h3 x-text="stats.native">0</h3>
                            <p>Sistema Nativo</p>
                        </div>
                        <div class="stat-box">
                            <h3 x-text="stats.mixed">0</h3>
                            <p>Dados Mistos</p>
                        </div>
                    </div>
                </div>
                
                <!-- Migration Actions -->
                <div class="card">
                    <h2>Ações de Migração</h2>
                    
                    <div class="migration-actions">
                        <button @click="scanInvestments()" 
                                :disabled="scanning"
                                class="button button-primary">
                            <span x-show="!scanning">🔍 Escanear Investimentos</span>
                            <span x-show="scanning">Escaneando...</span>
                        </button>
                        
                        <button @click="migrateAll()" 
                                :disabled="migrating || stats.acf === 0"
                                class="button button-secondary">
                            <span x-show="!migrating">🔄 Migrar Todos</span>
                            <span x-show="migrating">Migrando...</span>
                        </button>
                        
                        <button @click="validateMigration()" 
                                class="button">
                            ✅ Validar Migração
                        </button>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div x-show="migrating" class="migration-progress">
                        <progress :value="progress.current" :max="progress.total"></progress>
                        <p>
                            Migrando <span x-text="progress.current"></span> de <span x-text="progress.total"></span>
                            (<span x-text="Math.round((progress.current / progress.total) * 100)"></span>%)
                        </p>
                    </div>
                </div>
                
                <!-- Investment List -->
                <div class="card" x-show="investments.length > 0">
                    <h2>Lista de Investimentos</h2>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Status</th>
                                <th>Campos ACF</th>
                                <th>Campos Nativos</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="investment in investments" :key="investment.id">
                                <tr>
                                    <td x-text="investment.id"></td>
                                    <td x-text="investment.title"></td>
                                    <td>
                                        <span :class="getStatusClass(investment.status)" 
                                              x-text="getStatusLabel(investment.status)"></span>
                                    </td>
                                    <td x-text="investment.acf_count"></td>
                                    <td x-text="investment.native_count"></td>
                                    <td>
                                        <button @click="migrateInvestment(investment.id)"
                                                :disabled="investment.status === 'native'"
                                                class="button button-small">
                                            Migrar
                                        </button>
                                        <button @click="viewDetails(investment.id)"
                                                class="button button-small">
                                            Detalhes
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                
                <!-- Messages -->
                <div x-show="message" 
                     :class="messageType === 'error' ? 'notice-error' : 'notice-success'"
                     class="notice">
                    <p x-text="message"></p>
                </div>
            </div>
            
            <style>
            .migration-stats {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
                margin: 20px 0;
            }
            .stat-box {
                text-align: center;
                padding: 20px;
                background: #f9f9f9;
                border-radius: 5px;
            }
            .stat-box h3 {
                font-size: 2em;
                margin: 0;
                color: #2271b1;
            }
            .migration-actions {
                margin: 20px 0;
            }
            .migration-actions button {
                margin-right: 10px;
            }
            .migration-progress {
                margin-top: 20px;
            }
            .migration-progress progress {
                width: 100%;
                height: 20px;
            }
            .status-acf { color: #d63638; }
            .status-native { color: #00a32a; }
            .status-mixed { color: #dba617; }
            </style>
            
            <script>
            function migrationManager() {
                return {
                    stats: {
                        total: 0,
                        acf: 0,
                        native: 0,
                        mixed: 0
                    },
                    investments: [],
                    scanning: false,
                    migrating: false,
                    progress: {
                        current: 0,
                        total: 0
                    },
                    message: '',
                    messageType: 'success',
                    
                    init() {
                        this.scanInvestments();
                    },
                    
                    async scanInvestments() {
                        this.scanning = true;
                        this.message = '';
                        
                        try {
                            const response = await this.ajaxRequest('scan_investments');
                            
                            this.stats = response.stats;
                            this.investments = response.investments;
                            
                            this.showMessage('Escaneamento concluído!', 'success');
                        } catch (error) {
                            this.showMessage('Erro no escaneamento: ' + error.message, 'error');
                        } finally {
                            this.scanning = false;
                        }
                    },
                    
                    async migrateAll() {
                        if (!confirm('Migrar todos os investimentos? Esta ação não pode ser desfeita.')) {
                            return;
                        }
                        
                        this.migrating = true;
                        this.progress.current = 0;
                        this.progress.total = this.stats.acf;
                        
                        const acfInvestments = this.investments.filter(i => i.status === 'acf');
                        
                        for (const investment of acfInvestments) {
                            try {
                                await this.migrateInvestment(investment.id, false);
                                this.progress.current++;
                            } catch (error) {
                                console.error('Erro ao migrar investimento ' + investment.id, error);
                            }
                        }
                        
                        this.migrating = false;
                        this.showMessage('Migração em lote concluída!', 'success');
                        
                        // Rescanear
                        await this.scanInvestments();
                    },
                    
                    async migrateInvestment(investmentId, showMessage = true) {
                        try {
                            const response = await this.ajaxRequest('migrate_investment_from_acf', {
                                investment_id: investmentId
                            });
                            
                            if (showMessage) {
                                this.showMessage('Investimento migrado com sucesso!', 'success');
                            }
                            
                            // Atualizar item na lista
                            const investment = this.investments.find(i => i.id == investmentId);
                            if (investment) {
                                investment.status = 'native';
                                investment.native_count = response.migrated_fields;
                            }
                            
                            return response;
                            
                        } catch (error) {
                            if (showMessage) {
                                this.showMessage('Erro na migração: ' + error.message, 'error');
                            }
                            throw error;
                        }
                    },
                    
                    async validateMigration() {
                        try {
                            const response = await this.ajaxRequest('validate_migration');
                            
                            this.showMessage(`Validação: ${response.valid_count} válidos, ${response.error_count} com erro`, 
                                           response.error_count > 0 ? 'error' : 'success');
                            
                        } catch (error) {
                            this.showMessage('Erro na validação: ' + error.message, 'error');
                        }
                    },
                    
                    getStatusClass(status) {
                        return 'status-' + status;
                    },
                    
                    getStatusLabel(status) {
                        const labels = {
                            'acf': 'ACF',
                            'native': 'Nativo',
                            'mixed': 'Misto'
                        };
                        return labels[status] || status;
                    },
                    
                    viewDetails(investmentId) {
                        window.open(
                            '<?php echo admin_url('post.php?action=edit&post='); ?>' + investmentId,
                            '_blank'
                        );
                    },
                    
                    async ajaxRequest(action, data = {}) {
                        const formData = new FormData();
                        formData.append('action', action);
                        formData.append('nonce', '<?php echo wp_create_nonce('s_invest_migration'); ?>');
                        
                        Object.keys(data).forEach(key => {
                            formData.append(key, data[key]);
                        });
                        
                        const response = await fetch(ajaxurl, {
                            method: 'POST',
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (!result.success) {
                            throw new Error(result.data || 'Erro desconhecido');
                        }
                        
                        return result.data;
                    },
                    
                    showMessage(text, type = 'success') {
                        this.message = text;
                        this.messageType = type;
                        
                        setTimeout(() => {
                            this.message = '';
                        }, 5000);
                    }
                };
            }
            </script>
        </div>
        <?php
    }
    
    /**
     * AJAX: Migrar investimento do ACF
     */
    public function ajax_migrate_from_acf() {
        check_ajax_referer('s_invest_migration', 'nonce');
        
        $investment_id = absint($_POST['investment_id']);
        
        if (!current_user_can('edit_post', $investment_id)) {
            wp_send_json_error('Permissão negada');
        }
        
        try {
            $migrated_fields = $this->migrate_investment_data($investment_id);
            
            wp_send_json_success([
                'migrated_fields' => $migrated_fields,
                'message' => 'Migração concluída com sucesso'
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    /**
     * AJAX: Escanear investimentos
     */
    public function ajax_scan_investments() {
        check_ajax_referer('s_invest_migration', 'nonce');
        
        $investments = get_posts([
            'post_type' => 'investment',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);
        
        $stats = [
            'total' => 0,
            'acf' => 0,
            'native' => 0,
            'mixed' => 0
        ];
        
        $investment_data = [];
        
        foreach ($investments as $investment) {
            $acf_count = $this->count_acf_fields($investment->ID);
            $native_count = $this->count_native_fields($investment->ID);
            
            $status = 'native';
            if ($acf_count > 0 && $native_count === 0) {
                $status = 'acf';
            } elseif ($acf_count > 0 && $native_count > 0) {
                $status = 'mixed';
            }
            
            $stats['total']++;
            $stats[$status]++;
            
            $investment_data[] = [
                'id' => $investment->ID,
                'title' => $investment->post_title,
                'status' => $status,
                'acf_count' => $acf_count,
                'native_count' => $native_count
            ];
        }
        
        wp_send_json_success([
            'stats' => $stats,
            'investments' => $investment_data
        ]);
    }
    
    /**
     * AJAX: Validar migração
     */
    public function ajax_validate_migration() {
        check_ajax_referer('s_invest_migration', 'nonce');
        
        $investments = get_posts([
            'post_type' => 'investment',
            'posts_per_page' => -1
        ]);
        
        $valid_count = 0;
        $error_count = 0;
        $errors = [];
        
        foreach ($investments as $investment) {
            $native_count = $this->count_native_fields($investment->ID);
            
            if ($native_count > 0) {
                // Validar campos obrigatórios
                $required_fields = ['classe_de_ativos', 'valor_total', 'aporte_minimo'];
                $missing_fields = [];
                
                foreach ($required_fields as $field) {
                    $value = get_post_meta($investment->ID, 's_invest_' . $field, true);
                    if (empty($value)) {
                        $missing_fields[] = $field;
                    }
                }
                
                if (empty($missing_fields)) {
                    $valid_count++;
                } else {
                    $error_count++;
                    $errors[] = [
                        'id' => $investment->ID,
                        'title' => $investment->post_title,
                        'missing_fields' => $missing_fields
                    ];
                }
            }
        }
        
        wp_send_json_success([
            'valid_count' => $valid_count,
            'error_count' => $error_count,
            'errors' => $errors
        ]);
    }
    
    /**
     * Migrar dados de um investimento
     */
    private function migrate_investment_data($investment_id) {
        if (!function_exists('get_field')) {
            throw new Exception('ACF não está ativo');
        }
        
        $field_mapping = $this->get_field_mapping();
        $migrated_count = 0;
        
        foreach ($field_mapping as $acf_key => $native_key) {
            $acf_value = get_field($acf_key, $investment_id);
            
            if ($acf_value !== null && $acf_value !== false && $acf_value !== '') {
                // Converter valor se necessário
                $native_value = $this->convert_field_value($acf_key, $acf_value);
                
                // Salvar no sistema nativo
                update_post_meta($investment_id, 's_invest_' . $native_key, $native_value);
                $migrated_count++;
            }
        }
        
        // Determinar classe de ativos baseada na taxonomia
        $terms = wp_get_post_terms($investment_id, 'tipo_produto');
        if (!empty($terms)) {
            $term = $terms[0];
            $classe = (stripos($term->name, 'scp') !== false || stripos($term->name, 'private') !== false) 
                     ? 'private' : 'trade';
            
            update_post_meta($investment_id, 's_invest_classe_de_ativos', $classe);
            $migrated_count++;
        }
        
        // Migrar campos repeater
        $this->migrate_repeater_fields($investment_id);
        
        return $migrated_count;
    }
    
    /**
     * Mapeamento de campos ACF → Nativo
     */
    private function get_field_mapping() {
        return [
            // Campos básicos
            'status_captacao' => 'status_captacao',
            'data_lancamento' => 'data_lancamento',
            'fim_captacao' => 'fim_captacao',
            
            // Financeiro
            'valor_total' => 'valor_total',
            'aporte_minimo' => 'aporte_minimo',
            'total_captado' => 'total_captado',
            
            // SCP
            'nome_ativo' => 'nome_ativo',
            'valor_cota' => 'valor_cota',
            'total_cotas' => 'total_cotas',
            'cotas_vendidas' => 'cotas_vendidas',
            'cotas_disponiveis' => 'cotas_disponiveis',
            'vgv_total' => 'vgv_total',
            'percentual_total_vgv' => 'percentual_total_vgv',
            
            // Performance
            'rentabilidade' => 'rentabilidade',
            'prazo_min' => 'prazo_min',
            'prazo_max' => 'prazo_max',
            'risco' => 'risco',
            
            // Informações adicionais
            'regiao_projeto' => 'regiao_projeto',
            'originadora' => 'originadora',
            'descricao_originadora' => 'descricao_originadora',
            'url_lamina_tecnica' => 'url_lamina_tecnica'
        ];
    }
    
    /**
     * Converter valor do campo
     */
    private function convert_field_value($field_key, $value) {
        switch ($field_key) {
            case 'valor_total':
            case 'aporte_minimo':
            case 'valor_cota':
            case 'vgv_total':
                // Converter para número
                return floatval(str_replace([',', 'R$', ' '], ['', '', ''], $value));
                
            case 'total_cotas':
            case 'cotas_vendidas':
            case 'prazo_min':
            case 'prazo_max':
                return intval($value);
                
            case 'data_lancamento':
            case 'fim_captacao':
                // Converter data do ACF para formato nativo
                if (is_array($value)) {
                    return $value['date'] ?? '';
                }
                return $value;
                
            default:
                return $value;
        }
    }
    
    /**
     * Migrar campos repeater
     */
    private function migrate_repeater_fields($investment_id) {
        // Migrar motivos
        $motivos = get_field('motivos', $investment_id);
        if (is_array($motivos)) {
            update_post_meta($investment_id, 's_invest_motivos', $motivos);
        }
        
        // Migrar riscos
        $riscos = get_field('riscos', $investment_id);
        if (is_array($riscos)) {
            update_post_meta($investment_id, 's_invest_riscos', $riscos);
        }
        
        // Migrar documentos
        $documentos = get_field('documentos', $investment_id);
        if (is_array($documentos)) {
            update_post_meta($investment_id, 's_invest_documentos', $documentos);
        }
    }
    
    /**
     * Contar campos ACF
     */
    private function count_acf_fields($investment_id) {
        if (!function_exists('get_field')) {
            return 0;
        }
        
        $count = 0;
        $field_mapping = $this->get_field_mapping();
        
        foreach (array_keys($field_mapping) as $acf_key) {
            $value = get_field($acf_key, $investment_id);
            if ($value !== null && $value !== false && $value !== '') {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Contar campos nativos
     */
    private function count_native_fields($investment_id) {
        $meta = get_post_meta($investment_id);
        $count = 0;
        
        foreach ($meta as $key => $value) {
            if (strpos($key, 's_invest_') === 0 && !empty($value[0])) {
                $count++;
            }
        }
        
        return $count;
    }
}

// Initialize
S_Invest_ACF_Migration::get_instance();