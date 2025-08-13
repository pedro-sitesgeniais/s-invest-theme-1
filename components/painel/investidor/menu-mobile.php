<?php
/**
 * Menu Mobile Dedicado - SEM DUPLICAÇÕES v1.1
 * components/painel/investidor/menu-mobile.php
 * 
 * ✅ Evita componentes duplicados
 * ✅ Eventos únicos
 * ✅ Performance otimizada
 */
defined('ABSPATH') || exit;

$secao = sanitize_key($_GET['secao'] ?? 'dashboard');
$painel = sanitize_key($_GET['painel'] ?? 'investidor');
$base_url = home_url('/painel/');

// ========== MENU ITEMS ========== //
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
    ],
    [
        'key' => 'perfil',
        'label' => 'Perfil',
        'icon' => 'user-circle',
        'active' => $secao === 'perfil',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'perfil'], $base_url)
    ]
];
?>

<!-- ✅ BOTÃO TOGGLE MOBILE ÚNICO -->
<button x-data="{ 
            get isOpen() { 
                return Alpine.store('mobileMenu')?.isOpen || false; 
            },
            toggle() {
                if (Alpine.store('mobileMenu')) {
                    Alpine.store('mobileMenu').toggle();
                }
            }
        }" 
        @click="toggle()"
        class="mobile-menu-btn lg:hidden fixed top-4 left-4 z-[70] bg-primary text-white p-3 rounded-xl shadow-2xl transition-all duration-200"
        :class="{ 'bg-red-600': isOpen, 'bg-primary': !isOpen }"
        aria-label="Menu">
    
    <!-- Ícone Hamburger -->
    <svg x-show="!isOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 rotate-45"
         x-transition:enter-end="opacity-100 rotate-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 rotate-0"
         x-transition:leave-end="opacity-0 rotate-45"
         class="w-6 h-6" 
         fill="none" 
         stroke="currentColor" 
         viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
    </svg>
    
    <!-- Ícone X -->
    <svg x-show="isOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 rotate-45"
         x-transition:enter-end="opacity-100 rotate-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 rotate-0"
         x-transition:leave-end="opacity-0 rotate-45"
         class="w-6 h-6" 
         fill="none" 
         stroke="currentColor" 
         viewBox="0 0 24 24"
         style="display: none;">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
    </svg>
</button>

