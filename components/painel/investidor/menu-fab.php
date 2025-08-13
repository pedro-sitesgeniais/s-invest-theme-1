<?php
/**
 * FAB (Floating Action Button) Menu - S-Invest v1.0
 * components/painel/investidor/menu-fab.php
 * 
 * ✅ Design inovador
 * ✅ Zero sobreposição
 * ✅ Experiência única
 */
defined('ABSPATH') || exit;

$secao = sanitize_key($_GET['secao'] ?? 'dashboard');
$painel = sanitize_key($_GET['painel'] ?? 'investidor');
$base_url = home_url('/painel/');

// Ações principais do FAB
$fab_actions = [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'icon' => 'tachometer-alt',
        'color' => 'bg-blue-500 hover:bg-blue-600',
        'active' => $secao === 'dashboard',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'dashboard'], $base_url)
    ],
    [
        'key' => 'meus-investimentos', 
        'label' => 'Meus Investimentos',
        'icon' => 'chart-pie',
        'color' => 'bg-green-500 hover:bg-green-600',
        'active' => $secao === 'meus-investimentos',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'meus-investimentos'], $base_url)
    ],
    [
        'key' => 'produtos-gerais',
        'label' => 'Produtos Gerais', 
        'icon' => 'boxes',
        'color' => 'bg-purple-500 hover:bg-purple-600',
        'active' => $secao === 'produtos-gerais',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'produtos-gerais'], $base_url)
    ],
    // [
    //     'key' => 'documentos',
    //     'label' => 'Documentos',
    //     'icon' => 'folder-open',
    //     'color' => 'bg-orange-500 hover:bg-orange-600',
    //     'active' => $secao === 'documentos',
    //     'url' => add_query_arg(['painel' => $painel, 'secao' => 'documentos'], $base_url)
    // ],
    [
        'key' => 'comunicados',
        'label' => 'Comunicados',
        'icon' => 'bullhorn', 
        'color' => 'bg-red-500 hover:bg-red-600',
        'active' => $secao === 'comunicados',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'comunicados'], $base_url),
        'badge' => 3 // Comunicados não lidos
    ],
    [
        'key' => 'suporte',
        'label' => 'Suporte',
        'icon' => 'headset',
        'color' => 'bg-indigo-500 hover:bg-indigo-600',
        'active' => $secao === 'suporte', 
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'suporte'], $base_url)
    ]
];

// Ações secundárias (perfil e logout)
$secondary_actions = [
    [
        'key' => 'perfil',
        'label' => 'Perfil',
        'icon' => 'user-circle',
        'color' => 'bg-gray-600 hover:bg-gray-700',
        'active' => $secao === 'perfil',
        'url' => add_query_arg(['painel' => $painel, 'secao' => 'perfil'], $base_url)
    ],
    [
        'key' => 'logout',
        'label' => 'Sair',
        'icon' => 'sign-out-alt',
        'color' => 'bg-red-600 hover:bg-red-700',
        'active' => false,
        'url' => wp_logout_url(home_url()),
        'is_logout' => true
    ]
];

// Detectar seção ativa para highlights
$has_active_section = false;
foreach (array_merge($fab_actions, $secondary_actions) as $action) {
    if ($action['active']) {
        $has_active_section = true;
        break;
    }
}
?>

