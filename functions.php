<?php
/**
 * S-Invest Theme - Functions
 * Versão: 2.3.1
 */

if (!defined('WP_CLI')) ob_start();
defined('ABSPATH') || exit;

define('S_INVEST_VERSION', '2.3.1');
define('S_INVEST_THEME_DIR', get_template_directory());
define('S_INVEST_THEME_URL', get_template_directory_uri());

/**
 * NOVA FUNÇÃO: Calcula automaticamente o status da captação
 */
function s_invest_calcular_status_captacao($investment_id) {
    // Verificar se há status manual definido
    $status_manual = get_field('status_captacao_manual', $investment_id);
    
    // Se não há campo ou está em automático, calcular automaticamente
    if (empty($status_manual) || $status_manual === 'automatico') {
        
        // Dados do investimento
        $valor_total = floatval(get_field('valor_total', $investment_id) ?: 0);
        $total_captado = floatval(get_field('total_captado', $investment_id) ?: 0);
        $fim_captacao = get_field('fim_captacao', $investment_id);
        
        // Verificar se atingiu 100% da meta
        if ($valor_total > 0) {
            $porcentagem = ($total_captado / $valor_total) * 100;
            if ($porcentagem >= 100) {
                return 'encerrado_meta';
            }
        }
        
        // Verificar se passou da data final de captação
        if ($fim_captacao) {
            $data_fim = DateTime::createFromFormat('Y-m-d', $fim_captacao);
            if (!$data_fim) {
                $data_fim = DateTime::createFromFormat('d/m/Y', $fim_captacao);
            }
            
            if ($data_fim && $data_fim < new DateTime()) {
                return 'encerrado_data';
            }
        }
        
        // Se chegou até aqui, está ativo
        return 'ativo';
    }
    
    // Retornar o status manual definido
    return $status_manual;
}

/**
 * NOVA FUNÇÃO: Retorna informações detalhadas do status da captação
 */
function s_invest_get_status_captacao_info($investment_id) {
    $status = s_invest_calcular_status_captacao($investment_id);
    
    $info = [
        'status' => $status,
        'label' => '',
        'class' => '',
        'icon' => '',
        'description' => ''
    ];
    
    switch ($status) {
        case 'ativo':
            $info['label'] = 'Em Captação';
            $info['class'] = 'bg-green-500/20 text-green-400 border-green-500/30';
            $info['icon'] = 'fa-chart-line';
            $info['description'] = 'Investimento disponível para aportes';
            break;
            
        case 'encerrado_meta':
        case 'encerrado_data':
        case 'encerrado_manual':
        case 'encerrado':
            // SIMPLIFICADO: Todos os tipos de encerramento mostram apenas "Encerrado"
            $info['label'] = 'Encerrado';
            $info['class'] = 'bg-gray-500/20 text-gray-400 border-gray-500/30';
            $info['icon'] = 'fa-times-circle';
            $info['description'] = 'Captação finalizada';
            break;
            
        default:
            $info['label'] = 'Status Indefinido';
            $info['class'] = 'bg-gray-500/20 text-gray-400 border-gray-500/30';
            $info['icon'] = 'fa-question-circle';
            $info['description'] = 'Status não definido';
            break;
    }
    
    return $info;
}

/**
 * NOVA FUNÇÃO: Verifica se o investimento está disponível para investir
 */
function s_invest_investimento_disponivel($investment_id) {
    $status = s_invest_calcular_status_captacao($investment_id);
    return $status === 'ativo';
}

/**
 * CONFIGURAÇÃO BÁSICA DO TEMA
 */
function s_invest_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    
    add_theme_support('elementor');
    add_theme_support('elementor-pro');
    add_theme_support('elementor-theme-builder');
    remove_theme_support('widgets-block-editor');
    
    add_theme_support('custom-logo', [
        'height' => 60, 'width' => 200, 'flex-height' => true, 'flex-width' => true
    ]);
    
    register_nav_menus([
        'menu-1' => __('Menu Principal', 's-invest-theme'),
        'menu-footer' => __('Menu do Rodapé', 's-invest-theme')
    ]);
}
add_action('after_setup_theme', 's_invest_theme_setup');

/**
 * OTIMIZAÇÕES DE PERFORMANCE
 */
function s_invest_remove_bloat() {
    if (is_admin()) return;
    
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    
    if (!WP_DEBUG) {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
    }
}
add_action('init', 's_invest_remove_bloat');

function s_invest_remove_jquery_migrate($scripts) {
    if (!is_admin() && isset($scripts->registered['jquery'])) {
        $script = $scripts->registered['jquery'];
        if ($script->deps) {
            $script->deps = array_diff($script->deps, ['jquery-migrate']);
        }
    }
}
add_action('wp_default_scripts', 's_invest_remove_jquery_migrate');

/**
 * COMPATIBILIDADE ELEMENTOR
 */
function s_invest_elementor_compatibility() {
    if (is_admin() || (isset($_GET['elementor-preview']) && $_GET['elementor-preview'])) {
        remove_action('wp_head', 's_invest_preload_assets');
    }
}
add_action('init', 's_invest_elementor_compatibility');

function s_invest_elementor_body_class($classes) {
    if (class_exists('\Elementor\Plugin')) {
        $post_id = get_the_ID();
        if ($post_id && \Elementor\Plugin::$instance->documents->get($post_id)->is_built_with_elementor()) {
            $classes[] = 'elementor-page';
        }
    }
    return $classes;
}
add_filter('body_class', 's_invest_elementor_body_class');

