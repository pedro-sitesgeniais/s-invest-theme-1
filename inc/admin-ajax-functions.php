<?php
/**
 * Funções AJAX para o Painel Administrativo de Usuários
 * Arquivo: inc/admin-ajax-functions.php
 */

defined('ABSPATH') || exit;

/**
 * AJAX: Aprovar usuário individual
 */
function s_invest_admin_approve_user() {
    check_ajax_referer('approve_user', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissão negada.');
    }
    
    $user_id = absint($_POST['user_id'] ?? 0);
    
    if (!$user_id) {
        wp_send_json_error('ID de usuário inválido.');
    }
    
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        wp_send_json_error('Usuário não encontrado.');
    }
    
    // Aprovar o usuário
    update_user_meta($user_id, 'email_confirmed', true);
    delete_user_meta($user_id, 'email_confirmation_token');
    
    // Log da ação
    error_log("Admin aprovação: Usuário {$user->user_email} (ID: {$user_id}) foi aprovado por " . wp_get_current_user()->user_email);
    
    wp_send_json_success('Usuário aprovado com sucesso!');
}
add_action('wp_ajax_approve_user', 's_invest_admin_approve_user');

/**
 * AJAX: Reenviar e-mail de confirmação
 */
function s_invest_admin_resend_confirmation() {
    check_ajax_referer('resend_confirmation', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissão negada.');
    }
    
    $user_id = absint($_POST['user_id'] ?? 0);
    
    if (!$user_id) {
        wp_send_json_error('ID de usuário inválido.');
    }
    
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        wp_send_json_error('Usuário não encontrado.');
    }
    
    // Reenviar e-mail de confirmação
    if (function_exists('s_invest_send_confirmation_email')) {
        $result = s_invest_send_confirmation_email($user_id);
        
        if ($result) {
            wp_send_json_success('E-mail de confirmação reenviado com sucesso!');
        } else {
            wp_send_json_error('Erro ao enviar e-mail. Verifique as configurações de SMTP.');
        }
    } else {
        wp_send_json_error('Função de envio de e-mail não encontrada.');
    }
}
add_action('wp_ajax_resend_confirmation', 's_invest_admin_resend_confirmation');

/**
 * AJAX: Excluir usuário individual
 */
function s_invest_admin_delete_user() {
    check_ajax_referer('delete_user_admin', 'nonce');
    
    if (!current_user_can('delete_users')) {
        wp_send_json_error('Permissão negada para excluir usuários.');
    }
    
    $user_id = absint($_POST['user_id'] ?? 0);
    
    if (!$user_id) {
        wp_send_json_error('ID de usuário inválido.');
    }
    
    // Não permitir excluir o próprio usuário
    if ($user_id === get_current_user_id()) {
        wp_send_json_error('Não é possível excluir sua própria conta.');
    }
    
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        wp_send_json_error('Usuário não encontrado.');
    }
    
    // Verificar se o usuário tem o role correto
    if (!in_array('investidor', $user->roles) && !in_array('associado', $user->roles)) {
        wp_send_json_error('Só é possível excluir investidores e associados.');
    }
    
    require_once(ABSPATH . 'wp-admin/includes/user.php');
    
    $result = wp_delete_user($user_id);
    
    if ($result) {
        // Log da ação
        error_log("Admin exclusão: Usuário {$user->user_email} (ID: {$user_id}) foi excluído por " . wp_get_current_user()->user_email);
        wp_send_json_success('Usuário excluído com sucesso!');
    } else {
        wp_send_json_error('Erro ao excluir usuário. Tente novamente.');
    }
}
add_action('wp_ajax_delete_user_admin', 's_invest_admin_delete_user');

/**
 * AJAX: Ações em massa
 */
