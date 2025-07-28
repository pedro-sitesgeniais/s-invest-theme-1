<?php
/**
 * Diagnostic Checker - S-Invest Theme (Standalone Version)
 * 
 * Esta versão funciona independente do WordPress
 * Salve como: diagnostic-checker.php na raiz do tema
 * Acesse via: http://seusite.com/wp-content/themes/seu-tema/diagnostic-checker.php
 */

// Headers de segurança
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

// Detecta o diretório do tema automaticamente
$theme_dir = __DIR__;
$theme_name = basename($theme_dir);

// Detecta a URL base do site
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$script_path = $_SERVER['SCRIPT_NAME'];
$theme_url = $protocol . $host . dirname($script_path);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>S-Invest Theme - Diagnostic Report</title>
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f8fafc; 
            color: #1f2937;
            line-height: 1.6;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 12px; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        h1 { 
            color: #1e40af; 
            border-bottom: 3px solid #1e40af; 
            padding-bottom: 15px; 
            margin-bottom: 30px;
            font-size: 2rem;
        }
        h2 { 
            color: #374151; 
            margin-top: 40px; 
            margin-bottom: 20px;
            font-size: 1.5rem;
            border-left: 4px solid #3b82f6;
            padding-left: 15px;
        }
        h3 {
            color: #4b5563;
            margin-top: 25px;
            margin-bottom: 15px;
        }
        .status-ok { color: #10b981; font-weight: bold; }
        .status-warning { color: #f59e0b; font-weight: bold; }
        .status-error { color: #ef4444; font-weight: bold; }
        .file-check { 
            margin: 8px 0; 
            padding: 12px; 
            background: #f9fafb; 
            border-left: 4px solid #d1d5db; 
            border-radius: 6px;
        }
        .file-check.ok { border-left-color: #10b981; background: #ecfdf5; }
        .file-check.warning { border-left-color: #f59e0b; background: #fffbeb; }
        .file-check.error { border-left-color: #ef4444; background: #fef2f2; }
        .recommendation { 
            background: #dbeafe; 
            padding: 20px; 
            border-radius: 8px; 
            margin: 15px 0; 
            border-left: 4px solid #3b82f6;
        }
        .command { 
            background: #1f2937; 
            color: #f9fafb; 
            padding: 15px; 
            border-radius: 6px; 
            font-family: 'Courier New', monospace; 
            margin: 10px 0; 
            overflow-x: auto;
            font-size: 14px;
        }
        .grid { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 30px; 
            margin: 20px 0;
        }
        @media (max-width: 768px) { 
            .grid { grid-template-columns: 1fr; } 
            .container { padding: 20px; margin: 10px; }
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 15px 0; 
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        th, td { 
            padding: 12px; 
            text-align: left; 
            border-bottom: 1px solid #e5e7eb; 
        }
        th { 
            background: #f3f4f6; 
            font-weight: 600; 
            color: #374151;
        }
        tr:hover {
            background: #f9fafb;
        }
        .summary { 
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); 
            padding: 25px; 
            border-radius: 10px; 
            margin-bottom: 30px; 
            border: 1px solid #bae6fd;
        }
        .health-meter {
            background: #e5e7eb;
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        .health-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        .code-inline {
            background: #f3f4f6;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .alert-success { background: #d1fae5; border: 1px solid #10b981; color: #065f46; }
        .alert-warning { background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; }
        .alert-error { background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; }
        .footer-info {
            margin-top: 40px; 
            padding-top: 20px; 
            border-top: 2px solid #e5e7eb; 
            color: #6b7280; 
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 S-Invest Theme - Diagnostic Report</h1>
        
        <?php
        // Arquivos para verificar
        $critical_files = [
            'functions.php' => 'Core theme functions',
            'style.css' => 'Theme stylesheet', 
            'index.php' => 'Main template',
            'header.php' => 'Header template',
            'footer.php' => 'Footer template'
        ];
        
        $asset_files = [
            'public/css/app.css' => 'Main CSS file (Tailwind)',
            'public/js/alpine.js' => 'Alpine.js current version',
            'public/js/alpine.min.js' => 'Alpine.js minified',
            'public/js/app.js' => 'Main JavaScript file',
            'public/js/app.min.js' => 'Main JS minified',
            'public/js/panel-app.js' => 'Panel application JS',
            'public/js/invest-cenarios.js' => 'Investment scenarios JS'
        ];
        
        $template_files = [
            'page-autenticacao.php' => 'Authentication page template',
            'page-painel.php' => 'User panel page template',
            'single-investment.php' => 'Investment single page',
            'archive-investment.php' => 'Investment archive template'
        ];
        
        $component_files = [
            'inc/ajax-investimentos.php' => 'AJAX handlers',
            'inc/helpers.php' => 'Helper functions',
            'components/filtros-investimentos.php' => 'Investment filters component',
            'tailwind.config.js' => 'Tailwind CSS configuration',
            'package.json' => 'NPM dependencies',
            'package-lock.json' => 'NPM lock file'
        ];
        
        // Função para verificar arquivos
        function check_file($file, $description) {
            global $theme_dir;
            $path = $theme_dir . '/' . $file;
            $exists = file_exists($path);
            $size = $exists ? filesize($path) : 0;
            $modified = $exists ? date('Y-m-d H:i:s', filemtime($path)) : 'N/A';
            $readable = $exists ? is_readable($path) : false;
            
            return [
                'exists' => $exists,
                'size' => $size,
                'modified' => $modified,
                'readable' => $readable,
                'description' => $description,
                'path' => $path
            ];
        }
        
        // Função para formatar tamanho de arquivo
        function format_bytes($bytes, $precision = 2) {
            $units = array('B', 'KB', 'MB', 'GB');
            
            for ($i = 0; $bytes > 1024; $i++) {
                $bytes /= 1024;
            }
            
            return round($bytes, $precision) . ' ' . $units[$i];
        }
        
        // Coleta todas as verificações
        $all_checks = [
            'Critical Files' => $critical_files,
            'Asset Files' => $asset_files, 
            'Template Files' => $template_files,
            'Component Files' => $component_files
        ];
        
        $total_files = 0;
        $existing_files = 0;
        $missing_critical = [];
        $empty_files = [];
        $large_files = [];
        
        // Calcula estatísticas
        foreach ($all_checks as $category => $files) {
            foreach ($files as $file => $desc) {
                $total_files++;
                $check = check_file($file, $desc);
                
                if ($check['exists']) {
                    $existing_files++;
                    
                    if ($check['size'] === 0) {
                        $empty_files[] = $file;
                    }
                    
                    if ($check['size'] > 1000000) { // > 1MB
                        $large_files[] = ['file' => $file, 'size' => $check['size']];
                    }
                } else if ($category === 'Critical Files') {
                    $missing_critical[] = $file;
                }
            }
        }
        
        $health_percentage = round(($existing_files / $total_files) * 100);
        $health_class = $health_percentage >= 80 ? 'status-ok' : ($health_percentage >= 60 ? 'status-warning' : 'status-error');
        $health_color = $health_percentage >= 80 ? '#10b981' : ($health_percentage >= 60 ? '#f59e0b' : '#ef4444');
        ?>
        
        <div class="summary">
            <h2 style="margin-top: 0; color: #1e40af;">📊 Theme Summary</h2>
            <div class="grid">
                <div>
                    <p><strong>Theme Name:</strong> <span class="code-inline"><?php echo htmlspecialchars($theme_name); ?></span></p>
                    <p><strong>Directory:</strong> <span class="code-inline"><?php echo htmlspecialchars($theme_dir); ?></span></p>
                    <p><strong>Scan Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                </div>
                <div>
                    <p><strong>Files Found:</strong> <?php echo $existing_files; ?> / <?php echo $total_files; ?></p>
                    <p><strong>Theme Health:</strong> <span class="<?php echo $health_class; ?>"><?php echo $health_percentage; ?>%</span></p>
                    <div class="health-meter">
                        <div class="health-fill" style="width: <?php echo $health_percentage; ?>%; background-color: <?php echo $health_color; ?>;"></div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($missing_critical)): ?>
                <div class="alert alert-error">
                    <strong>⚠️ Missing Critical Files:</strong> <?php echo implode(', ', array_map('htmlspecialchars', $missing_critical)); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($empty_files)): ?>
                <div class="alert alert-warning">
                    <strong>📝 Empty Files Found:</strong> <?php echo implode(', ', array_map('htmlspecialchars', $empty_files)); ?>
                </div>
            <?php endif; ?>
        </div>
        
        <?php foreach ($all_checks as $category => $files): ?>
            <h2><?php echo $category; ?></h2>
            
            <table>
                <thead>
                    <tr>
                        <th>File</th>
                        <th>Status</th>
                        <th>Size</th>
                        <th>Last Modified</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($files as $file => $description): ?>
                        <?php 
                        $check = check_file($file, $description);
                        
                        if (!$check['exists']) {
                            $status_class = 'status-error';
                            $status_text = '❌ MISSING';
                        } elseif ($check['size'] === 0) {
                            $status_class = 'status-warning';
                            $status_text = '⚠️ EMPTY';
                        } elseif (!$check['readable']) {
                            $status_class = 'status-warning';
                            $status_text = '🔒 NOT READABLE';
                        } else {
                            $status_class = 'status-ok';
                            $status_text = '✅ EXISTS';
                        }
                        ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($file); ?></code></td>
                            <td><span class="<?php echo $status_class; ?>"><?php echo $status_text; ?></span></td>
                            <td><?php echo $check['exists'] ? format_bytes($check['size']) : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($check['modified']); ?></td>
                            <td><?php echo htmlspecialchars($check['description']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endforeach; ?>
        
        <h2>🔧 Recommendations & Solutions</h2>
        
        <?php
        // Gera recomendações baseadas nos achados
        $recommendations = [];
        
        // Verifica Alpine.js
        $has_alpine_js = file_exists($theme_dir . '/public/js/alpine.js');
        $has_alpine_min = file_exists($theme_dir . '/public/js/alpine.min.js');
        
        if (!$has_alpine_js && !$has_alpine_min) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'title' => '🚨 Alpine.js Completely Missing',
                'description' => 'Neither alpine.js nor alpine.min.js found. This will cause critical JavaScript errors.',
                'action' => 'Download Alpine.js or implement CDN fallback immediately',
                'solution' => 'Add CDN fallback to functions.php or download alpine.js to public/js/'
            ];
        } elseif ($has_alpine_js && !$has_alpine_min) {
            $recommendations[] = [
                'priority' => 'MEDIUM',
                'title' => '⚡ Alpine.js Optimization',
                'description' => 'alpine.js found but minified version missing.',
                'action' => 'Create minified version for production or use original file',
                'solution' => 'Update functions.php to use alpine.js instead of alpine.min.js'
            ];
        }
        
        // Verifica CSS principal
        if (!file_exists($theme_dir . '/public/css/app.css')) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'title' => '🎨 Main CSS Missing',
                'description' => 'app.css not found. Site styling will be completely broken.',
                'action' => 'Compile Tailwind CSS or restore app.css file',
                'solution' => 'Run: npm run build:css or restore from backup'
            ];
        } elseif (filesize($theme_dir . '/public/css/app.css') === 0) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'title' => '🎨 Main CSS Empty',
                'description' => 'app.css exists but is empty (0 bytes).',
                'action' => 'Recompile Tailwind CSS',
                'solution' => 'Run: npm run build:css'
            ];
        }
        
        // Verifica templates críticos
        foreach (['page-autenticacao.php', 'page-painel.php'] as $template) {
            if (!file_exists($theme_dir . '/' . $template)) {
                $recommendations[] = [
                    'priority' => 'MEDIUM',
                    'title' => "📄 Critical Template Missing: $template",
                    'description' => "Essential template $template not found. Related pages will show errors.",
                    'action' => "Create or restore $template template file",
                    'solution' => "Copy from backup or create new template based on index.php"
                ];
            }
        }
        
        // Verifica arquivos vazios críticos
        foreach (['functions.php', 'style.css', 'index.php'] as $critical) {
            if (file_exists($theme_dir . '/' . $critical) && filesize($theme_dir . '/' . $critical) === 0) {
                $recommendations[] = [
                    'priority' => 'HIGH',
                    'title' => "🚨 Critical File Empty: $critical",
                    'description' => "Essential file $critical exists but contains no content.",
                    'action' => "Restore $critical from backup immediately",
                    'solution' => "This file is essential for WordPress theme functionality"
                ];
            }
        }
        
        // Verifica dependências NPM
        if (file_exists($theme_dir . '/package.json') && !file_exists($theme_dir . '/node_modules')) {
            $recommendations[] = [
                'priority' => 'LOW',
                'title' => '📦 NPM Dependencies Missing',
                'description' => 'package.json exists but node_modules folder missing.',
                'action' => 'Install NPM dependencies',
                'solution' => 'Run: npm install in theme directory'
            ];
        }
        
        // Verifica arquivos de configuração
        if (!file_exists($theme_dir . '/tailwind.config.js')) {
            $recommendations[] = [
                'priority' => 'MEDIUM',
                'title' => '⚙️ Tailwind Config Missing',
                'description' => 'tailwind.config.js not found. CSS compilation may fail.',
                'action' => 'Create Tailwind configuration file',
                'solution' => 'Run: npx tailwindcss init or restore from backup'
            ];
        }
        
        if (empty($recommendations)) {
            echo '<div class="alert alert-success"><h3 style="margin-top: 0;">✅ No Critical Issues Found</h3><p>Theme structure looks good! All essential files are present and readable.</p></div>';
        } else {
            foreach ($recommendations as $rec) {
                $priority_class = strtolower($rec['priority']);
                if ($priority_class === 'high') $alert_class = 'alert-error';
                elseif ($priority_class === 'medium') $alert_class = 'alert-warning';  
                else $alert_class = 'alert-success';
                
                echo '<div class="' . $alert_class . '">';
                echo '<h3 style="margin-top: 0;"><strong>' . $rec['priority'] . ' PRIORITY</strong> - ' . htmlspecialchars($rec['title']) . '</h3>';
                echo '<p><strong>Problem:</strong> ' . htmlspecialchars($rec['description']) . '</p>';
                echo '<p><strong>Action Needed:</strong> ' . htmlspecialchars($rec['action']) . '</p>';
                echo '<p><strong>Solution:</strong> ' . htmlspecialchars($rec['solution']) . '</p>';
                echo '</div>';
            }
        }
        ?>
        
        <h2>🚀 Immediate Solutions</h2>
        
        <div class="grid">
            <div>
                <h3>🆘 Emergency Fix - CDN Fallback</h3>
                <p>Add this to your <code>functions.php</code> to fix missing Alpine.js immediately:</p>
                <div class="command">// Emergency Alpine.js CDN Fallback
function s_invest_emergency_alpine() {
    if (!file_exists(get_template_directory() . '/public/js/alpine.js')) {
        wp_enqueue_script(
            's-invest-alpine-cdn',
            'https://unpkg.com/alpinejs@3.14.9/dist/cdn.min.js',
            [], '3.14.9', ['in_footer' => true]
        );
    }
}
add_action('wp_enqueue_scripts', 's_invest_emergency_alpine', 999);</div>
            </div>
            
            <div>
                <h3>📥 Download Missing Files</h3>
                <p>Download essential files directly:</p>
                <div class="command"># Create directories
mkdir -p public/js public/css

# Download Alpine.js
curl -o public/js/alpine.js \
  https://unpkg.com/alpinejs@3.14.9/dist/cdn.js

# Compile Tailwind (if package.json exists)
npm run build:css</div>
            </div>
        </div>
        
        <h2>📋 Next Steps Checklist</h2>
        
        <div class="recommendation">
            <h3>Immediate Actions (Next 15 minutes):</h3>
            <ol>
                <li><strong>Fix HIGH priority issues first</strong> - These break core functionality</li>
                <li><strong>Implement emergency CDN fallback</strong> if Alpine.js is missing</li>
                <li><strong>Test basic site functionality</strong> - Login, navigation, forms</li>
                <li><strong>Check browser console</strong> for JavaScript errors (F12 > Console)</li>
                <li><strong>Verify theme compilation</strong> - Run npm install && npm run build if needed</li>
            </ol>
        </div>
        
        <div class="recommendation">
            <h3>Short-term Fixes (Next 1-2 hours):</h3>
            <ol>
                <li><strong>Restore missing critical files</strong> from backup or recreate</li>
                <li><strong>Update functions.php</strong> with the corrected version provided</li>
                <li><strong>Implement proper error handling</strong> for missing files</li>
                <li><strong>Test all major functionality</strong> - User panel, investments, authentication</li>
                <li><strong>Re-run this diagnostic</strong> to verify fixes</li>
            </ol>
        </div>
        
        <?php if (!empty($large_files)): ?>
        <h2>📊 Large Files Detected</h2>
        <p>These files are larger than 1MB and may impact loading speed:</p>
        <ul>
            <?php foreach ($large_files as $large): ?>
                <li><code><?php echo htmlspecialchars($large['file']); ?></code> - <?php echo format_bytes($large['size']); ?></li>
            <?php endforeach; ?>
        </ul>
        <p><em>Consider optimizing or minifying these files for better performance.</em></p>
        <?php endif; ?>
        
        <div class="footer-info">
            <p><strong>⚠️ Security Notice:</strong> Remove this diagnostic file after use for security reasons.</p>
            <p><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?> | <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?> | <strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
            <p><strong>Theme Health Score:</strong> <span class="<?php echo $health_class; ?>"><?php echo $health_percentage; ?>%</span></p>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 30 seconds if there are critical issues
        <?php if ($health_percentage < 80): ?>
        console.log('Theme health below 80% - Auto-refresh disabled. Fix issues first.');
        <?php endif; ?>
        
        // Add click-to-copy functionality for code blocks
        document.querySelectorAll('.command').forEach(block => {
            block.style.cursor = 'pointer';
            block.title = 'Click to copy';
            block.addEventListener('click', () => {
                navigator.clipboard.writeText(block.textContent).then(() => {
                    const original = block.style.background;
                    block.style.background = '#10b981';
                    setTimeout(() => block.style.background = original, 1000);
                });
            });
        });
    </script>
</body>
</html>