<?php
/**
 * Calculations Engine - Sistema Unificado de Cálculos
 * Migrado do plugin sky-invest-panel
 * Versão: 3.0.0
 */

defined('ABSPATH') || exit;

class S_Invest_Calculations {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hooks para recálculos automáticos
        add_action('acf/save_post', [$this, 'auto_recalculate_on_save'], 25);
        add_action('s_invest_force_recalculate_scp', [$this, 'force_recalculate_scp']);
        
        // Cache clearing
        add_action('save_post_aporte', [$this, 'clear_investment_cache'], 20, 1);
        add_action('save_post_investment', [$this, 'clear_investment_cache'], 20, 1);
    }
    
    /**
     * ==============================================
     * FUNÇÕES HELPER UNIFICADAS
     * ==============================================
     */
    
    /**
     * Retorna meta_captacao como valor_total (unificados)
     */
    public static function get_meta_captacao($investment_id) {
        return get_field('valor_total', $investment_id);
    }
    
    /**
     * Para compatibilidade com temas que ainda usam aporte_minimo nos SCP
     */
    public static function get_aporte_minimo_scp($investment_id) {
        return get_field('valor_cota', $investment_id);
    }
    
    /**
     * Verifica se um investimento é SCP
     */
    public static function is_scp_investment($investment_id) {
        $terms = get_the_terms($investment_id, 'tipo_produto');
        if ($terms && !is_wp_error($terms)) {
            return strtolower($terms[0]->slug) === 'private-scp';
        }
        return false;
    }
    
    /**
     * Verifica se um investimento é Trade
     */
    public static function is_trade_investment($investment_id) {
        $terms = get_the_terms($investment_id, 'tipo_produto');
        if ($terms && !is_wp_error($terms)) {
            return in_array(strtolower($terms[0]->slug), ['compra-em-lote', 'land-bank']);
        }
        return false;
    }
    
    /**
     * Retorna o tipo de investimento
     */
    public static function get_investment_type($investment_id) {
        $terms = get_the_terms($investment_id, 'tipo_produto');
        if ($terms && !is_wp_error($terms)) {
            return $terms[0]->slug;
        }
        return 'compra-em-lote'; // Fallback
    }
    
    /**
     * Retorna o nome do tipo de investimento
     */
    public static function get_investment_type_name($investment_id) {
        $terms = get_the_terms($investment_id, 'tipo_produto');
        if ($terms && !is_wp_error($terms)) {
            return $terms[0]->name;
        }
        return 'Compra em Lote'; // Fallback
    }
    
    /**
     * ==============================================
     * CÁLCULOS SCP
     * ==============================================
     */
    
    /**
     * Calcular participação por cota SCP
     */
    public static function calculate_scp_participation_per_share($investment_id) {
        $vgv_total = floatval(get_field('vgv_total', $investment_id));
        $total_cotas = intval(get_field('total_cotas', $investment_id));
        
        if ($total_cotas === 0) {
            return 0;
        }
        
        $percentual_total = floatval(get_field('percentual_total_vgv', $investment_id));
        
        if ($percentual_total === 0) {
            $percentual_total = 20; // Padrão de 20%
        }
        
        return ($percentual_total / $total_cotas);
    }
    
    /**
     * Calcular dados consolidados de um aporte SCP
     */
    public static function calculate_scp_aporte_data($aporte_id, $force_recalc = false) {
        $investment_id = get_field('investment_id', $aporte_id);
        
        if (!$investment_id || !self::is_scp_investment($investment_id)) {
            return false;
        }
        
        // Cache check
        $cache_key = "scp_aporte_data_{$aporte_id}";
        if (!$force_recalc && ($cached = get_transient($cache_key))) {
            return $cached;
        }
        
        $quantidade_cotas = intval(get_field('quantidade_cotas', $aporte_id));
        $valor_cota = floatval(get_field('valor_cota', $investment_id));
        
        // Dados básicos
        $valor_aportado = $quantidade_cotas * $valor_cota;
        
        // Participação
        $participacao_por_cota = self::calculate_scp_participation_per_share($investment_id);
        $participacao_total = $quantidade_cotas * $participacao_por_cota;
        
        // VGV
        $vgv_total = floatval(get_field('vgv_total', $investment_id));
        $vgv_por_cota = ($quantidade_cotas > 0 && $vgv_total > 0) ? 
                        ($vgv_total * $participacao_total / 100) : 0;
        
        // Dividendos (se houver)
        $dividendo_recebido_total = floatval(get_field('dividendo_recebido_total', $aporte_id));
        
        $result = [
            'quantidade_cotas' => $quantidade_cotas,
            'valor_cota' => $valor_cota,
            'valor_aportado' => $valor_aportado,
            'participacao_por_cota' => $participacao_por_cota,
            'participacao_total' => $participacao_total,
            'vgv_total' => $vgv_total,
            'vgv_por_cota' => $vgv_por_cota,
            'dividendo_recebido_total' => $dividendo_recebido_total,
            'updated_at' => current_time('mysql')
        ];
        
        // Cache por 1 hora
        set_transient($cache_key, $result, HOUR_IN_SECONDS);
        
        return $result;
    }
    
    /**
     * Atualizar campos calculados SCP automaticamente
     */
    public static function update_scp_calculated_fields($aporte_id) {
        $data = self::calculate_scp_aporte_data($aporte_id, true);
        
        if (!$data) {
            return false;
        }
        
        // Atualizar campos ACF
        update_field('valor_aportado', $data['valor_aportado'], $aporte_id);
        update_field('participacao_por_cota', $data['participacao_por_cota'], $aporte_id);
        update_field('participacao_total', $data['participacao_total'], $aporte_id);
        
        return true;
    }
    
    /**
     * ==============================================
     * CÁLCULOS TRADE
     * ==============================================
     */
    
    /**
     * Calcular rentabilidade de aporte Trade
     */
    public static function calculate_trade_profitability($aporte_id) {
        $investment_id = get_field('investment_id', $aporte_id);
        
        if (!$investment_id || self::is_scp_investment($investment_id)) {
            return false;
        }
        
        // Valor investido total
        $historico_aportes = get_field('historico_aportes', $aporte_id) ?: [];
        $valor_total_investido = 0;
        
        foreach ($historico_aportes as $item) {
            $valor_total_investido += floatval($item['valor_aporte'] ?? 0);
        }
        
        if ($valor_total_investido === 0) {
            $valor_total_investido = floatval(get_field('valor_compra', $aporte_id));
        }
        
        // Valor atual
        $valor_atual = floatval(get_field('valor_atual', $aporte_id));
        if ($valor_atual === 0) {
            $valor_atual = $valor_total_investido;
        }
        
        // Calcular rentabilidade
        $rentabilidade_absoluta = $valor_atual - $valor_total_investido;
        $rentabilidade_percentual = ($valor_total_investido > 0) ? 
                                   (($valor_atual / $valor_total_investido) - 1) * 100 : 0;
        
        return [
            'valor_investido' => $valor_total_investido,
            'valor_atual' => $valor_atual,
            'rentabilidade_absoluta' => $rentabilidade_absoluta,
            'rentabilidade_percentual' => $rentabilidade_percentual,
            'updated_at' => current_time('mysql')
        ];
    }
    
    /**
     * ==============================================
     * RESUMOS DE INVESTIDOR
     * ==============================================
     */
    
    /**
     * Obter resumo unificado e atualizado do investidor
     */
    public static function get_investor_summary_unified($user_id) {
        $cache_key = "investor_summary_unified_v3_{$user_id}";
        $summary = get_transient($cache_key);
        
        if (false === $summary) {
            // Buscar todos os aportes do usuário
            $aportes = get_posts([
                'post_type' => 'aporte',
                'posts_per_page' => -1,
                'meta_query' => [
                    ['key' => 'investidor_id', 'value' => $user_id]
                ],
                'post_status' => 'publish'
            ]);
            
            $summary = [
                'total_aportes' => count($aportes),
                'valor_total_trade' => 0,
                'valor_atual_trade' => 0,
                'total_scp' => 0,
                'valor_total_scp' => 0,
                'dividendos_scp_recebidos' => 0,
                'participacao_total_scp' => 0,
                'investimentos_agrupados' => []
            ];
            
            // Agrupar por investimento
            $investimentos_agrupados = [];
            
            foreach ($aportes as $aporte) {
                $investment_id = get_field('investment_id', $aporte->ID);
                
                if (!$investment_id) continue;
                
                $is_scp = self::is_scp_investment($investment_id);
                
                // Inicializar grupo se não existir
                if (!isset($investimentos_agrupados[$investment_id])) {
                    $investimentos_agrupados[$investment_id] = [
                        'aportes_ids' => [],
                        'is_scp' => $is_scp,
                        'valor_total_investido' => 0,
                        'valor_atual_total' => 0,
                        'total_cotas' => 0,
                        'participacao_total' => 0,
                        'dividendos_recebidos' => 0,
                        'nome_ativo' => $is_scp ? get_field('nome_ativo', $investment_id) : '',
                        'vgv_total' => $is_scp ? get_field('vgv_total', $investment_id) : 0,
                        'percentual_vgv_por_cota' => $is_scp ? self::calculate_scp_participation_per_share($investment_id) : 0
                    ];
                }
                
                // Adicionar aporte ao grupo
                $investimentos_agrupados[$investment_id]['aportes_ids'][] = $aporte->ID;
                
                if ($is_scp) {
                    // Cálculos SCP
                    $scp_data = self::calculate_scp_aporte_data($aporte->ID);
                    if ($scp_data) {
                        $investimentos_agrupados[$investment_id]['total_cotas'] += $scp_data['quantidade_cotas'];
                        $investimentos_agrupados[$investment_id]['valor_total_investido'] += $scp_data['valor_aportado'];
                        $investimentos_agrupados[$investment_id]['participacao_total'] += $scp_data['participacao_total'];
                        $investimentos_agrupados[$investment_id]['dividendos_recebidos'] += $scp_data['dividendo_recebido_total'];
                    }
                } else {
                    // Cálculos Trade
                    $trade_data = self::calculate_trade_profitability($aporte->ID);
                    if ($trade_data) {
                        $investimentos_agrupados[$investment_id]['valor_total_investido'] += $trade_data['valor_investido'];
                        $investimentos_agrupados[$investment_id]['valor_atual_total'] += $trade_data['valor_atual'];
                    }
                }
            }
            
            // Consolidar resumo
            foreach ($investimentos_agrupados as $investment_id => $dados) {
                if ($dados['is_scp']) {
                    $summary['total_scp']++;
                    $summary['valor_total_scp'] += $dados['valor_total_investido'];
                    $summary['dividendos_scp_recebidos'] += $dados['dividendos_recebidos'];
                    $summary['participacao_total_scp'] += $dados['participacao_total'];
                } else {
                    $summary['valor_total_trade'] += $dados['valor_total_investido'];
                    $summary['valor_atual_trade'] += $dados['valor_atual_total'];
                }
            }
            
            // Salvar agrupamentos
            $summary['investimentos_agrupados'] = $investimentos_agrupados;
            
            // Cache por 1 hora
            set_transient($cache_key, $summary, HOUR_IN_SECONDS);
            
            // Log para debug
            if (WP_DEBUG) {
                error_log("Resumo investidor UNIFIED {$user_id}: " . print_r($summary, true));
            }
        }
        
        return $summary;
    }
    
    /**
     * ==============================================
     * FORMATAÇÃO E UTILITÁRIOS
     * ==============================================
     */
    
    /**
     * Formatar moeda brasileira
     */
    public static function format_currency($value, $show_symbol = true) {
        $formatted = number_format(floatval($value), 2, ',', '.');
        return $show_symbol ? 'R$ ' . $formatted : $formatted;
    }
    
    /**
     * Formatar percentual
     */
    public static function format_percentage($value, $decimals = 2) {
        return number_format(floatval($value), $decimals, ',', '.') . '%';
    }
    
    /**
     * ==============================================
     * HOOKS E AUTOMAÇÕES
     * ==============================================
     */
    
    /**
     * Recálculo automático ao salvar
     */
    public function auto_recalculate_on_save($post_id) {
        if (get_post_type($post_id) === 'aporte') {
            $investment_id = get_field('investment_id', $post_id);
            
            if ($investment_id && self::is_scp_investment($investment_id)) {
                self::update_scp_calculated_fields($post_id);
            }
            
            // Limpar cache do investidor
            $user_id = get_field('investidor_id', $post_id);
            if ($user_id) {
                $this->clear_investor_cache($user_id);
            }
        }
    }
    
    /**
     * Forçar recálculo SCP
     */
    public function force_recalculate_scp($post_id) {
        if (get_post_type($post_id) === 'aporte') {
            self::update_scp_calculated_fields($post_id);
            
            // Limpar cache
            delete_transient("scp_aporte_data_{$post_id}");
            
            $user_id = get_field('investidor_id', $post_id);
            if ($user_id) {
                $this->clear_investor_cache($user_id);
            }
        }
    }
    
    /**
     * Limpar cache de investimento
     */
    public function clear_investment_cache($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        
        $post_type = get_post_type($post_id);
        if (!in_array($post_type, ['investment', 'aporte'])) return;

        // Limpar cache relacionado
        if ($post_type === 'aporte') {
            $user_id = get_field('investidor_id', $post_id);
            if ($user_id) {
                $this->clear_investor_cache($user_id);
            }
            
            // Limpar cache específico do aporte
            delete_transient("scp_aporte_data_{$post_id}");
        }
        
        // Limpar cache geral
        wp_cache_delete('s_invest_stats_general');
    }
    
    /**
     * Limpar cache do investidor
     */
    private function clear_investor_cache($user_id) {
        // Limpar todas as versões do cache
        $cache_keys = [
            "investor_summary_{$user_id}",
            "investor_summary_unified_{$user_id}",
            "investor_summary_unified_v2_{$user_id}",
            "investor_summary_unified_v3_{$user_id}",
            "dashboard_stats_{$user_id}",
            "investor_dashboard_{$user_id}"
        ];
        
        foreach ($cache_keys as $key) {
            delete_transient($key);
            wp_cache_delete($key, 'user_stats');
        }
        
        // Limpar cache de cartões de investimento
        $cache_contexts = ['public', 'panel', 'my-investments'];
        foreach ($cache_contexts as $context) {
            wp_cache_delete("investment_card_data_{$user_id}_{$context}", 'investment_cards');
        }
    }
    
    /**
     * ==============================================
     * FUNÇÕES DE DEBUG
     * ==============================================
     */
    
    /**
     * Debug: Listar dados de um aporte
     */
    public static function debug_aporte_data($aporte_id) {
        if (!WP_DEBUG) return;
        
        $investment_id = get_field('investment_id', $aporte_id);
        $is_scp = self::is_scp_investment($investment_id);
        
        error_log("=== DEBUG APORTE {$aporte_id} ===");
        error_log("Investment ID: {$investment_id}");
        error_log("Is SCP: " . ($is_scp ? 'Yes' : 'No'));
        
        if ($is_scp) {
            $scp_data = self::calculate_scp_aporte_data($aporte_id, true);
            error_log("SCP Data: " . print_r($scp_data, true));
        } else {
            $trade_data = self::calculate_trade_profitability($aporte_id);
            error_log("Trade Data: " . print_r($trade_data, true));
        }
        
        error_log("=== END DEBUG APORTE ===");
    }
}

// Inicializar
S_Invest_Calculations::get_instance();