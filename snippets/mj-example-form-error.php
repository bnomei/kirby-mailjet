<?php // https://kirby-uniform.readthedocs.io/en/latest/examples/extended/?>
<?php if ($form->error($field)): ?>
    <p class="error-text"><?php echo implode('<br>', $form->error($field)) ?></p>
<?php endif; ?>