function s_invest_elementor_css() {
    if (!defined('ELEMENTOR_VERSION') && !is_admin() && !isset($_GET['elementor-preview'])) return;
    
    echo '<style id="elementor-critical-css">
    .elementor-page, body.elementor-page { margin: 0 !important; padding: 0 !important; border: none !important; box-sizing: border-box !important; }
    .elementor-page #page, .elementor-page .site-main, .elementor-page #primary, .elementor-page #content, .elementor-page .site-content, .elementor-page .content-area { margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: none !important; }
    .elementor-section-wrap { width: 100% !important; margin: 0 !important; padding: 0 !important; }
    .elementor-container { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
    [x-cloak] { display: block !important; }
    .elementor-editor-active [x-cloak] { display: block !important; }
    </style>';
}
add_action('wp_head', 's_invest_elementor_css', 1);

/**
 * GERENCIAMENTO DE ASSETS OTIMIZADO
 */
function s_invest_enqueue_assets() {
    $css_file = S_INVEST_THEME_DIR . '/public/css/app.css';
    $js_file = S_INVEST_THEME_DIR . '/public/js/app.js';
    
    $css_version = file_exists($css_file) ? filemtime($css_file) : S_INVEST_VERSION;
    $js_version = file_exists($js_file) ? filemtime($js_file) : S_INVEST_VERSION;
    
    if (file_exists($css_file)) {
        wp_enqueue_style('s-invest-main', S_INVEST_THEME_URL . '/public/css/app.css', [], $css_version);
    }
    
    $script_handle = 's-invest-app';
    if (file_exists($js_file)) {
        wp_enqueue_script($script_handle, S_INVEST_THEME_URL . '/public/js/app.js', [], $js_version, true);
    } else {
        wp_enqueue_script($script_handle, 'https://unpkg.com/alpinejs@3.14.9/dist/cdn.min.js', [], '3.14.9', true);
        s_invest_admin_notice_build_missing();
    }
    
    wp_add_inline_script($script_handle, 
        'window.investments_ajax=' . json_encode([
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('filtrar_investimentos_nonce')
        ]) . ';', 'before'
    );
    
    s_invest_page_specific_scripts($script_handle, $js_version);
}
add_action('wp_enqueue_scripts', 's_invest_enqueue_assets');

function s_invest_page_specific_scripts($handle, $version) {
    if (is_page_template('page-autenticacao.php')) {
        wp_enqueue_script('s-invest-imask', 'https://unpkg.com/imask@7.6.1/dist/imask.min.js', [], '7.6.1', true);
        
        $auth_script = S_INVEST_THEME_DIR . '/resources/js/components/auth.js';
        if (file_exists($auth_script)) {
            wp_enqueue_script('s-invest-auth', S_INVEST_THEME_URL . '/resources/js/components/auth.js', [$handle], filemtime($auth_script), true);
        }
    }
    
    if (is_page_template('page-painel.php')) {
        $secao = $_GET['secao'] ?? 'dashboard';
        if (in_array($secao, ['dashboard', 'meus-investimentos', 'detalhes-investimento'])) {
            wp_enqueue_script('s-invest-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js', [], '4.4.9', true);
        }
        
        wp_add_inline_script($handle, 
            'window.profile_ajax=' . json_encode([
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('profile_nonce')
            ]) . ';', 'before'
        );
    }
    
    if (is_singular('investment') || is_post_type_archive('investment')) {
        wp_enqueue_script('s-invest-chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js', [], '4.4.9', true);
        
        if (is_singular('investment')) {
            $invest_script = S_INVEST_THEME_DIR . '/public/js/invest-cenarios.js';
            if (file_exists($invest_script)) {
                wp_enqueue_script('invest-cenarios', S_INVEST_THEME_URL . '/public/js/invest-cenarios.js', ['s-invest-chartjs'], filemtime($invest_script), true);
            }
        }
    }
}

/**
 * RESOURCE HINTS OTIMIZADOS
 */
function s_invest_resource_hints() {
    echo '<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>';
    
    if (is_page_template('page-autenticacao.php')) {
        echo '<link rel="preconnect" href="https://unpkg.com" crossorigin>';
    }
    
    if (is_page_template('page-painel.php') || is_singular('investment')) {
        echo '<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>';
    }
    
    echo '<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel=\'stylesheet\'">';
    echo '<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>';
}
add_action('wp_head', 's_invest_resource_hints', 1);

/**
 * ALPINE.JS INITIALIZATION
 */
function s_invest_alpine_init() {
    if (is_admin() || (isset($_GET['elementor-preview']) && $_GET['elementor-preview'])) return;
    
    echo '<style>[x-cloak]{display:none!important;}</style>';
    echo '<script>
    if (typeof window.alpinePreventMultiple === "undefined") {
        window.alpinePreventMultiple = true;
    }
    </script>';
}
add_action('wp_head', 's_invest_alpine_init', 2);

/**
 * FONT AWESOME COM FALLBACK
 */
function s_invest_font_awesome_fallback() {
    echo '<style id="icon-fallback-css">
    .fas, .far, .fab, .fal, .fad { font-family: "Font Awesome 6 Free", "Font Awesome 6 Pro", "Font Awesome 5 Free", "Font Awesome 5 Pro", sans-serif !important; font-weight: 900; font-style: normal; font-variant: normal; text-rendering: auto; line-height: 1; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    .far { font-weight: 400; }
    .fab { font-weight: 400; font-family: "Font Awesome 6 Brands", "Font Awesome 5 Brands" !important; }
    i[class*="fa-"] { display: inline-block; color: inherit; opacity: 1; visibility: visible; }
    .elementor-page i[class*="fa-"] { color: inherit !important; opacity: 1 !important; }
    </style>';
}
add_action('wp_head', 's_invest_font_awesome_fallback', 999);

/**
 * PROTEÇÃO CONTRA ERROS
 */
function s_invest_error_protection() {
    if (is_admin()) return;
    
    add_action('wp_footer', function() {
        echo '<script>
        window.addEventListener("error", function(e) {
            const externalScripts = ["maps.googleapis.com", "google.com/maps", "gstatic.com", "googletagmanager.com"];
            const isExternalScript = externalScripts.some(script => e.filename && e.filename.includes(script));
            
            if (isExternalScript) {
                e.preventDefault();
                return false;
            }
        });
        </script>';
    }, 1);
}
add_action('init', 's_invest_error_protection');

/**
 * DESABILITAR ELEMENTOR NO PAINEL
 */
function s_invest_disable_elementor_on_panel() {
    if (!is_page_template('page-painel.php')) return;
    
    add_action('wp_enqueue_scripts', function() {
        wp_dequeue_script('elementor-frontend');
        wp_dequeue_style('elementor-frontend');
        wp_dequeue_style('elementor-post-*');
        wp_dequeue_script('elementor-waypoints');
        wp_dequeue_script('swiper');
        wp_dequeue_script('share-link');
        wp_add_inline_script('jquery', 'window.elementorFrontendConfig = {};', 'before');
    }, 99);
    
    add_filter('elementor/frontend/print_google_fonts', '__return_false');
    add_filter('elementor/theme/need_override_location', '__return_false');
}
add_action('wp', 's_invest_disable_elementor_on_panel');

/**
 * SEGURANÇA E HEADERS
 */
add_filter('wp_headers', function($headers) {
    if (!is_admin()) {
        $headers['X-Content-Type-Options'] = 'nosniff';
        $headers['X-Frame-Options'] = 'SAMEORIGIN';
        $headers['X-XSS-Protection'] = '1; mode=block';
        
        // Não adicionar headers que podem interferir com cookies
        // em páginas de autenticação
        if (!is_page('acessar') && !strpos($_SERVER['REQUEST_URI'], '/acessar')) {
            $headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
        }
    }
    return $headers;
});

/**
 * CONTROLE DE ACESSO
 */
function s_invest_access_control() {
    if (current_user_can('investidor') || current_user_can('associado')) {
        show_admin_bar(false);
    }
}
add_action('after_setup_theme', 's_invest_access_control');

function s_invest_block_wp_admin() {
    // Não bloquear durante AJAX ou processos críticos
    if (defined('DOING_AJAX') || defined('DOING_CRON') || defined('XMLRPC_REQUEST')) {
        return;
    }
    
    // Não bloquear se não está no admin
    if (!is_admin()) {
        return;
    }
    
    // Verificar se o usuário tem permissões necessárias
    if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
        // Redirecionar apenas se não for uma página crítica
        $current_page = $_GET['page'] ?? '';
        $critical_pages = ['admin-ajax.php', 'async-upload.php'];
        
        if (!in_array(basename($_SERVER['SCRIPT_NAME']), $critical_pages)) {
            wp_safe_redirect(home_url('/painel/'));
            exit;
        }
    }
}
add_action('admin_init', 's_invest_block_wp_admin');
/**
 * Configurações de sessão mais robustas
 */
function s_invest_session_security() {
    // Apenas no frontend
    if (is_admin()) {
        return;
    }
    
    // Configurar cookies de sessão mais seguros
    if (!headers_sent()) {
        // Configurar parâmetros de cookie seguros
        $secure = is_ssl();
        $httponly = true;
        $samesite = 'Lax'; // Permite redirecionamentos entre páginas do mesmo site
        
        // Aplicar configurações se ainda não foram definidas
        if (function_exists('ini_set')) {
            ini_set('session.cookie_secure', $secure ? '1' : '0');
            ini_set('session.cookie_httponly', $httponly ? '1' : '0');
            ini_set('session.cookie_samesite', $samesite);
        }
    }
}
add_action('init', 's_invest_session_security', 1);
/**
 * Limpeza de cache em operações críticas
 */
function s_invest_clear_user_cache_on_login($user_login, $user) {
    if ($user && $user->ID) {
        // Limpar todos os caches relacionados ao usuário
        wp_cache_delete("dashboard_stats_{$user->ID}", 'user_stats');
        delete_transient("investor_dashboard_{$user->ID}");
        
        // Limpar cache de cartões de investimento
        $cache_contexts = ['public', 'panel', 'my-investments'];
        foreach ($cache_contexts as $context) {
            wp_cache_delete("investment_card_data_{$user->ID}_{$context}", 'investment_cards');
        }
    }
}
add_action('wp_login', 's_invest_clear_user_cache_on_login', 10, 2);
/**
 * REDIRECIONAMENTOS
 */
function s_invest_redirects() {
    // Não redirecionar em contextos específicos
    if (is_admin() || defined('DOING_AJAX') || defined('DOING_CRON') || defined('XMLRPC_REQUEST')) {
        return;
    }
    
    // Não redirecionar durante POST (formulários de login)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return;
    }
    
    // Não redirecionar se há parâmetros de autenticação
    if (isset($_GET['action']) && in_array($_GET['action'], ['lostpassword', 'resetpass', 'logout'])) {
        return;
    }
    
    // Não redirecionar se há tokens/nonces de autenticação
    if (isset($_GET['key']) || isset($_GET['login']) || isset($_GET['_wpnonce'])) {
        return;
    }
    
    $current_url = $_SERVER['REQUEST_URI'] ?? '';
    
    // Aguardar um pouco para garantir que a sessão foi processada
    if (headers_sent()) {
        return;
    }
    
    // Usuário logado tentando acessar página de login
    if (is_user_logged_in() && (is_page('acessar') || strpos($current_url, '/acessar') !== false)) {
        // Verificar se realmente está logado com uma segunda verificação
        $user = wp_get_current_user();
        if ($user && $user->ID > 0) {
            wp_safe_redirect(home_url('/painel/'));
            exit;
        }
    }
    
    // Usuário não logado tentando acessar painel
    if (!is_user_logged_in() && (is_page('painel') || strpos($current_url, '/painel') !== false)) {
        // Adicionar parâmetro para identificar redirecionamento
        $redirect_url = add_query_arg('redirect_reason', 'login_required', home_url('/acessar/'));
        wp_safe_redirect($redirect_url);
        exit;
    }
}
// MUDANÇA: Prioridade mais baixa para evitar conflitos
add_action('template_redirect', 's_invest_redirects', 10);

