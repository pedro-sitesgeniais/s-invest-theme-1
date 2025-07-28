<?php
/**
 * Funções auxiliares globais do tema - VERSÃO CORRIGIDA
 */

/**
 * Verifica se o usuário atual tem acesso a um painel com base na role
 * Admin sempre tem acesso total
 */
function user_has_panel_access( $role ) {
    if ( current_user_can( 'administrator' ) ) {
        return true;
    }
    return current_user_can( $role );
}

/**
 * Retorna os dados de aportes por investimento para o gráfico do investidor logado
 * Considera apenas aportes ativos (não vendidos)
 */
function icf_get_dados_grafico_aportes( $user_id = null, $incluir_vendidos = false ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }

    $meta_query = [
        [
            'key'     => 'investidor_id',
            'value'   => $user_id,
            'compare' => '=',
        ]
    ];
    
    if (!$incluir_vendidos) {
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key'     => 'venda_status',
                'compare' => 'NOT EXISTS'
            ],
            [
                'key'     => 'venda_status',
                'value'   => false,
                'compare' => '='
            ]
        ];
    }

    $aportes = get_posts([
        'post_type'      => 'aporte',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => $meta_query,
    ]);

    $dados = [];
    foreach ( $aportes as $aporte ) {
        $investment_id = get_field( 'investment_id', $aporte->ID );
        $titulo = $investment_id ? get_the_title( $investment_id ) : 'Outro';
        
        $venda_status = get_field('venda_status', $aporte->ID);
        if ($venda_status) {
            $valor = (float) get_field( 'venda_valor', $aporte->ID );
        } else {
            $valor = (float) get_field( 'valor_atual', $aporte->ID );
        }

        if ( ! isset( $dados[ $titulo ] ) ) {
            $dados[ $titulo ] = 0;
        }
        $dados[ $titulo ] += $valor;
    }

    $resultado = [];
    foreach ( $dados as $nome => $total ) {
        $resultado[] = [
            'label' => $nome,
            'valor' => $total,
        ];
    }
    return $resultado;
}

/**
 * FUNÇÃO CORRIGIDA: Retorna estatísticas de aportes de um usuário 
 * CORREÇÃO: Rentabilidade Projetada = Último valor do histórico de rentabilidade
 * CORREÇÃO: Rentabilidade Consolidada = Valor total recebido das vendas
 */