function s_invest_admin_bulk_user_action() {
    check_ajax_referer('bulk_user_action', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissão negada.');
    }
    
    $action = sanitize_text_field($_POST['bulk_action'] ?? '');
    $user_ids = array_map('absint', $_POST['user_ids'] ?? []);
    
    if (empty($action) || empty($user_ids)) {
        wp_send_json_error('Ação ou usuários não especificados.');
    }
    
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    foreach ($user_ids as $user_id) {
        $user = get_user_by('ID', $user_id);
        if (!$user) {
            $error_count++;
            $errors[] = "Usuário ID {$user_id} não encontrado.";
            continue;
        }
        
        switch ($action) {
            case 'approve':
                update_user_meta($user_id, 'email_confirmed', true);
                delete_user_meta($user_id, 'email_confirmation_token');
                $success_count++;
                break;
                
            case 'resend':
                if (function_exists('s_invest_send_confirmation_email')) {
                    if (s_invest_send_confirmation_email($user_id)) {
                        $success_count++;
                    } else {
                        $error_count++;
                        $errors[] = "Erro ao enviar e-mail para {$user->user_email}.";
                    }
                } else {
                    $error_count++;
                    $errors[] = "Função de envio não disponível.";
                }
                break;
                
            case 'delete':
                if (current_user_can('delete_users') && $user_id !== get_current_user_id()) {
                    require_once(ABSPATH . 'wp-admin/includes/user.php');
                    if (wp_delete_user($user_id)) {
                        $success_count++;
                    } else {
                        $error_count++;
                        $errors[] = "Erro ao excluir {$user->user_email}.";
                    }
                } else {
                    $error_count++;
                    $errors[] = "Não é possível excluir {$user->user_email}.";
                }
                break;
                
            default:
                $error_count++;
                $errors[] = "Ação '{$action}' não reconhecida.";
        }
    }
    
    $message = '';
    if ($success_count > 0) {
        $message .= "{$success_count} usuário(s) processado(s) com sucesso. ";
    }
    if ($error_count > 0) {
        $message .= "{$error_count} erro(s): " . implode(', ', array_slice($errors, 0, 3));
        if (count($errors) > 3) {
            $message .= '...';
        }
    }
    
    if ($success_count > 0) {
        wp_send_json_success($message);
    } else {
        wp_send_json_error($message);
    }
}
add_action('wp_ajax_bulk_user_action', 's_invest_admin_bulk_user_action');

/**
 * AJAX: Obter detalhes do usuário
 */
