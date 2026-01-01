<?php
class musiqueapproximativeGlitchLogoTask extends sfBaseTask
{
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'frontend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('amount', null, sfCommandOption::PARAMETER_OPTIONAL, 'Glitch amount', 75),
      new sfCommandOption('seed', null, sfCommandOption::PARAMETER_OPTIONAL, 'Glitch seed', null),
    ));

    $this->namespace        = 'musiqueapproximative';
    $this->name             = 'glitch-logo';
    $this->briefDescription = 'Glitches the logo using the Gliche service and saves it locally.';
    $this->detailedDescription = <<<EOF
The [musiqueapproximative:glitch-logo|INFO] task fetches a glitched version of the logo from the Gliche service.
Call it with:

  [php symfony musiqueapproximative:glitch-logo --amount=75|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $amount = $options['amount'];
    $seed = $options['seed'] ?: rand(1, 10000000);
    $logoUrl = 'https://www.musiqueapproximative.net/images/logo.png';
    
    $glicheUrl = sprintf(
        'https://gliche.constructions-incongrues.net/glitch?amount=%d&seed=%s&url=%s',
        $amount,
        $seed,
        urlencode($logoUrl)
    );

    $this->logSection('gliche', sprintf('Fetching glitched logo from %s', $glicheUrl));

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $glicheUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $data = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    if ($info['http_code'] !== 200) {
        $this->logBlock(sprintf('Error fetching glitched logo (HTTP %d)', $info['http_code']), 'ERROR');
        return 1;
    }

    $targetPath = sfConfig::get('sf_web_dir') . '/images/glitched_logo.png';
    file_put_contents($targetPath, $data);

    $this->logSection('gliche', sprintf('Saved glitched logo to %s', $targetPath));
  }
}
