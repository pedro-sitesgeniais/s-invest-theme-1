<?php
/**
 * AJAX Handlers para Sistema Nativo de Campos
 */

defined('ABSPATH') || exit;

class S_Invest_Native_Fields_AJAX {
    
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
        // AJAX handlers
        add_action('wp_ajax_save_investment_data', [$this, 'ajax_save_investment_data']);
        add_action('wp_ajax_upload_investment_file', [$this, 'ajax_upload_file']);
        add_action('wp_ajax_get_investment_calculations', [$this, 'ajax_get_calculations']);
        add_action('wp_ajax_duplicate_investment', [$this, 'ajax_duplicate_investment']);
        
        // Admin notices
        add_action('admin_notices', [$this, 'admin_notices']);
    }
    
    /**
     * AJAX: Salvar dados do investimento
     */
    public function ajax_save_investment_data() {
        check_ajax_referer('s_invest_admin', 'nonce');
        
        $post_id = absint($_POST['post_id']);
        $data = json_decode(stripslashes($_POST['data']), true);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permissão negada');
        }
        
        if (get_post_type($post_id) !== 'investment') {
            wp_send_json_error('Tipo de post inválido');
        }
        
        try {
            // Validar dados antes de salvar
            $validation = $this->validate_investment_data($data);
            if (!$validation['valid']) {
                wp_send_json_error($validation['message']);
            }
            
            // Salvar dados
            $this->save_investment_data($post_id, $data);
            
            // Calcular valores automáticos
            $calculated = $this->calculate_investment_values($post_id);
            
            // Trigger hooks para outros sistemas
            do_action('s_invest_investment_saved', $post_id, $data);
            
            wp_send_json_success([
                'message' => 'Investimento salvo com sucesso!',
                'calculated' => $calculated
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error('Erro ao salvar: ' . $e->getMessage());
        }
    }
    
    /**
     * AJAX: Upload de arquivo
     */
    public function ajax_upload_file() {
        check_ajax_referer('s_invest_admin', 'nonce');
        
        if (!current_user_can('upload_files')) {
            wp_send_json_error('Permissão negada para upload');
        }
        
        if (empty($_FILES['file'])) {
            wp_send_json_error('Nenhum arquivo enviado');
        }
        
        $field = sanitize_text_field($_POST['field']);
        
        // Validar tipo de arquivo baseado no campo
        $allowed_types = $this->get_allowed_file_types($field);
        
        $file = $_FILES['file'];
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file_type['ext'], $allowed_types)) {
            wp_send_json_error('Tipo de arquivo não permitido');
        }
        
        // Upload
        $upload = wp_handle_upload($file, ['test_form' => false]);
        
        if (isset($upload['error'])) {
            wp_send_json_error($upload['error']);
        }
        
        wp_send_json_success([
            'url' => $upload['url'],
            'file' => $upload['file'],
            'type' => $file_type['type']
        ]);
    }
    
    /**
     * AJAX: Obter cálculos em tempo real
     */
    public function ajax_get_calculations() {
        check_ajax_referer('s_invest_admin', 'nonce');
        
        $post_id = absint($_POST['post_id']);
        $data = json_decode(stripslashes($_POST['data']), true);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Permissão negada');
        }
        
        $calculations = $this->calculate_values_from_data($data);
        
        wp_send_json_success($calculations);
    }
    
    /**
     * AJAX: Duplicar investimento
     */
    public function ajax_duplicate_investment() {
        check_ajax_referer('s_invest_admin', 'nonce');
        
        $original_id = absint($_POST['post_id']);
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permissão negada');
        }
        
        $original_post = get_post($original_id);
        if (!$original_post || $original_post->post_type !== 'investment') {
            wp_send_json_error('Investimento não encontrado');
        }
        
        // Criar novo post
        $new_post_data = [
            'post_title' => $original_post->post_title . ' (Cópia)',
            'post_content' => $original_post->post_content,
            'post_status' => 'draft',
            'post_type' => 'investment',
            'post_author' => get_current_user_id()
        ];
        
        $new_post_id = wp_insert_post($new_post_data);
        
        if (is_wp_error($new_post_id)) {
            wp_send_json_error('Erro ao criar cópia');
        }
        
        // Copiar metadados
        $meta_data = get_post_meta($original_id);
        foreach ($meta_data as $key => $values) {
            if (strpos($key, 's_invest_') === 0) {
                update_post_meta($new_post_id, $key, $values[0]);
            }
        }
        
        // Resetar campos que não devem ser copiados
        update_post_meta($new_post_id, 's_invest_total_captado', 0);
        update_post_meta($new_post_id, 's_invest_cotas_vendidas', 0);
        
        wp_send_json_success([
            'new_post_id' => $new_post_id,
            'edit_url' => admin_url('post.php?post=' . $new_post_id . '&action=edit')
        ]);
    }
    
    /**
     * Validar dados do investimento
     */
    private function validate_investment_data($data) {
        $errors = [];
        
        // Validações obrigatórias
        if (empty($data['classe_de_ativos'])) {
            $errors[] = 'Classe de ativos é obrigatória';
        }
        
        if (empty($data['valor_total']) || floatval($data['valor_total']) <= 0) {
            $errors[] = 'Valor total deve ser maior que zero';
        }
        
        if (empty($data['aporte_minimo']) || floatval($data['aporte_minimo']) <= 0) {
            $errors[] = 'Aporte mínimo deve ser maior que zero';
        }
        
        // Validações específicas para SCP
        if ($data['classe_de_ativos'] === 'private') {
            if (empty($data['valor_cota']) || floatval($data['valor_cota']) <= 0) {
                $errors[] = 'Valor da cota é obrigatório para SCP';
            }
            
            if (empty($data['total_cotas']) || intval($data['total_cotas']) <= 0) {
                $errors[] = 'Total de cotas é obrigatório para SCP';
            }
            
            // Verificar se valor_cota não é menor que aporte_minimo
            if ($data['valor_cota'] && $data['aporte_minimo']) {
                if (floatval($data['valor_cota']) < floatval($data['aporte_minimo'])) {
                    $errors[] = 'Valor da cota não pode ser menor que o aporte mínimo';
                }
            }
        }
        
        // Validação de datas
        if (!empty($data['data_lancamento']) && !empty($data['fim_captacao'])) {
            $data_lancamento = strtotime($data['data_lancamento']);
            $fim_captacao = strtotime($data['fim_captacao']);
            
            if ($fim_captacao <= $data_lancamento) {
                $errors[] = 'Data fim da captação deve ser posterior à data de lançamento';
            }
        }
        
        return [
            'valid' => empty($errors),
            'message' => implode(', ', $errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Salvar dados do investimento
     */
    private function save_investment_data($post_id, $data) {
        foreach ($data as $key => $value) {
            // Sanitizar valor baseado no tipo
            $sanitized_value = $this->sanitize_field_value($key, $value);
            update_post_meta($post_id, 's_invest_' . $key, $sanitized_value);
        }
        
        // Atualizar taxonomia se necessário
        if (!empty($data['classe_de_ativos'])) {
            $term = $data['classe_de_ativos'] === 'private' ? 'private-scp' : 'compra-em-lote';
            wp_set_post_terms($post_id, $term, 'tipo_produto');
        }
    }
    
    /**
     * Sanitizar valor do campo
     */
    private function sanitize_field_value($key, $value) {
        switch ($key) {
            case 'classe_de_ativos':
            case 'status_captacao':
            case 'risco':
                return sanitize_text_field($value);
                
            case 'valor_total':
            case 'aporte_minimo':
            case 'valor_cota':
            case 'rentabilidade':
            case 'vgv_total':
                return floatval(str_replace([',', 'R$', ' '], ['', '', ''], $value));
                
            case 'total_cotas':
            case 'prazo_min':
            case 'prazo_max':
                return intval($value);
                
            case 'data_lancamento':
            case 'fim_captacao':
                return sanitize_text_field($value);
                
            case 'descricao_originadora':
                return wp_kses_post($value);
                
            case 'motivos':
            case 'riscos':
            case 'documentos':
                return is_array($value) ? array_map([$this, 'sanitize_repeater_item'], $value) : [];
                
            default:
                return sanitize_text_field($value);
        }
    }
    
    /**
     * Sanitizar item de campo repeater
     */
    private function sanitize_repeater_item($item) {
        if (!is_array($item)) return [];
        
        $sanitized = [];
        foreach ($item as $sub_key => $sub_value) {
            if ($sub_key === 'descricao') {
                $sanitized[$sub_key] = sanitize_textarea_field($sub_value);
            } else {
                $sanitized[$sub_key] = sanitize_text_field($sub_value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Calcular valores automáticos
     */
    private function calculate_investment_values($investment_id) {
        $classe = get_post_meta($investment_id, 's_invest_classe_de_ativos', true);
        
        if ($classe === 'private') {
            return $this->calculate_scp_values($investment_id);
        } else {
            return $this->calculate_trade_values($investment_id);
        }
    }
    
    /**
     * Calcular valores SCP
     */
    private function calculate_scp_values($investment_id) {
        // Buscar aportes SCP existentes
        $aportes = get_posts([
            'post_type' => 'aporte',
            'meta_query' => [
                [
                    'key' => 's_invest_investment_id',
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
        
        // Atualizar campos calculados
        update_post_meta($investment_id, 's_invest_cotas_vendidas', $total_cotas_vendidas);
        update_post_meta($investment_id, 's_invest_cotas_disponiveis', $cotas_disponiveis);
        update_post_meta($investment_id, 's_invest_total_captado', $total_captado);
        
        return [
            'cotas_vendidas' => $total_cotas_vendidas,
            'cotas_disponiveis' => $cotas_disponiveis,
            'total_captado' => $total_captado
        ];
    }
    
    /**
     * Calcular valores Trade
     */
    private function calculate_trade_values($investment_id) {
        $aportes = get_posts([
            'post_type' => 'aporte',
            'meta_query' => [
                [
                    'key' => 's_invest_investment_id',
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
        
        return [
            'total_captado' => $total_captado
        ];
    }
    
    /**
     * Calcular valores a partir de dados temporários
     */
    private function calculate_values_from_data($data) {
        $calculations = [];
        
        if ($data['classe_de_ativos'] === 'private') {
            $valor_cota = floatval($data['valor_cota'] ?? 0);
            $total_cotas = intval($data['total_cotas'] ?? 0);
            $cotas_vendidas = intval($data['cotas_vendidas'] ?? 0);
            
            $calculations['cotas_disponiveis'] = max(0, $total_cotas - $cotas_vendidas);
            
            if ($valor_cota > 0 && $total_cotas > 0) {
                $calculations['valor_total_calculado'] = $valor_cota * $total_cotas;
            }
        }
        
        return $calculations;
    }
    
    /**
     * Obter tipos de arquivo permitidos
     */
    private function get_allowed_file_types($field) {
        $types = [
            'url_lamina_tecnica' => ['pdf'],
            'documentos' => ['pdf', 'doc', 'docx'],
            'default' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']
        ];
        
        return $types[$field] ?? $types['default'];
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        global $post;
        
        if (!$post || $post->post_type !== 'investment') {
            return;
        }
        
        // Notice sobre migração do ACF
        if (function_exists('get_field') && get_field('valor_total', $post->ID)) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p><strong>Sistema Nativo Ativo:</strong> Este investimento ainda usa campos ACF. ';
            echo '<a href="#" onclick="migrateFromACF(' . $post->ID . ')">Migrar para sistema nativo</a></p>';
            echo '</div>';
        }
    }
}

// Initialize
S_Invest_Native_Fields_AJAX::get_instance();