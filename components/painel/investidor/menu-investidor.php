<?php
/**
 * Menu Lateral Desktop - SEM LOOPS INFINITOS v1.1
 * components/painel/investidor/menu-investidor.php
 * 
 * ✅ Evita loops de eventos
 * ✅ Performance otimizada
 * ✅ Estado consistente
 */
defined('ABSPATH') || exit;

$secao = sanitize_key($_GET['secao'] ?? 'dashboard');
$painel = sanitize_key($_GET['painel'] ?? 'investidor');
$base_url = home_url('/painel/');

// ========== LOGOS CUSTOMIZADAS ========== //
function get_custom_menu_logos() {
    static $logos = null;
    
    if ($logos !== null) {
        return $logos;
    }
    
    // MÉTODO 1: Customizer WordPress
    $customizer_expanded = get_theme_mod('s_invest_menu_logo_expanded');
    $customizer_collapsed = get_theme_mod('s_invest_menu_icon_collapsed');
    
    $logo_expanded = '';
    $icon_collapsed = '';
    
    if ($customizer_expanded) {
        $image = wp_get_attachment_image_src($customizer_expanded, 'full');
        $logo_expanded = $image ? $image[0] : '';
    }
    
    if ($customizer_collapsed) {
        $image = wp_get_attachment_image_src($customizer_collapsed, 'full');
        $icon_collapsed = $image ? $image[0] : '';
    }
    
    // MÉTODO 2: Upload direto (fallback)
    if (!$logo_expanded || !$icon_collapsed) {
        $upload_dir = wp_upload_dir();
        $menu_logos_dir = $upload_dir['baseurl'] . '/menu-logos/';
        
        if (!$logo_expanded) {
            $direct_logo = $menu_logos_dir . 'logo-expanded.png';
            if (file_exists(str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $direct_logo))) {
                $logo_expanded = $direct_logo;
            }
        }
        
        if (!$icon_collapsed) {
            $direct_icon = $menu_logos_dir . 'icon-collapsed.png';
            if (file_exists(str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $direct_icon))) {
                $icon_collapsed = $direct_icon;
            }
        }
    }
    
    // MÉTODO 3: Fallbacks do WordPress
    if (!$logo_expanded) {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $image = wp_get_attachment_image_src($custom_logo_id, 'full');
            $logo_expanded = $image ? $image[0] : '';
        }
    }
    
    if (!$icon_collapsed) {
        $favicon = get_site_icon_url(32);
        $icon_collapsed = $favicon ?: home_url('/favicon.ico');
    }
    
    $logos = [
        'expanded' => $logo_expanded,
        'collapsed' => $icon_collapsed,
        'has_expanded' => !empty($logo_expanded),
        'has_collapsed' => !empty($icon_collapsed)
    ];
    
    return $logos;
}

// Obter logos
$menu_logos = get_custom_menu_logos();

// Estrutura do menu
$menu_items = [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'icon' => 'tachometer-alt',
        'active' => $secao === 'dashboard',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'dashboard'], $base_url)
    ],
    [
        'key' => 'meus-investimentos', 
        'label' => 'Meus Investimentos',
        'icon' => 'chart-pie',
        'active' => $secao === 'meus-investimentos',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'meus-investimentos'], $base_url)
    ],
    [
        'key' => 'produtos-gerais',
        'label' => 'Produtos Gerais', 
        'icon' => 'boxes',
        'active' => $secao === 'produtos-gerais',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'produtos-gerais'], $base_url)
    ],
    // [
    //     'key' => 'documentos',
    //     'label' => 'Documentos',
    //     'icon' => 'folder-open',
    //     'active' => $secao === 'documentos',
    //     'url' => add_query_arg(['painel' => $painel, 'secao' => 'documentos'], $base_url)
    // ],
    [
        'key' => 'comunicados',
        'label' => 'Comunicados',
        'icon' => 'bullhorn', 
        'active' => $secao === 'comunicados',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'comunicados'], $base_url)
    ],
    [
        'key' => 'suporte',
        'label' => 'Suporte',
        'icon' => 'headset',
        'active' => $secao === 'suporte', 
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'suporte'], $base_url)
    ]
];

