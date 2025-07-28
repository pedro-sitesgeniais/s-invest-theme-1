<?php
/**
 * Template Completo da Página Administrativa de Usuários
 * Arquivo: inc/admin-users-template.php
 */

defined('ABSPATH') || exit;

// Parâmetros de busca e filtro
$current_tab = $_GET['tab'] ?? 'all';
$search_term = sanitize_text_field($_GET['search'] ?? '');
$user_role = sanitize_text_field($_GET['role'] ?? '');
$status_filter = sanitize_text_field($_GET['status'] ?? '');
$per_page = 20;
$paged = max(1, absint($_GET['paged'] ?? 1));

// Configurar argumentos de busca
$user_args = [
    'number' => $per_page,
    'offset' => ($paged - 1) * $per_page,
    'orderby' => 'registered',
    'order' => 'DESC'
];

// Aplicar filtros
if ($user_role && in_array($user_role, ['investidor', 'associado'])) {
    $user_args['role'] = $user_role;
} else {
    $user_args['role__in'] = ['investidor', 'associado'];
}

if ($search_term) {
    $user_args['search'] = '*' . $search_term . '*';
    $user_args['search_columns'] = ['user_login', 'user_email', 'display_name'];
}

// Filtro por status de confirmação
if ($status_filter === 'confirmed') {
    $user_args['meta_query'] = [['key' => 'email_confirmed', 'value' => true, 'compare' => '=']];
} elseif ($status_filter === 'pending') {
    $user_args['meta_query'] = [['key' => 'email_confirmed', 'value' => false, 'compare' => '=']];
}

// Buscar usuários
$users_query = new WP_User_Query($user_args);
$users = $users_query->get_results();
$total_users = $users_query->get_total();

// Estatísticas rápidas
$total_investidores = count(get_users(['role' => 'investidor']));
$total_associados = count(get_users(['role' => 'associado']));
$total_confirmados = count(get_users([
    'meta_query' => [['key' => 'email_confirmed', 'value' => true, 'compare' => '=']]
]));
$total_pendentes = count(get_users([
    'meta_query' => [['key' => 'email_confirmed', 'value' => false, 'compare' => '=']]
]));

// Calcular paginação
$total_pages = ceil($total_users / $per_page);
?>