function icf_get_estatisticas_aportes_usuario( $user_id = null ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    
    $todos_aportes = get_posts([
        'post_type'      => 'aporte',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            [
                'key'   => 'investidor_id',
                'value' => $user_id,
            ]
        ],
        'fields' => 'ids',
    ]);
    
    if (empty($todos_aportes)) {
        return [
            'aportes_ativos' => 0,
            'aportes_vendidos' => 0,
            'valor_investido_ativo' => 0,
            'valor_atual_total' => 0,
            'rentabilidade_ativa' => 0,
            'rentabilidade_projetada' => 0,
            'vendas' => [
                'total_investido' => 0,
                'total_recebido' => 0,
                'total_rentabilidade' => 0,
                'rentabilidade_percentual_media' => 0,
                'quantidade' => 0
            ]
        ];
    }
    
    $aportes_ativos = 0;
    $aportes_vendidos = 0;
    $valor_investido_ativo = 0;
    $valor_atual_total = 0;
    $total_investido_vendidos = 0;
    
    // ===== CORREÇÃO: AGORA COLETA O VALOR TOTAL RECEBIDO DAS VENDAS =====
    $total_recebido_vendidos = 0;
    
    // ===== CORREÇÃO: COLETA O ÚLTIMO VALOR DO HISTÓRICO DE RENTABILIDADE =====
    $rentabilidade_projetada_total = 0;
    
    $soma_percentuais_vendas = 0;
    
    foreach ($todos_aportes as $aporte_id) {
        $venda_status = get_field('venda_status', $aporte_id);
        
        // Calcular valor total investido neste aporte
        $historico_aportes = get_field('historico_aportes', $aporte_id) ?: [];
        $valor_total_aporte = 0;
        
        if (!empty($historico_aportes) && is_array($historico_aportes)) {
            foreach ($historico_aportes as $item) {
                $valor_total_aporte += (float) ($item['valor_aporte'] ?? 0);
            }
        }
        
        if ($valor_total_aporte == 0) {
            $valor_total_aporte = (float) get_field('valor_compra', $aporte_id);
        }
        
        if ($venda_status) {
            // APORTE VENDIDO
            $aportes_vendidos++;
            
            // ===== CORREÇÃO: PEGA O VALOR TOTAL RECEBIDO (não o lucro) =====
            $valor_recebido_total = (float) get_field('venda_valor', $aporte_id);
            
            $total_investido_vendidos += $valor_total_aporte;
            $total_recebido_vendidos += $valor_recebido_total;
            
            // CALCULAR PERCENTUAL INDIVIDUAL COM FÓRMULA CORRETA
            if ($valor_total_aporte > 0 && $valor_recebido_total > 0) {
                $percentual_individual = ($valor_recebido_total / $valor_total_aporte) * 100;
                $soma_percentuais_vendas += $percentual_individual;
            }
            
        } else {
            // APORTE ATIVO
            $aportes_ativos++;
            $valor_atual = (float) get_field('valor_atual', $aporte_id);
            
            if ($valor_atual == 0) {
                $valor_atual = $valor_total_aporte;
            }
            
            $valor_investido_ativo += $valor_total_aporte;
            $valor_atual_total += $valor_atual;
            
            // ===== CORREÇÃO: RENTABILIDADE PROJETADA = ÚLTIMO VALOR DO HISTÓRICO =====
            $historico_rentabilidade = get_field('rentabilidade_historico', $aporte_id);
            if (!empty($historico_rentabilidade) && is_array($historico_rentabilidade)) {
                $ultimo_valor = end($historico_rentabilidade);
                if (isset($ultimo_valor['valor'])) {
                    $rentabilidade_projetada_total += (float) $ultimo_valor['valor'];
                }
            }
        }
    }
    
    $rentabilidade_ativa = $valor_atual_total - $valor_investido_ativo;
    
    // ===== CORREÇÃO: RENTABILIDADE CONSOLIDADA = VALOR RECEBIDO (não lucro) =====
    $rentabilidade_consolidada = $total_recebido_vendidos;
    
    // Calcular percentual médio das vendas
    $rentabilidade_percentual_media = $aportes_vendidos > 0 ? ($soma_percentuais_vendas / $aportes_vendidos) : 0;
    
    return [
        'aportes_ativos' => $aportes_ativos,
        'aportes_vendidos' => $aportes_vendidos,
        'valor_investido_ativo' => $valor_investido_ativo,
        'valor_atual_total' => $valor_atual_total,
        'rentabilidade_ativa' => $rentabilidade_ativa,
        'rentabilidade_projetada' => $rentabilidade_projetada_total,
        'vendas' => [
            // MANTER CHAVES ORIGINAIS PARA COMPATIBILIDADE:
            'total_compra' => $total_investido_vendidos,        // era 'total_investido'
            'total_venda' => $total_recebido_vendidos,          // era 'total_recebido' 
            'total_rentabilidade' => $rentabilidade_consolidada, // AGORA É O VALOR RECEBIDO
            'rentabilidade_percentual_media' => $rentabilidade_percentual_media,
            'quantidade' => $aportes_vendidos
        ]
    ];
}

/**
 * Retorna o valor total investido de um usuário
 * Usa histórico de aportes em vez de valor_compra
 */