add_filter('registration_redirect', function() { 
    return home_url('/painel/'); 
});

function s_invest_logout_redirect() {
    $user_id = get_current_user_id();
    
    // Limpar cache específico do usuário
    if ($user_id) {
        wp_cache_delete("dashboard_stats_{$user_id}", 'user_stats');
        delete_transient("investor_dashboard_{$user_id}");
    }
    
    // Logout mais seguro
    wp_destroy_current_session();
    wp_clear_auth_cookie();
    
    wp_safe_redirect(home_url('/acessar/?logged_out=1'));
    exit;
}
add_action('wp_logout', 's_invest_logout_redirect');

/**
 * SISTEMA DE CONFIRMAÇÃO DE E-MAIL
 */
function s_invest_send_confirmation_email($user_id) {
    $user = get_user_by('ID', $user_id);
    if (!$user) return false;
    
    $token = wp_generate_password(32, false);
    update_user_meta($user_id, 'email_confirmation_token', $token);
    update_user_meta($user_id, 'email_confirmed', false);
    
    $confirm_url = add_query_arg([
        'action' => 'confirm_email',
        'user_id' => $user_id,
        'token' => $token
    ], home_url('/acessar/'));
    
    $subject = 'Confirme seu e-mail - ' . get_bloginfo('name');
    $message = "
    <h2>Bem-vindo ao " . get_bloginfo('name') . "!</h2>
    <p>Olá {$user->first_name},</p>
    <p>Para ativar sua conta, clique no link abaixo:</p>
    <p><a href='{$confirm_url}' style='background: #3B82F6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;'>Confirmar E-mail</a></p>
    <p>Ou copie e cole este link no seu navegador:<br>{$confirm_url}</p>
    <p>Este link expira em 24 horas.</p>
    ";
    
    return wp_mail($user->user_email, $subject, $message, ['Content-Type: text/html; charset=UTF-8']);
}