<div class="wrap">
    <h1>
        <span class="dashicons dashicons-groups" style="font-size: 24px; margin-right: 8px;"></span>
        Gerenciar Investidores
        <a href="<?php echo admin_url('user-new.php'); ?>" class="page-title-action">Adicionar Novo</a>
    </h1>
    
    <!-- Estatísticas Dashboard -->
    <div class="s-invest-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin: 20px 0;">
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #3b82f6;">
            <h3 style="margin: 0 0 8px 0; color: #374151; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Total Investidores</h3>
            <p style="margin: 0; font-size: 24px; font-weight: bold; color: #3b82f6;"><?php echo $total_investidores; ?></p>
        </div>
        
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #10b981;">
            <h3 style="margin: 0 0 8px 0; color: #374151; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Total Associados</h3>
            <p style="margin: 0; font-size: 24px; font-weight: bold; color: #10b981;"><?php echo $total_associados; ?></p>
        </div>
        
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #8b5cf6;">
            <h3 style="margin: 0 0 8px 0; color: #374151; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">E-mails Confirmados</h3>
            <p style="margin: 0; font-size: 24px; font-weight: bold; color: #8b5cf6;"><?php echo $total_confirmados; ?></p>
        </div>
        
        <div class="stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #f59e0b;">
            <h3 style="margin: 0 0 8px 0; color: #374151; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Pendentes</h3>
            <p style="margin: 0; font-size: 24px; font-weight: bold; color: #f59e0b;"><?php echo $total_pendentes; ?></p>
        </div>
    </div>
    
    <!-- Navegação por Abas -->
    <div class="nav-tab-wrapper" style="margin-bottom: 20px;">
        <a href="<?php echo admin_url('users.php?page=s-invest-users&tab=all'); ?>" 
           class="nav-tab <?php echo $current_tab === 'all' ? 'nav-tab-active' : ''; ?>">
            Todos os Usuários (<?php echo $total_investidores + $total_associados; ?>)
        </a>
        <a href="<?php echo admin_url('users.php?page=s-invest-users&tab=pending'); ?>" 
           class="nav-tab <?php echo $current_tab === 'pending' ? 'nav-tab-active' : ''; ?>">
            Pendentes (<?php echo $total_pendentes; ?>)
        </a>
        <a href="<?php echo admin_url('users.php?page=s-invest-users&tab=confirmed'); ?>" 
           class="nav-tab <?php echo $current_tab === 'confirmed' ? 'nav-tab-active' : ''; ?>">
            Confirmados (<?php echo $total_confirmados; ?>)
        </a>
        <a href="<?php echo admin_url('users.php?page=s-invest-users&tab=investidores'); ?>" 
           class="nav-tab <?php echo $current_tab === 'investidores' ? 'nav-tab-active' : ''; ?>">
            Investidores (<?php echo $total_investidores; ?>)
        </a>
        <a href="<?php echo admin_url('users.php?page=s-invest-users&tab=associados'); ?>" 
           class="nav-tab <?php echo $current_tab === 'associados' ? 'nav-tab-active' : ''; ?>">
            Associados (<?php echo $total_associados; ?>)
        </a>
    </div>
    
    <!-- Filtros e Busca -->
    <div class="s-invest-filters" style="background: #fff; padding: 16px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <form method="get" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="page" value="s-invest-users">
            <input type="hidden" name="tab" value="<?php echo esc_attr($current_tab); ?>">
            
            <div style="flex: 1; min-width: 200px;">
                <input type="text" name="search" placeholder="Buscar por nome, email ou login..." 
                       value="<?php echo esc_attr($search_term); ?>" 
                       style="width: 100%; padding: 8px 12px; border-radius: 4px; border: 1px solid #d1d5db;">
            </div>
            
            <select name="role" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #d1d5db;">
                <option value="">Todos os tipos</option>
                <option value="investidor" <?php selected($user_role, 'investidor'); ?>>Investidores</option>
                <option value="associado" <?php selected($user_role, 'associado'); ?>>Associados</option>
            </select>
            
            <select name="status" style="padding: 8px 12px; border-radius: 4px; border: 1px solid #d1d5db;">
                <option value="">Todos os status</option>
                <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>>Confirmados</option>
                <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pendentes</option>
            </select>
            
            <button type="submit" class="button button-primary">
                <span class="dashicons dashicons-search" style="font-size: 16px; margin-right: 4px;"></span>
                Filtrar
            </button>
            
            <a href="<?php echo admin_url('users.php?page=s-invest-users'); ?>" class="button">
                <span class="dashicons dashicons-update" style="font-size: 16px; margin-right: 4px;"></span>
                Limpar
            </a>
        </form>
    </div>
    
    <!-- Ações em Massa -->
    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <label for="bulk-action-selector-top" class="screen-reader-text">Selecionar ação em massa</label>
            <select name="action" id="bulk-action-selector-top">
                <option value="-1">Ações em massa</option>
                <option value="approve">Aprovar usuários</option>
                <option value="resend">Reenviar confirmação</option>
                <option value="delete">Excluir usuários</option>
            </select>
            <input type="submit" id="doaction" class="button action" value="Aplicar">
        </div>
        
        <div class="alignright actions">
            <button type="button" class="button" onclick="exportUsers()">
                <span class="dashicons dashicons-download" style="margin-right: 4px;"></span>
                Exportar CSV
            </button>
        </div>
        
        <br class="clear">
    </div>
    
    <!-- Tabela de Usuários -->
    <div class="s-invest-table-container" style="background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <?php if (empty($users)): ?>
            <div style="text-align: center; padding: 40px; background: #f9fafb; border: 2px dashed #d1d5db; border-radius: 8px;">
                <span class="dashicons dashicons-search" style="font-size: 48px; color: #9ca3af; margin-bottom: 16px;"></span>
                <h3 style="color: #374151; margin-bottom: 8px;">Nenhum usuário encontrado</h3>
                <p style="color: #6b7280; margin: 0;">Tente ajustar os filtros de busca.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped users">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column">
                            <input id="cb-select-all-1" type="checkbox">
                        </td>
                        <th scope="col" style="width: 50px;">Avatar</th>
                        <th scope="col">Nome</th>
                        <th scope="col">E-mail</th>
                        <th scope="col">Telefone</th>
                        <th scope="col">CPF</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Status</th>
                        <th scope="col">Registro</th>
                        <th scope="col">Último Acesso</th>
                        <th scope="col">Ações</th>
                    </tr>
                </thead>
                <tbody id="the-list">
                    <?php foreach ($users as $user): 
                        $telefone = get_user_meta($user->ID, 'telefone', true) ?: get_field('telefone', 'user_' . $user->ID);
                        $cpf = get_user_meta($user->ID, 'cpf', true) ?: get_field('cpf', 'user_' . $user->ID);
                        $email_confirmed = get_user_meta($user->ID, 'email_confirmed', true);
                        $last_login = get_user_meta($user->ID, 'last_login', true);
                        $user_roles = $user->roles;
                        $primary_role = !empty($user_roles) ? $user_roles[0] : 'subscriber';
                        
                        // Contar investimentos do usuário
                        $user_investments = 0;
                        if (function_exists('get_posts')) {
                            $investments = get_posts([
                                'post_type' => 'investment',
                                'meta_query' => [
                                    ['key' => 'investor_id', 'value' => $user->ID, 'compare' => '=']
                                ],
                                'posts_per_page' => -1
                            ]);
                            $user_investments = count($investments);
                        }
                    ?>
                    <tr id="user-<?php echo $user->ID; ?>">
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="users[]" id="user_<?php echo $user->ID; ?>" class="administrator" value="<?php echo $user->ID; ?>">
                        </th>
                        <td class="avatar column-avatar">
                            <?php echo get_avatar($user->ID, 32, '', '', ['class' => 'avatar-32 photo']); ?>
                        </td>
                        <td class="username column-username has-row-actions column-primary">
                            <strong>
                                <a href="<?php echo get_edit_user_link($user->ID); ?>">
                                    <?php echo esc_html($user->first_name . ' ' . $user->last_name ?: $user->display_name); ?>
                                </a>
                            </strong>
                            <br>
                            <small style="color: #666;">@<?php echo esc_html($user->user_login); ?></small>
                            
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo get_edit_user_link($user->ID); ?>">Editar</a> |
                                </span>
                                <span class="view">
                                    <a href="<?php echo get_author_posts_url($user->ID); ?>" target="_blank">Ver Perfil</a> |
                                </span>
                                <?php if (!$email_confirmed): ?>
                                <span class="approve">
                                    <a href="#" onclick="approveUser(<?php echo $user->ID; ?>)" style="color: #10b981;">Aprovar</a> |
                                </span>
                                <span class="resend">
                                    <a href="#" onclick="resendConfirmation(<?php echo $user->ID; ?>)" style="color: #3b82f6;">Reenviar</a> |
                                </span>
                                <?php endif; ?>
                                <span class="delete">
                                    <a href="#" onclick="deleteUser(<?php echo $user->ID; ?>)" style="color: #dc2626;">Excluir</a>
                                </span>
                            </div>
                        </td>
                        <td class="email column-email">
                            <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                                <?php echo esc_html($user->user_email); ?>
                            </a>
                        </td>
                        <td class="phone column-phone">
                            <?php if ($telefone): ?>
                                <a href="tel:<?php echo esc_attr(preg_replace('/\D/', '', $telefone)); ?>">
                                    <?php echo esc_html($telefone); ?>
                                </a>
                            <?php else: ?>
                                <span style="color: #9ca3af;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="cpf column-cpf">
                            <?php if ($cpf): ?>
                                <code style="font-size: 12px; background: #f3f4f6; padding: 2px 4px; border-radius: 3px;">
                                    <?php echo esc_html(preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf)); ?>
                                </code>
                            <?php else: ?>
                                <span style="color: #9ca3af;">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="role column-role">
                            <span class="role-badge" style="background: <?php echo $primary_role === 'investidor' ? '#3b82f6' : '#10b981'; ?>; color: white; padding: 4px 8px; border-radius: 12px; font-size: 11px; text-transform: uppercase; font-weight: bold;">
                                <?php echo esc_html($primary_role); ?>
                            </span>
                            <?php if ($user_investments > 0): ?>
                                <br><small style="color: #666;"><?php echo $user_investments; ?> investimento(s)</small>
                            <?php endif; ?>
                        </td>
                        <td class="status column-status">
                            <?php if ($email_confirmed): ?>
                                <span style="color: #10b981; font-weight: bold;">
                                    <span class="dashicons dashicons-yes-alt" style="font-size: 16px;"></span>
                                    Confirmado
                                </span>
                            <?php else: ?>
                                <span style="color: #f59e0b; font-weight: bold;">
                                    <span class="dashicons dashicons-clock" style="font-size: 16px;"></span>
                                    Pendente
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="registered column-registered">
                            <time datetime="<?php echo esc_attr($user->user_registered); ?>" 
                                  title="<?php echo esc_attr(date('d/m/Y H:i:s', strtotime($user->user_registered))); ?>">
                                <?php echo esc_html(date('d/m/Y', strtotime($user->user_registered))); ?>
                            </time>
                        </td>
                        <td class="last-login column-last-login">
                            <?php if ($last_login): ?>
                                <time datetime="<?php echo esc_attr($last_login); ?>" 
                                      title="<?php echo esc_attr(date('d/m/Y H:i:s', strtotime($last_login))); ?>">
                                    <?php echo esc_html(human_time_diff(strtotime($last_login), current_time('timestamp')) . ' atrás'); ?>
                                </time>
                            <?php else: ?>
                                <span style="color: #9ca3af;">Nunca</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions column-actions">
                            <div style="display: flex; gap: 4px;">
                                <a href="<?php echo get_edit_user_link($user->ID); ?>" 
                                   class="button button-small" title="Editar usuário">
                                    <span class="dashicons dashicons-edit" style="font-size: 14px;"></span>
                                </a>
                                
                                <?php if (!$email_confirmed): ?>
                                <button type="button" class="button button-primary button-small" 
                                        onclick="approveUser(<?php echo $user->ID; ?>)" title="Aprovar usuário">
                                    <span class="dashicons dashicons-yes-alt" style="font-size: 14px;"></span>
                                </button>
                                <?php endif; ?>
                                
                                <button type="button" class="button button-small" 
                                        onclick="showUserDetails(<?php echo $user->ID; ?>)" title="Ver detalhes">
                                    <span class="dashicons dashicons-visibility" style="font-size: 14px;"></span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <!-- Paginação -->
    <?php if ($total_pages > 1): ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num"><?php echo sprintf('%d itens', $total_users); ?></span>
            <span class="pagination-links">
                <?php
                $base_url = admin_url('users.php?page=s-invest-users');
                if ($search_term) $base_url = add_query_arg('search', $search_term, $base_url);
                if ($user_role) $base_url = add_query_arg('role', $user_role, $base_url);
                if ($status_filter) $base_url = add_query_arg('status', $status_filter, $base_url);
                
                if ($paged > 1):
                    echo '<a class="first-page button" href="' . add_query_arg('paged', 1, $base_url) . '">‹‹</a>';
                    echo '<a class="prev-page button" href="' . add_query_arg('paged', $paged - 1, $base_url) . '">‹</a>';
                endif;
                
                echo '<span class="paging-input">';
                echo '<label for="current-page-selector" class="screen-reader-text">Página atual</label>';
                echo '<input class="current-page" id="current-page-selector" type="text" name="paged" value="' . $paged . '" size="2" aria-describedby="table-paging">';
                echo '<span class="tablenav-paging-text"> de <span class="total-pages">' . $total_pages . '</span></span>';
                echo '</span>';
                
                if ($paged < $total_pages):
                    echo '<a class="next-page button" href="' . add_query_arg('paged', $paged + 1, $base_url) . '">›</a>';
                    echo '<a class="last-page button" href="' . add_query_arg('paged', $total_pages, $base_url) . '">››</a>';
                endif;
                ?>
            </span>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal para Detalhes do Usuário -->
