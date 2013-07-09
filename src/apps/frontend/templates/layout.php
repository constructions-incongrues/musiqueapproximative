<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">

    <!-- favicon and other icons -->
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="pinned-icon.png">
    <meta name="application-name" content="Musique Approximative">
    <link rel="shortcut icon" type="image/png" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/img/favicon.png"/>
    <link rel="apple-touch-icon" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/img/apple-touch-icon-72x72-precomposed.png"/>
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/img/apple-touch-icon-72x72-precomposed.png"/>
    <link rel="apple-touch-icon" sizes="114x114" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/img/apple-touch-icon-114x114-precomposed.png"/>

    <!-- Stylesheets -->
    <!--[if lt IE 9]>
    <script src="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/javascripts/html5.js"></script>
    <![endif]-->
    <link rel="stylesheet" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/stylesheets/main.css" type="text/css">
    <link rel="stylesheet" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/stylesheets/reset.css" type="text/css"><!--[if (gt IE 8) | (IEMobile)]><!-->
    <link rel="stylesheet" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/stylesheets/unsemantic-grid-responsive.css" type="text/css"><!--<![endif]-->
    <!--[if (lt IE 9) & (!IEMobile)]>
    <link rel="stylesheet" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/stylesheets/ie.css" />
    <![endif]-->
    <link type="text/css" href="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/player/skin/ma2/ma.css" rel="stylesheet">

    <?php include_http_metas() ?>
    <?php include_metas() ?>

    <!-- Opengraph -->
    <meta property="og:site_name" content="Musique Approximative" />

    <?php include_title() ?>

    <!-- XSPF -->
    <link rel="alternate" type="application/xspf+xml" title="La playlist au format XSPF" href="<?php echo url_for('@post_list?sf_format=xspf') ?>" />

    <!-- oEmbed -->
    <link rel="alternate" type="application/json+oembed" href="<?php echo url_for('@post_oembed?format=json&url=http://www.musiqueapproximative.net'.$_SERVER['REQUEST_URI'], true) ?>" />
    <link rel="alternate" type="text/xml+oembed" href="<?php echo url_for('@post_oembed?format=xml&url=http://www.musiqueapproximative.net'.$_SERVER['REQUEST_URI'], true) ?>" />

    <!-- RSS -->
    <!--
    <link type="application/rss+xml" title="Musique Approximative" rel="alternate" href="<?php echo url_for('@post_feed') ?>"/>
    -->
    <link type="application/rss+xml" title="Musique Approximative" rel="alternate" href="http://feeds.feedburner.com/musique-approximative"/>
  </head>
  <body>
    <script type="text/javascript">
    window.script_name = '<?php echo $sf_request->getScriptName() ?>';
    window.autoplay    = <?php echo $sf_request->getParameter('play', 0) ?>;
    window.random      = <?php echo $sf_request->getParameter('random', 0) ?>;
<?php if ($sf_request->getParameter('c')): ?>
    window.c           = '<?php echo $sf_request->getParameter('c') ?>';
<?php endif; ?>
    </script>

    <div class="grid-container">
        <header class="grid-100 grid-parent">
            <div class="grid-40">
                <?php include_slot('browse') ?>
            </div>
            <div class="grid-60 hide-on-mobile">
                <form id="search" method="get" action="<?php echo url_for('post_list') ?>">
                    <input type="text" class="search" name="q" value=""> <input type="submit" class="submit" value="Search !">
                </form>
            </div>
            <div class="grid-60 hide-on-desktop">
                <form id="search" method="get" action="<?php echo url_for('post_list') ?>">
                    <input type="text" class="search" name="q" value=""> <input type="submit" class="submit" value="Search !">
                </form>
            </div>
            <!-- posts list -->
                        <div id="index" style="display: none;"></div>

        </header>

