<?php while ( $query->have_posts() ): $query->the_post(); 
  $aporte_id = get_the_ID();
  $data      = get_field('data_aporte', $aporte_id);
  $valor     = floatval(get_field('valor_aporte', $aporte_id));
  $inv_id    = get_field('investment_id', $aporte_id);
  $titulo    = $inv_id ? get_the_title($inv_id) : '—';
  $contrato  = get_field('contrato_pdf', $aporte_id);
?>
  <tr class="border-t">
    <td class="px-4 py-2"><?php echo date_i18n('d/m/Y', strtotime($data)); ?></td>
    <td class="px-4 py-2"><?php echo number_format($valor,2,',','.'); ?></td>
    <td class="px-4 py-2"><?php echo esc_html($titulo); ?></td>
    <td class="px-4 py-2">
      <?php if ( $contrato && ! empty($contrato['url']) ): ?>
        <a href="<?php echo esc_url($contrato['url']); ?>"
           target="_blank" class="text-blue-600 hover:underline text-sm">
          Baixar Contrato
        </a>
      <?php else: ?>
        <span class="text-gray-400 text-sm">—</span>
      <?php endif; ?>
    </td>
  </tr>
<?php endwhile; wp_reset_postdata(); ?>
