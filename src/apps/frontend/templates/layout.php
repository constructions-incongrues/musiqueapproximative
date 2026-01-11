<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr" prefix="og: http://ogp.me/ns#">

<head>
  <meta charset="utf-8">
  <meta http-equiv="x-ua-compatible" content="ie=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">

  <?php
  $chk = sfConfig::get('app_version', 'dev');
  ?>
  <!-- favicon and other icons -->
  <meta name="msapplication-TileColor" content="#ffffff">
  <meta name="msapplication-TileImage" content="pinned-icon.png">
  <meta name="application-name" content="<?php echo sfConfig::get('app_title') ?>">
  <link rel="shortcut icon" type="image/png" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/theme/<?php echo sfConfig::get('app_theme') ?>/images/favicon.png?v=<?php echo $chk ?>" />
  <link rel="apple-touch-icon" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/theme/<?php echo sfConfig::get('app_theme') ?>/images/apple-touch-icon-72x72-precomposed.png?v=<?php echo $chk ?>" />
  <link rel="apple-touch-icon" sizes="72x72" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/theme/<?php echo sfConfig::get('app_theme') ?>/images/apple-touch-icon-72x72-precomposed.png?v=<?php echo $chk ?>" />
  <link rel="apple-touch-icon" sizes="114x114" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/theme/<?php echo sfConfig::get('app_theme') ?>/images/apple-touch-icon-114x114-precomposed.png?v=<?php echo $chk ?>" />
  <link rel="manifest" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/manifest.json?v=<?php echo $chk ?>">
  <meta name="theme-color" content="#000000">

  <!-- Stylesheets -->
  <!--[if lt IE 9]>
    <script src="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/javascripts/html5.js?v=<?php echo $chk ?>"></script>
    <![endif]-->
  <link rel="stylesheet" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/stylesheets/main.css?v=<?php echo $chk ?>" type="text/css">
  <link rel="stylesheet" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/stylesheets/reset.css?v=<?php echo $chk ?>" type="text/css"><!--[if (gt IE 8) | (IEMobile)]><!-->
  <link rel="stylesheet" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/stylesheets/layout.css?v=<?php echo $chk ?>" type="text/css"><!--<![endif]-->
  <!--[if (lt IE 9) & (!IEMobile)]>
    <link rel="stylesheet" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/stylesheets/ie.css?v=<?php echo $chk ?>" />
    <![endif]-->
  <link type="text/css" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/player/skin/ma2/ma.css?v=<?php echo $chk ?>" rel="stylesheet">

  <link type="text/css" href="<?php echo sprintf('%s/theme/%s/main.css?v=%s', $sf_request->getRelativeUrlRoot(), sfConfig::get('app_theme', 'musiqueapproximative'), $chk) ?>" rel="stylesheet">

  <?php include_http_metas() ?>
  <?php foreach ($sf_context->getResponse()->getMetas() as $name => $content): ?>
    <meta property="<?php echo $name ?>" content="<?php echo html_entity_decode(html_entity_decode($content)) ?>" />
  <?php endforeach ?>

  <!-- Opengraph -->
  <meta property="og:site_name" content="<?php echo sfConfig::get('app_title') ?>" />

  <?php include_title() ?>

  <!-- oEmbed -->
  <link rel="alternate" type="application/json+oembed" href="<?php echo url_for(sprintf('@post_oembed?format=json&url=http://%s%s', sfConfig::get('app_domain'), $_SERVER['REQUEST_URI'], true)) ?>" />
  <link rel="alternate" type="text/xml+oembed" href="<?php echo url_for(sprintf('@post_oembed?format=xml&url=http://%s%s', sfConfig::get('app_domain'), $_SERVER['REQUEST_URI'], true)) ?>" />

  <!-- Formats -->
  <?php include_slot('formats_head') ?>

  <!-- RSS -->
  <!--
    <link type="application/rss+xml" title="<?php echo sfConfig::get('app_title') ?>" rel="alternate" href="<?php echo url_for('@post_feed') ?>"/>
    -->
  <link type="application/rss+xml" title="<?php echo sfConfig::get('app_title') ?>" rel="alternate" href="http://feeds.feedburner.com/musique-approximative" />

  <!-- Désastres -->
  <?php foreach (sfContext::getInstance()->getResponse()->getStylesheets() as $file => $options): ?>
    <?php echo stylesheet_tag($file.'?v='.$chk, $options) ?>
  <?php endforeach ?>
</head>

