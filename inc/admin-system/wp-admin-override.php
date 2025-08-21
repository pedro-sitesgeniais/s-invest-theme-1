<?php
/**
 * WordPress Admin Override - Sistema Inteligente de Redirecionamento
 * Substitui seletivamente o wp-admin por painel customizado
 * Versão: 3.0.0
 */

defined('ABSPATH') || exit;

class S_Invest_WP_Admin_Override {
    
    private static $instance = null;
    
    // Páginas permitidas no wp-admin para cada role
    private $allowed_admin_pages = [
        'associado' => [
            'index.php',           // Dashboard
            'edit.php',            // Lista de posts
            'post-new.php',        // Novo post
            'post.php',            // Editar post
            'upload.php',          // Mídia
            'media-upload.php',    // Upload de mídia
            'media-new.php',       // Nova mídia
            'profile.php',         // Perfil
            'admin-ajax.php',      // AJAX
            'async-upload.php',    // Upload assíncrono
        ],
        'investidor' => [
            'profile.php',         // Apenas perfil
            'admin-ajax.php',      // AJAX (necessário)
        ]
    ];
    
    // CPTs permitidos por role
    private $allowed_post_types = [
        'associado' => ['investment', 'aporte', 'comunicado', 'faq', 'attachment'],
        'investidor' => []
    ];
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Hook principal de redirecionamento
        add_action('admin_init', [$this, 'handle_admin_access'], 1);
        
        // Customizar interface para roles específicas
        add_action('admin_head', [$this, 'customize_admin_interface']);
        add_action('admin_footer', [$this, 'add_custom_admin_scripts']);
        
        // Filtros de menu
        add_action('admin_menu', [$this, 'customize_admin_menu'], 999);
        add_filter('removable_query_args', [$this, 'add_custom_query_args']);
        
        // Notices customizadas
        add_action('admin_notices', [$this, 'show_custom_notices']);
        
