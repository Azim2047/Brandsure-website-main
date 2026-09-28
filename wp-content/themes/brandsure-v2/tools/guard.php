<?php
/**
 * CI entry point for the guard.
 *
 * Runs the disclosure, language and integrity checks against a checkout with
 * no WordPress and no database. That matters: the check that stops the
 * commercial mechanism reaching the public site must be runnable on any
 * machine, in any pipeline, in under a second, or it will not be run.
 *
 *   php wp-content/themes/brandsure-v2/tools/guard.php
 *   php wp-content/themes/brandsure-v2/tools/guard.php --launch
 *
 * Exit code 0 = pass, 1 = fail.
 *
 * @package brandsure-v2
 */

define( 'BRANDSURE_GUARD_CLI', true );

require_once __DIR__ . '/../inc/guard.php';

$launch = in_array( '--launch', $argv, true );
$guard  = new BrandSure_Guard( dirname( __DIR__ ), $launch );

$guard->run();

fwrite( STDOUT, $guard->report() );

exit( $guard->exit_code() );