// Menu inferior
$bottom_items = [
];
?>

<!-- ✅ SIDEBAR DESKTOP SEM LOOPS -->
<nav x-data="desktopSidebar()" 
     class="desktop-sidebar hidden lg:flex fixed inset-y-0 left-0 z-40 flex-col transition-all duration-300 ease-in-out bg-gradient-to-b from-primary to-slate-950 shadow-2xl"
     :class="{
         'w-64': expanded,
         'w-16': !expanded
     }"
     @mouseenter="expandOnHover()" 
     @mouseleave="collapseOnLeave()">
     
    <!-- Content -->
    <div class="relative flex flex-col h-full text-white">
        
        <!-- ✅ HEADER DESKTOP -->
        <div class="flex items-center justify-between p-4 border-b border-white/20 min-h-[80px]">
            <!-- Logo Container -->
            <div class="flex items-center min-w-0 flex-1">
                <div class="flex-shrink-0 transition-all duration-300 flex items-center justify-center"
                     :class="expanded ? 'w-auto h-10 max-w-[160px]' : 'w-8 h-8'">
                     
                    <!-- Logo expandido CUSTOMIZADA -->
                    <?php if ($menu_logos['has_expanded']): ?>
                        <img x-show="expanded"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-50"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-50"
                             src="<?php echo esc_url($menu_logos['expanded']); ?>" 
                             alt="<?php echo esc_attr(get_bloginfo('name')); ?> - Logo Menu"
                             class="h-full w-auto object-contain"
                             style="max-height: 52px; max-width: 180px;">
                    <?php endif; ?>
                    
                    <!-- Ícone contraído CUSTOMIZADO -->
                    <?php if ($menu_logos['has_collapsed']): ?>
                        <img x-show="!expanded"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-50"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-50"
                             src="<?php echo esc_url($menu_logos['collapsed']); ?>" 
                             alt="<?php echo esc_attr(get_bloginfo('name')); ?> - Ícone Menu"
                             class="w-full h-full object-contain">
                    <?php endif; ?>
                         
                    <!-- Fallback icon -->
                    <?php if (!$menu_logos['has_expanded'] && !$menu_logos['has_collapsed']): ?>
                        <div class="w-full h-full bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-chart-line text-white" 
                               :class="expanded ? 'text-xl' : 'text-lg'"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ✅ BOTÃO TOGGLE DESKTOP -->
            <button @click="toggle()" 
                    x-show="expanded"
                    x-transition:enter="transition ease-out duration-200 delay-100"
                    x-transition:enter-start="opacity-0 scale-50"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="p-1.5 rounded-lg hover:bg-white/10 transition-colors ml-2 flex-shrink-0"
                    aria-label="Recolher menu">
                <i class="fas fa-chevron-left text-sm transition-transform duration-200"
                   :class="expanded ? 'rotate-0' : 'rotate-180'"></i>
            </button>
        </div>

        <!-- ✅ NAVEGAÇÃO PRINCIPAL DESKTOP -->
        <div class="flex-1 px-3 py-4 space-y-2 overflow-y-auto">
            <?php foreach ($menu_items as $item): ?>
                <a href="<?php echo esc_url($item['url']); ?>"
                   class="group flex items-center px-3 py-3 rounded-xl text-sm font-medium transition-all duration-200 hover:bg-white/10 relative <?php echo $item['active'] ? 'bg-white/15 text-white shadow-lg' : 'text-white/80 hover:text-white'; ?>"
                   :class="expanded ? '' : 'justify-center'"
                   title="<?php echo esc_attr($item['label']); ?>">
                   
                    <!-- Icon -->
                    <i class="fas fa-<?php echo esc_attr($item['icon']); ?> text-lg flex-shrink-0 transition-colors duration-200 <?php echo $item['active'] ? 'text-white' : 'text-white/70 group-hover:text-white'; ?>"></i>
                    
                    <!-- ✅ LABEL DESKTOP -->
                    <span x-show="expanded"
                          x-transition:enter="transition ease-out duration-200 delay-75"
                          x-transition:enter-start="opacity-0 transform translate-x-2"
                          x-transition:enter-end="opacity-100 transform translate-x-0"
                          x-transition:leave="transition ease-in duration-150"
                          x-transition:leave-start="opacity-100 transform translate-x-0"
                          x-transition:leave-end="opacity-0 transform translate-x-2"
                          class="ml-3 whitespace-nowrap">
                        <?php echo esc_html($item['label']); ?>
                    </span>
                    
                    <!-- Active indicators -->
                    <?php if ($item['active']): ?>
                        <!-- Expandido -->
                        <div class="absolute right-2 w-2 h-2 bg-white rounded-full flex-shrink-0 animate-pulse"
                             x-show="expanded"
                             x-transition:enter="transition ease-out duration-200 delay-100"
                             x-transition:enter-start="opacity-0 scale-50"
                             x-transition:enter-end="opacity-100 scale-100"></div>
                        
                        <!-- Contraído -->
                        <div x-show="!expanded" 
                             class="absolute right-1 top-1 w-2 h-2 bg-white rounded-full"></div>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <!-- ✅ NAVEGAÇÃO INFERIOR DESKTOP -->
        <div class="p-3 border-t border-white/20 space-y-2">
            <?php foreach ($bottom_items as $item): ?>
                <a href="<?php echo esc_url($item['url']); ?>"
                   class="group flex items-center px-3 py-3 rounded-xl text-sm font-medium transition-all duration-200 hover:bg-white/10 <?php echo $item['active'] ? 'bg-white/15 text-white' : 'text-white/80 hover:text-white'; ?>"
                   :class="expanded ? '' : 'justify-center'"
                   title="<?php echo esc_attr($item['label']); ?>">
                   
                    <i class="fas fa-<?php echo esc_attr($item['icon']); ?> text-lg flex-shrink-0 <?php echo $item['active'] ? 'text-white' : 'text-white/70 group-hover:text-white'; ?>"></i>
                    
                    <span x-show="expanded"
                          x-transition:enter="transition ease-out duration-200 delay-75"
                          x-transition:enter-start="opacity-0 transform translate-x-2"
                          x-transition:enter-end="opacity-100 transform translate-x-0"
                          x-transition:leave="transition ease-in duration-150"
                          x-transition:leave-start="opacity-100 transform translate-x-0"
                          x-transition:leave-end="opacity-0 transform translate-x-2"
                          class="ml-3 whitespace-nowrap">
                        <?php echo esc_html($item['label']); ?>
                    </span>
                </a>
            <?php endforeach; ?>
            
            <!-- Logout -->
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>"
               class="group flex items-center px-3 py-3 rounded-xl text-sm font-medium text-white/80 hover:text-white hover:bg-red-500/20 transition-all duration-200"
               :class="expanded ? '' : 'justify-center'"
               title="Sair da conta">
               
                <i class="fas fa-sign-out-alt text-lg flex-shrink-0 text-white/70 group-hover:text-white"></i>
                
                <span x-show="expanded"
                      x-transition:enter="transition ease-out duration-200 delay-75"
                      x-transition:enter-start="opacity-0 transform translate-x-2"
                      x-transition:enter-end="opacity-100 transform translate-x-0"
                      x-transition:leave="transition ease-in duration-150"
                      x-transition:leave-start="opacity-100 transform translate-x-0"
                      x-transition:leave-end="opacity-0 transform translate-x-2"
                      class="ml-3 whitespace-nowrap">
                    Sair
                </span>
            </a>
        </div>
    </div>
