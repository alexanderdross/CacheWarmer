<?php

namespace SearchForge;

defined( 'ABSPATH' ) || exit;

class Autoloader {

	public static function register(): void {
		spl_autoload_register( [ __CLASS__, 'autoload' ] );
	}

	public static function autoload( string $class ): void {
		$prefix = 'SearchForge\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$file     = SEARCHFORGE_PATH . 'includes/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