function s_invest_admin_get_user_details() {
    check_ajax_referer('get_user_details', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissão negada.');
    }
    
    $user_id = absint($_POST['user_id'] ?? 0);
    
    if (!$user_id) {
        wp_send_json_error('ID de usuário inválido.');
    }
    
    $user = get_user_by('ID', $user_id);
    if (!$user) {
        wp_send_json_error('Usuário não encontrado.');
    }
    
    // Dados básicos
    $telefone = get_user_meta($user_id, 'telefone', true) ?: get_field('telefone', 'user_' . $user_id);
    $cpf = get_user_meta($user_id, 'cpf', true) ?: get_field('cpf', 'user_' . $user_id);
    $email_confirmed = get_user_meta($user_id, 'email_confirmed', true);
    $last_login = get_user_meta($user_id, 'last_login', true);
    
    // Investimentos
    $investments = [];
    if (post_type_exists('investment')) {
        $investment_posts = get_posts([
            'post_type' => 'investment',
            'meta_query' => [
                ['key' => 'investor_id', 'value' => $user_id, 'compare' => '=']
            ],
            'posts_per_page' => -1
        ]);
        
        foreach ($investment_posts as $inv) {
            $investments[] = [
                'title' => $inv->post_title,
                'date' => $inv->post_date,
                'status' => $inv->post_status,
                'amount' => get_post_meta($inv->ID, 'investment_amount', true),
                'edit_link' => get_edit_post_link($inv->ID)
            ];
        }
    }
    
    // Aportes
    $aportes = [];
    if (post_type_exists('aporte')) {
        $aporte_posts = get_posts([
            'post_type' => 'aporte',
            'meta_query' => [
                ['key' => 'user_id', 'value' => $user_id, 'compare' => '=']
            ],
            'posts_per_page' => -1
        ]);
        
        foreach ($aporte_posts as $aporte) {
            $aportes[] = [
                'title' => $aporte->post_title,
                'date' => $aporte->post_date,
                'amount' => get_post_meta($aporte->ID, 'aporte_amount', true),
                'status' => get_post_meta($aporte->ID, 'aporte_status', true)
            ];
        }
    }
    
    // Montar HTML
    ob_start();
    ?>
    <div class="user-details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="user-basic-info">
            <h3>Informações Básicas</h3>
            <table class="form-table">
                <tr>
                    <th>Nome Completo:</th>
                    <td><?php echo esc_html($user->first_name . ' ' . $user->last_name); ?></td>
                </tr>
                <tr>
                    <th>E-mail:</th>
                    <td>
                        <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                            <?php echo esc_html($user->user_email); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th>Telefone:</th>
                    <td>
                        <?php if ($telefone): ?>
                            <a href="tel:<?php echo esc_attr(preg_replace('/\D/', '', $telefone)); ?>">
                                <?php echo esc_html($telefone); ?>
                            </a>
                        <?php else: ?>
                            <span style="color: #666;">Não informado</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>CPF:</th>
                    <td>
                        <?php if ($cpf): ?>
                            <?php echo esc_html(preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf)); ?>
                        <?php else: ?>
                            <span style="color: #666;">Não informado</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Tipo:</th>
                    <td>
                        <span class="role-badge" style="background: <?php echo in_array('investidor', $user->roles) ? '#3b82f6' : '#10b981'; ?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; text-transform: uppercase; font-weight: bold;">
                            <?php echo esc_html(implode(', ', $user->roles)); ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Status E-mail:</th>
                    <td>
                        <?php if ($email_confirmed): ?>
                            <span style="color: #10b981; font-weight: bold;">✓ Confirmado</span>
                        <?php else: ?>
                            <span style="color: #f59e0b; font-weight: bold;">⏱ Pendente</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Registro:</th>
                    <td><?php echo esc_html(date('d/m/Y H:i', strtotime($user->user_registered))); ?></td>
                </tr>
                <tr>
                    <th>Último Acesso:</th>
                    <td>
                        <?php if ($last_login): ?>
                            <?php echo esc_html(date('d/m/Y H:i', strtotime($last_login))); ?>
                        <?php else: ?>
                            <span style="color: #666;">Nunca</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="user-activity">
            <h3>Atividade</h3>
            
            <?php if (!empty($investments)): ?>
            <h4>Investimentos (<?php echo count($investments); ?>)</h4>
            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
                <?php foreach ($investments as $inv): ?>
                <div style="padding: 8px; border-bottom: 1px solid #eee;">
                    <strong><?php echo esc_html($inv['title']); ?></strong><br>
                    <small>
                        <?php echo esc_html(date('d/m/Y', strtotime($inv['date']))); ?>
                        <?php if ($inv['amount']): ?>
                        - R$ <?php echo number_format($inv['amount'], 2, ',', '.'); ?>
                        <?php endif; ?>
                        <?php if ($inv['edit_link']): ?>
                        - <a href="<?php echo esc_url($inv['edit_link']); ?>" target="_blank">Editar</a>
                        <?php endif; ?>
                    </small>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p style="color: #666;">Nenhum investimento encontrado.</p>
            <?php endif; ?>
            
            <?php if (!empty($aportes)): ?>
            <h4 style="margin-top: 20px;">Aportes (<?php echo count($aportes); ?>)</h4>
            <div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px;">
                <?php foreach ($aportes as $aporte): ?>
                <div style="padding: 8px; border-bottom: 1px solid #eee;">
                    <strong><?php echo esc_html($aporte['title']); ?></strong><br>
                    <small>
                        <?php echo esc_html(date('d/m/Y', strtotime($aporte['date']))); ?>
                        <?php if ($aporte['amount']): ?>
                        - R$ <?php echo number_format($aporte['amount'], 2, ',', '.'); ?>
                        <?php endif; ?>
                        <?php if ($aporte['status']): ?>
                        - Status: <?php echo esc_html($aporte['status']); ?>
                        <?php endif; ?>
                    </small>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; text-align: right;">
        <a href="<?php echo get_edit_user_link($user_id); ?>" class="button button-primary" target="_blank">
            Editar Usuário no WordPress
        </a>
        
        <?php if (!$email_confirmed): ?>
        <button type="button" class="button" onclick="approveUser(<?php echo $user_id; ?>); closeUserModal();">
            Aprovar Usuário
        </button>
        <button type="button" class="button" onclick="resendConfirmation(<?php echo $user_id; ?>);">
            Reenviar Confirmação
        </button>
        <?php endif; ?>
    </div>
    <?php
    
    $html = ob_get_clean();
    wp_send_json_success($html);
}
add_action('wp_ajax_get_user_details', 's_invest_admin_get_user_details');

