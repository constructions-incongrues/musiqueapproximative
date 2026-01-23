<?php
class musiqueapproximativePurgeCloudflareCacheTask extends sfBaseTask
{
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('purge-all', null, sfCommandOption::PARAMETER_NONE, 'Purge all files (instead of just HTML)'),
    ));

    $this->namespace        = 'musiqueapproximative';
    $this->name             = 'purge-cloudflare-cache';
    $this->briefDescription = 'Purges Cloudflare cache for the configured zone.';
    $this->detailedDescription = <<<EOF
The [musiqueapproximative:purge-cloudflare-cache|INFO] task purges Cloudflare cache for the configured zone.
Call it with:

  [php symfony musiqueapproximative:purge-cloudflare-cache|INFO]

To purge all files:

  [php symfony musiqueapproximative:purge-cloudflare-cache --purge-all|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // Get configuration from environment variables
    $apiToken = getenv('CLOUDFLARE_API_TOKEN');
    $zoneId = getenv('CLOUDFLARE_ZONE_ID');

    // Validate configuration
    if (empty($apiToken)) {
      $this->logBlock('Cloudflare API token not configured. Set CLOUDFLARE_API_TOKEN environment variable', 'ERROR');
      return 1;
    }

    if (empty($zoneId)) {
      $this->logBlock('Cloudflare Zone ID not configured. Set CLOUDFLARE_ZONE_ID environment variable', 'ERROR');
      return 1;
    }

    // Build API request
    $apiUrl = sprintf('https://api.cloudflare.com/client/v4/zones/%s/purge_cache', $zoneId);

    // Prepare payload
    if ($options['purge-all']) {
      $payload = json_encode(array('purge_everything' => true));
      $this->logSection('cloudflare', 'Purging all files from cache');
    } else {
      // Purge only HTML files by default
      $payload = json_encode(array(
        'types' => array('html')
      ));
      $this->logSection('cloudflare', 'Purging HTML files from cache');
    }

    // Make API request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Authorization: Bearer ' . $apiToken,
      'Content-Type: application/json'
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Parse response
    $result = json_decode($response, true);

    if ($httpCode !== 200 || !$result || !isset($result['success']) || !$result['success']) {
      $errorMessage = 'Unknown error';
      if ($result && isset($result['errors']) && is_array($result['errors']) && count($result['errors']) > 0) {
        $errorMessage = $result['errors'][0]['message'];
      }
      $this->logBlock(sprintf('Failed to purge cache (HTTP %d): %s', $httpCode, $errorMessage), 'ERROR');
      return 1;
    }

    $this->logSection('cloudflare', 'Cache purged successfully');

    if (isset($result['result']['id'])) {
      $this->logSection('cloudflare', sprintf('Purge ID: %s', $result['result']['id']));
    }

    return 0;
  }
}
