<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';
require_once sfConfig::get('sf_lib_dir').'/helper/ApiResponse.php';

$t = new lime_test(18);

// --- ApiResponse::resource() ---

$t->diag('ApiResponse::resource()');

$result = ApiResponse::resource('my-post-slug', 'posts', ['title' => 'Test Post', 'body' => 'Content']);

$t->is($result['type'], 'posts', '->resource() sets type');
$t->is($result['id'], 'my-post-slug', '->resource() sets id as string');
$t->is($result['attributes']['title'], 'Test Post', '->resource() sets attributes');
$t->ok(!isset($result['attributes']['id']), '->resource() does not include id in attributes');
$t->ok(!isset($result['attributes']['type']), '->resource() does not include type in attributes');

$result_with_rels = ApiResponse::resource('1', 'posts', ['title' => 'Test'], [
  'contributor' => ['data' => ['type' => 'users', 'id' => '42']]
]);
$t->is($result_with_rels['relationships']['contributor']['data']['type'], 'users', '->resource() sets relationships');

$result_with_links = ApiResponse::resource('1', 'posts', ['title' => 'Test'], [], [
  'self' => '/api/posts/1'
]);
$t->is($result_with_links['links']['self'], '/api/posts/1', '->resource() sets links');

$result_no_optional = ApiResponse::resource('1', 'posts', ['title' => 'Test']);
$t->ok(!isset($result_no_optional['relationships']), '->resource() omits empty relationships');
$t->ok(!isset($result_no_optional['links']), '->resource() omits empty links');

// --- ApiResponse::data() ---

$t->diag('ApiResponse::data()');

$resource = ApiResponse::resource('1', 'posts', ['title' => 'Test']);
$response = ApiResponse::data($resource);

$t->ok(array_key_exists('data', $response), '->data() wraps in data key');
$t->is($response['data']['id'], '1', '->data() preserves resource data');
$t->ok(!isset($response['meta']), '->data() omits empty meta');
$t->ok(!isset($response['links']), '->data() omits empty links');

$response_with_meta = ApiResponse::data($resource, ['total' => 42]);
$t->is($response_with_meta['meta']['total'], 42, '->data() includes meta when provided');

$response_with_links = ApiResponse::data($resource, [], ['self' => '/api/posts/1']);
$t->is($response_with_links['links']['self'], '/api/posts/1', '->data() includes links when provided');

// --- ApiResponse::collection() ---

$t->diag('ApiResponse::collection()');

$items = [
  ApiResponse::resource('1', 'posts', ['title' => 'Test 1']),
  ApiResponse::resource('2', 'posts', ['title' => 'Test 2']),
];
$response = ApiResponse::collection($items);

$t->ok(is_array($response['data']), '->collection() returns array in data');
$t->is(count($response['data']), 2, '->collection() contains all items');

$response_with_meta = ApiResponse::collection($items, ['total' => 100, 'limit' => 20, 'offset' => 0]);
$t->is($response_with_meta['meta']['total'], 100, '->collection() includes meta when provided');
