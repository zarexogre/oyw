<?php

/**
 * @file
 */

use Dotenv\Dotenv;

$databases = [];
$config_directories = [];
$settings['update_free_access'] = FALSE;
$settings['file_scan_ignore_directories'] = ['node_modules', 'bower_components'];
$settings['config_sync_directory'] = '../config/sync';

if (file_exists($app_root . '/' . $site_path . '/settings.platformsh.php')) {
  include $app_root . '/' . $site_path . '/settings.platformsh.php';
}

$platformsh_subsite_id = basename(__DIR__);
$settings['config_sync_directory'] = '../config/sync';

if (is_readable(getcwd() . '/../.env') && class_exists(Dotenv::class)) {
  Dotenv::createImmutable(getcwd() . '/..')->safeLoad();
}

$envv = static function (string $k, $d = NULL) {
  $v = getenv($k);
  if ($v !== FALSE && $v !== '') {
    return $v;
  }
  if (isset($_SERVER[$k]) && $_SERVER[$k] !== '') {
    return $_SERVER[$k];
  }
  if (isset($_ENV[$k]) && $_ENV[$k] !== '') {
    return $_ENV[$k];
  }
  return $d;
};

$env = $envv('SITE_ENVIRONMENT', 'production');

$databases['default']['default'] = [
  'database' => $envv('DATABASE_DATABASE'),
  'username' => $envv('DATABASE_USER'),
  'password' => $envv('DATABASE_PASSWORD'),
  'host' => $envv('DATABASE_HOSTNAME', 'mysql'),
  'port' => (int) $envv('DATABASE_PORT', '3306'),
  'driver' => $envv('DATABASE_DRIVER', 'mysql'),
  'prefix' => '',
  'collation' => 'utf8mb4_general_ci',
  'init_commands' => ['isolation_level' => 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED'],
];

$settings['hash_salt'] = $envv('DRUPAL_HASH_SALT', $settings['hash_salt'] ?? '');

if ($trusted_host = $envv('DRUPAL_TRUSTED_HOST')) {
  foreach (explode(',', $trusted_host) as $thost) {
    $settings['trusted_host_patterns'][] = trim($thost);
  }
}

if ($env === 'development' || $env === 'local' || (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/dashboard'))) {
  $settings['container_yamls'][] = getcwd() . '/sites/default/services.dev.yml';
  $settings['cache']['bins']['render'] = 'cache.backend.null';
  $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';
  $settings['cache']['bins']['page'] = 'cache.backend.null';
  $config['system.logging']['error_level'] = 'all';
  $config['system.performance']['css']['preprocess'] = FALSE;
  $config['system.performance']['css']['gzip'] = FALSE;
  $config['system.performance']['js']['preprocess'] = FALSE;
  $config['system.performance']['js']['gzip'] = FALSE;
  $config['system.performance']['cache']['page']['max_age'] = 0;
  $config['system.performance']['cache']['page']['use_internal'] = FALSE;
  $config['system.performance']['response']['gzip'] = FALSE;
  $settings['extension_discovery_scan_tests'] = FALSE;
  error_reporting(E_ALL);
  ini_set('display_errors', TRUE);
  ini_set('display_startup_errors', TRUE);
  $config['views.settings']['ui']['show']['sql_query']['enabled'] = TRUE;
  $config['views.settings']['ui']['show']['performance_statistics'] = TRUE;
  $settings['rebuild_access'] = FALSE;
  $config['advagg.settings']['enabled'] = FALSE;
  $config['system.performance']['cache']['page']['max_age'] = 0;
  $config['system.performance']['cache']['page']['use_internal'] = FALSE;
}
else {
  $settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';
  $config['system.logging']['error_level'] = 'hide';
  // $config['system.performance']['css']['preprocess'] = TRUE;
  // $config['system.performance']['js']['preprocess'] = TRUE;
  // $config['advagg.settings']['enabled'] = TRUE;
}

$settings['file_temp_path'] = '/tmp/';
$settings['file_private_path'] = 'sites/default/files/private';

$ddev_settings = dirname(__FILE__) . '/settings.ddev.php';
if ($envv('IS_DDEV_PROJECT') === 'true' && is_readable($ddev_settings)) {
  require $ddev_settings;
}

$settings['state_cache'] = TRUE;
$config['update.settings']['notification']['emails'] = [];
$config['block_timer.settings']['block_timer_enabled'] = TRUE;
$config['block_timer.settings']['block_timer_timing_good'] = 50;
$config['block_timer.settings']['block_timer_timing_bad'] = 200;

$settings['s3fs.access_key'] = $envv('S3_ACCESS_KEY');
$settings['s3fs.secret_key'] = $envv('S3_SECRET_KEY');
$config['s3fs.settings']['bucket'] = $envv('S3_BUCKET');
$config['s3fs.settings']['region'] = $envv('S3_REGION');
$settings['s3fs.use_s3_for_public'] = TRUE;
$settings['s3fs.use_s3_for_private'] = TRUE;
$settings['s3fs.upload_as_private'] = TRUE;