<div id="user-details-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; border-radius: 8px; padding: 24px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Detalhes do Usuário</h2>
            <button type="button" onclick="window.closeUserModal()" style="background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <div id="user-details-content">
            <!-- Conteúdo será carregado via AJAX -->
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
// Definir variáveis globais
window.ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

// Funções utilitárias sem dependência do jQuery
function makeAjaxRequest(data, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', window.ajaxurl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    callback(response);
                } catch(e) {
                    callback({success: false, data: 'Erro ao processar resposta'});
                }
            } else {
                callback({success: false, data: 'Erro de conexão'});
            }
        }
    };
    
    // Converter objeto para string de parâmetros
    var params = Object.keys(data).map(function(key) {
        return encodeURIComponent(key) + '=' + encodeURIComponent(data[key]);
    }).join('&');
    
    xhr.send(params);
}

// Funções principais (sem jQuery)
window.approveUser = function(userId) {
    if (confirm('Aprovar este usuário?')) {
        makeAjaxRequest({
            action: 'approve_user',
            user_id: userId,
            nonce: '<?php echo wp_create_nonce('approve_user'); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Erro: ' + response.data);
            }
        });
    }
};

window.resendConfirmation = function(userId) {
    if (confirm('Reenviar e-mail de confirmação?')) {
        makeAjaxRequest({
            action: 'resend_confirmation',
            user_id: userId,
            nonce: '<?php echo wp_create_nonce('resend_confirmation'); ?>'
        }, function(response) {
            if (response.success) {
                alert('E-mail enviado com sucesso!');
            } else {
                alert('Erro: ' + response.data);
            }
        });
    }
};