<!-- ✅ MENU MOBILE FULL SCREEN -->
<div x-data="{ 
        get isOpen() { 
            return Alpine.store('mobileMenu')?.isOpen || false; 
        },
        close() {
            if (Alpine.store('mobileMenu')) {
                Alpine.store('mobileMenu').close();
            }
        }
     }"
     x-show="isOpen"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="mobile-menu lg:hidden fixed inset-0 z-[60] bg-primary"
     style="display: none;">
     
    <!-- Container do menu -->
    <div class="flex flex-col h-full">
        
        <!-- ✅ HEADER DO MENU MOBILE -->
        <div class="flex items-center justify-between p-6 border-b border-white/20">
            <div class="flex items-center">
                <!-- Logo/Ícone -->
                <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center mr-3">
                    <i class="fas fa-chart-line text-white text-lg"></i>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white">
                        <?php echo esc_html(get_bloginfo('name') ?: 'S-Invest'); ?>
                    </h1>
                    <p class="text-xs text-white/70">Painel do Investidor</p>
                </div>
            </div>
            
            <!-- Botão fechar -->
            <button @click="close()" 
                    class="p-2 rounded-lg hover:bg-white/10 transition-colors text-white">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- ✅ NAVEGAÇÃO PRINCIPAL -->
        <div class="flex-1 overflow-y-auto py-6">
            <nav class="px-6 space-y-2">
                <?php foreach ($menu_items as $item): ?>
                    <a href="<?php echo esc_url($item['url']); ?>"
                       @click="close()"
                       class="mobile-menu-item flex items-center px-4 py-4 rounded-xl text-white transition-all duration-200 <?php echo $item['active'] ? 'bg-white/20 shadow-lg' : 'hover:bg-white/10'; ?>"
                       data-section="<?php echo esc_attr($item['key']); ?>">
                       
                        <!-- Ícone -->
                        <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center mr-4 flex-shrink-0">
                            <i class="fas fa-<?php echo esc_attr($item['icon']); ?> text-lg <?php echo $item['active'] ? 'text-white' : 'text-white/80'; ?>"></i>
                        </div>
                        
                        <!-- Label -->
                        <div class="flex-1">
                            <span class="text-base font-medium <?php echo $item['active'] ? 'text-white' : 'text-white/90'; ?>">
                                <?php echo esc_html($item['label']); ?>
                            </span>
                        </div>
                        
                        <!-- Indicador ativo -->
                        <?php if ($item['active']): ?>
                            <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                        <?php else: ?>
                            <i class="fas fa-chevron-right text-white/50 text-sm"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>
        
        <!-- ✅ FOOTER DO MENU -->
        <div class="p-6 border-t border-white/20">
            <!-- Logout -->
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>"
               @click="close()"
               class="flex items-center px-4 py-4 rounded-xl text-white/80 hover:text-white hover:bg-red-500/20 transition-all duration-200">
               
                <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center mr-4">
                    <i class="fas fa-sign-out-alt text-lg text-red-400"></i>
                </div>
                
                <span class="text-base font-medium">Sair da conta</span>
            </a>
            
            <!-- Info adicional -->
            <div class="mt-4 px-4 py-3 bg-white/5 rounded-lg">
                <p class="text-xs text-white/60 text-center">
                    S-Invest Mobile v2.3<br>
                    <?php echo esc_html(wp_get_current_user()->display_name); ?>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- ✅ BACKDROP MOBILE -->
<div x-data="{ 
        get isOpen() { 
            return Alpine.store('mobileMenu')?.isOpen || false; 
        },
        close() {
            if (Alpine.store('mobileMenu')) {
                Alpine.store('mobileMenu').close();
            }
        }
     }"
     x-show="isOpen"
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="close()"
     class="mobile-backdrop lg:hidden fixed inset-0 z-[50] bg-black/50"
     style="display: none;">
</div>

<!-- ✅ CSS DEDICADO PARA MOBILE -->
<style>
/* ========== MOBILE MENU STYLES ========== */
.mobile-menu-btn {
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}

.mobile-menu-btn:active {
    transform: scale(0.95);
}

