<?php
/**
 * Deploy.
 *
 * @package Akwaaba/WordPress/Plugin/akwaaba-post-campaign-for-brevo
 */

declare(strict_types=1);

namespace Deployer;

require 'recipe/common.php';

set( 'application', 'akwaaba-post-campaign-for-brevo' );
set( 'build_path', './build/' );
set( 'keep_releases', 5 );
set( 'allow_anonymous_stats', false );

/**
 * Get an environment variable or its fallback value.
 *
 * @param string $variable Environment variable name.
 * @param string $fallback Fallback value.
 * @return string
 */
function get_env( string $variable, string $fallback ): string {
	$value = getenv( $variable );

	return false !== $value && '' !== $value ? $value : $fallback;
}

$host_alias  = get_env( 'DEPLOY_HOST_ALIAS', 'akwaaba-post-campaign-for-brevo' );
$hostname    = getenv( 'DEPLOY_HOSTNAME' );
$remote_user = getenv( 'DEPLOY_REMOTE_USER' );
$port        = (int) get_env( 'DEPLOY_PORT', '22' );
$deploy_path = get_env( 'DEPLOY_PATH', '~/projects/{{application}}' );
$plugin_dir  = get_env( 'DEPLOY_PLUGIN_DIR', '~/htdocs/{{application}}/wp-content/plugins' );

if ( ! $hostname ) {
	throw new RuntimeException( 'Set DEPLOY_HOSTNAME before deploying.' );
}

if ( ! $remote_user ) {
	throw new RuntimeException( 'Set DEPLOY_REMOTE_USER before deploying.' );
}

host( $host_alias )
	->setHostname( $hostname )
	->setRemoteUser( $remote_user )
	->setPort( $port )
	->set( 'deploy_path', $deploy_path )
	->set( 'plugin_dir', $plugin_dir )
	->set( 'stage', 'production' );

task(
	'build',
	function (): void {
		runLocally( 'rm -rf {{build_path}}' );
		runLocally( 'mkdir -p {{build_path}}' );
		runLocally( 'rsync --recursive --delete --delete-excluded --exclude=.git --exclude=.github --exclude=.dep --exclude=build --exclude=node_modules --exclude=vendor ./ "{{build_path}}"' );
		runLocally( 'composer install --verbose --prefer-dist --no-progress --no-interaction --no-dev --optimize-autoloader --working-dir={{build_path}}' );
	}
)->local();

task(
	'deploy:update_code',
	function (): void {
		upload( '{{build_path}}/', '{{release_path}}' );
	}
);

after(
	'deploy:update_code',
	function (): void {
		runLocally( 'rm -rf {{build_path}}' );
	}
);

after(
	'deploy:symlink',
	function (): void {
		run( 'ln -sfn {{deploy_path}}/current {{plugin_dir}}/{{application}}' );
	}
);

task(
	'deploy',
	[
		'deploy:prepare',
		'deploy:lock',
		'deploy:release',
		'build',
		'deploy:update_code',
		'deploy:shared',
		'deploy:symlink',
		'deploy:unlock',
		'cleanup',
		'success',
	]
);