<!-- ✅ FAB MENU CONTAINER -->
<div x-data="fabMenu()" 
     class="lg:hidden fixed bottom-6 right-6 z-50"
     @click.away="closeFab()">
     
    <!-- ✅ BACKDROP QUANDO ABERTO -->
    <div x-show="isOpen"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeFab()"
         class="fixed inset-0 bg-black/30 backdrop-blur-sm -z-10"
         style="display: none;">
    </div>
    
    <!-- ✅ ITENS DO FAB (Aparecem quando expandido) -->
    <div class="relative">
        
        <!-- Ações Principais -->
        <div class="absolute bottom-20 right-0 flex flex-col-reverse items-end space-y-reverse space-y-3">
            <?php foreach ($fab_actions as $index => $action): ?>
                <div x-show="isOpen"
                     x-transition:enter="transition-all ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform translate-x-8 translate-y-2 scale-75"
                     x-transition:enter-end="opacity-100 transform translate-x-0 translate-y-0 scale-100"
                     x-transition:leave="transition-all ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform translate-x-0 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 transform translate-x-8 translate-y-2 scale-75"
                     style="transition-delay: <?php echo $index * 50; ?>ms; display: none;"
                     class="flex items-center group">
                     
                    <!-- Label do item -->
                    <div class="mr-4 bg-white px-3 py-2 rounded-lg shadow-lg border border-gray-200 opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-700">
                            <?php echo esc_html($action['label']); ?>
                        </span>
                    </div>
                    
                    <!-- Botão do item -->
                    <a href="<?php echo esc_url($action['url']); ?>"
                       @click="navigateAndClose($event)"
                       class="relative w-12 h-12 rounded-full <?php echo esc_attr($action['color']); ?> text-white shadow-lg flex items-center justify-center transition-all duration-200 transform hover:scale-110 active:scale-95 <?php echo $action['active'] ? 'ring-4 ring-white ring-opacity-50' : ''; ?>"
                       data-section="<?php echo esc_attr($action['key']); ?>"
                       title="<?php echo esc_attr($action['label']); ?>">
                       
                        <i class="fas fa-<?php echo esc_attr($action['icon']); ?> text-lg"></i>
                        
                        <!-- Badge para notificações -->
                        <?php if (isset($action['badge']) && $action['badge']): ?>
                            <div class="absolute -top-1 -right-1 bg-yellow-400 text-yellow-900 text-xs rounded-full min-w-[18px] h-5 flex items-center justify-center px-1 font-bold border-2 border-white animate-pulse">
                                <?php echo $action['badge']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Indicador de seção ativa -->
                        <?php if ($action['active']): ?>
                            <div class="absolute inset-0 rounded-full bg-white/20 animate-ping"></div>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Ações Secundárias (Perfil e Logout) -->
        <div class="absolute bottom-20 -left-16 flex flex-col-reverse items-start space-y-reverse space-y-3">
            <?php foreach ($secondary_actions as $index => $action): ?>
                <div x-show="isOpen"
                     x-transition:enter="transition-all ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform -translate-x-8 translate-y-2 scale-75"
                     x-transition:enter-end="opacity-100 transform translate-x-0 translate-y-0 scale-100"
                     x-transition:leave="transition-all ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform translate-x-0 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 transform -translate-x-8 translate-y-2 scale-75"
                     style="transition-delay: <?php echo ($index + 3) * 50; ?>ms; display: none;"
                     class="flex items-center group">
                     
                    <!-- Botão do item -->
                    <a href="<?php echo esc_url($action['url']); ?>"
                       @click="<?php echo isset($action['is_logout']) ? 'handleLogout($event)' : 'navigateAndClose($event)'; ?>"
                       class="relative w-10 h-10 rounded-full <?php echo esc_attr($action['color']); ?> text-white shadow-lg flex items-center justify-center transition-all duration-200 transform hover:scale-110 active:scale-95 <?php echo $action['active'] ? 'ring-4 ring-white ring-opacity-50' : ''; ?>"
                       data-section="<?php echo esc_attr($action['key']); ?>"
                       title="<?php echo esc_attr($action['label']); ?>">
                       
                        <i class="fas fa-<?php echo esc_attr($action['icon']); ?>"></i>
                        
                        <?php if ($action['active']): ?>
                            <div class="absolute inset-0 rounded-full bg-white/20 animate-ping"></div>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Label do item -->
                    <div class="ml-4 bg-white px-3 py-2 rounded-lg shadow-lg border border-gray-200 opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-700">
                            <?php echo esc_html($action['label']); ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- ✅ BOTÃO PRINCIPAL FAB -->
        <button @click="toggleFab()"
                class="w-16 h-16 bg-gradient-to-r from-primary to-blue-600 text-white rounded-full shadow-xl flex items-center justify-center transition-all duration-300 transform hover:scale-105 active:scale-95 relative overflow-hidden"
                :class="{ 
                    'rotate-45': isOpen,
                    'shadow-2xl': isOpen,
                    'ring-4 ring-primary/20': isOpen 
                }"
                aria-label="Menu de navegação">
                
            <!-- Ícone principal (muda conforme estado) -->
            <div class="relative">
                <!-- Ícone fechado -->
                <i x-show="!isOpen" 
                   x-transition:enter="transition-all ease-out duration-200 delay-100"
                   x-transition:enter-start="opacity-0 rotate-45 scale-75"
                   x-transition:enter-end="opacity-100 rotate-0 scale-100"
                   x-transition:leave="transition-all ease-in duration-200"
                   x-transition:leave-start="opacity-100 rotate-0 scale-100"
                   x-transition:leave-end="opacity-0 rotate-45 scale-75"
                   class="fas fa-bars text-2xl"></i>
                   
                <!-- Ícone aberto -->
                <i x-show="isOpen" 
                   x-transition:enter="transition-all ease-out duration-200 delay-100"
                   x-transition:enter-start="opacity-0 -rotate-45 scale-75"
                   x-transition:enter-end="opacity-100 rotate-0 scale-100"
                   x-transition:leave="transition-all ease-in duration-200"
                   x-transition:leave-start="opacity-100 rotate-0 scale-100"
                   x-transition:leave-end="opacity-0 -rotate-45 scale-75"
                   class="fas fa-times text-2xl"
                   style="display: none;"></i>
            </div>
            
            <!-- Indicador de seção ativa -->
            <?php if ($has_active_section): ?>
                <div class="absolute top-2 right-2 w-3 h-3 bg-green-400 rounded-full border-2 border-white animate-pulse"></div>
            <?php endif; ?>
            
            <!-- Efeito ripple -->
            <div x-show="isOpen" 
                 class="absolute inset-0 bg-white/10 rounded-full animate-ping"></div>
        </button>
        
        <!-- ✅ INDICADOR DE TOQUE -->
        <div x-show="!hasInteracted && !isOpen"
             x-transition:enter="transition-opacity ease-out duration-500"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute -top-2 -right-2 w-6 h-6 bg-yellow-400 rounded-full animate-bounce flex items-center justify-center text-yellow-900">
            <i class="fas fa-hand-pointer text-xs"></i>
        </div>
    </div>
