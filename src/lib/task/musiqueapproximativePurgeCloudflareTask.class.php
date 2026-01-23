<?php

/**
 * Purge Cloudflare cache for musiqueapproximative.net
 *
 * @package    musiqueapproximative
 * @subpackage task
 * @author     Tristan Rivoallan
 */
class musiqueapproximativePurgeCloudflareTask extends sfBaseTask
{
  /**
   * @see sfTask
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_OPTIONAL, 'The application name', 'frontend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('zone-id', null, sfCommandOption::PARAMETER_OPTIONAL, 'Cloudflare zone ID (overrides config)'),
      new sfCommandOption('api-token', null, sfCommandOption::PARAMETER_OPTIONAL, 'Cloudflare API token (overrides config)'),
      new sfCommandOption('files', null, sfCommandOption::PARAMETER_OPTIONAL | sfCommandOption::IS_ARRAY, 'Specific files to purge (URLs)'),
    ));

    $this->namespace        = 'musiqueapproximative';
    $this->name             = 'purge-cloudflare';
    $this->briefDescription = 'Purge Cloudflare cache';
    $this->detailedDescription = <<<EOF
The [musiqueapproximative:purge-cloudflare|INFO] task purges the Cloudflare cache for the site.

Call it with:

  [php symfony musiqueapproximative:purge-cloudflare|INFO]

You can purge specific files by passing URLs:

  [php symfony musiqueapproximative:purge-cloudflare --files=https://www.musiqueapproximative.net/index.html --files=https://www.musiqueapproximative.net/css/main.css|INFO]

Or purge everything:

  [php symfony musiqueapproximative:purge-cloudflare|INFO]
EOF;
  }

  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    // Initialize application
    $databaseManager = new sfDatabaseManager($this->configuration);
    $context = sfContext::createInstance($this->configuration);

    // Get Cloudflare credentials from config or options
    $zoneId = $options['zone-id'] ?: sfConfig::get('app_cloudflare_zone_id');
    $apiToken = $options['api-token'] ?: sfConfig::get('app_cloudflare_api_token');

    if (!$zoneId || !$apiToken) {
      throw new sfException('Cloudflare zone ID and API token must be configured in app.yml or passed as options');
    }

    $this->logSection('cloudflare', 'Preparing to purge cache...');

    // Prepare the API request
    $url = "https://api.cloudflare.com/client/v4/zones/{$zoneId}/purge_cache";

    // Build the request body
    if (!empty($options['files'])) {
      // Purge specific files
      $data = json_encode(array('files' => $options['files']));
      $this->logSection('cloudflare', sprintf('Purging %d specific file(s)', count($options['files'])));
    } else {
      // Purge everything
      $data = json_encode(array('purge_everything' => true));
      $this->logSection('cloudflare', 'Purging everything');
    }

    // Make the API request using cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Authorization: Bearer ' . $apiToken,
      'Content-Length: ' . strlen($data)
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
      throw new sfException('cURL error: ' . $curlError);
    }

    $result = json_decode($response, true);

    if ($httpCode === 200 && isset($result['success']) && $result['success']) {
      $this->logSection('cloudflare', 'Cache purged successfully!', null, 'INFO');
      if (isset($result['result']['id'])) {
        $this->logSection('cloudflare', 'Purge ID: ' . $result['result']['id'], null, 'INFO');
      }
    } else {
      $errorMsg = 'Failed to purge cache';
      if (isset($result['errors']) && is_array($result['errors'])) {
        foreach ($result['errors'] as $error) {
          $errorMsg .= "\n  - " . (isset($error['message']) ? $error['message'] : json_encode($error));
        }
      } else {
        $errorMsg .= "\nHTTP Code: " . $httpCode . "\nResponse: " . $response;
      }
      throw new sfException($errorMsg);
    }
  }
}
