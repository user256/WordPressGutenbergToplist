<?php
/**
 * Lite build smoke tests — run after scripts/build-lite.php
 *
 * Usage: php tests/build/run.php
 */

$root = dirname(__DIR__, 2);
$lite = $root . '/toplist-block-lite';
// Smoke builds use the placeholder upgrade URL; allow it (ticket 710 guard).
$build = 'cd ' . escapeshellarg($root) . ' && TOPLIST_LITE_ALLOW_PLACEHOLDER_URL=1 php scripts/build-lite.php 2>&1';

exec($build, $out, $code);
$output = implode("\n", $out);

if ($code !== 0) {
	fwrite(STDERR, "Build failed (exit $code):\n$output\n");
	exit(1);
}

if (strpos($output, 'Lite-tree smoke checks passed') === false) {
	fwrite(STDERR, "Build did not report lite smoke pass:\n$output\n");
	exit(1);
}

if (!is_dir($lite)) {
	fwrite(STDERR, "Missing toplist-block-lite/\n");
	exit(1);
}

$errors = array();
$needles = array('toplist_list', 'toplist_handle_import', 'renderLibraryTab', 'apiFetch({', 'class-toplist-block-updater', 'pre_set_site_transient_update_plugins', 'plugins_api', 'data-toplist-spreadsheet', 'toplist-spreadsheet', 'schemaEnabled', 'toplist_build_itemlist_schema');
$whitelist = array('Toplist Block Pro', 'example.com/toplist-block-pro');

foreach (glob($lite . '/*') as $path) {
	// scan all php/js recursively
}
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($lite, FilesystemIterator::SKIP_DOTS));
foreach ($rii as $file) {
	if (!$file->isFile()) {
		continue;
	}
	$ext = strtolower($file->getExtension());
	if (!in_array($ext, array('php', 'js'), true)) {
		continue;
	}
	$lines = preg_split('/\R/', (string) file_get_contents($file->getPathname()));
	foreach ($lines as $i => $line) {
		foreach ($whitelist as $allow) {
			if (strpos($line, $allow) !== false) {
				continue 2;
			}
		}
		foreach ($needles as $needle) {
			if (strpos($line, $needle) !== false) {
				$rel = ltrim(str_replace($lite, '', $file->getPathname()), '/');
				$errors[] = "$rel:" . ($i + 1) . " [$needle]";
			}
		}
	}
}

$forbidden_paths = array('tickets/', 'scripts/', 'tests/');
$zip = $root . '/toplist-block-lite.zip';
if (file_exists($zip)) {
	$za = new ZipArchive();
	if ($za->open($zip) === true) {
		for ($i = 0; $i < $za->numFiles; $i++) {
			$name = $za->getNameIndex($i);
			foreach ($forbidden_paths as $bad) {
				if (strpos($name, $bad) !== false) {
					$errors[] = "zip contains forbidden path: $name";
				}
			}
		}
		$za->close();
	}
}

foreach (toplist_php_lint($lite) as $lint_err) {
	$errors[] = $lint_err;
}

if ($errors) {
	fwrite(STDERR, "Smoke test FAILED:\n  " . implode("\n  ", $errors) . "\n");
	exit(1);
}

echo "All lite build smoke tests passed.\n";
exit(0);

function toplist_php_lint(string $dir): array
{
	$failures = array();
	$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
	foreach ($rii as $file) {
		if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
			continue;
		}
		$path = $file->getPathname();
		exec('php -l ' . escapeshellarg($path) . ' 2>&1', $out, $code);
		if ($code !== 0) {
			$rel = ltrim(str_replace($dir, '', $path), '/');
			$failures[] = "php -l $rel: " . implode(' ', $out);
		}
		$out = array();
	}
	return $failures;
}