</div>

<!-- ✅ FAB SHORTCUTS (Quick actions sempre visíveis) -->
<div class="lg:hidden fixed bottom-6 left-6 z-40 flex flex-col space-y-3">
    <!-- Quick Action: Investir -->
    <a href="<?php echo esc_url(add_query_arg(['painel' => $painel, 'secao' => 'produtos-gerais'], $base_url)); ?>"
       class="w-12 h-12 bg-green-500 hover:bg-green-600 text-white rounded-full shadow-lg flex items-center justify-center transition-all duration-200 transform hover:scale-110 active:scale-95"
       title="Investir Agora">
        <i class="fas fa-plus text-lg"></i>
    </a>
    
    <!-- Quick Action: Buscar -->
    <button @click="showSearchModal = true"
            x-data="{ showSearchModal: false }"
            class="w-12 h-12 bg-blue-500 hover:bg-blue-600 text-white rounded-full shadow-lg flex items-center justify-center transition-all duration-200 transform hover:scale-110 active:scale-95"
            title="Buscar">
        <i class="fas fa-search text-lg"></i>
    </button>
</div>

<!-- ✅ CSS CUSTOMIZADO -->
<style>
/* FAB específico */
.fab-container {
    -webkit-tap-highlight-color: transparent;
    user-select: none;
}

/* Animações customizadas */
@keyframes fabPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.fab-pulse {
    animation: fabPulse 2s infinite;
}

/* Efeitos de hover melhorados */
.fab-item:hover {
    transform: scale(1.1) !important;
    filter: brightness(1.1);
}

/* Sombras dinâmicas */
.fab-shadow-dynamic {
    box-shadow: 
        0 10px 25px rgba(0, 0, 0, 0.15),
        0 4px 10px rgba(0, 0, 0, 0.1);
}

/* Estados ativos melhorados */
.fab-active {
    transform: scale(1.1);
    box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.3);
}

/* Ripple effect customizado */
@keyframes ripple {
    0% {
        transform: scale(0);
        opacity: 1;
    }
    100% {
        transform: scale(4);
        opacity: 0;
    }
}

.fab-ripple {
    animation: ripple 0.6s linear;
}

/* Responsividade */
@media (max-height: 600px) {
    .fab-container {
        bottom: 1rem;
        right: 1rem;
    }
}

/* Safe area para iPhones */
@supports (bottom: env(safe-area-inset-bottom)) {
    .fab-container {
        bottom: calc(1.5rem + env(safe-area-inset-bottom));
    }
}
</style>

