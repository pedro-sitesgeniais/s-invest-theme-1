<?php
/**
 * Footer otimizado - S-Invest Theme v2.0
 */

// Renderização do footer Elementor
if (class_exists('Elementor\Plugin')) {
    $builder = \Elementor\Plugin::instance()->theme_builder ?? null;

    if ($builder && method_exists($builder, 'render_location')) {
        $builder->render_location('footer');
    } else {
        ?>
        <footer id="site-footer" class="bg-white mt-10 py-6 text-center text-sm text-gray-500">
            <div class="max-w-7xl mx-auto px-4">
                <p>&copy; <?php echo date_i18n('Y'); ?> – Plataforma S‑Invest</p>
            </div>
        </footer>
        <?php
    }
} else {
    ?>
    <footer id="site-footer" class="bg-white mt-10 py-6 text-center text-sm text-gray-500">
        <div class="max-w-7xl mx-auto px-4">
            <p>&copy; <?php echo date_i18n('Y'); ?> – Plataforma S‑Invest</p>
        </div>
    </footer>
    <?php
}

wp_footer();
?>

<script>
(() => {
    'use strict';
    
    window.sInvestConfig = window.sInvestConfig || {
        isLoaded: false,
        ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
        nonce: '<?php echo wp_create_nonce('s_invest_global'); ?>'
    };
    
    <?php if (!is_admin() && !isset($_GET['elementor-preview']) && (is_front_page() || is_page('acessar'))): ?>
    const initPreloader = () => {
        const preloader = document.getElementById('global-preloader');
        const progressBar = document.querySelector('#global-progress-bar > div');
        
        if (!preloader || !progressBar) return;
        
        let progress = 0;
        const interval = setInterval(() => {
            if (progress < 85) { 
                progress += Math.random() * 10; 
                progressBar.style.width = Math.min(progress, 85) + '%'; 
            }
        }, 150);
        
        const finishPreloader = () => {
            clearInterval(interval);
            progressBar.style.width = '100%';
            setTimeout(() => {
                preloader.style.opacity = '0';
                setTimeout(() => preloader.remove(), 500);
            }, 300);
        };
        
        if (window.alpineInitialized || typeof Alpine !== 'undefined') {
            finishPreloader();
        } else {
            document.addEventListener('alpine:init', finishPreloader);
            window.addEventListener('load', finishPreloader);
            setTimeout(finishPreloader, 3000);
        }
    };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPreloader);
    } else {
        initPreloader();
    }
    <?php endif; ?>
    
    document.addEventListener('alpine:init', () => {
        if (!window.sInvestConfig.isLoaded) {
            window.sInvestConfig.isLoaded = true;
        }
    });
})();
</script>

<noscript>
    <style>
        [x-cloak] { display: block !important; }
        #global-preloader { display: none !important; }
    </style>
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 m-4 rounded">
        <strong>JavaScript está desabilitado.</strong> Algumas funcionalidades podem não funcionar corretamente.
    </div>
</noscript>

</body>
</html>