</nav>

<!-- ✅ CSS OTIMIZADO DESKTOP -->
<style>
/* ========== DESKTOP SIDEBAR STYLES ========== */
.desktop-sidebar {
    /* Sempre visível no desktop */
    transform: translateX(0) !important;
    transition: width 0.3s ease-in-out;
}

/* Mobile - completamente escondido */
@media (max-width: 1023px) {
    .desktop-sidebar {
        display: none !important;
    }
}

/* Logos responsivas */
.desktop-sidebar img {
    transition: all 0.3s ease;
}

.desktop-sidebar img[alt*="Logo Menu"] {
    max-width: 160px;
    max-height: 40px;
    width: auto;
    height: auto;
}

.desktop-sidebar img[alt*="Ícone Menu"] {
    width: 32px;
    height: 32px;
    object-fit: contain;
}

/* Otimizações de performance */
.desktop-sidebar * {
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Hover effects melhorados */
.desktop-sidebar .group:hover {
    transform: translateX(2px);
}

/* Indicadores de estado */
.desktop-sidebar .bg-white\/15 {
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.1);
}
</style>

<!-- ✅ JAVASCRIPT SEM LOOPS INFINITOS -->
<script>
// ✅ PREVENÇÃO DE COMPONENTES DUPLICADOS
if (!window.desktopSidebarInitialized) {
    document.addEventListener('alpine:init', () => {
        // ✅ COMPONENTE DESKTOP SIDEBAR SEM LOOPS
        Alpine.data('desktopSidebar', () => ({
            expanded: true,
            hoverTimeout: null,
            isDispatchingEvent: false, // ✅ PREVINE LOOPS
            
            init() {
                // ✅ RESTAURA ESTADO SALVO
                const saved = localStorage.getItem('desktop-sidebar-expanded');
                this.expanded = saved !== 'false';
                
                // ✅ WATCH CHANGE PARA SALVAR ESTADO (SEM DISPATCH)
                this.$watch('expanded', (value) => {
                    localStorage.setItem('desktop-sidebar-expanded', value);
                    
                    // ✅ DISPATCH APENAS SE NÃO ESTIVER EM LOOP
                    if (!this.isDispatchingEvent) {
                        this.isDispatchingEvent = true;
                        
                        // ✅ DEBOUNCE PARA EVITAR MÚLTIPLOS EVENTOS
                        clearTimeout(this.dispatchTimeout);
                        this.dispatchTimeout = setTimeout(() => {
                            window.dispatchEvent(new CustomEvent('sidebar-toggled', { 
                                detail: { expanded: value }
                            }));
                            
                            // Reset flag após dispatch
                            setTimeout(() => {
                                this.isDispatchingEvent = false;
                            }, 100);
                        }, 50);
                    }
                });
            },
            
            toggle() {
                // ✅ TOGGLE SIMPLES SEM DISPATCH RECURSIVO
                this.expanded = !this.expanded;
            },
            
            expandOnHover() {
                if (!this.expanded) {
                    clearTimeout(this.hoverTimeout);
                    this.hoverTimeout = setTimeout(() => {
                        this.expanded = true;
                    }, 100);
                }
            },
            
            collapseOnLeave() {
                clearTimeout(this.hoverTimeout);
                const wasExpanded = localStorage.getItem('desktop-sidebar-expanded') !== 'false';
                if (!wasExpanded) {
                    this.hoverTimeout = setTimeout(() => {
                        this.expanded = false;
                    }, 300);
                }
            },
            
            // ✅ CLEANUP
            destroy() {
                clearTimeout(this.hoverTimeout);
                clearTimeout(this.dispatchTimeout);
            }
        }));
    });
    
    // Marca como inicializado
    window.desktopSidebarInitialized = true;
} else {
    // Marca que já foi inicializado mas não registra no console
}
</script>