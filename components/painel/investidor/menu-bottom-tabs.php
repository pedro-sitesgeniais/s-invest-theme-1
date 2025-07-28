<?php
/**
 * Bottom Tab Bar Mobile - S-Invest v1.0
 * components/painel/investidor/menu-bottom-tabs.php
 * 
 * ✅ Substitui o menu mobile atual
 * ✅ Zero sobreposição
 * ✅ Padrão mobile moderno
 */
defined('ABSPATH') || exit;

$secao = sanitize_key($_GET['secao'] ?? 'dashboard');
$painel = sanitize_key($_GET['painel'] ?? 'investidor');
$base_url = home_url('/painel/');

// Menu items principais (os mais usados ficam na tab bar)
$tab_items = [
    [
        'key' => 'dashboard',
        'label' => 'Home',
        'icon' => 'tachometer-alt',
        'active' => $secao === 'dashboard',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'dashboard'], $base_url),
        'badge' => null
    ],
    [
        'key' => 'meus-investimentos', 
        'label' => 'Investimentos',
        'icon' => 'chart-pie',
        'active' => $secao === 'meus-investimentos',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'meus-investimentos'], $base_url),
        'badge' => null
    ],
    [
        'key' => 'produtos-gerais',
        'label' => 'Produtos', 
        'icon' => 'boxes',
        'active' => $secao === 'produtos-gerais',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'produtos-gerais'], $base_url),
        'badge' => null
    ],
    [
        'key' => 'documentos',
        'label' => 'Docs',
        'icon' => 'folder-open',
        'active' => $secao === 'documentos',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'documentos'], $base_url),
        'badge' => null
    ],
    [
        'key' => 'more',
        'label' => 'Mais',
        'icon' => 'ellipsis-h',
        'active' => in_array($secao, ['comunicados', 'suporte']),
        'url' => '#',
        'badge' => null,
        'is_menu' => true
    ]
];

// Itens do menu "Mais"
$more_items = [
    [
        'key' => 'comunicados',
        'label' => 'Comunicados',
        'icon' => 'bullhorn', 
        'active' => $secao === 'comunicados',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'comunicados'], $base_url),
        'badge' => null
    ],
    [
        'key' => 'suporte',
        'label' => 'Suporte',
        'icon' => 'headset',
        'active' => $secao === 'suporte', 
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'suporte'], $base_url),
        'badge' => null
    ]
    // [
    //     'key' => 'perfil',
    //     'label' => 'Perfil',
    //     'icon' => 'user-circle',
    //     'active' => $secao === 'perfil',
    //     'url' => add_query_arg(['painel' => $painel, 'secao' => 'perfil'], $base_url),
    //     'badge' => null
    // ]
];
error_log('More menu debug - secao: ' . $secao . ', moreMenuOpen should be: false');
?>