<?php echo $sf_content ?>

            <div class="grid-90 prefix-5 suffix-5 infos">
                <p class="title hide-on-mobile">
                    Musique Approximative
                </p>
                <p class="title hide-on-desktop">
                    Musique Approximative
                </p>
            </div>
            <section class="contributors">
                <div class="grid-90 prefix-5 suffix-5">

                     <div class="grid-50 push-50 about">
                        <h1>
                            À propos
                        </h1>
                        <p>
                            C'est l'exutoire anarchique d'une bande de mélomanes fêlés. C’est une playlist infernale alimentée chaque jour par les obsessions et les découvertes de chacun. L’arbitraire y est roi et on s’y amuse bien : c’est Musique Approximative.
                        </p>
                        <h2>
                            Consommer de la musique autrement :
                        </h2>
                        <p>
                            <a href="http://feeds.feedburner.com/musique-approximative">RSS</a> 
                            / <a href="https://www.facebook.com/musiqueapproximative">Facebook</a> 
                            / <a href="http://twitter.com/approximazik">Twitter</a>
                            / <a href="#email-subscription" class="email-subscription-link">Email</a>
                        </p>
                        <div class="mailing-list" id="email-subscription" style="display:none;">
                            <form method="post" action="http://feedburner.google.com/fb/a/mailverify">
                                <input type="text" class="mailing" name="email" placeholder="Votre adresse email" />
                                <input type="submit" class="mailing-ok" value="Recevoir les nouveaux morceaux par email">
                            </form>
                        </div>
                        <h2>
                            Contact
                        </h2>
                        <p>
                            <a href="mailto:bertier@musiqueapproximative.net">bertier@musiqueapproximative.net</a>
                        </p>
                        <h2>
                            Raccourcis
                        </h2>
                        <ul class="shortcuts">
                            <li>espace : play / pause</li>
                            <li>j : morceau précédent</li>
                            <li>k : morceau suivant</li>
                            <li>p : parcourir tous les morceaux</li>
                            <li>c : parcourir les morceaux du contributeur</li>
                            <li>r : aléatoire</li>
                            <li>s : recherche</li>
                        </ul>
                        <h2>
                            Crédits
                        </h2>
                        <p>
                            Musique Approximative est développé par <a href="http://www.constructions-incongrues.net">Constructions Incongrues</a> et est hébergé par <a href="http://www.pastis-hosting.net">Pastis Hosting</a>. Le code source du projet est <a href="https://github.com/constructions-incongrues/net.musiqueapproximative.www">distribué</a> sous licence <a href="http://www.gnu.org/licenses/agpl-3.0.html">GNU AGPLv3</a>.
                        </p>
                        <p>
                            <br />Le <a href="<?php echo $sf_request->getRelativeUrlRoot() ?>/images/logo.png">logo</a> a été créé par <a href="http://iris.ledrome.com/">Iris Veverka</a>.
                        </p>
                        <p>
                             Les flèches ont été créées par <a href="http://www.juangarces.net/">Juan Garces</a> pour <a href="http://thenounproject.com/">The Noun Project</a>.
                        </p> 
                    </div>
                    <div class="grid-50 pull-50">
                        <?php include_component('post', 'contributors') ?>
                    </div>                   
                </div><!-- .grid-90 -->
            </section>

    </div><!-- grid-container -->
    <script src="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/javascripts/jquery.js" type="text/javascript"></script>
    <script src="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/javascripts/jquery.hotkeys.js" type="text/javascript"></script>
    <script src="<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/player/jquery.jplayer.min.js" type="text/javascript"></script>
    <script type="text/javascript">
//<![CDATA[
        $(document).ready(function(){
            $('#jquery_jplayer_1').jPlayer({
                swfPath: '.<?php echo $sf_request->getRelativeUrlRoot() ?>/frontend/assets/player',
                solution: 'html',
                supplied: 'mp3',
                errorAlerts: false,
                warningAlerts: false,
                ready: function (event) {
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

            // "Browse tracks"
            $('.index-toggle-all, .index-toggle-contributor').click(function(event) {
              if ($('#index').is(':visible')) {
                $('#index, #close').hide();
                $('.index-toggle-all, .index-toggle-contributor').removeClass('current');
              } else {
                  $('#loading').fadeIn();
                  $('#index').load($(event.target).attr('href'), function() {
                    $('#loading').fadeOut();
                        $('#close').show();
                    if (!$('#index').is(':visible')) {
                      $('#index').show();
                    }
                    $(event.target).addClass('current');
                  });
              }
              return false;
            });

            $('a.email-subscription-link').click(function(event) {
              $('div#email-subscription').toggle();
            });

            $('.nav-l img, .nav-r img').height($('.content').height());

            if (window.random) {
              var current_post_id = $('#download').data().postid;
              var queryCommon = 'play=' + window.autoplay + '&random=' + window.random;
              if (window.c != undefined) {
                queryCommon += '&c=' + window.c;
              }
              var urlRandom = window.script_name + '/posts/random?current=' + current_post_id + '&' + queryCommon;
              $.get(urlRandom, {}, function(data) {
                $('.nav-l a').attr('href', data + '?' + queryCommon);
              });
              $.get(urlRandom, {}, function(data) {
                $('.nav-r a').attr('href', data + '?' + queryCommon);
              });
            }

            // Random button
            $('#random').click(
              function(event) {
                event.preventDefault();
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
                    $('.nav-l a').attr('href', data + '?' + queryCommon);
                  });
                  $.get(urlRandom, {}, function(data) {
                    $('.nav-r a').attr('href', data + '?' + queryCommon);
                  });
                } else {
                  $(this).addClass('not');
                  window.random = 0;
                  var queryCommon = 'play=' + window.autoplay + '&random=' + window.random;
                  if (window.c != undefined) {
                    queryCommon += '&c=' + window.c;
                  }
                  $.get(
                    window.script_name + '/posts/prev?current=' + current_post_id + '&' + queryCommon,
                    {}, function(data) {
                      $('.nav-l a').attr(
                        'href',
                        data + '?' + queryCommon);
                    });
                  $.get(
                    window.script_name + '/posts/next?current=' + current_post_id + '&' + queryCommon,
                    {}, function(data) {
                      $('.nav-r a').attr(
                        'href',
                        data + '?' + queryCommon);
                    });
                }

                  // TODO : get appropriate information and update links titles
                  $('.nav-r a').attr('title', '');
                  $('.nav-l a').attr('title', '');
                });

            $('form#search').submit(function() {
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

             // browse tracks (all)
             $(document).bind('keydown', 'p', function() {
                $('.index-toggle-all').click();
                return false;
             });

             // browse tracks (all)
             $(document).bind('keydown', 'c', function() {
                $('.index-toggle-contributor').click();
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
        } catch(err) {}</script>
  </body>
</html>
