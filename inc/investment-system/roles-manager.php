<?php
/**
 * Roles Manager - Sistema Unificado de Roles e Permissões
 * Migrado do plugin sky-invest-panel
 * Versão: 3.0.0
 */

defined('ABSPATH') || exit;

class S_Invest_Roles_Manager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'create_custom_roles']);
        add_action('after_setup_theme', [$this, 'setup_capabilities']);
        
        // Cleanup ao desativar tema
        add_action('switch_theme', [$this, 'cleanup_on_deactivation']);
    }
    
    /**
     * Criar roles personalizadas
     */
    public function create_custom_roles() {
        // Role Investidor
        if (!get_role('investidor')) {
            add_role('investidor', 'Investidor', [
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'upload_files' => false,
                'edit_profile' => true,
                
                // Capacidades específicas do investidor
                'view_own_investments' => true,
                'create_aportes' => true,
                'edit_own_aportes' => true,
                'view_investment_details' => true,
                'access_investor_panel' => true,
                'download_investment_docs' => true,
            ]);
        }
        
        // Role Associado (gerente/admin de investimentos)
        if (!get_role('associado')) {
            add_role('associado', 'Associado', [
                'read' => true,
                'edit_posts' => true,
                'delete_posts' => true,
                'upload_files' => true,
                'edit_profile' => true,
                
                // Capacidades básicas de posts
                'edit_published_posts' => true,
                'delete_published_posts' => true,
                'edit_private_posts' => true,
                'delete_private_posts' => true,
                
                // Capacidades específicas do associado
                'manage_investments' => true,
                'edit_investments' => true,
                'edit_others_investments' => true,
                'publish_investments' => true,
                'read_private_investments' => true,
                'delete_investments' => true,
                'delete_others_investments' => true,
                
                'manage_aportes' => true,
                'edit_aportes' => true,
                'edit_others_aportes' => true,
                'publish_aportes' => true,
                'read_private_aportes' => true,
                'delete_aportes' => true,
                'delete_others_aportes' => true,
                
                'manage_comunicados' => true,
                'edit_comunicados' => true,
                'publish_comunicados' => true,
                'delete_comunicados' => true,
                
                'manage_faq' => true,
                'edit_faq' => true,
                'publish_faq' => true,
                'delete_faq' => true,
                
                // Acesso ao painel administrativo
                'access_admin_panel' => true,
                'manage_investors' => true,
                'view_financial_reports' => true,
                'export_data' => true,
                
                // Taxonomias
                'manage_categories' => true,
                'edit_categories' => false, // Não pode editar categorias do blog
                'manage_investment_categories' => true,
            ]);
        }
        
        // Adicionar capacidades para administradores
        $this->setup_admin_capabilities();
    }
    
    /**
     * Configurar capacidades personalizadas
     */
    public function setup_capabilities() {
        // Adicionar capacidades para administradores
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_caps = [
                // Investimentos
                'manage_investments',
                'edit_investments',
                'edit_others_investments',
                'publish_investments',
                'read_private_investments',
                'delete_investments',
                'delete_others_investments',
                
                // Aportes
                'manage_aportes',
                'edit_aportes',
                'edit_others_aportes',
                'publish_aportes',
                'read_private_aportes',
                'delete_aportes',
                'delete_others_aportes',
                
                // Comunicados
                'manage_comunicados',
                'edit_comunicados',
                'publish_comunicados',
                'delete_comunicados',
                
                // FAQ
                'manage_faq',
                'edit_faq',
                'publish_faq',
                'delete_faq',
                
                // Sistema
                'access_admin_panel',
                'manage_investors',
                'view_financial_reports',
                'export_data',
                'view_own_investments',
                'access_investor_panel',
                'download_investment_docs',
                'manage_investment_categories',
            ];
            
            foreach ($admin_caps as $cap) {
                $admin_role->add_cap($cap);
            }
        }
    }
    
    /**
     * Configurar capacidades específicas do administrador
     */
    private function setup_admin_capabilities() {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            // Capacidades especiais do admin
            $admin_role->add_cap('manage_investment_system');
            $admin_role->add_cap('configure_investment_settings');
            $admin_role->add_cap('access_system_reports');
            $admin_role->add_cap('manage_user_roles');
        }
    }
    
    /**
     * Verificar se usuário tem acesso ao painel administrativo
     */
    public static function user_can_access_admin_panel($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Administradores sempre têm acesso
        if (in_array('administrator', $user->roles)) {
            return true;
        }
        
        // Associados têm acesso ao painel administrativo
        if (in_array('associado', $user->roles)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar se usuário tem acesso ao painel do investidor
     */
    public static function user_can_access_investor_panel($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        
        // Administradores sempre têm acesso
        if (in_array('administrator', $user->roles)) {
            return true;
        }
        
        // Investidores têm acesso ao painel do investidor
        if (in_array('investidor', $user->roles)) {
            return true;
        }
        
        // Associados também podem acessar o painel do investidor
        if (in_array('associado', $user->roles)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Obter tipo de painel preferido do usuário
     */
    public static function get_user_default_panel($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return 'investidor';
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return 'investidor';
        }
        
        // Administradores - painel preferido (associado por padrão)
        if (in_array('administrator', $user->roles)) {
            $preferred = get_user_meta($user_id, 'preferred_panel', true);
            return $preferred ?: 'associado';
        }
        
        // Associados - painel administrativo
        if (in_array('associado', $user->roles)) {
            return 'associado';
        }
        
        // Investidores - painel do investidor
        if (in_array('investidor', $user->roles)) {
            return 'investidor';
        }
        
        return 'investidor';
    }
    
    /**
     * Verificar se usuário pode gerenciar investimentos
     */
    public static function user_can_manage_investments($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'manage_investments');
    }
    
    /**
     * Verificar se usuário pode ver relatórios financeiros
     */
    public static function user_can_view_reports($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'view_financial_reports');
    }
    
    /**
     * Verificar se usuário pode gerenciar investidores
     */
    public static function user_can_manage_investors($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'manage_investors');
    }
    
    /**
     * Obter lista de capacidades por role
     */
    public static function get_role_capabilities($role_name) {
        $role = get_role($role_name);
        
        if (!$role) {
            return [];
        }
        
        return array_keys(array_filter($role->capabilities));
    }
    
    /**
     * Verificar se usuário pode acessar uma seção específica
     */
    public static function user_can_access_section($section, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $section_permissions = [
            // Seções do painel do investidor
            'dashboard' => ['access_investor_panel'],
            'meus-investimentos' => ['view_own_investments'],
            'meus-aportes' => ['view_own_investments'],
            'produtos-gerais' => ['view_investment_details'],
            'comunicados' => ['access_investor_panel'],
            'documentos' => ['download_investment_docs'],
            'perfil' => ['edit_profile'],
            'suporte' => ['access_investor_panel'],
            'detalhes-investimento' => ['view_investment_details'],
            
            // Seções do painel administrativo
            'admin-dashboard' => ['access_admin_panel'],
            'investimentos' => ['manage_investments'],
            'aportes' => ['manage_aportes'],
            'investidores' => ['manage_investors'],
            'relatorios' => ['view_financial_reports'],
            'configuracoes' => ['manage_investment_system'],
            'usuarios' => ['manage_investors'],
            'exportar' => ['export_data'],
        ];
        
        if (!isset($section_permissions[$section])) {
            return false;
        }
        
        $required_caps = $section_permissions[$section];
        
        foreach ($required_caps as $cap) {
            if (user_can($user_id, $cap)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Redirecionar usuário para painel adequado
     */
    public static function redirect_to_appropriate_panel($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            wp_safe_redirect(home_url('/acessar/'));
            exit;
        }
        
        $default_panel = self::get_user_default_panel($user_id);
        
        // Se já está na página do painel, não redirecionar
        if (is_page('painel')) {
            return;
        }
        
        $redirect_url = add_query_arg([
            'painel' => $default_panel,
            'secao' => 'dashboard'
        ], home_url('/painel/'));
        
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Limpeza ao desativar tema
     */
    public function cleanup_on_deactivation() {
        // Opcional: manter roles ou removê-las
        // Por segurança, vamos manter as roles criadas
        
        // Se quiser remover as roles:
        // remove_role('investidor');
        // remove_role('associado');
        
        // Log da desativação
        if (WP_DEBUG) {
            error_log('S-Invest Theme: Roles Manager desativado');
        }
    }
    
    /**
     * Função para remover roles (usar apenas se necessário)
     */
    public static function remove_custom_roles() {
        remove_role('investidor');
        remove_role('associado');
        
        // Remover capacidades customizadas do administrador
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $custom_caps = [
                'manage_investments', 'edit_investments', 'edit_others_investments',
                'publish_investments', 'read_private_investments', 'delete_investments',
                'delete_others_investments', 'manage_aportes', 'edit_aportes',
                'edit_others_aportes', 'publish_aportes', 'read_private_aportes',
                'delete_aportes', 'delete_others_aportes', 'manage_comunicados',
                'edit_comunicados', 'publish_comunicados', 'delete_comunicados',
                'manage_faq', 'edit_faq', 'publish_faq', 'delete_faq',
                'access_admin_panel', 'manage_investors', 'view_financial_reports',
                'export_data', 'view_own_investments', 'access_investor_panel',
                'download_investment_docs', 'manage_investment_categories',
                'manage_investment_system', 'configure_investment_settings',
                'access_system_reports', 'manage_user_roles'
            ];
            
            foreach ($custom_caps as $cap) {
                $admin_role->remove_cap($cap);
            }
        }
    }
    
    /**
     * Debug: Listar todas as capacidades de um usuário
     */
    public static function debug_user_capabilities($user_id = null) {
        if (!WP_DEBUG) {
            return;
        }
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        error_log("User {$user_id} ({$user->user_login}) capabilities:");
        error_log("Roles: " . implode(', ', $user->roles));
        error_log("Capabilities: " . implode(', ', array_keys($user->get_role_caps())));
    }
}

// Inicializar
S_Invest_Roles_Manager::get_instance();