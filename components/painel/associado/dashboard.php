<?php
/**
 * Painel do Associado com controle de seções (?secao=)
 */

$secao = $_GET['secao'] ?? 'dashboard';

switch ($secao) {
  case 'investimentos':
    get_template_part('components/painel/associado/investimentos-lista');
    break;
  case 'investidores':
    get_template_part('components/painel/associado/investidores');
    break;
  case 'perfil':
    get_template_part('components/painel/associado/perfil');
    break;
  default:
    echo '<h1 class="text-2xl font-bold mb-4">Bem-vindo ao seu painel de associados.</h1>';
    get_template_part('components/painel/associado/investimento-form');
    break;
}
?>