        // Override específico para investidores
        add_action('wp_loaded', [$this, 'redirect_investors_from_admin']);
    }
    
    /**
     * Manipular acesso ao admin principal
     */
    public function handle_admin_access() {
        // Não interferir durante AJAX, CRON, ou requisições críticas
        if (defined('DOING_AJAX') || defined('DOING_CRON') || defined('XMLRPC_REQUEST')) {
            return;
        }
        
        // Não interferir se headers já foram enviados
        if (headers_sent()) {
            return;
        }
        
        $user = wp_get_current_user();
        if (!$user || !$user->exists()) {
            return;
        }
        
        // Administradores têm acesso total
        if (in_array('administrator', $user->roles)) {
            return;
        }
        
        $current_page = basename($_SERVER['SCRIPT_NAME']);
        $post_type = $_GET['post_type'] ?? 'post';
        
        // Verificar acesso para associados
        if (in_array('associado', $user->roles)) {
            $this->handle_associado_access($current_page, $post_type);
        }
        
        // Verificar acesso para investidores
        if (in_array('investidor', $user->roles)) {
            $this->handle_investidor_access($current_page);
        }
    }
    
    /**
     * Manipular acesso de associados
     */
    private function handle_associado_access($current_page, $post_type) {
        // Verificar se a página é permitida
        if (!in_array($current_page, $this->allowed_admin_pages['associado'])) {
            $this->redirect_to_custom_panel('associado');
            return;
        }
        
        // Para edit.php, verificar se o post_type é permitido
        if ($current_page === 'edit.php') {
            if (!in_array($post_type, $this->allowed_post_types['associado'])) {
                $this->redirect_to_custom_panel('associado');
                return;
            }
        }
        
        // Verificar permissões específicas
        if (!$this->user_has_access_to_current_page()) {
            $this->redirect_to_custom_panel('associado');
            return;
        }
    }
    
    /**
     * Manipular acesso de investidores
     */
    private function handle_investidor_access($current_page) {
        // Investidores só podem acessar perfil e AJAX
        if (!in_array($current_page, $this->allowed_admin_pages['investidor'])) {
            $this->redirect_to_custom_panel('investidor');
            return;
        }
    }
    
    /**
     * Verificar se usuário tem acesso à página atual
     */
    private function user_has_access_to_current_page() {
        $screen = get_current_screen();
        
        if (!$screen) {
            return true; // Se não conseguir determinar, permitir
        }
        
        // Verificar por post type
        if ($screen->post_type) {
            $post_type = $screen->post_type;
            
            // Verificar se usuário pode editar este post type
            if (!current_user_can("edit_{$post_type}s") && !current_user_can('edit_posts')) {
                return false;
            }
        }
        
        // Verificar por base da tela
        $restricted_screens = [
            'themes', 'plugins', 'tools', 'options-general',
            'options-writing', 'options-reading', 'options-discussion',
            'options-media', 'options-permalink', 'import', 'export'
        ];
        
        if (in_array($screen->base, $restricted_screens)) {
            return current_user_can('administrator');
        }
        
        return true;
    }
    
    /**
     * Redirecionar para painel customizado
     */
    private function redirect_to_custom_panel($panel_type) {
        $redirect_url = add_query_arg([
            'painel' => $panel_type,
            'secao' => $panel_type === 'associado' ? 'admin-dashboard' : 'dashboard',
            'from_admin' => '1'
        ], home_url('/painel/'));
        
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Redirecionamento específico para investidores
     */
    public function redirect_investors_from_admin() {
        // Verificar se está tentando acessar wp-admin
        if (!is_admin() || defined('DOING_AJAX')) {
            return;
        }
        
        $user = wp_get_current_user();
        if (!$user || !$user->exists()) {
            return;
        }
        
        // Apenas para investidores
        if (!in_array('investidor', $user->roles) || in_array('administrator', $user->roles)) {
            return;
        }
        
        $current_page = basename($_SERVER['SCRIPT_NAME']);
        
        // Permitir apenas páginas críticas
        $critical_pages = ['admin-ajax.php', 'async-upload.php', 'profile.php'];
        
        if (!in_array($current_page, $critical_pages)) {
            wp_safe_redirect(home_url('/painel/'));
            exit;
        }
    }
    
    /**
     * Customizar interface administrativa
     */
    public function customize_admin_interface() {
        $user = wp_get_current_user();
        
        // Apenas para associados
        if (!in_array('associado', $user->roles) || in_array('administrator', $user->roles)) {
            return;
        }
        
        ?>
        <style id="s-invest-admin-customization">
            /* Customizações para associados */
            #wpadminbar .ab-top-menu > li.menupop.hover > .ab-sub-wrapper,
            #wpadminbar .ab-top-menu > li.menupop:hover > .ab-sub-wrapper {
                display: block;
            }
            
            /* Esconder elementos desnecessários */
            #wp-admin-bar-comments,
            #wp-admin-bar-new-content,
            #wp-admin-bar-edit,
            #wp-admin-bar-view,
            #adminmenu .wp-submenu-head,
            #menu-appearance,
            #menu-plugins,
            #menu-tools,
            #menu-settings {
                display: none !important;
            }
            
            /* Adicionar banner do sistema */
            .wrap h1:first-of-type::after {
                content: " - Sistema S-Invest";
                font-size: 14px;
                color: #666;
                font-weight: normal;
            }
            
            /* Botão de acesso ao painel */
            #wp-admin-bar-root-default::before {
                content: '<li id="wp-admin-bar-custom-panel"><a class="ab-item" href="<?php echo home_url('/painel/?painel=associado'); ?>">🏠 Painel S-Invest</a></li>';
            }
        </style>
        <?php
    }
    
    /**
     * Adicionar scripts customizados
     */
    public function add_custom_admin_scripts() {
        $user = wp_get_current_user();
        
        if (!in_array('associado', $user->roles) || in_array('administrator', $user->roles)) {
            return;
        }
        
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Adicionar botão de acesso ao painel na barra de admin
            $('#wp-admin-bar-root-default').prepend(
                '<li id="wp-admin-bar-custom-panel">' +
                '<a class="ab-item" href="<?php echo home_url('/painel/?painel=associado'); ?>" style="background: #0073aa; color: white;">' +
                '<span class="ab-icon dashicons dashicons-admin-home"></span>' +
                '<span class="ab-label">Painel S-Invest</span>' +
                '</a></li>'
            );
            
            // Melhorar UX para CPTs
            $('.post-type-investment .page-title-action, .post-type-aporte .page-title-action').each(function() {
                $(this).addClass('button-primary');
            });
            
            // Adicionar confirmação para ações críticas
            $('.submitdelete').on('click', function(e) {
                if (!confirm('Tem certeza que deseja excluir este item? Esta ação não pode ser desfeita.')) {
                    e.preventDefault();
                }
            });
            
            // Feedback visual melhorado
            $('.button-primary').on('click', function() {
                const button = $(this);
                if (button.closest('form').length > 0 && !button.hasClass('no-loading')) {
                    button.prop('disabled', true).append(' <span class="spinner is-active" style="float: none; margin: 0 0 0 5px;"></span>');
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Customizar menu administrativo
     */
    public function customize_admin_menu() {
        $user = wp_get_current_user();
        
        // Apenas para associados
        if (!in_array('associado', $user->roles) || in_array('administrator', $user->roles)) {
            return;
        }
        
        // Remover menus desnecessários
        remove_menu_page('edit-comments.php');
        remove_menu_page('themes.php');
        remove_menu_page('plugins.php');
        remove_menu_page('tools.php');
        remove_menu_page('options-general.php');
        
        // Adicionar separador e link para painel
        add_menu_page(
            'Painel S-Invest',
            'Painel S-Invest',
            'read',
            'painel-s-invest',
            function() {
                wp_redirect(home_url('/painel/?painel=associado'));
                exit;
            },
            'dashicons-admin-home',
            2
        );
        
        // Renomear menus existentes
        global $menu;
        foreach ($menu as $key => $item) {
            // Renomear "Mídia" para "Arquivos"
            if ($item[2] === 'upload.php') {
                $menu[$key][0] = 'Arquivos';
            }
            
            // Renomear "Usuários" se tiver acesso
            if ($item[2] === 'users.php') {
                $menu[$key][0] = 'Investidores';
            }
        }
    }
    
    /**
     * Adicionar argumentos de query customizados
     */
    public function add_custom_query_args($args) {
        $args[] = 'from_admin';
        $args[] = 'admin_redirect';
        return $args;
    }
    
    /**
     * Mostrar notices customizadas
     */
    public function show_custom_notices() {
        // Notice de boas-vindas ao sistema unificado
        if (isset($_GET['from_admin']) && $_GET['from_admin'] === '1') {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>Sistema Unificado S-Invest:</strong> 
                    Você foi redirecionado para o painel administrativo customizado. 
                    Use o menu à esquerda para navegar entre as funcionalidades.
                </p>
            </div>
            <?php
        }
        
        // Notice sobre funcionalidades migradas
        $user = wp_get_current_user();
        if (in_array('associado', $user->roles) && !in_array('administrator', $user->roles)) {
            $screen = get_current_screen();
            
            if ($screen && in_array($screen->post_type, ['investment', 'aporte'])) {
                ?>
                <div class="notice notice-success" style="border-left-color: #00a32a;">
                    <p>
                        <strong>💡 Dica:</strong> 
                        Acesse o <a href="<?php echo home_url('/painel/?painel=associado&secao=admin-dashboard'); ?>">
                        Dashboard Administrativo</a> para uma visão completa do sistema.
                    </p>
                </div>
                <?php
            }
        }
    }
    
    /**
     * Verificar se o redirecionamento deve ser aplicado
     */
    private function should_apply_redirect() {
        // Não aplicar em páginas críticas
        $critical_scripts = [
            'admin-ajax.php',
            'async-upload.php',
            'media-upload.php',
            'admin-post.php'
        ];
        
        $current_script = basename($_SERVER['SCRIPT_NAME']);
        
        if (in_array($current_script, $critical_scripts)) {
            return false;
        }
        
        // Não aplicar durante upload de arquivos
        if (isset($_POST['action']) && in_array($_POST['action'], ['upload-attachment', 'image-editor'])) {
            return false;
        }
        
        // Não aplicar se está editando perfil
        if (isset($_GET['user_id']) || strpos($_SERVER['REQUEST_URI'], 'profile.php') !== false) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Debug: Log de redirecionamentos
     */
    private function log_redirect($from, $to, $reason) {
        if (!WP_DEBUG) {
            return;
        }
        
        $user = wp_get_current_user();
        $log_entry = sprintf(
            '[%s] User %s (%s) redirected from %s to %s - Reason: %s',
            current_time('Y-m-d H:i:s'),
            $user->user_login,
            implode(', ', $user->roles),
            $from,
            $to,
            $reason
        );
        
        error_log('S-Invest Admin Override: ' . $log_entry);
    }
}

// Inicializar apenas se não estiver no contexto de AJAX crítico
if (!defined('DOING_AJAX') || !in_array($_POST['action'] ?? '', ['heartbeat', 'wp-compression-test'])) {
    S_Invest_WP_Admin_Override::get_instance();
}