function s_invest_process_email_confirmation() {
    if (!isset($_GET['action']) || $_GET['action'] !== 'confirm_email') return;
    
    $user_id = absint($_GET['user_id'] ?? 0);
    $token = sanitize_text_field($_GET['token'] ?? '');
    
    if ($user_id && $token) {
        $stored_token = get_user_meta($user_id, 'email_confirmation_token', true);
        
        if ($stored_token && $stored_token === $token) {
            update_user_meta($user_id, 'email_confirmed', true);
            delete_user_meta($user_id, 'email_confirmation_token');
            
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            wp_safe_redirect(add_query_arg('confirmed', '1', home_url('/painel/')));
            exit;
        }
    }
    
    wp_safe_redirect(add_query_arg('error', 'invalid_token', home_url('/acessar/')));
    exit;
}
add_action('init', 's_invest_process_email_confirmation');

/**
 * NOVA FUNÇÃO: Verifica se CPF já existe no sistema
 */
function s_invest_cpf_exists($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    $users_meta = get_users([
        'meta_query' => [
            [
                'key' => 'cpf',
                'value' => $cpf,
                'compare' => '='
            ]
        ],
        'fields' => 'ID'
    ]);
    
    if (!empty($users_meta)) {
        return true;
    }
    
    if (function_exists('get_field')) {
        global $wpdb;
        
        $acf_results = $wpdb->get_results($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} 
             WHERE meta_key LIKE %s 
             AND meta_value = %s",
            '%cpf%',
            $cpf
        ));
        
        if (!empty($acf_results)) {
            return true;
        }
    }
    
    return false;
}

/**
 * VALIDAÇÃO DE CPF
 */
function s_invest_validate_cpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) !== 11) return false;
    if (preg_match('/(\d)\1{10}/', $cpf)) return false;
    
    for ($i = 9; $i < 11; $i++) {
        $sum = 0;
        for ($j = 0; $j < $i; $j++) {
            $sum += intval($cpf[$j]) * (($i + 1) - $j);
        }
        $remainder = $sum % 11;
        $digit = $remainder < 2 ? 0 : 11 - $remainder;
        if (intval($cpf[$i]) !== $digit) return false;
    }
    
    return true;
}

/**
 * AJAX HANDLERS
 */
function s_invest_reset_password() {
    check_ajax_referer('auth_nonce', 'nonce');
    
    $email = sanitize_email($_POST['email'] ?? '');
    if (!is_email($email)) {
        wp_send_json_error('E-mail inválido.');
    }
    
    $user = get_user_by('email', $email);
    if (!$user) {
        wp_send_json_error('E-mail não encontrado.');
    }
    
    $reset_key = get_password_reset_key($user);
    if (is_wp_error($reset_key)) {
        wp_send_json_error('Erro ao gerar chave de reset.');
    }
    
    $reset_url = network_site_url("wp-login.php?action=rp&key=$reset_key&login=" . rawurlencode($user->user_login), 'login');
    
    $subject = 'Redefinir senha - ' . get_bloginfo('name');
    $message = sprintf(
        'Olá %s,

Recebemos uma solicitação para redefinir a senha da sua conta.

Clique no link abaixo para criar uma nova senha:
%s

Se você não solicitou esta alteração, ignore este e-mail.

Este link expira em 24 horas.

Atenciosamente,
Equipe %s',
        $user->first_name ?: $user->display_name,
        $reset_url,
        get_bloginfo('name')
    );
    
    if (wp_mail($user->user_email, $subject, $message)) {
        wp_send_json_success('E-mail de recuperação enviado com sucesso!');
    } else {
        wp_send_json_error('Erro ao enviar e-mail. Tente novamente.');
    }
}
add_action('wp_ajax_nopriv_reset_password', 's_invest_reset_password');

function s_invest_update_profile() {
    check_ajax_referer('update_profile_nonce');
    
    $user_id = get_current_user_id();
    if (!$user_id) wp_send_json_error('Usuário não autenticado.');
    
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name = sanitize_text_field($_POST['last_name'] ?? '');
    $telefone = sanitize_text_field($_POST['telefone'] ?? '');
    $cpf = sanitize_text_field($_POST['cpf'] ?? '');
    
    if (empty($first_name)) wp_send_json_error('O primeiro nome é obrigatório.');
    
    $result = wp_update_user([
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name
    ]);
    
    if (is_wp_error($result)) {
        wp_send_json_error($result->get_error_message());
    }
    
    update_field('telefone', $telefone, 'user_' . $user_id);
    update_field('cpf', $cpf, 'user_' . $user_id);
    
    wp_send_json_success([
        'message' => 'Perfil atualizado com sucesso!',
        'data' => compact('first_name', 'last_name', 'telefone', 'cpf')
    ]);
}
add_action('wp_ajax_update_user_profile', 's_invest_update_profile');

function s_invest_verify_email() {
    $nonce_valid = false;
    if (wp_verify_nonce($_REQUEST['nonce'] ?? '', 'verificar_email')) {
        $nonce_valid = true;
    } elseif (wp_verify_nonce($_REQUEST['nonce'] ?? '', 'verificar_cpf')) {
        $nonce_valid = true;
    }
    
    if (!$nonce_valid) {
        wp_send_json_error(['message' => 'Nonce inválido.'], 403);
    }
    
    $email = sanitize_email($_REQUEST['email'] ?? '');
    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Formato de email inválido.'], 400);
    }
    
    wp_send_json_success(['exists' => email_exists($email) ? true : false]);
}
add_action('wp_ajax_verificar_email_existente', 's_invest_verify_email');
add_action('wp_ajax_nopriv_verificar_email_existente', 's_invest_verify_email');
/**
 * Verificação adicional de autenticação
 */