.mobile-menu {
    /* Gradiente personalizado */
    background: linear-gradient(135deg, var(--color-primary, #000E35) 0%, #001A5C 50%, #000E35 100%);
    
    /* Garantir que está acima de tudo */
    z-index: 60;
    
    /* Prevenir scroll do conteúdo atrás */
    overflow: hidden;
}

.mobile-menu-item {
    /* Efeito de touch feedback */
    -webkit-tap-highlight-color: rgba(255, 255, 255, 0.1);
    
    /* Animação suave */
    transform: translateX(0);
    transition: all 0.2s ease;
}

.mobile-menu-item:active {
    transform: translateX(4px);
    background-color: rgba(255, 255, 255, 0.15) !important;
}

/* Animação de entrada dos itens */
.mobile-menu-item {
    animation: slideInLeft 0.3s ease-out forwards;
}

.mobile-menu-item:nth-child(1) { animation-delay: 0.1s; }
.mobile-menu-item:nth-child(2) { animation-delay: 0.15s; }
.mobile-menu-item:nth-child(3) { animation-delay: 0.2s; }
.mobile-menu-item:nth-child(4) { animation-delay: 0.25s; }
.mobile-menu-item:nth-child(5) { animation-delay: 0.3s; }
.mobile-menu-item:nth-child(6) { animation-delay: 0.35s; }
.mobile-menu-item:nth-child(7) { animation-delay: 0.4s; }

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Body lock quando menu aberto */
body.mobile-menu-open {
    overflow: hidden !important;
    position: fixed !important;
    width: 100% !important;
    height: 100% !important;
}

/* Esconder em desktop */
@media (min-width: 1024px) {
    .mobile-menu-btn,
    .mobile-menu,
    .mobile-backdrop {
        display: none !important;
    }
}

/* Otimizações para performance */
.mobile-menu * {
    will-change: transform;
    backface-visibility: hidden;
    perspective: 1000px;
}

/* Melhor contraste */
.mobile-menu-item.active {
    box-shadow: inset 0 0 0 2px rgba(255, 255, 255, 0.2);
}
</style>

<!-- ✅ JAVASCRIPT ÚNICO SEM DUPLICAÇÕES -->
<script>
// ✅ PREVENÇÃO DE DUPLICAÇÃO
if (!window.mobileMenuInitialized) {
    document.addEventListener('alpine:init', () => {
        // ✅ STORE MOBILE GLOBAL ÚNICO
        if (!Alpine.store('mobileMenu')) {
            Alpine.store('mobileMenu', {
                isOpen: false,
                
                open() {
                    this.isOpen = true;
                    document.body.classList.add('mobile-menu-open');
                },
                
                close() {
                    this.isOpen = false;
                    document.body.classList.remove('mobile-menu-open');
                },
                
                toggle() {
                    if (this.isOpen) {
                        this.close();
                    } else {
                        this.open();
                    }
                }
            });
        }
    });
    
    // ✅ GLOBAL LISTENERS SEM DUPLICAÇÕES
    document.addEventListener('DOMContentLoaded', function() {
        // ✅ APENAS UM EVENT LISTENER DE CADA TIPO
        
        // ESC para fechar
        if (!window.mobileMenuEscListener) {
            window.mobileMenuEscListener = (e) => {
                if (e.key === 'Escape' && window.innerWidth < 1024) {
                    if (Alpine.store('mobileMenu')?.isOpen) {
                        Alpine.store('mobileMenu').close();
                    }
                }
            };
            document.addEventListener('keydown', window.mobileMenuEscListener);
        }
        
        // Resize para fechar
        if (!window.mobileMenuResizeListener) {
            window.mobileMenuResizeListener = () => {
                if (window.innerWidth >= 1024) {
                    if (Alpine.store('mobileMenu')?.isOpen) {
                        Alpine.store('mobileMenu').close();
                    }
                }
            };
            window.addEventListener('resize', window.mobileMenuResizeListener);
        }
    });
    
    // ✅ HELPERS GLOBAIS ÚNICOS
    if (!window.mobileMenuHelpers) {
        window.mobileMenuHelpers = {
            forceClose() {
                if (Alpine.store('mobileMenu')) {
                    Alpine.store('mobileMenu').close();
                }
            },
            
            isOpen() {
                return Alpine.store('mobileMenu')?.isOpen || false;
            },
            
            debug() {
                return {
                    isOpen: Alpine.store('mobileMenu')?.isOpen,
                    bodyClasses: Array.from(document.body.classList),
                    windowWidth: window.innerWidth,
                    storeExists: !!Alpine.store('mobileMenu')
                };
            }
        };
    }
    
    // ✅ CLEANUP AUTOMÁTICO ÚNICO
    if (!window.mobileMenuCleanupListener) {
        window.mobileMenuCleanupListener = () => {
            if (Alpine.store('mobileMenu')?.isOpen) {
                document.body.classList.remove('mobile-menu-open');
            }
        };
        window.addEventListener('beforeunload', window.mobileMenuCleanupListener);
    }
    
    // Marca como inicializado
    window.mobileMenuInitialized = true;
} else {
    // Marca que já foi inicializado mas não registra no console
}
</script>