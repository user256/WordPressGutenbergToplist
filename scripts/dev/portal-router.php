<?php
/**
 * PHP built-in server router for the portal repo (local license E2E).
 *
 * Usage (from toplist repo):
 *   PORTAL_ROOT=/path/to/portal php -S 127.0.0.1:9080 -t "$PORTAL_ROOT" scripts/dev/portal-router.php
 *
 * @package Toplist_Block
 */

declare(strict_types=1);

$portal_root = getenv('PORTAL_ROOT');
if (!is_string($portal_root) || $portal_root === '') {
	$portal_root = realpath(dirname(__DIR__, 3) . '/../portal');
}
if (!is_string($portal_root) || $portal_root === '' || !is_dir($portal_root)) {
	http_response_code(500);
	header('Content-Type: text/plain; charset=utf-8');
	echo "PORTAL_ROOT is not set and portal repo was not found.\n";
	return true;
}

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Module API: /api/v1/<module>/<path> → module-dispatch.php (mirrors api/v1/.htaccess).
if (preg_match('#^/api/v1/([a-z0-9-]+)/(.+)$#', $uri, $matches)) {
	$_GET['module'] = $matches[1];
	$_GET['path']     = '/' . ltrim(str_replace('\\', '/', $matches[2]), '/');
	require $portal_root . '/api/v1/module-dispatch.php';
	return true;
}

// Legacy v1 endpoints without a module prefix: /api/v1/<script>.php
if (preg_match('#^/api/v1/([^/]+)$#', $uri, $matches)) {
	$candidate = $portal_root . '/api/v1/' . $matches[1] . '.php';
	if (is_file($candidate)) {
		require $candidate;
		return true;
	}
}

$static = $portal_root . $uri;
if ($uri !== '/' && is_file($static)) {
	return false;
}

$index = $portal_root . '/index.php';
if (is_file($index)) {
	require $index;
	return true;
}

http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "Not Found\n";
return true;
