<?php
namespace PSU\M2C2;

$module->removeLogs('message = ?', M2C2_LOGGING_INVALID_CONFIG);
$module->removeLogs('message = ?', M2C2_LOGGING_LAUNCHING);

$logURL = $module->getUrl('m2c2-log-viewer.php');

header("Location: {$logURL}");