window.deleteUser = function(userId) {
    if (confirm('ATENÇÃO: Esta ação irá excluir permanentemente o usuário. Tem certeza?')) {
        makeAjaxRequest({
            action: 'delete_user_admin',
            user_id: userId,
            nonce: '<?php echo wp_create_nonce('delete_user_admin'); ?>'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Erro: ' + response.data);
            }
        });
    }
};

window.bulkAction = function(action, userIds) {
    makeAjaxRequest({
        action: 'bulk_user_action',
        bulk_action: action,
        user_ids: userIds,
        nonce: '<?php echo wp_create_nonce('bulk_user_action'); ?>'
    }, function(response) {
        if (response.success) {
            location.reload();
        } else {
            alert('Erro: ' + response.data);
        }
    });
};

window.showUserDetails = function(userId) {
    var modal = document.getElementById('user-details-modal');
    var content = document.getElementById('user-details-content');
    
    if (modal && content) {
        modal.style.display = 'block';
        content.innerHTML = '<p>Carregando...</p>';
        
        makeAjaxRequest({
            action: 'get_user_details',
            user_id: userId,
            nonce: '<?php echo wp_create_nonce('get_user_details'); ?>'
        }, function(response) {
            if (response.success) {
                content.innerHTML = response.data;
            } else {
                content.innerHTML = '<p>Erro ao carregar dados.</p>';
            }
        });
    }
};

