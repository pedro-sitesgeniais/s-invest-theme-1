<?php
/**
 * Template Name: Painel do Usuário (VERSÃO 2.4)
 * Descrição: Painel com menus separados
 */
defined('ABSPATH') || exit;

require_once get_template_directory() . '/inc/helpers.php';

$painel = 'investidor';
$secao = sanitize_key($_GET['secao'] ?? 'dashboard');

if (current_user_can('administrator')) {
    $painel = in_array($_GET['painel'] ?? '', ['investidor', 'associado'], true) 
        ? sanitize_key($_GET['painel']) 
        : 'investidor';
} else {
    $painel = user_has_panel_access('associado') ? 'associado' : 'investidor';
    
    if (!user_has_panel_access($painel)) {
        echo '<div class="p-6 text-red-600">Acesso negado.</div>';
        wp_footer();
        exit;
    }
}

add_filter('elementor/frontend/print_google_fonts', '__return_false');
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    
    <style id="panel-layout-styles">
        header, #wpadminbar, .elementor-kit-*, [class*="elementor"] { 
            display: none !important; 
        }

        body { 
            overflow-x: hidden; 
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f8fafc;
        }

        [x-cloak] { display: none !important; }

        :root {
            --color-primary: #000E35;
            --color-primary-800: #000722;
            --color-secondary: #44A6FF;
            --color-accent: #2072D6;
            
            --sidebar-width-expanded: 16rem;
            --sidebar-width-collapsed: 4rem;
            --sidebar-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .panel-layout {
            display: flex;
            min-height: 100vh;
            position: relative;
        }

        .main-content {
            flex: 1;
            transition: var(--sidebar-transition);
            min-height: 100vh;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            
            margin-left: var(--sidebar-width-collapsed);
            width: calc(100vw - var(--sidebar-width-collapsed));
            max-width: calc(100vw - var(--sidebar-width-collapsed));
        }

        .main-content.sidebar-expanded {
            margin-left: var(--sidebar-width-expanded);
            width: calc(100vw - var(--sidebar-width-expanded));
            max-width: calc(100vw - var(--sidebar-width-expanded));
        }

        @media (max-width: 1023px) {
            .main-content {
                margin-left: 0 !important;
                width: 100vw !important;
                max-width: 100vw !important;
                position: relative;
                z-index: 10;
            }
            
            .main-content.sidebar-expanded {
                margin-left: 0 !important;
                width: 100vw !important;
                max-width: 100vw !important;
            }
            .content-wrapper {
                position: relative;
                z-index: 1;
            }
        }

        .content-wrapper {
            padding: 2rem;
            max-width: 100%;
            margin: 0 auto;
            box-sizing: border-box;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                padding: 1rem;
                margin-top: 1rem;
            }
        }

        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .loading-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }

        .container-responsive {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        @media (min-width: 640px) {
            .container-responsive {
                padding: 0 1.5rem;
            }
        }

        @media (min-width: 1024px) {
            .container-responsive {
                padding: 0 2rem;
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in-right {
            animation: slideInRight 0.3s ease-in-out;
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .main-content,
            .desktop-sidebar,
            * {
                transition: none !important;
                animation: none !important;
            }
        }

        @media print {
            .desktop-sidebar,
            .mobile-menu-btn,
            .mobile-menu,
            .mobile-backdrop {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body class="bg-gray-50">

<?php
$mobile_menu_template = "components/painel/{$painel}/menu-bottom-tabs";
if (locate_template("{$mobile_menu_template}.php")) {
    get_template_part($mobile_menu_template);
} else {
    echo '<!-- Mobile menu template not found: ' . esc_html($mobile_menu_template) . ' -->';
}
?>

<div x-data="panelLayout()" 
     x-on:sidebar-toggled.window="handleSidebarToggle($event.detail)"
     class="panel-layout">
    
    <?php
    $desktop_menu_template = "components/painel/{$painel}/menu-{$painel}";
    if (locate_template("{$desktop_menu_template}.php")) {
        get_template_part($desktop_menu_template);
    } else {
        echo '<div class="hidden lg:block p-4 text-red-400 bg-red-100">❌ Menu desktop não encontrado: ' . esc_html($desktop_menu_template) . '</div>';
    }
    ?>
    
    <main class="main-content transition-all" 
          :class="{'sidebar-expanded': sidebarExpanded}"
          role="main">
          
        <div class="content-wrapper container-responsive">
            
            <div x-show="loading" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="loading-overlay"
                 style="display: none;">
                <div class="text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mb-4"></div>
                    <p class="text-gray-600">Carregando...</p>
                </div>
            </div>
            
            <div class="section-content">
                <div x-data="sectionLoader()" @section-loaded="onSectionLoaded()">
                    <?php
                    $valid_sections = [
                        'dashboard', 'meus-investimentos', 'meus-aportes', 
                        'produtos-gerais', 'comunicados', 'suporte', 'perfil',
                        'detalhes-investimento', 'documentos'
                    ];

                    if (in_array($secao, $valid_sections)) {
                        $template_path = "components/painel/{$painel}/{$secao}";
                        
                        if ($secao === 'documentos' && isset($_GET['id']) && absint($_GET['id'])) {
                            $template_path = "components/painel/{$painel}/secao-documentos";
                        }
                        
                        if (locate_template("{$template_path}.php")) {
                            get_template_part($template_path);
                        } else {
                            ?>
                            <div class="bg-white rounded-2xl shadow-sm p-8 text-center fade-in">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-tools text-2xl text-gray-400"></i>
                                </div>
                                <h2 class="text-xl font-semibold mb-2 text-gray-900">
                                    <?php echo ucfirst(str_replace('-', ' ', $secao)); ?>
                                </h2>
                                <p class="text-gray-600 mb-4">Esta seção está em desenvolvimento.</p>
                                <a href="<?php echo esc_url(add_query_arg('secao', 'dashboard')); ?>" 
                                   class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-800 transition-colors">
                                    <i class="fas fa-arrow-left mr-2"></i>
                                    Voltar ao Dashboard
                                </a>
                            </div>
                            <?php
                        }
                    } else {
                        ?>
                        <div class="bg-white rounded-2xl shadow-sm p-8 text-center fade-in">
                            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-exclamation-triangle text-2xl text-red-500"></i>
                            </div>
                            <h2 class="text-xl font-semibold mb-2 text-gray-900">Página não encontrada</h2>
                            <p class="text-gray-600 mb-4">A seção solicitada não existe ou não está disponível.</p>
                            <a href="<?php echo esc_url(add_query_arg('secao', 'dashboard')); ?>" 
                               class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-800 transition-colors">
                                <i class="fas fa-home mr-2"></i>
                                Ir para Dashboard
                            </a>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </main>
</div>

<?php wp_footer(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function syncDesktopSidebarLayout() {
        const desktopSidebar = document.querySelector('.desktop-sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (!desktopSidebar || !mainContent) {
            if (mainContent && window.innerWidth >= 1024) {
                mainContent.style.marginLeft = '0';
                mainContent.style.width = '100vw';
                mainContent.classList.remove('sidebar-expanded');
            }
            return;
        }
        
        function updateMainContentLayout() {
            if (window.innerWidth < 1024) {
                mainContent.style.marginLeft = '0';
                mainContent.style.width = '100vw';
                mainContent.classList.remove('sidebar-expanded');
                return;
            }
            
            const sidebarWidth = desktopSidebar.offsetWidth;
            const isExpanded = sidebarWidth > 100;
            
            if (isExpanded) {
                mainContent.style.marginLeft = '16rem';
                mainContent.style.width = 'calc(100vw - 16rem)';
                mainContent.classList.add('sidebar-expanded');
            } else {
                mainContent.style.marginLeft = '4rem';
                mainContent.style.width = 'calc(100vw - 4rem)';
                mainContent.classList.remove('sidebar-expanded');
            }
        }
        
        const observer = new MutationObserver((mutations) => {
            let shouldUpdate = false;
            
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && 
                    (mutation.attributeName === 'class' || mutation.attributeName === 'style')) {
                    shouldUpdate = true;
                }
            });
            
            if (shouldUpdate) {
                setTimeout(updateMainContentLayout, 100);
            }
        });
        
        observer.observe(desktopSidebar, { 
            attributes: true, 
            attributeFilter: ['class', 'style']
        });
        
        if ('ResizeObserver' in window) {
            const resizeObserver = new ResizeObserver((entries) => {
                for (let entry of entries) {
                    if (entry.target === desktopSidebar) {
                        updateMainContentLayout();
                    }
                }
            });
            
            resizeObserver.observe(desktopSidebar);
        }
        
        updateMainContentLayout();
    }
    
    function setupDesktopListeners() {
        window.addEventListener('sidebar-toggled', (event) => {
            setTimeout(() => {
                const mainContent = document.querySelector('.main-content');
                const expanded = event.detail?.expanded;
                
                if (window.innerWidth >= 1024 && mainContent) {
                    if (expanded) {
                        mainContent.style.marginLeft = '16rem';
                        mainContent.style.width = 'calc(100vw - 16rem)';
                        mainContent.classList.add('sidebar-expanded');
                    } else {
                        mainContent.style.marginLeft = '4rem';
                        mainContent.style.width = 'calc(100vw - 4rem)';
                        mainContent.classList.remove('sidebar-expanded');
                    }
                }
            }, 150);
        });
        
        window.addEventListener('resize', () => {
            const mainContent = document.querySelector('.main-content');
            
            if (window.innerWidth < 1024 && mainContent) {
                mainContent.style.marginLeft = '0';
                mainContent.style.width = '100vw';
                mainContent.classList.remove('sidebar-expanded');
            } else {
                setTimeout(syncDesktopSidebarLayout, 100);
            }
        });
    }
    
    function setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }
                        if (img.dataset.srcset) {
                            img.srcset = img.dataset.srcset;
                            img.removeAttribute('data-srcset');
                        }
                        img.style.opacity = '0';
                        img.onload = () => {
                            img.style.transition = 'opacity 0.3s ease';
                            img.style.opacity = '1';
                            img.classList.remove('lazy');
                            img.classList.add('loaded');
                        };
                        imageObserver.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });
            
            document.querySelectorAll('img[data-src], img.lazy').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }
    
    function setupSmoothScroll() {
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href^="#"]');
            if (link && link.getAttribute('href') !== '#') {
                const target = document.querySelector(link.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    }
    
    try {
        syncDesktopSidebarLayout();
        setupDesktopListeners();
        setupLazyLoading();
        setupSmoothScroll();
    } catch (error) {
        // Error handled silently
    }
});

document.addEventListener('alpine:initialized', () => {
    setTimeout(() => {
        const mainContent = document.querySelector('.main-content');
        const desktopSidebar = document.querySelector('.desktop-sidebar');
        
        if (mainContent && window.innerWidth >= 1024) {
            if (desktopSidebar) {
                const isExpanded = desktopSidebar.offsetWidth > 100;
                
                if (isExpanded) {
                    mainContent.style.marginLeft = '16rem';
                    mainContent.style.width = 'calc(100vw - 16rem)';
                    mainContent.classList.add('sidebar-expanded');
                } else {
                    mainContent.style.marginLeft = '4rem';
                    mainContent.style.width = 'calc(100vw - 4rem)';
                    mainContent.classList.remove('sidebar-expanded');
                }
            } else {
                mainContent.style.marginLeft = '0';
                mainContent.style.width = '100vw';
                mainContent.classList.remove('sidebar-expanded');
            }
        }
    }, 200);
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (window.mobileMenuHelpers && window.mobileMenuHelpers.isOpen()) {
            window.mobileMenuHelpers.forceClose();
        }
    }
});
</script>

</body>
</html>