function icf_get_valor_total_aportes_investidor( $user_id = null, $incluir_vendidos = false ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    
    $meta_query = [
        [
            'key'   => 'investidor_id',
            'value' => $user_id,
        ]
    ];
    
    if (!$incluir_vendidos) {
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key'     => 'venda_status',
                'compare' => 'NOT EXISTS'
            ],
            [
                'key'     => 'venda_status',
                'value'   => false,
                'compare' => '='
            ]
        ];
    }
    
    $aportes = get_posts([
        'post_type'      => 'aporte',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => $meta_query,
        'fields' => 'ids',
    ]);

    $total = 0;
    foreach ( $aportes as $aporte_id ) {
        $historico = get_field( 'historico_aportes', $aporte_id );
        if ($historico && is_array($historico)) {
            foreach ($historico as $ap) {
                $total += (float) ($ap['valor_aporte'] ?? 0);
            }
        }
    }
    return $total;
}

/**
 * Retorna array de progresso mensal (acumulado de aportes) do investidor
 * Considera status de venda
 */
function icf_get_progresso_mensal_usuario( $user_id = null, $months = 12, $incluir_vendidos = false ) {
  if ( ! $user_id ) {
      $user_id = get_current_user_id();
  }

  $meta_query = [
      [
          'key'   => 'investidor_id',
          'value' => $user_id,
      ]
  ];
  
  if (!$incluir_vendidos) {
      $meta_query[] = [
          'relation' => 'OR',
          [
              'key'     => 'venda_status',
              'compare' => 'NOT EXISTS'
          ],
          [
              'key'     => 'venda_status',
              'value'   => false,
              'compare' => '='
          ]
      ];
  }

  $aportes = get_posts([
      'post_type'      => 'aporte',
      'posts_per_page' => -1,
      'post_status'    => 'publish',
      'meta_query'     => $meta_query,
  ]);

  $somas = [];
  foreach ( $aportes as $aporte ) {
      $historico = get_field( 'historico_aportes', $aporte->ID );
      if ($historico && is_array($historico)) {
          foreach ($historico as $ap) {
              $data = $ap['data_aporte'] ?? '';
              $ts   = strtotime( $data );
              if ( ! $ts ) {
                  continue;
              }
              $ym    = date( 'Y-m', $ts );
              $valor = floatval( $ap['valor_aporte'] ?? 0 );
              if ( ! isset( $somas[ $ym ] ) ) {
                  $somas[ $ym ] = 0;
              }
              $somas[ $ym ] += $valor;
          }
      }
  }

  ksort( $somas );
  $acumulado = 0;
  $resultado = [];
  $cutoff = strtotime( "-{$months} months" );
  foreach ( $somas as $ym => $total ) {
      $ts = strtotime( $ym . '-01' );
      if ( $ts < $cutoff ) {
          continue;
      }
      $acumulado += $total;
      $resultado[] = [
          'label' => date_i18n( 'M/Y', $ts ),
          'value' => $acumulado,
      ];
  }
  return $resultado;
}

/**
 * Retorna array de rentabilidade média mensal para um investimento específico
 * Considera status de venda
 */