function s_invest_verify_authentication() {
    // Apenas no painel
    if (!is_page('painel')) {
        return;
    }
    
    // Verificar se o usuário está realmente autenticado
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        
        // Verificação adicional: o usuário existe e tem ID válido?
        if (!$user || !$user->exists() || $user->ID === 0) {
            // Sessão inválida, forçar logout
            wp_logout();
            wp_safe_redirect(home_url('/acessar/?session_expired=1'));
            exit;
        }
        
        // Verificar se o usuário tem role válida
        if (empty($user->roles)) {
            wp_safe_redirect(home_url('/acessar/?invalid_role=1'));
            exit;
        }
    }
}
add_action('template_redirect', 's_invest_verify_authentication', 5);
/**
 * NOVA FUNÇÃO AJAX: Verificar se CPF já existe
 */
function s_invest_verify_cpf() {
    $nonce_valid = false;
    if (wp_verify_nonce($_REQUEST['nonce'] ?? '', 'verificar_cpf')) {
        $nonce_valid = true;
    } elseif (wp_verify_nonce($_REQUEST['nonce'] ?? '', 'verificar_email')) {
        $nonce_valid = true;
    }
    
    if (!$nonce_valid) {
        wp_send_json_error(['message' => 'Nonce inválido.'], 403);
    }
    
    $cpf = sanitize_text_field($_REQUEST['cpf'] ?? '');
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) !== 11) {
        wp_send_json_error(['message' => 'CPF deve ter 11 dígitos.'], 400);
    }
    
    if (!s_invest_validate_cpf($cpf)) {
        wp_send_json_error(['message' => 'CPF inválido.'], 400);
    }
    
    $exists = s_invest_cpf_exists($cpf);
    
    wp_send_json_success([
        'exists' => $exists,
        'message' => $exists ? 'CPF já cadastrado' : 'CPF disponível'
    ]);
}
add_action('wp_ajax_verificar_cpf_existente', 's_invest_verify_cpf');
add_action('wp_ajax_nopriv_verificar_cpf_existente', 's_invest_verify_cpf');

function s_invest_menu_logos_customizer($wp_customize) {
    
    $wp_customize->add_section('s_invest_menu_logos', array(
        'title'       => 'Logos do Menu',
        'description' => 'Configure logos específicas para o menu lateral.',
        'priority'    => 30,
    ));
    
    $wp_customize->add_setting('menu_logo_expanded');
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'menu_logo_expanded', array(
        'label'       => 'Logo Menu Expandido',
        'description' => 'Recomendado: 180x40px, PNG transparente',
        'section'     => 's_invest_menu_logos',
        'mime_type'   => 'image',
    )));
    
    $wp_customize->add_setting('menu_icon_collapsed');
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'menu_icon_collapsed', array(
        'label'       => 'Ícone Menu Contraído',
        'description' => 'Recomendado: 32x32px, PNG transparente',
        'section'     => 's_invest_menu_logos',
        'mime_type'   => 'image',
    )));
}
add_action('customize_register', 's_invest_menu_logos_customizer');

/**
 * PAINEL ADMINISTRATIVO
 */
function s_invest_admin_menu() {
    $page = add_users_page(
        'Gerenciar Investidores', 
        'Investidores', 
        'manage_options', 
        's-invest-users', 
        's_invest_admin_users_page'
    );
    
    add_action('load-' . $page, 's_invest_admin_enqueue_scripts');
}
add_action('admin_menu', 's_invest_admin_menu');

/**
 * Enqueue scripts para a página administrativa
 */
function s_invest_admin_enqueue_scripts() {
    wp_enqueue_script('jquery');
    
    wp_add_inline_script('jquery', '
        window.s_invest_admin = {
            ajax_url: "' . admin_url('admin-ajax.php') . '",
            nonces: {
                approve_user: "' . wp_create_nonce('approve_user') . '",
                resend_confirmation: "' . wp_create_nonce('resend_confirmation') . '",
                bulk_user_action: "' . wp_create_nonce('bulk_user_action') . '",
                get_user_details: "' . wp_create_nonce('get_user_details') . '",
                export_users: "' . wp_create_nonce('export_users') . '",
                delete_user_admin: "' . wp_create_nonce('delete_user_admin') . '"
            }
        };
    ');
}

/**
 * Página administrativa principal atualizada
 */
function s_invest_admin_users_page() {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $user_id = absint($_POST['user_id']);
        
        if ($_POST['action'] === 'approve' && wp_verify_nonce($_POST['_wpnonce'], 'approve_user_' . $user_id)) {
            update_user_meta($user_id, 'email_confirmed', true);
            delete_user_meta($user_id, 'email_confirmation_token');
            echo '<div class="notice notice-success is-dismissible"><p>Usuário aprovado com sucesso!</p></div>';
        }
        
        if ($_POST['action'] === 'resend' && wp_verify_nonce($_POST['_wpnonce'], 'resend_user_' . $user_id)) {
            if (function_exists('s_invest_send_confirmation_email') && s_invest_send_confirmation_email($user_id)) {
                echo '<div class="notice notice-success is-dismissible"><p>E-mail reenviado com sucesso!</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Erro ao enviar e-mail.</p></div>';
            }
        }
    }
    
    $current_tab = $_GET['tab'] ?? 'all';
    
    switch ($current_tab) {
        case 'pending':
            $_GET['status'] = 'pending';
            break;
        case 'confirmed':
            $_GET['status'] = 'confirmed';
            break;
        case 'investidores':
            $_GET['role'] = 'investidor';
            break;
        case 'associados':
            $_GET['role'] = 'associado';
            break;
    }
    
    include S_INVEST_THEME_DIR . '/inc/admin-users-template.php';
}

/**
 * Incluir arquivo de funções AJAX
 */
$ajax_file = S_INVEST_THEME_DIR . '/inc/admin-ajax-functions.php';
if (file_exists($ajax_file)) {
    require_once $ajax_file;
}

/**
 * Adicionar coluna personalizada na lista de usuários do WordPress
 */
function s_invest_add_user_columns($columns) {
    $columns['email_status'] = 'Status E-mail';
    $columns['user_role'] = 'Tipo de Usuário';
    $columns['last_login'] = 'Último Acesso';
    return $columns;
}
add_filter('manage_users_columns', 's_invest_add_user_columns');

