<div class="sf_admin_form_row sf_admin_text sf_admin_form_field_body">
  <div>
    <label>Avatar</label>
    <div class="content">
        <?php // ponytail: meme URL que le front (showSuccess.php) -- seed = id du post, amount aleatoire ?>
        <img width="250" alt="Avatar du morceau" src="<?php echo sprintf(
          'https://gliche.constructions-incongrues.net/glitch?seed=%d&amount=%d&url=%s%s/images/logo_500.png',
          $form['id']->getValue(),
          rand(0, 100),
          $sf_request->getUriPrefix(),
          $sf_request->getRelativeUrlRoot()
        ) ?>" />
    </div>
    <div class="help">La représentation visuelle unique du morceau.</div>
  </div>
</div>