<!-- ✅ JAVASCRIPT INTEGRADO -->
<script>
// ✅ PREVENÇÃO DE DUPLICAÇÃO
if (!window.fabMenuInitialized) {
    document.addEventListener('alpine:init', () => {
        // Store global para FAB
        Alpine.store('fabMenu', {
            isOpen: false,
            hasInteracted: localStorage.getItem('fab-interacted') === 'true',
            activeSection: '<?php echo esc_js($secao); ?>',
            
            toggle() {
                this.isOpen = !this.isOpen;
                if (!this.hasInteracted) {
                    this.hasInteracted = true;
                    localStorage.setItem('fab-interacted', 'true');
                }
            },
            
            open() {
                this.isOpen = true;
                if (!this.hasInteracted) {
                    this.hasInteracted = true;
                    localStorage.setItem('fab-interacted', 'true');
                }
            },
            
            close() {
                this.isOpen = false;
            },
            
            setActiveSection(section) {
                this.activeSection = section;
                this.close();
            }
        });
        
        // Componente do FAB
        Alpine.data('fabMenu', () => ({
            get isOpen() {
                return Alpine.store('fabMenu').isOpen;
            },
            
            get hasInteracted() {
                return Alpine.store('fabMenu').hasInteracted;
            },
            
            toggleFab() {
                Alpine.store('fabMenu').toggle();
                
                // Haptic feedback (se suportado)
                if ('vibrate' in navigator) {
                    navigator.vibrate(50);
                }
            },
            
            closeFab() {
                Alpine.store('fabMenu').close();
            },
            
            navigateAndClose(event) {
                // Adiciona pequeno delay para mostrar feedback visual
                const target = event.currentTarget;
                target.classList.add('fab-active');
                
                setTimeout(() => {
                    this.closeFab();
                    // Deixa o navegador seguir o link naturalmente
                }, 150);
            },
            
            handleLogout(event) {
                event.preventDefault();
                
                if (confirm('Tem certeza que deseja sair da sua conta?')) {
                    this.closeFab();
                    window.location.href = event.currentTarget.href;
                }
            },
            
            init() {
                // Auto-close após inatividade
                let inactivityTimeout;
                
                this.$watch('isOpen', (isOpen) => {
                    if (isOpen) {
                        // Fecha automaticamente após 10 segundos sem interação
                        inactivityTimeout = setTimeout(() => {
                            this.closeFab();
                        }, 10000);
                    } else {
                        clearTimeout(inactivityTimeout);
                    }
                });
                
                // Gesture: swipe up para abrir FAB
                let startY = 0;
                
                document.addEventListener('touchstart', (e) => {
                    startY = e.touches[0].clientY;
                }, { passive: true });
                
                document.addEventListener('touchend', (e) => {
                    if (!this.isOpen) {
                        const endY = e.changedTouches[0].clientY;
                        const diffY = startY - endY;
                        
                        // Swipe up de pelo menos 100px
                        if (diffY > 100 && startY > window.innerHeight * 0.7) {
                            this.toggleFab();
                        }
                    }
                }, { passive: true });
                
                // Adiciona classe para styling
                document.body.classList.add('has-fab-menu');
            },
            
            destroy() {
                document.body.classList.remove('has-fab-menu');
            }
        }));
    });
    
    // Event listeners globais
    document.addEventListener('DOMContentLoaded', () => {
        // ESC para fechar
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && window.innerWidth < 1024) {
                if (Alpine.store('fabMenu')?.isOpen) {
                    Alpine.store('fabMenu').close();
                }
            }
        });
        
        // Fecha FAB quando navega
        window.addEventListener('beforeunload', () => {
            if (Alpine.store('fabMenu')?.isOpen) {
                Alpine.store('fabMenu').close();
            }
        });
        
        // Fecha ao redimensionar para desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                if (Alpine.store('fabMenu')?.isOpen) {
                    Alpine.store('fabMenu').close();
                }
            }
        });
    });
    
    // Helpers globais
    window.fabMenuHelpers = {
        openFab() {
            if (Alpine.store('fabMenu')) {
                Alpine.store('fabMenu').open();
            }
        },
        
        closeFab() {
            if (Alpine.store('fabMenu')) {
                Alpine.store('fabMenu').close();
            }
        },
        
        isOpen() {
            return Alpine.store('fabMenu')?.isOpen || false;
        },
        
        debug() {
            return {
                isOpen: Alpine.store('fabMenu')?.isOpen,
                hasInteracted: Alpine.store('fabMenu')?.hasInteracted,
                activeSection: Alpine.store('fabMenu')?.activeSection,
                windowWidth: window.innerWidth
            };
        }
    };
    
    window.fabMenuInitialized = true;
}
</script>