<body>
  <script type="text/javascript">
    window.script_name = '<?php echo $sf_request->getScriptName() ?>';
    window.autoplay = <?php echo $sf_request->getParameter('play', sfConfig::get('app_autoplay', 0)) ?>;
    window.random = <?php echo $sf_request->getParameter('random', 0) ?>;
    <?php if ($sf_request->getParameter('c')): ?>
      window.c = '<?php echo $sf_request->getParameter('c') ?>';
    <?php endif; ?>
  </script>

  <div class="grid-container">
    <header>
      <?php include_slot('browse') ?>
      <div class="search-container">
        <form id="search" method="get" action="<?php echo url_for('post_list') ?>">
          <input type="text" class="search" name="q" value="<?php echo $sf_request->getParameter('q') ?>"> <input type="submit" class="submit" value="Search !">
        </form>
      </div>
      <!-- posts list -->
      <div id="index" style="display: none;"></div>

    </header>

    <?php echo $sf_content ?>


    <?php if (sfConfig::get('app_theme') == 'quickos'): ?>
      <div class="grid-90 prefix-5 suffix-5" style="text-align: center;">
        <img src="<?php echo sprintf('%s/quickos/quickos_recto.png', $sf_request->getRelativeUrlRoot()) ?>" style="width:20%" />
      </div>
    <?php endif ?>
    
    <section class="contributors">
    <p class="title">
    Musique Approximative </p>
      <div class="wrapper">

        <div class="about">
          <h1>
            À propos
          </h1>
          <p>
            C'est l'exutoire anarchique d'une bande de mélomanes fêlé⋅e⋅s. C’est une playlist infernale alimentée chaque jour par les obsessions et les découvertes de chacun⋅e. L’arbitraire y est roi et on s’y amuse bien : c’est Musique Approximative.
          </p>
          <h2>
            Contact
          </h2>
          <p>
            <a href="mailto:bertier@musiqueapproximative.net">bertier@musiqueapproximative.net</a>
          </p>
          <h2>
            Abonnement
          </h2>
          <p>
            <a href="http://feeds.feedburner.com/musique-approximative">RSS</a>
          </p>
          <h2>
            Raccourcis
          </h2>
          <ul class="shortcuts" id="shortcuts">
            <li>espace : play / pause</li>
            <li>j : morceau précédent</li>
            <li>k : morceau suivant</li>
            <li>r : aléatoire</li>
            <li>s : recherche</li>
          </ul>
          <h2>
            Radio Approximative
          </h2>
          <p>
            <a href="http://radio.musiqueapproximative.net">Radio Approximative</a> est un projet musical et informatique où chaque émission est générée aléatoirement à partir du corpus de morceaux disponibles et de génériques et jingles créés par les contributeurs du site.
          </p>
          <h2>
            Ondes
          </h2>
          <p>
            <a href="http://ondes.pantagruweb.club/public/musiqueapproximative">Pantagruweb</a> a bien mangé. Il ne compte pas s'arrêter là.
          </p>
          <h2>
            Crédits
          </h2>
          <p>
            Musique Approximative est développé par <a href="http://www.constructions-incongrues.net">Constructions Incongrues</a> et est hébergé par <a href="http://www.pastis-hosting.net">Pastis Hosting</a>. Le code source du projet est <a href="https://github.com/constructions-incongrues/musiqueapproximative">distribué</a> sous licence <a href="http://www.gnu.org/licenses/agpl-3.0.html">GNU AGPLv3</a>.
          </p>
          <p>
            <br />Le <a href="https://gliche.constructions-incongrues.net/glitch?amount=75&url=<?php echo $sf_request->getUriPrefix() . $sf_request->getRelativeUrlRoot() . '/images/logo.png?seed=' . rand(1, 10000000) . '&amount=75' ?>">logo</a> a été créé par <a href="http://iris.ledrome.com/">Iris Veverka</a>.
          </p>
          <h2>
            Aidez-nous !
          </h2>
          <p>Le fonctionnement de ce site demande du temps et de l'argent. Vous pouvez nous aider en nous faisant un <a href="https://www.helloasso.com/associations/constructions-incongrues">don</a>!</p>
          <h2>Contribution</h2>
          <p><a href="https://www.musiqueapproximative.net/login">Se connecter</a></p>
          <?php include_slot('formats_footer') ?>
        </div>
        <div class="contributors_ul">
          <?php include_component('post', 'contributors') ?>
        </div>
      </div><!-- .grid-90 -->
    </section>

  </div><!-- grid-container -->
  <script src="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/javascripts/jquery.js?v=<?php echo $chk ?>" type="text/javascript"></script>
  <script src="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/javascripts/jquery.hotkeys.js?v=<?php echo $chk ?>" type="text/javascript"></script>
  <script src="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/player/jquery.jplayer.min.js?v=<?php echo $chk ?>" type="text/javascript"></script>
  <!-- Désastres -->
  <?php include_javascripts(); ?>
  <script type="text/javascript">
    //<![CDATA[
    $(document).ready(function() {
      $('#jquery_jplayer_1').jPlayer({
        cssSelectorAncestor: '#jp_container_1',
        swfPath: '<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/player',
        solution: 'html, flash',
        supplied: 'mp3',
        volume: 0.8,
        errorAlerts: false,
        warningAlerts: false,
        preload: 'metadata',
        ready: function(event) {
          $(this).jPlayer("setMedia", {
            mp3: window.trackUrl
          });
          if (window.autoplay) {
            $('#jquery_jplayer_1').jPlayer("play");
          }
        }
      });

      $("#jquery_jplayer_1").bind($.jPlayer.event.ended, function(event) {
        window.location = $('.nav-l a').attr('href') + '&play=1';
      });

      // Volume sliding support
      var moveVolume = function(e) {
        var $bar = $('.jp-volume-bar');
        var offset = $bar.offset();
        var x = e.pageX - offset.left;
        var w = $bar.width();
        var pc = x / w;
        if (pc > 1) pc = 1;
        if (pc < 0) pc = 0;
        $('#jquery_jplayer_1').jPlayer("volume", pc);
      };

      $('.jp-volume-bar').mousedown(function(e) {
        $(document).on('mousemove.jp-volume', moveVolume);
        moveVolume(e);
        return false;
      });

      $(document).mouseup(function() {
        $(document).off('mousemove.jp-volume');
        $(document).off('mousemove.jp-pitch');
      });

      // Pitch sliding support
      // playbackRate: 0.5 = 1 octave en dessous, 1.0 = normal (centré), 1.5 = limite supérieure
      var pitchValue = 1.0; // Valeur par défaut (normal)
      var pitchMin = 0.5;
      var pitchMax = 1.5;
      var updatePitchBar = function() {
        // Convertir playbackRate (0.5-1.5) en pourcentage (0-100%)
        // 0.5 -> 0%, 1.0 -> 50% (centré), 1.5 -> 100%
        var percentage = ((pitchValue - pitchMin) / (pitchMax - pitchMin)) * 100;
        // Positionner le rond (handle) selon le pourcentage
        var $handle = $('.jp-pitch-handle');
        if ($handle.length) {
          $handle.css({
            'left': percentage + '%',
            'transform': 'translate(-50%, -50%)'
          });
          // Mettre à jour l'affichage de la valeur
          $('.jp-pitch-value').text('x' + pitchValue.toFixed(2));
        }
      };

      var movePitch = function(e) {
        var $bar = $('.jp-pitch-bar');
        var offset = $bar.offset();
        var x = e.pageX - offset.left;
        var w = $bar.width();
        var pc = x / w;
        if (pc > 1) pc = 1;
        if (pc < 0) pc = 0;
        // Convertir pourcentage (0-100%) en playbackRate (0.5-1.5)
        pitchValue = pitchMin + (pc * (pitchMax - pitchMin));
        var audioElement = document.querySelector('.jp-jplayer audio');
        if (audioElement) {
          audioElement.playbackRate = pitchValue;
        }
        updatePitchBar();
      };

      $('.jp-pitch-bar').mousedown(function(e) {
        $(document).on('mousemove.jp-pitch', movePitch);
        movePitch(e);
        return false;
      });

      $('.jp-pitch-handle').mousedown(function(e) {
        e.stopPropagation();
        $(document).on('mousemove.jp-pitch', movePitch);
        return false;
      });

      // Initialiser la barre de pitch une fois le DOM prêt
      setTimeout(function() {
        updatePitchBar();
      }, 100);

      $('a.email-subscription-link').click(function(event) {
        $('div#email-subscription').toggle();
      });

      var h = $('.content').height();
      var pad = (h - 30) / 2;
      
      // Positionner verticalement nav-l et nav-r selon la position de .jp-progress
      function positionNavElements() {
        var $progress = $('.jp-progress');
        var $navL = $('.nav-l');
        var $navR = $('.nav-r');
        
        if ($progress.length && ($navL.length || $navR.length)) {
          // Récupérer la position de .jp-progress par rapport au document
          var progressOffset = $progress.offset();
          var progressHeight = $progress.outerHeight();
          
          // Récupérer la position du parent (wrapper) pour calculer la position relative
          var $wrapper = $('.content > .wrapper');
          var wrapperOffset = $wrapper.offset();
          
          if (progressOffset && wrapperOffset) {
            // Calculer la position verticale du centre de .jp-progress par rapport au wrapper
            var progressCenterY = (progressOffset.top - wrapperOffset.top) + (progressHeight / 2);
            
            // Appliquer le positionnement
            $navL.css({
              'position': 'relative',
              'top': progressCenterY + 'px',
              'transform': 'translateY(-50%)'
            });
            
            $navR.css({
              'position': 'relative',
              'top': progressCenterY + 'px',
              'transform': 'translateY(-50%)'
            });
          }
        }
      }
      
      // Positionner immédiatement
      positionNavElements();
      
      // Repositionner lors du redimensionnement de la fenêtre
      $(window).on('resize', positionNavElements);
      
      // Repositionner après un court délai pour s'assurer que le DOM est complètement chargé
      setTimeout(positionNavElements, 100);
      // $('.nav-l img, .nav-r img').height(h);
      // $('.nav-l img, .nav-r img').css({
      //   'padding-top': pad + 'px',
      //   'padding-bottom': pad + 'px'
      // });

      if (window.random !== 0) {
        var current_post_id = $('#download').data().postid;
        var queryCommon = 'play=' + window.autoplay + '&random=' + window.random;
        if (window.c != undefined) {
          queryCommon += '&c=' + window.c;
        }
        var urlRandom = window.script_name + '/posts/random?current=' + current_post_id + '&' + queryCommon;
        $.get(urlRandom, {}, function(data) {
          $('.nav-l a').attr('href', data.url + '?' + queryCommon).attr('title', data.title);
        });
        $.get(urlRandom, {}, function(data) {
          $('.nav-r a').attr('href', data.url + '?' + queryCommon).attr('title', data.title);
        });
      } else {
        $('#random').addClass('not');
      }

      // Randomx button
      $('#random').click(
        function(event) {
          var current_post_id = $('#download').data().postid;
          if ($(this).hasClass('not')) {
            $(this).removeClass('not');
            window.random = 1;
            var queryCommon = 'play=' + window.autoplay + '&random=' + window.random;
            if (window.c != undefined) {
              queryCommon += '&c=' + window.c;
            }
            var urlRandom = window.script_name + '/posts/random?current=' + current_post_id + '&' + queryCommon;
            $.get(urlRandom, {}, function(data) {
              $('.nav-l a').attr('href', data.url + '?' + queryCommon).attr('title', data.title);
            });
            $.get(urlRandom, {}, function(data) {
              $('.nav-r a').attr('href', data.url + '?' + queryCommon).attr('title', data.title);
            });
          } else {
            $(this).addClass('not');
            window.random = 0;
            var queryCommon = 'play=' + window.autoplay + '&random=' + window.random;
            if (window.c != undefined) {
              queryCommon += '&c=' + window.c;
            }
            $.get(
              window.script_name + '/posts/prev?current=' + current_post_id + '&' + queryCommon, {},
              function(data) {
                $('.nav-l a').attr('href', data.url + '?' + queryCommon).attr('title', data.title);
              });
            $.get(
              window.script_name + '/posts/next?current=' + current_post_id + '&' + queryCommon, {},
              function(data) {
                $('.nav-r a').attr('href', data.url + '?' + queryCommon).attr('title', data.title);
              });

          }

          // TODO : get appropriate information and update links titles
          $('.nav-r a').attr('title', '');
          $('.nav-l a').attr('title', '');

          return false;
        });

      /*
       * Hotkeys
       * @see https://github.com/jeresig/jquery.hotkeys
       */

      // play / pause
      $(document).bind('keydown', 'space', function() {
        var $player = $('#jquery_jplayer_1');
        var isPaused = $player.data().jPlayer.status.paused;
        if (isPaused) {
          $player.jPlayer('play');
        } else {
          $player.jPlayer('pause');
        }
        return false;
      });

      // random
      $(document).bind('keydown', 'r', function() {
        $('#random').click();
        return false;
      });

      // random
      $(document).bind('keydown', 's', function() {
        $('.search').focus();
        return false;
      });

      // previous track
      $(document).bind('keydown', 'j', function() {
        var url = $('.nav-l a').attr('href');
        if (url != undefined) {
          window.location = url;
        }
        return false;
      });

      // next track
      $(document).bind('keydown', 'k', function() {
        var url = $('.nav-r a').attr('href')
        if (url != undefined) {
          window.location = url;
        }
        return false;
      });
    });
    //]]>
  </script>
  <script type="text/javascript">
    var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
    document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
  </script>
  <script type="text/javascript">
    try {
      var pageTracker = _gat._getTracker("UA-4958604-1");
      pageTracker._trackPageview();
    } catch (err) {}
  </script>
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function() {
        navigator.serviceWorker.register('<?php echo $sf_request->getRelativeUrlRoot() ?>/sw.js').then(function(registration) {
          console.log('ServiceWorker registration successful with scope: ', registration.scope);
        }, function(err) {
          console.log('ServiceWorker registration failed: ', err);
        });
      });
    }
  </script>
</body>

</html>