<?php

// Bail if this is Travis CI
if (getenv("CI")) {
  return;
}

// Create a tmp directory in the root of this project and use it for tests

require_once __DIR__ . "/Process.php";

$temp = realpath(__DIR__ . "/../../tmp");

if (!is_dir($temp)) {
  mkdir($temp);
}

if (is_dir($temp)) {
  \WP_CLI\Process::create("rm -rf {$temp}/wp-cli*")->run();
  putenv("TMPDIR={$temp}");
}
