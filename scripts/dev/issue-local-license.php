<?php
/**
 * Issue (or re-issue) a lifetime dev license for a WordPress site domain.
 *
 * Usage: php scripts/dev/issue-local-license.php [domain]
 *
 * @package Toplist_Block
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
	fwrite(STDERR, "CLI only.\n");
	exit(1);
}

$portal_root = getenv('PORTAL_ROOT');
if (!is_string($portal_root) || $portal_root === '') {
	$portal_root = realpath(dirname(__DIR__, 3) . '/../portal');
}
if (!is_string($portal_root) || !is_dir($portal_root)) {
	fwrite(STDERR, "Portal repo not found. Set PORTAL_ROOT.\n");
	exit(1);
}

require_once $portal_root . '/api/lib/Bootstrap.php';
ScApi_Bootstrap::init();

$domain = isset($argv[1]) ? trim((string) $argv[1]) : '127.0.0.1';
$normalized = ScApi_Domain::normalize($domain);
if ($normalized === '' || !ScApi_Domain::isValidHost($normalized)) {
	fwrite(STDERR, "Invalid domain: {$domain}\n");
	exit(1);
}

$dev_email = 'toplist-dev@localhost';
$svc       = ScApi_Bootstrap::licenseService();
$pdo       = ScApi_Bootstrap::pdo();
$stmt      = $pdo->prepare(
	'SELECT id FROM licenses
	 WHERE domain_normalized = ?
	   AND (revoked_at IS NULL OR revoked_at = \'\')
	 ORDER BY id DESC LIMIT 1'
);
$stmt->execute(array($normalized));
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if (is_array($existing) && !empty($existing['id'])) {
	$reissued = $svc->reissueLicenseKeyByIdentity($normalized, $dev_email);
	if (!empty($reissued['ok']) && is_array($reissued['data']['license_key'] ?? null)) {
		echo (string) $reissued['data']['license_key'];
		exit(0);
	}
}

$issued = $svc->issueLicense($normalized, 'lifetime', null, $dev_email, 'single');
$key    = (string) ($issued['license_key'] ?? '');
if ($key === '') {
	fwrite(STDERR, "issueLicense did not return a key.\n");
	exit(1);
}

echo $key;