<!-- ✅ BOTTOM TAB BAR CONTAINER -->
<div x-data="bottomTabBar()" 
     class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 safe-area-bottom">
     
    <!-- Tab Bar Principal -->
    <div class="flex items-center justify-around px-2 py-2 bg-white">
        <?php foreach ($tab_items as $index => $item): ?>
            <?php if ($item['is_menu'] ?? false): ?>
                <!-- Botão "Mais" com menu popup -->
                <button @click="toggleMoreMenu()" 
                        class="relative flex flex-col items-center justify-center p-2 min-w-0 flex-1 transition-all duration-200 <?php echo $item['active'] ? 'text-primary' : 'text-gray-500'; ?>"
                        :class="{ 'text-primary': moreMenuOpen, 'text-gray-500': !moreMenuOpen }">
                        
                    <!-- Ícone -->
                    <div class="relative">
                        <i class="fas fa-<?php echo esc_attr($item['icon']); ?> text-lg mb-1 transition-transform duration-200"
                           :class="moreMenuOpen ? 'rotate-90' : ''"></i>
                           
                        <!-- Badge para seções ativas -->
                        <?php if ($item['active']): ?>
                            <div class="absolute -top-1 -right-1 w-2 h-2 bg-primary rounded-full animate-pulse"></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Label -->
                    <span class="text-xs font-medium truncate w-full text-center leading-tight">
                        <?php echo esc_html($item['label']); ?>
                    </span>
                </button>
            <?php else: ?>
                <!-- Tab normal -->
                <a href="<?php echo esc_url($item['url']); ?>"
                   @click="closeMoreMenu()"
                   class="relative flex flex-col items-center justify-center p-2 min-w-0 flex-1 transition-all duration-200 group <?php echo $item['active'] ? 'text-primary' : 'text-gray-500 hover:text-gray-700'; ?>"
                   data-section="<?php echo esc_attr($item['key']); ?>">
                   
                    <!-- Ícone com efeito de escala -->
                    <div class="relative transform transition-transform duration-200 group-active:scale-90">
                        <i class="fas fa-<?php echo esc_attr($item['icon']); ?> text-lg mb-1 transition-colors duration-200"></i>
                        
                        <!-- Badge -->
                        <?php if ($item['badge']): ?>
                            <?php if (is_numeric($item['badge'])): ?>
                                <div class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full min-w-[16px] h-4 flex items-center justify-center px-1 font-bold">
                                    <?php echo $item['badge']; ?>
                                </div>
                            <?php else: ?>
                                <div class="absolute -top-1 -right-1 bg-green-500 text-white text-xs rounded-full px-1.5 py-0.5 font-bold leading-none">
                                    <?php echo strtoupper($item['badge']); ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Indicador ativo -->
                        <?php if ($item['active']): ?>
                            <div class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-primary rounded-full animate-pulse"></div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Label -->
                    <span class="text-xs font-medium truncate w-full text-center leading-tight">
                        <?php echo esc_html($item['label']); ?>
                    </span>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    
    <!-- Menu "Mais" Popup -->
    <div x-show="moreMenuOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform translate-y-2"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform translate-y-0"
         x-transition:leave-end="opacity-0 transform translate-y-2"
         @click.away="closeMoreMenu()"
         class="absolute bottom-full right-4 mb-2 bg-white rounded-2xl shadow-xl border border-gray-200 min-w-[200px] overflow-hidden"
         style="display: none;">
         
        <!-- Header do popup -->
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">Menu</h3>
                <button @click="closeMoreMenu()" 
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        </div>
        
        <!-- Itens do menu -->
        <div class="py-2">
            <?php foreach ($more_items as $item): ?>
                <a href="<?php echo esc_url($item['url']); ?>"
                   @click="closeMoreMenu()"
                   class="flex items-center px-4 py-3 hover:bg-gray-50 transition-colors group <?php echo $item['active'] ? 'bg-primary/5 text-primary' : 'text-gray-700'; ?>"
                   data-section="<?php echo esc_attr($item['key']); ?>">
                   
                    <!-- Ícone -->
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center mr-3 group-hover:bg-gray-200 transition-colors <?php echo $item['active'] ? 'bg-primary/10' : ''; ?>">
                        <i class="fas fa-<?php echo esc_attr($item['icon']); ?> text-sm <?php echo $item['active'] ? 'text-primary' : 'text-gray-600'; ?>"></i>
                    </div>
                    
                    <!-- Label e Badge -->
                    <div class="flex-1 flex items-center justify-between">
                        <span class="text-sm font-medium">
                            <?php echo esc_html($item['label']); ?>
                        </span>
                        
                        <?php if ($item['badge']): ?>
                            <span class="bg-red-500 text-white text-xs rounded-full min-w-[18px] h-5 flex items-center justify-center px-1.5 font-bold ml-2">
                                <?php echo $item['badge']; ?>
                            </span>
                        <?php endif; ?>
                        
                        <?php if ($item['active']): ?>
                            <i class="fas fa-check text-primary text-sm ml-2"></i>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Footer com logout -->
        <div class="border-t border-gray-100 px-4 py-3">
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>"
               class="flex items-center text-red-600 hover:text-red-700 transition-colors">
                <div class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center mr-3">
                    <i class="fas fa-sign-out-alt text-sm"></i>
                </div>
                <span class="text-sm font-medium">Sair da conta</span>
            </a>
        </div>
    </div>
