<?php
/**
 * Processamento de Autenticação - CORRIGIDO PARA LOOP
 * Arquivo: inc/auth-process.php
 * 
 * CORREÇÃO: Problemas de redirecionamento e loop no login
 */

defined('ABSPATH') || exit;

// Função para redirecionar com parâmetros de erro/sucesso
if (!function_exists('s_invest_redirect_with_message')) {
    function s_invest_redirect_with_message($type, $message, $extra_params = []) {
        $params = array_merge([$type => urlencode($message)], $extra_params);
        $redirect_url = wp_get_referer() ?: home_url('/acessar/');
        wp_redirect(add_query_arg($params, $redirect_url));
        exit;
    }
}

// Validação de senha forte
if (!function_exists('s_invest_validate_password')) {
    function s_invest_validate_password($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'A senha deve conter ao menos uma letra maiúscula.';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'A senha deve conter ao menos uma letra minúscula.';
        }
        
        if (!preg_match('/\d/', $password)) {
            $errors[] = 'A senha deve conter ao menos um número.';
        }
        
        return $errors;
    }
}

// Verificar se é uma requisição POST válida
if (!isset($_POST['sky_auth_action']) || empty($_POST['sky_auth_action'])) {
    return; // Não processar se não for uma ação de auth
}

// PROCESSAMENTO DO LOGIN
if ($_POST['sky_auth_action'] === 'login') {
    // Verificar nonce (se disponível)
    if (function_exists('wp_verify_nonce') && isset($_POST['_wpnonce'])) {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'login_nonce')) {
            s_invest_redirect_with_message('erro', 'Sessão expirada. Tente novamente.');
        }
    }
    
    $username = sanitize_user($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validações básicas
    if (empty($username) || empty($password)) {
        s_invest_redirect_with_message('erro', 'Usuário e senha são obrigatórios.');
    }
    
    // Remove qualquer sessão anterior
    wp_destroy_current_session();
    wp_clear_auth_cookie();
    
    // Tentativa de login
    $creds = [
        'user_login' => $username,
        'user_password' => $password,
        'remember' => $remember
    ];
    
    $user = wp_signon($creds, false);
    
    if (is_wp_error($user)) {
        $error_msg = 'Credenciais inválidas. Verifique seus dados.';
        
        // Mensagens específicas para alguns erros
        $error_code = $user->get_error_code();
        if ($error_code === 'incorrect_password') {
            $error_msg = 'Senha incorreta.';
        } elseif ($error_code === 'invalid_username') {
            $error_msg = 'Usuário não encontrado.';
        } elseif ($error_code === 'empty_username') {
            $error_msg = 'Digite seu usuário ou e-mail.';
        } elseif ($error_code === 'empty_password') {
            $error_msg = 'Digite sua senha.';
        }
        
        s_invest_redirect_with_message('erro', $error_msg);
    }
    
    // Verifica se e-mail foi confirmado (apenas se função existe)
    if (function_exists('get_user_meta')) {
        $email_confirmed = get_user_meta($user->ID, 'email_confirmed', true);
        if ($email_confirmed === false || $email_confirmed === '0') {
            wp_logout();
            s_invest_redirect_with_message('erro', 'Confirme seu e-mail antes de fazer login.', [
                'show_resend' => $user->ID,
                'email' => urlencode($user->user_email)
            ]);
        }
    }
    
    // Login bem-sucedido - redireciona para painel
    wp_safe_redirect(home_url('/painel/'));
    exit;
}

