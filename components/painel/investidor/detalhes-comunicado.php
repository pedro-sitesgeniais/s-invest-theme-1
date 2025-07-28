<?php
/**
 * Seção Detalhes de Comunicado
 * components/painel/investidor/detalhes-comunicado.php
 */
defined( 'ABSPATH' ) || exit;

$com_id = absint( $_GET['comunicado_id'] ?? 0 );
$post   = get_post( $com_id );

if ( ! $post || $post->post_type !== 'comunicado' ) {
  echo '<p class="text-red-600">Comunicado não encontrado.</p>';
  return;
}

setup_postdata( $post );
?>

<h1 class="text-2xl font-semibold mb-4"><?php echo esc_html( get_the_title() ); ?></h1>
<span class="text-sm text-gray-500">
  <?php echo date_i18n( 'd/m/Y', strtotime( get_the_date() ) ); ?>
</span>

<div class="prose mt-6">
  <?php the_content(); ?>
</div>

<?php wp_reset_postdata(); ?>