function icf_get_rentabilidade_mensal( $investment_id, $user_id = null, $incluir_vendidos = false ) {
    global $wpdb;

    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }

    $rent_proj = floatval( get_field( 'rentabilidade', $investment_id ) );

    $where_venda = '';
    if (!$incluir_vendidos) {
        $where_venda = "AND (meta_venda.meta_value IS NULL OR meta_venda.meta_value = '0' OR meta_venda.meta_value = '')";
    }

    $rows = $wpdb->get_results( $wpdb->prepare(
        "
        SELECT DISTINCT
          YEAR(meta_aporte.meta_value) AS ano,
          MONTH(meta_aporte.meta_value) AS mes
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} meta_inv
          ON p.ID = meta_inv.post_id
         AND meta_inv.meta_key   = 'investment_id'
         AND meta_inv.meta_value = %d
        INNER JOIN {$wpdb->postmeta} meta_user
          ON p.ID = meta_user.post_id
         AND meta_user.meta_key   = 'investidor_id'
         AND meta_user.meta_value = %d
        INNER JOIN {$wpdb->postmeta} meta_aporte
          ON p.ID = meta_aporte.post_id
         AND meta_aporte.meta_key = 'data_aporte'
        LEFT JOIN {$wpdb->postmeta} meta_venda
          ON p.ID = meta_venda.post_id
         AND meta_venda.meta_key = 'venda_status'
        WHERE p.post_type   = 'aporte'
          AND p.post_status = 'publish'
          {$where_venda}
        ORDER BY ano ASC, mes ASC
        ",
        $investment_id,
        $user_id
    ), ARRAY_A );

    $dados = [];
    foreach ( $rows as $row ) {
        $timestamp = strtotime( sprintf( '%04d-%02d-01', $row['ano'], $row['mes'] ) );
        $dados[]   = [
            'label' => date_i18n( 'M/Y', $timestamp ),
            'value' => $rent_proj,
        ];
    }

    return $dados;
}

/**
 * FUNÇÃO ATUALIZADA: Retorna o status atual de um investimento, usando o novo sistema
 */
function icf_get_investment_status( int $inv_id ): string {
    // Usar o novo sistema de status da captação
    if (function_exists('s_invest_get_status_captacao_info')) {
        $status_info = s_invest_get_status_captacao_info($inv_id);
        return $status_info['label'];
    }
    
    // Fallback para o sistema antigo
    $rows = get_field( 'datas_de_repasse', $inv_id ) ?: [];

    if ( empty( $rows ) ) {
        return 'Em Captação';
    }

    $timestamps = array_map(
        fn( $r ) => strtotime( $r['data_repasse'] ),
        $rows
    );
    sort( $timestamps );

    $hoje     = current_time( 'timestamp' );
    $primeiro = reset( $timestamps );
    $ultimo   = end( $timestamps );

    if ( $hoje < $primeiro ) {
        return 'Em Captação';
    }

    if ( $hoje <= $ultimo ) {
        return 'Em Andamento';
    }

    return 'Concluído';
}

/**
 * NOVA FUNÇÃO: Wrapper para verificar status da captação
 */
function icf_get_captacao_status($investment_id) {
    if (function_exists('s_invest_calcular_status_captacao')) {
        return s_invest_calcular_status_captacao($investment_id);
    }
    
    // Fallback básico
    $valor_total = floatval(get_field('valor_total', $investment_id) ?: 0);
    $total_captado = floatval(get_field('total_captado', $investment_id) ?: 0);
    
    if ($valor_total > 0 && ($total_captado / $valor_total) >= 1.0) {
        return 'encerrado_meta';
    }
    
    return 'ativo';
}

/**
 * NOVA FUNÇÃO: Wrapper para informações completas do status
 */
function icf_get_captacao_status_info($investment_id) {
    if (function_exists('s_invest_get_status_captacao_info')) {
        return s_invest_get_status_captacao_info($investment_id);
    }
    
    // Fallback básico
    $status = icf_get_captacao_status($investment_id);
    
    return [
        'status' => $status,
        'label' => $status === 'ativo' ? 'Em Captação' : 'Encerrado',
        'class' => $status === 'ativo' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400',
        'icon' => $status === 'ativo' ? 'fa-chart-line' : 'fa-times-circle',
        'description' => $status === 'ativo' ? 'Investimento disponível' : 'Captação encerrada'
    ];
}

/**
 * Verifica se um aporte pode ser vendido
 */