// PROCESSAMENTO DO CADASTRO
if ($_POST['sky_auth_action'] === 'register') {
    // Verifica se cadastro está habilitado
    if (get_option('s_invest_user_registration', 'enabled') !== 'enabled') {
        s_invest_redirect_with_message('erro', 'Cadastros estão temporariamente desabilitados.');
    }
    
    // Sanitização dos dados
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirmation'] ?? '';
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $telefone = sanitize_text_field($_POST['telefone'] ?? '');
    $terms = isset($_POST['terms']) && $_POST['terms'];
    
    // Validações obrigatórias
    $errors = [];
    
    if (empty($first_name)) $errors[] = 'Nome é obrigatório.';
    if (empty($last_name)) $errors[] = 'Sobrenome é obrigatório.';
    if (empty($email) || !is_email($email)) $errors[] = 'E-mail válido é obrigatório.';
    if (empty($password)) $errors[] = 'Senha é obrigatória.';
    if ($password !== $password_confirm) $errors[] = 'As senhas não coincidem.';
    if (empty($cpf)) $errors[] = 'CPF é obrigatório.';
    if (empty($telefone)) $errors[] = 'Telefone é obrigatório.';
    if (!$terms) $errors[] = 'Você deve aceitar os termos de uso.';
    
    // Validações específicas
    if (!empty($email) && email_exists($email)) {
        $errors[] = 'Este e-mail já está cadastrado.';
    }
    
    if (!empty($password)) {
        $password_errors = s_invest_validate_password($password);
        $errors = array_merge($errors, $password_errors);
    }
    
    if (!empty($cpf)) {
        if (!s_invest_validate_cpf($cpf)) {
            $errors[] = 'CPF inválido.';
        } elseif (s_invest_cpf_exists($cpf)) {
            $errors[] = 'Este CPF já está cadastrado no sistema.';
        }
    }
    
    // Verifica se há erros
    if (!empty($errors)) {
        s_invest_redirect_with_message('erro', implode(' ', $errors));
    }
    
    // Gera username único
    $base_username = strtolower($first_name . '_' . $last_name);
    $username = $base_username;
    $counter = 1;
    
    while (username_exists($username)) {
        $username = $base_username . '_' . $counter;
        $counter++;
        
        // Previne loop infinito
        if ($counter > 999) {
            $username = $base_username . '_' . wp_rand(1000, 9999);
            break;
        }
    }
    
    // Criação do usuário
    $user_id = wp_create_user($username, $password, $email);
    
    if (is_wp_error($user_id)) {
        s_invest_redirect_with_message('erro', 'Erro ao criar conta: ' . $user_id->get_error_message());
    }
    
    // Atualiza dados do usuário
    $update_result = wp_update_user([
        'ID' => $user_id,
        'role' => 'investidor',
        'first_name' => $first_name,
        'last_name' => $last_name,
        'display_name' => $first_name . ' ' . $last_name
    ]);
    
    if (is_wp_error($update_result)) {
        // Remove o usuário se não conseguir atualizar
        wp_delete_user($user_id);
        s_invest_redirect_with_message('erro', 'Erro ao configurar conta.');
    }
    
    // Salva campos customizados
    update_user_meta($user_id, 'cpf', $cpf);
    update_user_meta($user_id, 'telefone', $telefone);
    update_user_meta($user_id, 'email_confirmed', false);
    update_user_meta($user_id, 'registration_date', current_time('mysql'));
    
    // Se ACF estiver ativo, usa os campos do ACF
    if (function_exists('update_field')) {
        update_field('cpf', $cpf, 'user_' . $user_id);
        update_field('telefone', $telefone, 'user_' . $user_id);
    }
    
    // Envia e-mail de confirmação
    if (function_exists('s_invest_send_confirmation_email')) {
        $email_sent = s_invest_send_confirmation_email($user_id);
        
        if (!$email_sent) {
            // Não falha o cadastro, apenas registra que houve problema
        }
    }
    
    // Redireciona para tela de confirmação
    wp_safe_redirect(add_query_arg([
        'view' => 'confirm',
        'email' => urlencode($email),
        'success' => 'registered'
    ], home_url('/acessar/')));
    exit;
}

// REENVIO DE E-MAIL DE CONFIRMAÇÃO
if ($_POST['sky_auth_action'] === 'resend_confirmation') {
    $user_id = absint($_POST['user_id'] ?? 0);
    $email = sanitize_email($_POST['email'] ?? '');
    
    if (!$user_id || !$email) {
        s_invest_redirect_with_message('erro', 'Dados inválidos para reenvio.');
    }
    
    $user = get_user_by('ID', $user_id);
    if (!$user || $user->user_email !== $email) {
        s_invest_redirect_with_message('erro', 'Usuário não encontrado.');
    }
    
    // Verifica se já não está confirmado
    if (get_user_meta($user_id, 'email_confirmed', true)) {
        s_invest_redirect_with_message('sucesso', 'E-mail já confirmado. Você pode fazer login.');
    }
    
    // Reenvia e-mail
    if (function_exists('s_invest_send_confirmation_email')) {
        $email_sent = s_invest_send_confirmation_email($user_id);
        
        if ($email_sent) {
            s_invest_redirect_with_message('sucesso', 'E-mail de confirmação reenviado com sucesso!');
        } else {
            s_invest_redirect_with_message('erro', 'Erro ao reenviar e-mail. Tente novamente em alguns minutos.');
        }
    } else {
        s_invest_redirect_with_message('erro', 'Funcionalidade de e-mail não disponível.');
    }
}

// Se chegou até aqui e não processou nenhuma ação, não fazer nada
// (não redirecionar para evitar loops)