<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';
require_once sfConfig::get('sf_lib_dir').'/filter/JsonApiFilter.class.php';

$t = new lime_test(8);

// --- Mock Classes ---

class MockRequest
{
  private $parameters = [];
  private $requestFormat = 'json';
  
  public function setParameter($name, $value)
  {
    $this->parameters[$name] = $value;
  }
  
  public function getParameter($name, $default = null)
  {
    return isset($this->parameters[$name]) ? $this->parameters[$name] : $default;
  }
  
  public function setRequestFormat($format)
  {
    $this->requestFormat = $format;
  }
  
  public function getRequestFormat()
  {
    return $this->requestFormat;
  }
}

class MockResponse
{
  private $contentType = 'application/json; charset=utf-8';
  
  public function setContentType($type)
  {
    $this->contentType = $type;
  }
  
  public function getContentType()
  {
    return $this->contentType;
  }
}

class MockContext
{
  private $request;
  private $response;
  private $moduleName;

  public function __construct()
  {
    $this->request = new MockRequest();
    $this->response = new MockResponse();
  }

  public function getRequest()
  {
    return $this->request;
  }

  public function getResponse()
  {
    return $this->response;
  }

  public function setModuleName($moduleName)
  {
    $this->moduleName = $moduleName;
  }

  // JsonApiFilter::execute() now short-circuits on the 'rest' module (see
  // the guard added for the Subsonic API) : the mock needs this method too,
  // or every test using it fatals before reaching any assertion.
  public function getModuleName()
  {
    return $this->moduleName;
  }
}

class MockFilterChain
{
  public function execute()
  {
    // No-op for testing
  }
}

// --- Helper to create mock filter with mocked request/response ---

function createFilterWithMockContext($module, $action, $requestFormat = 'json', $existingContentType = null)
{
  $context = new MockContext();
  $context->setModuleName($module);

  // Set up request
  $request = $context->getRequest();
  $request->setParameter('module', $module);
  $request->setParameter('action', $action);
  $request->setRequestFormat($requestFormat);
  
  // Set up response
  $response = $context->getResponse();
  if ($existingContentType !== null) {
    $response->setContentType($existingContentType);
  } else {
    $response->setContentType('application/json; charset=utf-8');
  }
  
  $filter = new JsonApiFilter($context);
  
  return [$filter, $context];
}

// --- Test: Filter applies to post/show ---

$t->diag('Filter applies to JSON API endpoints');

list($filter, $context) = createFilterWithMockContext('post', 'show');
$chain = new MockFilterChain();
$filter->execute($chain);

$response = $context->getResponse();
$contentType = $response->getContentType();
$t->like($contentType, '/application\/vnd\.api\+json/', 'post/show gets JSON API content-type');

// --- Test: Filter applies to post/list ---

list($filter, $context) = createFilterWithMockContext('post', 'list');
$chain = new MockFilterChain();
$filter->execute($chain);

$response = $context->getResponse();
$contentType = $response->getContentType();
$t->like($contentType, '/application\/vnd\.api\+json/', 'post/list gets JSON API content-type');

// --- Test: Filter applies to posts module (wildcard) ---

list($filter, $context) = createFilterWithMockContext('posts', 'index');
$chain = new MockFilterChain();
$filter->execute($chain);

$response = $context->getResponse();
$contentType = $response->getContentType();
$t->like($contentType, '/application\/vnd\.api\+json/', 'posts/* gets JSON API content-type');

// --- Test: Filter SKIPS oembed endpoint ---

$t->diag('Filter skips non-JSON API endpoints');

list($filter, $context) = createFilterWithMockContext('post', 'oembed');
$chain = new MockFilterChain();
$filter->execute($chain);

$response = $context->getResponse();
$contentType = $response->getContentType();
$t->unlike($contentType, '/application\/vnd\.api\+json/', 'post/oembed does NOT get JSON API content-type');
$t->like($contentType, '/application\/json/', 'post/oembed keeps application/json');

// --- Test: Filter skips non-JSON formats ---

list($filter, $context) = createFilterWithMockContext('post', 'show', 'html', 'text/html');
$chain = new MockFilterChain();
$filter->execute($chain);

$response = $context->getResponse();
$contentType = $response->getContentType();
$t->unlike($contentType, '/application\/vnd\.api\+json/', 'HTML format does NOT get JSON API content-type');

// --- Test: Filter works with format parameter ---

list($filter, $context) = createFilterWithMockContext('post', 'show');
$context->getRequest()->setParameter('format', 'json');
$chain = new MockFilterChain();
$filter->execute($chain);

$response = $context->getResponse();
$contentType = $response->getContentType();
$t->like($contentType, '/application\/vnd\.api\+json/', 'post/show with format=json gets JSON API content-type');

// --- Test: Filter handles missing module/action gracefully ---

$t->diag('Filter handles edge cases');

$context = new sfContext();
$response = $context->getResponse();
$response->setContentType('application/json');
$filter = new JsonApiFilter($context);
$chain = new MockFilterChain();
$filter->execute($chain);

$response = $context->getResponse();
$contentType = $response->getContentType();
$t->unlike($contentType, '/application\/vnd\.api\+json/', 'Missing module/action does NOT apply JSON API content-type');