</div>

<!-- ✅ BACKDROP SIMPLIFICADO -->
<div x-data="bottomTabBar()"
     x-show="moreMenuOpen"
     x-transition:enter="transition-opacity ease-linear duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="closeMoreMenu()"
     class="lg:hidden fixed inset-0 bg-black/20 z-40"
     style="display: none;">
</div>

<!-- ✅ ESPAÇAMENTO PARA O CONTEÚDO -->
<style>
/* Adiciona padding-bottom para o conteúdo não ficar atrás da tab bar */
@media (max-width: 1023px) {
    .main-content-mobile {
        padding-bottom: 90px !important;
        padding-top: 20px !important; /* Ajuste para espaçamento superior */
    }
    
    /* Safe area para iPhones com notch */
    .safe-area-bottom {
        padding-bottom: env(safe-area-inset-bottom);
    }
}

/* Animações customizadas */
.bottom-tab-bar {
    backdrop-filter: blur(20px);
    background: rgba(255, 255, 255, 0.95);
    border-top: 1px solid rgba(0, 0, 0, 0.06);
}

/* Efeito ripple ao tocar */
.bottom-tab-bar a:active,
.bottom-tab-bar button:active {
    background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 50%);
}

/* Sombra suave */
.bottom-tab-bar {
    box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.1);
}

/* Estados dos ícones */
.bottom-tab-bar .text-primary i {
    transform: scale(1.1);
}

/* Animação do badge */
@keyframes badgePulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.bottom-tab-bar .badge-animate {
    animation: badgePulse 2s infinite;
}
</style>

<!-- ✅ JAVASCRIPT INTEGRADO -->
<script>
// ✅ PREVENÇÃO DE DUPLICAÇÃO
if (!window.bottomTabBarInitialized) {
    document.addEventListener('alpine:init', () => {
        // Store global para tab bar
        Alpine.store('bottomTabs', {
            moreMenuOpen: false,
            activeSection: '<?php echo esc_js($secao); ?>',
            
            toggleMoreMenu() {
                this.moreMenuOpen = !this.moreMenuOpen;
            },
            
            openMoreMenu() {
                this.moreMenuOpen = true;
            },
            
            closeMoreMenu() {
                this.moreMenuOpen = false;
            },
            
            setActiveSection(section) {
                this.activeSection = section;
                this.closeMoreMenu();
            }
        });
        
        // Componente da tab bar
        Alpine.data('bottomTabBar', () => ({
            get moreMenuOpen() {
                return Alpine.store('bottomTabs').moreMenuOpen;
            },
            
            toggleMoreMenu() {
                Alpine.store('bottomTabs').toggleMoreMenu();
            },
            
            closeMoreMenu() {
                Alpine.store('bottomTabs').closeMoreMenu();
            },
            
            init() {
                // Fecha menu ao navegar
                this.$el.addEventListener('click', (e) => {
                    if (e.target.closest('a:not([href="#"])')) {
                        this.closeMoreMenu();
                    }
                });
                Alpine.store('bottomTabs').closeMoreMenu();
                // Adiciona classe ao body para ajustar conteúdo
                document.body.classList.add('has-bottom-tabs');
            },
            
            destroy() {
                document.body.classList.remove('has-bottom-tabs');
            }
        }));
    });
    
    // ESC para fechar menu mais
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && window.innerWidth < 1024) {
            if (Alpine.store('bottomTabs')?.moreMenuOpen) {
                Alpine.store('bottomTabs').closeMoreMenu();
            }
        }
    });
    
    // Fecha menu ao redimensionar para desktop
    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1024) {
            if (Alpine.store('bottomTabs')?.moreMenuOpen) {
                Alpine.store('bottomTabs').closeMoreMenu();
            }
        }
    });
    // Reset forçado quando a página carrega
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            if (Alpine.store('bottomTabs')) {
                Alpine.store('bottomTabs').moreMenuOpen = false;
            }
        }, 100);
    });
    window.bottomTabBarInitialized = true;
}
</script>
