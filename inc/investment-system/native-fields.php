<?php
/**
 * Sistema Nativo de Campos para Investimentos
 * Substitui o ACF com sistema customizado
 */

defined('ABSPATH') || exit;

class S_Invest_Native_Fields {
    
    private static $instance = null;
    private $field_definitions = [];
    
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
        // Hooks para meta boxes
        add_action('add_meta_boxes', [$this, 'add_investment_meta_boxes']);
        add_action('save_post', [$this, 'save_investment_fields']);
        
        // AJAX para formulários dinâmicos
        add_action('wp_ajax_get_investment_form', [$this, 'ajax_get_investment_form']);
        add_action('wp_ajax_save_investment_data', [$this, 'ajax_save_investment_data']);
        add_action('wp_ajax_validate_investment_field', [$this, 'ajax_validate_field']);
        
        // Admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        $this->define_field_structure();
    }
    
    /**
     * Definir estrutura completa dos campos
     */
    private function define_field_structure() {
        $this->field_definitions = [
            
            // ===== CAMPOS BÁSICOS (TODOS OS TIPOS) =====
            'basic_info' => [
                'title' => 'Informações Básicas',
                'fields' => [
                    'classe_de_ativos' => [
                        'type' => 'select',
                        'label' => 'Classe de Ativos',
                        'required' => true,
                        'options' => [
                            'trade' => 'Trade',
                            'private' => 'Private (SCP)'
                        ],
                        'description' => 'Define o tipo de investimento e campos disponíveis',
                        'trigger_conditional' => true
                    ],
                    'status_captacao' => [
                        'type' => 'select',
                        'label' => 'Status da Captação',
                        'options' => [
                            'ativo' => 'Ativo',
                            'encerrado' => 'Encerrado',
                            'pausado' => 'Pausado'
                        ],
                        'default' => 'ativo'
                    ],
                    'data_lancamento' => [
                        'type' => 'date',
                        'label' => 'Data de Lançamento'
                    ],
                    'fim_captacao' => [
                        'type' => 'date',
                        'label' => 'Fim da Captação'
                    ]
                ]
            ],
            
            // ===== DADOS FINANCEIROS =====
            'financial_data' => [
                'title' => 'Dados Financeiros',
                'fields' => [
                    'valor_total' => [
                        'type' => 'currency',
                        'label' => 'Valor Total do Investimento',
                        'required' => true,
                        'description' => 'Valor total disponível para captação'
                    ],
                    'aporte_minimo' => [
                        'type' => 'currency', 
                        'label' => 'Aporte Mínimo',
                        'required' => true
                    ],
                    'total_captado' => [
                        'type' => 'currency',
                        'label' => 'Total Captado',
                        'readonly' => true,
                        'calculated' => true,
                        'description' => 'Calculado automaticamente com base nos aportes'
                    ]
                ]
            ],
            
            // ===== DADOS SCP (CONDICIONAL) =====
            'scp_data' => [
                'title' => 'Dados SCP (Private)',
                'condition' => 'classe_de_ativos == private',
                'fields' => [
                    'nome_ativo' => [
                        'type' => 'text',
                        'label' => 'Nome do Ativo',
                        'placeholder' => 'Ex: ESBE300',
                        'description' => 'Código identificador do ativo'
                    ],
                    'valor_cota' => [
                        'type' => 'currency',
                        'label' => 'Valor por Cota',
                        'required' => true,
                        'description' => 'Valor individual de cada cota'
                    ],
                    'total_cotas' => [
                        'type' => 'number',
                        'label' => 'Total de Cotas',
                        'required' => true,
                        'description' => 'Número total de cotas disponíveis'
                    ],
                    'cotas_vendidas' => [
                        'type' => 'number',
                        'label' => 'Cotas Vendidas',
                        'readonly' => true,
                        'calculated' => true
                    ],
                    'cotas_disponiveis' => [
                        'type' => 'number',
                        'label' => 'Cotas Disponíveis',
                        'readonly' => true,
                        'calculated' => true
                    ],
                    'vgv_total' => [
                        'type' => 'currency',
                        'label' => 'VGV Total',
                        'description' => 'Valor Geral de Vendas total do projeto'
                    ],
                    'percentual_total_vgv' => [
                        'type' => 'percentage',
                        'label' => 'Percentual Total do VGV',
                        'description' => 'Percentual que este investimento representa do VGV total'
                    ]
                ]
            ],
            
            // ===== PERFORMANCE E RETORNO =====
            'performance' => [
                'title' => 'Performance e Retorno',
                'fields' => [
                    'rentabilidade' => [
                        'type' => 'percentage',
                        'label' => 'Rentabilidade Esperada (%)',
                        'description' => 'Projeção de rentabilidade anual'
                    ],
                    'prazo_min' => [
                        'type' => 'number',
                        'label' => 'Prazo Mínimo (meses)',
                        'min' => 1
                    ],
                    'prazo_max' => [
                        'type' => 'number',
                        'label' => 'Prazo Máximo (meses)',
                        'min' => 1
                    ],
                    'risco' => [
                        'type' => 'select',
                        'label' => 'Nível de Risco',
                        'options' => [
                            'baixo' => 'Baixo',
                            'medio' => 'Médio',
                            'alto' => 'Alto'
                        ]
                    ]
                ]
            ],
            
            // ===== INFORMAÇÕES ADICIONAIS =====
            'additional_info' => [
                'title' => 'Informações Adicionais',
                'fields' => [
                    'regiao_projeto' => [
                        'type' => 'text',
                        'label' => 'Região do Projeto'
                    ],
                    'originadora' => [
                        'type' => 'url',
                        'label' => 'Link da Originadora'
                    ],
                    'descricao_originadora' => [
                        'type' => 'wysiwyg',
                        'label' => 'Descrição da Originadora'
                    ]
                ]
            ],
            
            // ===== DOCUMENTOS E ARQUIVOS =====
            'documents' => [
                'title' => 'Documentos',
                'fields' => [
                    'url_lamina_tecnica' => [
                        'type' => 'file',
                        'label' => 'Lâmina Técnica (PDF)',
                        'accept' => '.pdf'
                    ],
                    'documentos' => [
                        'type' => 'repeater',
                        'label' => 'Documentos Adicionais',
                        'fields' => [
                            'titulo' => [
                                'type' => 'text',
                                'label' => 'Título do Documento'
                            ],
                            'arquivo' => [
                                'type' => 'file',
                                'label' => 'Arquivo',
                                'accept' => '.pdf,.doc,.docx'
                            ]
                        ]
                    ]
                ]
            ],
            
            // ===== MOTIVOS E RISCOS =====
            'analysis' => [
                'title' => 'Análise de Investimento',
                'fields' => [
                    'motivos' => [
                        'type' => 'repeater',
                        'label' => 'Motivos para Investir',
                        'fields' => [
                            'titulo' => [
                                'type' => 'text',
                                'label' => 'Título'
                            ],
                            'descricao' => [
                                'type' => 'textarea',
                                'label' => 'Descrição'
                            ]
                        ]
                    ],
                    'riscos' => [
                        'type' => 'repeater',
                        'label' => 'Fatores de Risco',
                        'fields' => [
                            'titulo' => [
                                'type' => 'text',
                                'label' => 'Título'
                            ],
                            'descricao' => [
                                'type' => 'textarea',
                                'label' => 'Descrição'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
    
    /**
     * Adicionar meta boxes
     */
    public function add_investment_meta_boxes() {
        add_meta_box(
            's_invest_native_fields',
            'Dados do Investimento',
            [$this, 'render_meta_box'],
            'investment',
            'normal',
            'high'
        );
    }
    
    /**
     * Renderizar meta box principal
     */
    public function render_meta_box($post) {
        wp_nonce_field('s_invest_save_fields', 's_invest_nonce');
        
        // Get current values
        $current_values = $this->get_investment_data($post->ID);
        
        ?>
        <div id="s-invest-fields-app" x-data="investmentFields(<?php echo esc_attr(json_encode($current_values)); ?>)">
            
            <!-- Navigation Tabs -->
            <div class="nav-tab-wrapper">
                <?php foreach ($this->field_definitions as $section_key => $section): ?>
                    <a href="#<?php echo $section_key; ?>" 
                       class="nav-tab"
                       :class="activeTab === '<?php echo $section_key; ?>' ? 'nav-tab-active' : ''"
                       @click.prevent="activeTab = '<?php echo $section_key; ?>'">
                        <?php echo esc_html($section['title']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Field Sections -->
            <?php foreach ($this->field_definitions as $section_key => $section): ?>
                <div x-show="activeTab === '<?php echo $section_key; ?>'" 
                     x-transition
                     id="<?php echo $section_key; ?>"
                     class="s-invest-section">
                    
                    <h3><?php echo esc_html($section['title']); ?></h3>
                    
                    <?php 
                    // Render condition wrapper if needed
                    $condition = $section['condition'] ?? '';
                    if ($condition) {
                        echo '<div x-show="' . esc_attr($this->convert_condition_to_alpine($condition)) . '">';
                    }
                    ?>
                    
                    <table class="form-table">
                        <?php foreach ($section['fields'] as $field_key => $field): ?>
                            <tr>
                                <th scope="row">
                                    <label for="<?php echo $field_key; ?>">
                                        <?php echo esc_html($field['label']); ?>
                                        <?php if (!empty($field['required'])): ?>
                                            <span class="required">*</span>
                                        <?php endif; ?>
                                    </label>
                                </th>
                                <td>
                                    <?php $this->render_field($field_key, $field, $current_values); ?>
                                    <?php if (!empty($field['description'])): ?>
                                        <p class="description"><?php echo esc_html($field['description']); ?></p>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <?php if ($condition) echo '</div>'; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- Save Button -->
            <div class="s-invest-save-section">
                <button type="button" 
                        @click="saveData()"
                        :disabled="saving"
                        class="button button-primary button-large">
                    <span x-show="!saving">Salvar Dados</span>
                    <span x-show="saving">Salvando...</span>
                </button>
                
                <div x-show="message" 
                     :class="messageType === 'success' ? 'notice-success' : 'notice-error'"
                     class="notice inline">
                    <p x-text="message"></p>
                </div>
            </div>
        </div>
        
        <style>
        .s-invest-section { margin-top: 20px; }
        .s-invest-save-section { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; }
        .required { color: #d63638; }
        .s-invest-field-group { margin-bottom: 15px; }
        .s-invest-repeater { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; }
        .s-invest-calculated { background: #f0f0f1; }
        .alpine-hide { display: none !important; }
        [x-cloak] { display: none !important; }
        </style>
        <?php
    }
    
    /**
     * Renderizar campo individual
     */
    private function render_field($field_key, $field, $values) {
        $value = $values[$field_key] ?? ($field['default'] ?? '');
        $readonly = !empty($field['readonly']);
        $required = !empty($field['required']);
        
        switch ($field['type']) {
            case 'text':
                echo sprintf(
                    '<input type="text" id="%s" name="s_invest[%s]" value="%s" %s %s class="regular-text" x-model="data.%s">',
                    esc_attr($field_key),
                    esc_attr($field_key),
                    esc_attr($value),
                    $readonly ? 'readonly' : '',
                    $required ? 'required' : '',
                    esc_attr($field_key)
                );
                break;
                
            case 'select':
                echo sprintf('<select id="%s" name="s_invest[%s]" %s x-model="data.%s">', 
                    esc_attr($field_key), esc_attr($field_key), $required ? 'required' : '', esc_attr($field_key));
                
                if (!$required) {
                    echo '<option value="">Selecione...</option>';
                }
                
                foreach ($field['options'] as $option_value => $option_label) {
                    echo sprintf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($option_value),
                        selected($value, $option_value, false),
                        esc_html($option_label)
                    );
                }
                echo '</select>';
                break;
                
            case 'currency':
                echo sprintf(
                    '<input type="text" id="%s" name="s_invest[%s]" value="%s" %s %s class="regular-text s-invest-currency" x-model="data.%s" placeholder="R$ 0,00">',
                    esc_attr($field_key),
                    esc_attr($field_key),
                    esc_attr($value),
                    $readonly ? 'readonly' : '',
                    $required ? 'required' : '',
                    esc_attr($field_key)
                );
                break;
                
            case 'percentage':
                echo sprintf(
                    '<input type="number" id="%s" name="s_invest[%s]" value="%s" %s %s class="small-text" step="0.01" min="0" max="100" x-model="data.%s"> %%',
                    esc_attr($field_key),
                    esc_attr($field_key),
                    esc_attr($value),
                    $readonly ? 'readonly' : '',
                    $required ? 'required' : '',
                    esc_attr($field_key)
                );
                break;
                
            case 'number':
                echo sprintf(
                    '<input type="number" id="%s" name="s_invest[%s]" value="%s" %s %s class="small-text" x-model="data.%s">',
                    esc_attr($field_key),
                    esc_attr($field_key),
                    esc_attr($value),
                    $readonly ? 'readonly' : '',
                    $required ? 'required' : '',
                    esc_attr($field_key)
                );
                break;
                
            case 'date':
                echo sprintf(
                    '<input type="date" id="%s" name="s_invest[%s]" value="%s" %s %s class="regular-text" x-model="data.%s">',
                    esc_attr($field_key),
                    esc_attr($field_key),
                    esc_attr($value),
                    $readonly ? 'readonly' : '',
                    $required ? 'required' : '',
                    esc_attr($field_key)
                );
                break;
                
            case 'textarea':
                echo sprintf(
                    '<textarea id="%s" name="s_invest[%s]" %s %s class="large-text" rows="4" x-model="data.%s">%s</textarea>',
                    esc_attr($field_key),
                    esc_attr($field_key),
                    $readonly ? 'readonly' : '',
                    $required ? 'required' : '',
                    esc_attr($field_key),
                    esc_textarea($value)
                );
                break;
                
            case 'file':
                $this->render_file_field($field_key, $field, $value);
                break;
                
            case 'repeater':
                $this->render_repeater_field($field_key, $field, $value);
                break;
        }
        
        // Add calculated field indicator
        if (!empty($field['calculated'])) {
            echo ' <span class="dashicons dashicons-calculator" title="Campo calculado automaticamente"></span>';
        }
    }
    
    /**
     * Renderizar campo de arquivo
     */
    private function render_file_field($field_key, $field, $value) {
        ?>
        <div class="s-invest-file-field">
            <input type="hidden" name="s_invest[<?php echo esc_attr($field_key); ?>]" x-model="data.<?php echo esc_attr($field_key); ?>">
            <button type="button" class="button" @click="selectFile('<?php echo esc_attr($field_key); ?>')">
                Selecionar Arquivo
            </button>
            <span x-show="data.<?php echo esc_attr($field_key); ?>" x-text="getFileName(data.<?php echo esc_attr($field_key); ?>)"></span>
        </div>
        <?php
    }
    
    /**
     * Renderizar campo repeater
     */
    private function render_repeater_field($field_key, $field, $value) {
        ?>
        <div class="s-invest-repeater-field">
            <template x-for="(item, index) in data.<?php echo esc_attr($field_key); ?>" :key="index">
                <div class="s-invest-repeater">
                    <?php foreach ($field['fields'] as $sub_key => $sub_field): ?>
                        <div class="s-invest-field-group">
                            <label><?php echo esc_html($sub_field['label']); ?></label>
                            <?php
                            // Simplified sub-field rendering
                            switch ($sub_field['type']) {
                                case 'text':
                                    echo '<input type="text" x-model="item.' . esc_attr($sub_key) . '" class="regular-text">';
                                    break;
                                case 'textarea':
                                    echo '<textarea x-model="item.' . esc_attr($sub_key) . '" class="large-text" rows="3"></textarea>';
                                    break;
                            }
                            ?>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" @click="removeRepeaterItem('<?php echo esc_attr($field_key); ?>', index)" class="button button-small">Remover</button>
                </div>
            </template>
            <button type="button" @click="addRepeaterItem('<?php echo esc_attr($field_key); ?>')" class="button">Adicionar Item</button>
        </div>
        <?php
    }
    
    /**
     * Converter condições para Alpine.js
     */
    private function convert_condition_to_alpine($condition) {
        // Converter "classe_de_ativos == private" para "data.classe_de_ativos === 'private'"
        return str_replace(
            ['==', 'classe_de_ativos'],
            ['===', 'data.classe_de_ativos'],
            $condition
        );
    }
    
    /**
     * Salvar campos
     */
    public function save_investment_fields($post_id) {
        if (!isset($_POST['s_invest_nonce']) || !wp_verify_nonce($_POST['s_invest_nonce'], 's_invest_save_fields')) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        if (get_post_type($post_id) !== 'investment') {
            return;
        }
        
        $data = $_POST['s_invest'] ?? [];
        
        foreach ($data as $key => $value) {
            update_post_meta($post_id, 's_invest_' . $key, sanitize_meta($key, $value, 'post'));
        }
        
        // Trigger calculations
        $this->calculate_investment_values($post_id);
    }
    
    /**
     * Calcular valores automáticos
     */
    private function calculate_investment_values($investment_id) {
        $classe = get_post_meta($investment_id, 's_invest_classe_de_ativos', true);
        
        if ($classe === 'private') {
            $this->calculate_scp_values($investment_id);
        } else {
            $this->calculate_trade_values($investment_id);
        }
    }
    
    /**
     * Calcular valores SCP
     */
    private function calculate_scp_values($investment_id) {
        // Get SCP aportes
        $aportes = get_posts([
            'post_type' => 'aporte',
            'meta_query' => [
                [
                    'key' => 'investment_id',
                    'value' => $investment_id
                ]
            ],
            'posts_per_page' => -1
        ]);
        
        $total_cotas_vendidas = 0;
        $total_captado = 0;
        
        foreach ($aportes as $aporte) {
            $quantidade_cotas = floatval(get_post_meta($aporte->ID, 's_invest_quantidade_cotas', true));
            $valor_aportado = floatval(get_post_meta($aporte->ID, 's_invest_valor_aportado', true));
            
            $total_cotas_vendidas += $quantidade_cotas;
            $total_captado += $valor_aportado;
        }
        
        $total_cotas = floatval(get_post_meta($investment_id, 's_invest_total_cotas', true));
        $cotas_disponiveis = max(0, $total_cotas - $total_cotas_vendidas);
        
        // Update calculated fields
        update_post_meta($investment_id, 's_invest_cotas_vendidas', $total_cotas_vendidas);
        update_post_meta($investment_id, 's_invest_cotas_disponiveis', $cotas_disponiveis);
        update_post_meta($investment_id, 's_invest_total_captado', $total_captado);
    }
    
    /**
     * Calcular valores Trade
     */
    private function calculate_trade_values($investment_id) {
        // Get Trade aportes
        $aportes = get_posts([
            'post_type' => 'aporte',
            'meta_query' => [
                [
                    'key' => 'investment_id',
                    'value' => $investment_id
                ]
            ],
            'posts_per_page' => -1
        ]);
        
        $total_captado = 0;
        
        foreach ($aportes as $aporte) {
            $valor_compra = floatval(get_post_meta($aporte->ID, 's_invest_valor_compra', true));
            $total_captado += $valor_compra;
        }
        
        update_post_meta($investment_id, 's_invest_total_captado', $total_captado);
    }
    
    /**
     * Obter dados do investimento
     */
    public function get_investment_data($investment_id) {
        $data = [];
        
        // Get all s_invest_ meta fields
        $meta = get_post_meta($investment_id);
        
        foreach ($meta as $key => $value) {
            if (strpos($key, 's_invest_') === 0) {
                $clean_key = str_replace('s_invest_', '', $key);
                $data[$clean_key] = is_array($value) ? $value[0] : $value;
            }
        }
        
        // Set defaults for required fields
        if (empty($data['classe_de_ativos'])) {
            $data['classe_de_ativos'] = 'trade';
        }
        
        return $data;
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post;
        
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        if (!$post || $post->post_type !== 'investment') {
            return;
        }
        
        wp_enqueue_script('s-invest-native-fields', 
            S_INVEST_THEME_URL . '/public/js/admin/investment-fields.js', 
            [], S_INVEST_VERSION, true);
        
        wp_localize_script('s-invest-native-fields', 'sInvestAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('s_invest_admin'),
            'post_id' => $post->ID
        ]);
    }
    
    /**
     * AJAX: Validar campo
     */
    public function ajax_validate_field() {
        check_ajax_referer('s_invest_admin', 'nonce');
        
        $field = sanitize_text_field($_POST['field']);
        $value = sanitize_text_field($_POST['value']);
        
        $validation = $this->validate_field($field, $value);
        
        wp_send_json($validation);
    }
    
    /**
     * Validar campo individual
     */
    private function validate_field($field, $value) {
        switch ($field) {
            case 'total_cotas':
                if (!is_numeric($value) || $value <= 0) {
                    return ['valid' => false, 'message' => 'Total de cotas deve ser um número positivo'];
                }
                break;
                
            case 'valor_cota':
                if (!is_numeric($value) || $value <= 0) {
                    return ['valid' => false, 'message' => 'Valor da cota deve ser um número positivo'];
                }
                break;
        }
        
        return ['valid' => true];
    }
}

// Initialize
S_Invest_Native_Fields::get_instance();