<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$failed = false;
foreach ($files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR) === false) {
        exec('"' . PHP_BINARY . '" -l "' . $file->getPathname() . '"', $output, $code);
        if ($code !== 0) {
            $failed = true;
            echo implode(PHP_EOL, $output), PHP_EOL;
        }
    }
}
exit($failed ? 1 : 0);
