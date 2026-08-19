<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
    <?php include_http_metas() ?>
    <?php include_metas() ?>
    <?php include_title() ?>
    <link rel="shortcut icon" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/images/favico.png" />
    <?php include_stylesheets() ?>
    <?php include_javascripts() ?>
  </head>
  <body>
    <?php include_component('sfAdminDash','header'); ?>
    <?php echo $sf_content ?>
    <?php include_partial('sfAdminDash/footer'); ?> 
<?php // ponytail: meme geste que le front -- une page sur app_glitch_divisor clignote.
      // Version autonome (overlay fixe non cliquable) : le bloc de showSuccess.php
      // est couple au gabarit du front (header, .grid-container, section.content).
      if (rand(1, sfConfig::get('app_glitch_divisor', 10)) == 1): ?>
      <style>
        @keyframes ma-glitch-flash {
          0%, 10%, 20%, 50%, 100% { opacity: 0 }
          5%, 15%, 25%, 35% { opacity: 1 }
          30% { opacity: .2 }
          40% { opacity: .1 }
          45% { opacity: .8 }
        }

        #ma-glitch {
          position: fixed;
          inset: 0;
          z-index: 9999;
          pointer-events: none;
          opacity: 0;
          background: url('<?php echo sprintf(
            'https://gliche.constructions-incongrues.net/glitch?seed=%d&amount=%d&url=%s%s/images/logo_500.png',
            rand(0, 10000000),
            rand(0, 100),
            $sf_request->getUriPrefix(),
            $sf_request->getRelativeUrlRoot()
          ) ?>') center / cover no-repeat;
          animation: ma-glitch-flash 2s linear 1s forwards;
        }
      </style>
      <div id="ma-glitch"></div>
    <?php endif; ?>
  </body>
</html>