/**
 * AJAX: Exportar usuários para CSV
 */
function s_invest_admin_export_users() {
    if (!wp_verify_nonce($_GET['nonce'] ?? '', 'export_users')) {
        wp_die('Nonce inválido.');
    }
    
    if (!current_user_can('manage_options')) {
        wp_die('Permissão negada.');
    }
    
    // Parâmetros de busca (mesmos do filtro)
    $search_term = sanitize_text_field($_GET['search'] ?? '');
    $user_role = sanitize_text_field($_GET['role'] ?? '');
    $status_filter = sanitize_text_field($_GET['status'] ?? '');
    
    // Configurar argumentos de busca
    $user_args = [
        'number' => -1, // Todos os usuários
        'orderby' => 'registered',
        'order' => 'DESC'
    ];
    
    // Aplicar filtros
    if ($user_role && in_array($user_role, ['investidor', 'associado'])) {
        $user_args['role'] = $user_role;
    } else {
        $user_args['role__in'] = ['investidor', 'associado'];
    }
    
    if ($search_term) {
        $user_args['search'] = '*' . $search_term . '*';
        $user_args['search_columns'] = ['user_login', 'user_email', 'display_name'];
    }
    
    // Filtro por status de confirmação
    if ($status_filter === 'confirmed') {
        $user_args['meta_query'] = [['key' => 'email_confirmed', 'value' => true, 'compare' => '=']];
    } elseif ($status_filter === 'pending') {
        $user_args['meta_query'] = [['key' => 'email_confirmed', 'value' => false, 'compare' => '=']];
    }
    
    // Buscar usuários
    $users = get_users($user_args);
    
    // Configurar headers para download
    $filename = 'usuarios_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    
    // Abrir output
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fputs($output, "\xEF\xBB\xBF");
    
    // Cabeçalhos do CSV
    fputcsv($output, [
        'ID',
        'Nome',
        'E-mail',
        'Telefone',
        'CPF',
        'Tipo',
        'Status E-mail',
        'Data de Registro',
        'Último Acesso'
    ], ';');
    
    // Dados dos usuários
    foreach ($users as $user) {
        $telefone = get_user_meta($user->ID, 'telefone', true) ?: get_field('telefone', 'user_' . $user->ID);
        $cpf = get_user_meta($user->ID, 'cpf', true) ?: get_field('cpf', 'user_' . $user->ID);
        $email_confirmed = get_user_meta($user->ID, 'email_confirmed', true);
        $last_login = get_user_meta($user->ID, 'last_login', true);
        
        fputcsv($output, [
            $user->ID,
            $user->first_name . ' ' . $user->last_name,
            $user->user_email,
            $telefone ?: 'Não informado',
            $cpf ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf) : 'Não informado',
            implode(', ', $user->roles),
            $email_confirmed ? 'Confirmado' : 'Pendente',
            date('d/m/Y H:i', strtotime($user->user_registered)),
            $last_login ? date('d/m/Y H:i', strtotime($last_login)) : 'Nunca'
        ], ';');
    }
    
    fclose($output);
    exit;
}
add_action('wp_ajax_export_users', 's_invest_admin_export_users');

/**
 * Rastrear último login do usuário
 */
function s_invest_track_last_login($user_login, $user) {
    update_user_meta($user->ID, 'last_login', current_time('mysql'));
}
add_action('wp_login', 's_invest_track_last_login', 10, 2);