window.closeUserModal = function() {
    var modal = document.getElementById('user-details-modal');
    if (modal) {
        modal.style.display = 'none';
    }
};

window.exportUsers = function() {
    var params = new URLSearchParams(window.location.search);
    params.set('action', 'export_users');
    params.set('nonce', '<?php echo wp_create_nonce('export_users'); ?>');
    
    window.location.href = window.ajaxurl + '?' + params.toString();
};

// Funções de controle (sem jQuery)
window.toggleAllUsers = function(selectAll) {
    var userCheckboxes = document.querySelectorAll('.user-checkbox');
    userCheckboxes.forEach(function(checkbox) {
        checkbox.checked = selectAll.checked;
    });
    updateBulkActions();
};

window.approveSelectedUsers = function() {
    var selectedUsers = Array.from(document.querySelectorAll('.user-checkbox:checked'))
        .map(function(cb) { return cb.value; });
    
    if (selectedUsers.length === 0) return;
    
    if (confirm('Aprovar ' + selectedUsers.length + ' usuário(s) selecionado(s)?')) {
        window.bulkAction('approve', selectedUsers);
    }
};

window.resendSelectedUsers = function() {
    var selectedUsers = Array.from(document.querySelectorAll('.user-checkbox:checked'))
        .map(function(cb) { return cb.value; });
    
    if (selectedUsers.length === 0) return;
    
    if (confirm('Reenviar e-mail de confirmação para ' + selectedUsers.length + ' usuário(s)?')) {
        window.bulkAction('resend', selectedUsers);
    }
};