function s_invest_user_column_content($value, $column_name, $user_id) {
    switch ($column_name) {
        case 'email_status':
            $confirmed = get_user_meta($user_id, 'email_confirmed', true);
            if ($confirmed) {
                return '<span style="color: #10b981; font-weight: bold;">✓ Confirmado</span>';
            } else {
                return '<span style="color: #f59e0b; font-weight: bold;">⏱ Pendente</span>';
            }
            
        case 'user_role':
            $user = get_user_by('ID', $user_id);
            if ($user && !empty($user->roles)) {
                $role = $user->roles[0];
                $color = $role === 'investidor' ? '#3b82f6' : '#10b981';
                return '<span style="background: ' . $color . '; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; text-transform: uppercase;">' . $role . '</span>';
            }
            return '—';
            
        case 'last_login':
            $last_login = get_user_meta($user_id, 'last_login', true);
            if ($last_login) {
                return human_time_diff(strtotime($last_login), current_time('timestamp')) . ' atrás';
            }
            return '<span style="color: #666;">Nunca</span>';
    }
    return $value;
}
add_filter('manage_users_custom_column', 's_invest_user_column_content', 10, 3);

/**
 * Adicionar filtros rápidos na página de usuários do WordPress
 */
function s_invest_user_list_filters() {
    if (get_current_screen()->id === 'users') {
        $pending_count = count(get_users([
            'meta_query' => [['key' => 'email_confirmed', 'value' => false, 'compare' => '=']]
        ]));
        
        if ($pending_count > 0) {
            echo '<div class="notice notice-warning" style="margin: 5px 0 15px 0; padding: 10px;">
                <p><strong>Atenção:</strong> Existem ' . $pending_count . ' usuário(s) pendente(s) de confirmação. 
                <a href="' . admin_url('users.php?page=s-invest-users&tab=pending') . '" class="button button-small">Ver Pendentes</a></p>
            </div>';
        }
    }
}
add_action('admin_notices', 's_invest_user_list_filters');

/**
 * CSS para o admin
 */
function s_invest_admin_styles() {
    if (get_current_screen()->id === 'users_page_s-invest-users') {
        echo '<style>
        .s-invest-stats { margin: 20px 0; }
        .stat-card { transition: all 0.2s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .wp-list-table tbody tr:hover { background-color: #f0f9ff !important; }
        .role-badge { letter-spacing: 0.5px; }
        .row-actions { visibility: hidden; }
        .wp-list-table tbody tr:hover .row-actions { visibility: visible; }
        #user-details-modal { z-index: 100000; }
        @media (max-width: 782px) {
            .s-invest-filters form { flex-direction: column; align-items: stretch; }
            .s-invest-stats { grid-template-columns: 1fr !important; }
            .column-avatar, .column-phone, .column-cpf, .column-last-login { display: none; }
        }
        </style>';
    }
}
add_action('admin_head', 's_invest_admin_styles');

/**
 * BUILD SYSTEM HELPERS
 */
function s_invest_admin_notice_build_missing() {
    if (!current_user_can('administrator')) return;
    
    add_action('admin_notices', function() {
        echo '<div class="notice notice-warning"><p><strong>S-Invest Theme:</strong> Assets não compilados. Execute <code>npm run build</code> para otimizar.</p></div>';
    });
}

function s_invest_build_status() {
    check_ajax_referer('s_invest_build', 'nonce');
    
    wp_send_json_success([
        'css_exists' => file_exists(S_INVEST_THEME_DIR . '/public/css/app.css'),
        'js_exists' => file_exists(S_INVEST_THEME_DIR . '/public/js/app.js'),
        'node_modules' => is_dir(S_INVEST_THEME_DIR . '/node_modules'),
        'package_json' => file_exists(S_INVEST_THEME_DIR . '/package.json'),
        'theme_dir' => S_INVEST_THEME_DIR
    ]);
}
add_action('wp_ajax_s_invest_build_status', 's_invest_build_status');

/**
 * INCLUSÃO DE ARQUIVOS
 */
$required_files = ['/inc/ajax-investimentos.php', '/inc/helpers.php'];
foreach ($required_files as $file) {
    $file_path = S_INVEST_THEME_DIR . $file;
    if (file_exists($file_path)) require_once $file_path;
}

/**
 * PROCESSAMENTO DE FORMULÁRIOS DE AUTENTICAÇÃO
 */
if (isset($_POST['sky_auth_action'])) {
    require_once S_INVEST_THEME_DIR . '/inc/auth-process.php';
}
// Adicione esta função temporariamente ao final do functions.php
function debug_status_captacao($investment_id) {
    echo "<div style='background: #f0f0f0; padding: 15px; margin: 10px; border-radius: 5px;'>";
    echo "<h3>DEBUG - Status da Captação (ID: {$investment_id})</h3>";
    
    // Verificar se ACF está funcionando
    if (!function_exists('get_field')) {
        echo "<p style='color: red;'>❌ ACF não está ativo!</p>";
        echo "</div>";
        return;
    }
    
    // Verificar o campo manual
    $status_manual = get_field('status_captacao_manual', $investment_id);
    echo "<p><strong>Status Manual ACF:</strong> " . var_export($status_manual, true) . "</p>";
    
    // Verificar outros campos
    $valor_total = get_field('valor_total', $investment_id);
    $total_captado = get_field('total_captado', $investment_id);
    $fim_captacao = get_field('fim_captacao', $investment_id);
    
    echo "<p><strong>Valor Total:</strong> {$valor_total}</p>";
    echo "<p><strong>Total Captado:</strong> {$total_captado}</p>";
    echo "<p><strong>Fim Captação:</strong> {$fim_captacao}</p>";
    
    // Calcular porcentagem
    if ($valor_total > 0) {
        $porcentagem = ($total_captado / $valor_total) * 100;
        echo "<p><strong>Porcentagem:</strong> {$porcentagem}%</p>";
    }
    
    // Verificar função de cálculo
    if (function_exists('s_invest_calcular_status_captacao')) {
        $status_calculado = s_invest_calcular_status_captacao($investment_id);
        echo "<p><strong>Status Calculado:</strong> {$status_calculado}</p>";
    } else {
        echo "<p style='color: red;'>❌ Função s_invest_calcular_status_captacao não existe!</p>";
    }
    
    // Verificar função de info
    if (function_exists('s_invest_get_status_captacao_info')) {
        $status_info = s_invest_get_status_captacao_info($investment_id);
        echo "<p><strong>Status Info:</strong> " . var_export($status_info, true) . "</p>";
    } else {
        echo "<p style='color: red;'>❌ Função s_invest_get_status_captacao_info não existe!</p>";
    }
    
    echo "</div>";
}

// Para testar, adicione esta linha em um card ou na página do investimento:
// debug_status_captacao(get_the_ID());

// ========== DIAGNÓSTICO DE PROBLEMAS DE LOGIN ==========

/**
 * Função para diagnosticar problemas de autenticação
 * Chame: ?debug_auth=1 (apenas para admins)
 */
function s_invest_auth_diagnostics() {
    if (!isset($_GET['debug_auth']) || !current_user_can('administrator')) {
        return;
    }
    
    $diagnostics = [
        'current_user_id' => get_current_user_id(),
        'is_user_logged_in' => is_user_logged_in(),
        'session_tokens' => get_user_meta(get_current_user_id(), 'session_tokens', true),
        'auth_cookies' => [
            'wordpress_logged_in' => $_COOKIE[LOGGED_IN_COOKIE] ?? 'not_set',
            'wordpress_auth' => $_COOKIE[AUTH_COOKIE] ?? 'not_set'
        ],
        'user_roles' => wp_get_current_user()->roles ?? [],
        'cache_status' => [
            'object_cache' => wp_using_ext_object_cache(),
            'page_cache' => defined('WP_CACHE') ? WP_CACHE : false
        ],
        'server_info' => [
            'php_session' => session_status(),
            'headers_sent' => headers_sent(),
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_uri' => $_SERVER['REQUEST_URI']
        ]
    ];
    
    header('Content-Type: application/json');
    echo json_encode($diagnostics, JSON_PRETTY_PRINT);
    exit;
}
add_action('init', 's_invest_auth_diagnostics');

// ========== CORREÇÃO ESPECÍFICA PARA CACHE PLUGINS ==========

/**
 * Excluir páginas de autenticação do cache
 */
function s_invest_exclude_auth_pages_from_cache() {
    // Para WP Super Cache
    if (function_exists('wp_cache_serve_cache_file')) {
        global $cache_enabled;
        if (is_page(['acessar', 'painel']) || strpos($_SERVER['REQUEST_URI'], '/acessar') !== false || strpos($_SERVER['REQUEST_URI'], '/painel') !== false) {
            $cache_enabled = false;
        }
    }
    
    // Para W3 Total Cache
    if (defined('W3TC')) {
        if (is_page(['acessar', 'painel'])) {
            define('DONOTCACHEPAGE', true);
        }
    }
    
    // Para WP Rocket
    if (function_exists('rocket_clean_domain')) {
        if (is_page(['acessar', 'painel'])) {
            define('DONOTCACHEPAGE', true);
        }
    }
    
    // Para LiteSpeed Cache
    if (defined('LSCWP_V')) {
        if (is_page(['acessar', 'painel'])) {
            do_action('litespeed_control_set_nocache', 'auth pages');
        }
    }
}
add_action('init', 's_invest_exclude_auth_pages_from_cache');

// ========== MELHORAR AJAX DE AUTENTICAÇÃO ==========

/**
 * Verificar sessão via AJAX
 */
function s_invest_check_session_ajax() {
    // Verificar nonce básico
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'check_session') && !current_user_can('read')) {
        wp_send_json_error('Invalid request');
    }
    
    $response = [
        'logged_in' => is_user_logged_in(),
        'user_id' => get_current_user_id(),
        'has_valid_session' => false,
        'session_expiry' => null
    ];
    
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $sessions = WP_Session_Tokens::get_instance($user->ID);
        $current_session = $sessions->get($_COOKIE[AUTH_COOKIE] ?? '');
        
        $response['has_valid_session'] = !empty($current_session);
        if (!empty($current_session['expiration'])) {
            $response['session_expiry'] = $current_session['expiration'];
        }
    }
    
    wp_send_json_success($response);
}
add_action('wp_ajax_check_session', 's_invest_check_session_ajax');
add_action('wp_ajax_nopriv_check_session', 's_invest_check_session_ajax');

