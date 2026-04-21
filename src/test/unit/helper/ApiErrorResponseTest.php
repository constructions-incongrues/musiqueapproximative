<?php

require_once dirname(__FILE__).'/../../bootstrap/unit.php';
require_once sfConfig::get('sf_lib_dir').'/helper/ApiErrorResponse.php';

$t = new lime_test(12);

// --- Basic error format ---

$t->diag('ApiErrorResponse::format()');

$response = ApiErrorResponse::format(404, 'Not Found', 'Post not found');

$t->ok(array_key_exists('errors', $response), '->format() wraps in errors key');
$t->ok(is_array($response['errors']), '->format() errors is an array');
$t->is(count($response['errors']), 1, '->format() contains one error');
$t->is($response['errors'][0]['status'], '404', '->format() status is a string');
$t->is($response['errors'][0]['title'], 'Not Found', '->format() sets title');
$t->is($response['errors'][0]['detail'], 'Post not found', '->format() sets detail');

// --- Error with source ---

$t->diag('ApiErrorResponse::format() with source');

$response = ApiErrorResponse::format(422, 'Validation Error', 'Field is required', [
  'pointer' => '/data/attributes/title'
]);

$t->is($response['errors'][0]['source']['pointer'], '/data/attributes/title', '->format() includes source pointer');

// --- Error without optional fields ---

$t->diag('ApiErrorResponse::format() without optional fields');

$response = ApiErrorResponse::format(500, 'Internal Server Error');

$t->is($response['errors'][0]['status'], '500', '->format() works without detail');
$t->ok(!isset($response['errors'][0]['detail']), '->format() omits empty detail');
$t->ok(!isset($response['errors'][0]['source']), '->format() omits empty source');

// --- Multiple errors ---

$t->diag('ApiErrorResponse::formatMultiple()');

$errors = [
  ['status' => 422, 'title' => 'Validation Error', 'detail' => 'Title is required', 'source' => ['pointer' => '/data/attributes/title']],
  ['status' => 422, 'title' => 'Validation Error', 'detail' => 'Body is required', 'source' => ['pointer' => '/data/attributes/body']],
];
$response = ApiErrorResponse::formatMultiple($errors);

$t->is(count($response['errors']), 2, '->formatMultiple() contains multiple errors');
$t->is($response['errors'][1]['detail'], 'Body is required', '->formatMultiple() preserves all errors');