window.approveAllUsers = function() {
    var userCheckboxes = document.querySelectorAll('.user-checkbox');
    var totalUsers = userCheckboxes.length;
    
    if (confirm('Aprovar TODOS os ' + totalUsers + ' usuários pendentes? Esta ação não pode ser desfeita.')) {
        var selectAllCheckbox = document.getElementById('cb-select-all-1');
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = true;
            window.toggleAllUsers(selectAllCheckbox);
        }
        
        setTimeout(function() {
            window.approveSelectedUsers();
        }, 100);
    }
};

// Função para atualizar estado dos botões em massa
function updateBulkActions() {
    var selectedUsers = document.querySelectorAll('.user-checkbox:checked');
    var count = selectedUsers.length;
    
    var bulkApproveBtn = document.getElementById('bulk-approve-btn');
    var bulkResendBtn = document.getElementById('bulk-resend-btn');
    var selectedCount = document.getElementById('selected-count');
    
    if (bulkApproveBtn) bulkApproveBtn.disabled = count === 0;
    if (bulkResendBtn) bulkResendBtn.disabled = count === 0;
    
    if (selectedCount) {
        if (count === 0) {
            selectedCount.textContent = 'Nenhum usuário selecionado';
        } else if (count === 1) {
            selectedCount.textContent = '1 usuário selecionado';
        } else {
            selectedCount.textContent = count + ' usuários selecionados';
        }
    }
}

// Inicialização quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin panel JavaScript inicializado');
    
    // Event listeners para checkboxes
    var userCheckboxes = document.querySelectorAll('.user-checkbox');
    userCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', updateBulkActions);
    });
    
    // Event listener para ações em massa
    var bulkActionBtn = document.getElementById('doaction');
    if (bulkActionBtn) {
        bulkActionBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            var action = document.getElementById('bulk-action-selector-top').value;
            var selectedUsers = Array.from(document.querySelectorAll('.user-checkbox:checked'))
                .map(function(cb) { return cb.value; });
            
            if (action === '-1' || selectedUsers.length === 0) {
                alert('Selecione uma ação e pelo menos um usuário.');
                return;
            }
            
            var actionText = {
                'approve': 'aprovar',
                'resend': 'reenviar confirmação para',
                'delete': 'excluir'
            };
            
            if (confirm('Tem certeza que deseja ' + actionText[action] + ' ' + selectedUsers.length + ' usuário(s)?')) {
                window.bulkAction(action, selectedUsers);
            }
        });
    }
    
    // Event listener para select all
    var selectAllCheckbox = document.getElementById('cb-select-all-1');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            window.toggleAllUsers(this);
        });
    }
    
    // Event listener para fechar modal clicando fora
    var modal = document.getElementById('user-details-modal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                window.closeUserModal();
            }
        });
    }
    
    // Inicializar estado
    updateBulkActions();
    
    console.log('Event listeners configurados');
});

// Fallback para jQuery se disponível (para funcionalidades extras)
function initializeJQueryFeatures() {
    if (typeof jQuery !== 'undefined') {
        var $ = jQuery;
        console.log('jQuery disponível, versão:', $.fn.jquery);
        
        // Aqui podem ser adicionadas funcionalidades extras que dependem do jQuery
        // Por exemplo: animações, tooltips, etc.
    } else {
        console.log('jQuery não disponível, usando vanilla JavaScript');
    }
}

// Tentar inicializar recursos jQuery (opcional)
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeJQueryFeatures);
} else {
    initializeJQueryFeatures();
}
</script>

<!-- Estilos -->
<style>
.s-invest-stats .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: all 0.2s ease;
}

.wp-list-table tbody tr:hover {
    background-color: #f0f9ff !important;
}

.role-badge {
    letter-spacing: 0.5px;
}

.row-actions {
    visibility: hidden;
}

.wp-list-table tbody tr:hover .row-actions {
    visibility: visible;
}

@media (max-width: 782px) {
    .s-invest-filters form {
        flex-direction: column;
        align-items: stretch;
    }
    
    .s-invest-stats {
        grid-template-columns: 1fr !important;
    }
    
    .wp-list-table .column-avatar,
    .wp-list-table .column-phone,
    .wp-list-table .column-cpf,
    .wp-list-table .column-last-login {
        display: none;
    }
}
</style>