// ========== JAVASCRIPT PARA VERIFICAR SESSÃO ==========

/**
 * Script para verificar sessão periodicamente
 */
function s_invest_session_check_script() {
    if (!is_page('painel')) {
        return;
    }
    ?>
    <script>
    // Verificar sessão a cada 5 minutos
    setInterval(function() {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                'action': 'check_session',
                'nonce': '<?php echo wp_create_nonce('check_session'); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.data.logged_in || !data.data.has_valid_session) {
                console.log('Sessão inválida detectada, redirecionando...');
                // Mostrar aviso antes de redirecionar
                if (confirm('Sua sessão expirou. Você será redirecionado para o login.')) {
                    window.location.href = '<?php echo home_url('/acessar/?session_check=expired'); ?>';
                }
            }
        })
        .catch(error => {
            console.log('Erro ao verificar sessão:', error);
        });
    }, 5 * 60 * 1000); // 5 minutos
    </script>
    <?php
}
add_action('wp_footer', 's_invest_session_check_script');

// ========== AUMENTAR TEMPO DE SESSÃO ==========

/**
 * Aumentar duração da sessão para reduzir expiração
 */
function s_invest_extend_session_duration($expiration, $user_id, $remember) {
    // Se "lembrar de mim" estiver marcado, manter 30 dias
    if ($remember) {
        return 30 * DAY_IN_SECONDS;
    }
    
    // Caso contrário, aumentar para 24 horas em vez de 2 horas padrão
    return 24 * HOUR_IN_SECONDS;
}
add_filter('auth_cookie_expiration', 's_invest_extend_session_duration', 10, 3);

// ========== MELHORAR PROCESSO DE LOGIN ==========

/**
 * Configurações aprimoradas de login
 */
function s_invest_improve_login_process() {
    // Regenerar ID de sessão após login bem-sucedido
    add_action('wp_login', function($user_login, $user) {
        if (function_exists('session_regenerate_id')) {
            session_regenerate_id(true);
        }
        
        // Limpar cache antigo do usuário
        wp_cache_delete("user_meta_{$user->ID}", 'users');
        clean_user_cache($user->ID);
        
    }, 10, 2);
    
    // Melhorar segurança do cookie
    add_action('set_auth_cookie', function($auth_cookie, $expire, $expiration, $user_id, $scheme, $token) {
        // Definir cookie com configurações mais seguras
        $secure = is_ssl();
        $httponly = true;
        
        if (!headers_sent()) {
            $cookie_domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';
            
            // Definir cookie manualmente com configurações otimizadas
            setcookie(
                LOGGED_IN_COOKIE,
                $auth_cookie,
                $expire,
                COOKIEPATH,
                $cookie_domain,
                $secure,
                $httponly
            );
        }
    }, 10, 6);
}
add_action('init', 's_invest_improve_login_process');