function icf_pode_vender_aporte( $aporte_id ) {
    $venda_status = get_field('venda_status', $aporte_id);
    if ($venda_status) {
        return [
            'pode_vender' => false,
            'motivo' => 'Aporte já foi vendido'
        ];
    }
    
    $historico_aportes = get_field('historico_aportes', $aporte_id);
    if (empty($historico_aportes)) {
        return [
            'pode_vender' => false,
            'motivo' => 'Histórico de aportes não encontrado'
        ];
    }
    
    $primeiro_aporte = reset($historico_aportes);
    $data_inicio_raw = $primeiro_aporte['data_aporte'] ?? '';
    
    if (empty($data_inicio_raw)) {
        return [
            'pode_vender' => false,
            'motivo' => 'Data de início não encontrada'
        ];
    }
    
    try {
        $data_investimento = DateTime::createFromFormat('d/m/Y', $data_inicio_raw);
        if (!$data_investimento) {
            $data_investimento = new DateTime($data_inicio_raw);
        }

        $investment_id = get_field('investment_id', $aporte_id);
        $prazo_investimento = get_field('prazo_do_investimento', $investment_id);
        $periodo_minimo = $prazo_investimento['prazo_min'] ?? 12;

        $data_liberacao = clone $data_investimento;
        $data_liberacao->modify("+{$periodo_minimo} months");

        $hoje = new DateTime();
        $pode_vender = $hoje >= $data_liberacao;
        
        return [
            'pode_vender' => $pode_vender,
            'data_liberacao' => $data_liberacao->format('d/m/Y'),
            'dias_restantes' => $pode_vender ? 0 : $hoje->diff($data_liberacao)->days,
            'periodo_minimo' => $periodo_minimo
        ];
        
    } catch (Exception $e) {
        return [
            'pode_vender' => false,
            'motivo' => 'Erro ao processar datas: ' . $e->getMessage()
        ];
    }
}

/**
 * NOVA FUNÇÃO: Calcula rentabilidade projetada usando último valor do histórico
 */
function icf_get_rentabilidade_projetada_usuario( $user_id = null ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    
    $aportes_ativos = get_posts([
        'post_type'      => 'aporte',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            ['key' => 'investidor_id', 'value' => $user_id],
            [
                'relation' => 'OR',
                ['key' => 'venda_status', 'compare' => 'NOT EXISTS'],
                ['key' => 'venda_status', 'value' => false, 'compare' => '=']
            ]
        ],
        'fields' => 'ids'
    ]);
    
    $rentabilidade_projetada_total = 0;
    
    foreach ($aportes_ativos as $aporte_id) {
        $historico_rentabilidade = get_field('rentabilidade_historico', $aporte_id);
        if (!empty($historico_rentabilidade) && is_array($historico_rentabilidade)) {
            $ultimo_valor = end($historico_rentabilidade);
            if (isset($ultimo_valor['valor'])) {
                $rentabilidade_projetada_total += (float) $ultimo_valor['valor'];
            }
        }
    }
    
    return $rentabilidade_projetada_total;
}

/**
 * NOVA FUNÇÃO: Calcula total de valor consolidado (vendas realizadas)
 */
function icf_get_rentabilidade_consolidada_usuario( $user_id = null ) {
    if ( ! $user_id ) {
        $user_id = get_current_user_id();
    }
    
    $aportes_vendidos = get_posts([
        'post_type'      => 'aporte',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => [
            ['key' => 'investidor_id', 'value' => $user_id],
            ['key' => 'venda_status', 'value' => true, 'compare' => '=']
        ],
        'fields' => 'ids'
    ]);
    
    $total_investido = 0;
    $total_recebido = 0;
    
    foreach ($aportes_vendidos as $aporte_id) {
        $historico_aportes = get_field('historico_aportes', $aporte_id) ?: [];
        foreach ($historico_aportes as $item) {
            $total_investido += (float) ($item['valor_aporte'] ?? 0);
        }
        
        $venda_valor = (float) get_field('venda_valor', $aporte_id);
        $total_recebido += $venda_valor;
    }
    
    return [
        'total_investido' => $total_investido,
        'total_recebido' => $total_recebido,
        'rentabilidade_consolidada' => ($total_recebido - $total_investido),
        'quantidade_vendas' => count($aportes_vendidos)
    ];
}