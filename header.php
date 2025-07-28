<?php
/**
 * Header Refinado - S-Invest Theme v2.1
 * Compatível com Elementor Pro 3.29.2 Theme Builder
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    
    <?php wp_head(); ?>
    
    <style id="s-invest-critical">
        body.elementor-page,
        body.elementor-default,
        .elementor-page,
        .elementor-kit-* {
            margin: 0 !important;
            padding: 0 !important;
            border: none !important;
        }
        
        body.elementor-page #page,
        body.elementor-page .site,
        body.elementor-page .site-main,
        body.elementor-page #primary,
        body.elementor-page #content,
        body.elementor-page .site-content,
        body.elementor-page .content-area,
        .elementor-section-wrap {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            max-width: none !important;
        }
        
        .elementor-location-header,
        .elementor-location-header *,
        header.elementor-location-header {
            all: revert !important;
        }
        
        [x-cloak] { display: none !important; }
        
        body { 
            margin: 0; 
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            line-height: 1.6;
        }
        
        #global-preloader {
            position: fixed;
            inset: 0;
            z-index: 999999;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease-out;
        }
        
        #global-preloader.fade-out {
            opacity: 0;
            pointer-events: none;
        }
        
        .s-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f1f5f9;
            border-top: 4px solid #000E35;
            border-radius: 50%;
            animation: s-spin 1s linear infinite;
        }
        
        @keyframes s-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        #global-progress-bar {
            width: 200px;
            height: 4px;
            background: #f1f5f9;
            border-radius: 2px;
            margin-top: 20px;
            overflow: hidden;
        }
        
        #global-progress-bar > div {
            height: 100%;
            background: linear-gradient(90deg, #000E35, #44A6FF);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        #s-invest-fallback-header {
            background: #000E35;
            color: white;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        #s-invest-fallback-header a {
            color: white;
            text-decoration: none;
            font-size: 1.5rem;
            font-weight: 700;
        }
        
        #s-invest-fallback-header .custom-logo img {
            max-height: 60px;
            width: auto;
        }
        
        .elementor-theme-builder-header {
            position: relative !important;
            z-index: 999 !important;
        }
        
        body.elementor-page #s-invest-fallback-header {
            display: none !important;
        }
    </style>
</head>
<body <?php body_class(); ?>>

<?php
if (function_exists('wp_body_open')) {
    wp_body_open();
}
?>

<?php if (s_invest_should_show_preloader()): ?>
<div id="global-preloader">
    <div style="text-align: center;">
        <div class="s-spinner"></div>
        <div id="global-progress-bar">
            <div></div>
        </div>
        <p style="margin-top: 15px; color: #6b7280; font-size: 14px;">
            Carregando...
        </p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const preloader = document.getElementById('global-preloader');
    const progressBar = document.querySelector('#global-progress-bar > div');
    
    if (!preloader || !progressBar) return;
    
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress > 100) progress = 100;
        
        progressBar.style.width = progress + '%';
        
        if (progress >= 100) {
            clearInterval(interval);
            setTimeout(() => {
                preloader.classList.add('fade-out');
                setTimeout(() => {
                    preloader.remove();
                }, 500);
            }, 200);
        }
    }, 100);
    
    setTimeout(() => {
        clearInterval(interval);
        if (preloader) {
            preloader.classList.add('fade-out');
            setTimeout(() => preloader.remove(), 500);
        }
    }, 3000);
});
</script>
<?php endif; ?>

<?php
if (s_invest_render_elementor_header()) {
    // Elementor Theme Builder renderizado com sucesso
} else {
    s_invest_render_fallback_header();
}
?>

<?php
function s_invest_should_show_preloader() {
    if (is_admin() || isset($_GET['elementor-preview'])) {
        return false;
    }
    
    $show_pages = ['acessar', 'painel'];
    
    if (is_front_page()) {
        return true;
    }
    
    if (is_page($show_pages)) {
        return true;
    }
    
    return false;
}

function s_invest_render_elementor_header() {
    if (!class_exists('ElementorPro\Plugin')) {
        return false;
    }
    
    if (!class_exists('ElementorPro\Modules\ThemeBuilder\Module')) {
        return false;
    }
    
    try {
        $theme_builder = \ElementorPro\Modules\ThemeBuilder\Module::instance();
        
        if (!$theme_builder) {
            return false;
        }
        
        $header_content = $theme_builder->get_locations_manager()->do_location('header');
        
        if (empty($header_content)) {
            return false;
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log('S-Invest Theme: Erro ao renderizar header Elementor - ' . $e->getMessage());
        return false;
    }
}

function s_invest_render_fallback_header() {
    ?>
    <header id="s-invest-fallback-header" role="banner">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
            <?php if (has_custom_logo()): ?>
                <div class="custom-logo">
                    <?php the_custom_logo(); ?>
                </div>
            <?php else: ?>
                <a href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                    <?php bloginfo('name'); ?>
                </a>
            <?php endif; ?>
        </div>
    </header>
    <?php
}

if (WP_DEBUG && current_user_can('administrator')) {
    add_action('wp_footer', function() {
        $elementor_pro_active = class_exists('ElementorPro\Plugin');
        $theme_builder_active = class_exists('ElementorPro\Modules\ThemeBuilder\Module');
        
        echo '<!-- S-Invest Header Debug: 
        Elementor Pro: ' . ($elementor_pro_active ? 'ATIVO' : 'INATIVO') . '
        Theme Builder: ' . ($theme_builder_active ? 'ATIVO' : 'INATIVO') . '
        -->';
    });
}
?>