// ========== LIMPEZA DE SESSÕES ANTIGAS ==========

/**
 * Limpar sessões antigas automaticamente
 */
function s_invest_cleanup_old_sessions() {
    // Limpar sessões expiradas diariamente
    if (!wp_next_scheduled('s_invest_cleanup_sessions')) {
        wp_schedule_event(time(), 'daily', 's_invest_cleanup_sessions');
    }
}
add_action('init', 's_invest_cleanup_old_sessions');

add_action('s_invest_cleanup_sessions', function() {
    global $wpdb;
    
    // Deletar sessões expiradas
    $wpdb->query(
        "DELETE FROM {$wpdb->usermeta} 
         WHERE meta_key = 'session_tokens' 
         AND meta_value LIKE '%expiration%' 
         AND meta_value < " . time()
    );
    
    // Log da limpeza
    if (WP_DEBUG) {
        error_log('Sessões antigas limpas automaticamente');
    }
});
// 1. DESABILITAR VERIFICAÇÕES AGRESSIVAS
remove_action('wp_footer', 's_invest_session_check_script');
remove_action('template_redirect', 's_invest_verify_authentication');

// 2. AUMENTAR TEMPO DE SESSÃO
add_filter('auth_cookie_expiration', function($expiration, $user_id, $remember) {
    return $remember ? (30 * DAY_IN_SECONDS) : (24 * HOUR_IN_SECONDS);
}, 999, 3);

// 3. CONFIGURAÇÕES DE SESSÃO MAIS ESTÁVEIS
function s_invest_fix_session_config() {
    if (is_admin() || wp_doing_ajax()) return;
    
    if (function_exists('ini_set') && !headers_sent()) {
        ini_set('session.gc_maxlifetime', '86400');
        ini_set('session.cookie_lifetime', '86400');
        ini_set('session.cookie_samesite', 'Lax');
    }
}
add_action('init', 's_invest_fix_session_config', 1);

// 4. REDIRECIONAMENTOS MAIS SUAVES
function s_invest_simple_redirects() {
    if (is_admin() || wp_doing_ajax() || headers_sent()) return;
    
    // Apenas redirecionamentos essenciais
    if (is_user_logged_in() && is_page('acessar') && !isset($_GET['action'])) {
        wp_safe_redirect(home_url('/painel/'));
        exit;
    }
    
    if (!is_user_logged_in() && is_page('painel')) {
        wp_safe_redirect(home_url('/acessar/'));
        exit;
    }
}
add_action('template_redirect', 's_invest_simple_redirects', 5);

// 5. EVITAR CACHE EM PÁGINAS DE AUTENTICAÇÃO
function s_invest_no_cache_auth_pages() {
    if (is_page(['acessar', 'painel'])) {
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        
        if (!headers_sent()) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
}
add_action('template_redirect', 's_invest_no_cache_auth_pages', 1);

// 6. LIMPEZA SUAVE DE CACHE NO LOGIN
function s_invest_gentle_login_cleanup($user_login, $user) {
    if ($user && $user->ID) {
        // Apenas limpar cache específico, não forçar logout
        wp_cache_delete("dashboard_stats_{$user->ID}", 'user_stats');
        delete_transient("investor_dashboard_{$user->ID}");
    }
}
add_action('wp_login', 's_invest_gentle_login_cleanup', 10, 2);

// 7. DEBUG ESPECÍFICO PARA SESSÃO (se necessário)
function s_invest_debug_session_only() {
    if (!isset($_GET['debug_session']) || !current_user_can('administrator')) {
        return;
    }
    
    $info = [
        'is_logged_in' => is_user_logged_in(),
        'user_id' => get_current_user_id(),
        'session_cookies' => [
            'auth' => isset($_COOKIE[AUTH_COOKIE]) ? 'presente' : 'ausente',
            'logged_in' => isset($_COOKIE[LOGGED_IN_COOKIE]) ? 'presente' : 'ausente'
        ],
        'session_config' => [
            'gc_maxlifetime' => ini_get('session.gc_maxlifetime'),
            'cookie_lifetime' => ini_get('session.cookie_lifetime')
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    wp_die('<pre>' . print_r($info, true) . '</pre>');
}
add_action('init', 's_invest_debug_session_only');
// ========== FUNÇÕES PARA PRODUTOS PRIVATE/SCP ==========

/**
 * Verifica se um investimento é do tipo Private/SCP
 */
function s_invest_is_private_scp($investment_id) {
    if (!$investment_id) return false;
    
    // Verificar por taxonomia tipo_produto
    $terms = wp_get_post_terms($investment_id, 'tipo_produto');
    
    if (!is_wp_error($terms) && !empty($terms)) {
        foreach ($terms as $term) {
            $term_name = strtolower($term->name);
            $term_slug = strtolower($term->slug);
            
            // Palavras-chave que identificam SCP/Private
            $keywords_scp = ['scp', 'private', 'capital semente', 'sociedade', 'participacao'];
            
            foreach ($keywords_scp as $keyword) {
                if (strpos($term_name, $keyword) !== false || 
                    strpos($term_slug, $keyword) !== false) {
                    return true;
                }
            }
        }
    }
    
    // Verificar por campo customizado específico (caso exista)
    $tipo_produto_manual = get_field('tipo_produto_manual', $investment_id);
    if ($tipo_produto_manual === 'private' || $tipo_produto_manual === 'scp') {
        return true;
    }
    
    return false;
}

/**
 * Retorna o tipo de produto formatado (PRIVATE, TRADE)
 */
function s_invest_get_product_type_label($investment_id) {
    if (s_invest_is_private_scp($investment_id)) {
        return 'PRIVATE';
    }
    return 'TRADE';
}

/**
 * Retorna as classes CSS para o badge do tipo de produto
 */
function s_invest_get_product_type_class($investment_id) {
    if (s_invest_is_private_scp($investment_id)) {
        return 'bg-purple-500/20 text-purple-400 border-purple-500/30';
    }
    return 'bg-blue-500/20 text-blue-400 border-blue-500/30';
}

/**
 * Verifica se um aporte é de produto Private/SCP
 */
function s_invest_aporte_is_private($aporte_id) {
    $investment_id = get_field('investment_id', $aporte_id);
    return $investment_id ? s_invest_is_private_scp($investment_id) : false;
}