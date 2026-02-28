<?php
/**
 * Test bootstrap — provides WordPress function stubs for standalone testing.
 *
 * This allows running PHPUnit tests without a full WordPress installation.
 */

// Simulate ABSPATH.
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/tmp/wp/' );
}

// Plugin constants.
define( 'CACHEWARMER_VERSION', '1.0.0' );
define( 'CACHEWARMER_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'CACHEWARMER_PLUGIN_URL', 'http://example.com/wp-content/plugins/cachewarmer/' );
define( 'CACHEWARMER_PLUGIN_BASENAME', 'cachewarmer/cachewarmer.php' );
define( 'CACHEWARMER_DB_VERSION', '1.0.0' );

// Stub storage for options.
$_wp_options = array();

// ──────────────────────────────────────────────
// WordPress function stubs
// ──────────────────────────────────────────────

if ( ! function_exists( 'get_option' ) ) {
    function get_option( string $name, $default = false ) {
        global $_wp_options;
        return $_wp_options[ $name ] ?? $default;
    }
}

if ( ! function_exists( 'add_option' ) ) {
    function add_option( string $name, $value = '' ): bool {
        global $_wp_options;
        if ( ! isset( $_wp_options[ $name ] ) ) {
            $_wp_options[ $name ] = $value;
            return true;
        }
        return false;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( string $name, $value ): bool {
        global $_wp_options;
        $_wp_options[ $name ] = $value;
        return true;
    }
}

if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( string $name ): bool {
        global $_wp_options;
        unset( $_wp_options[ $name ] );
        return true;
    }
}

if ( ! function_exists( 'wp_generate_uuid4' ) ) {
    function wp_generate_uuid4(): string {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            mt_rand( 0, 0xffff ),
            mt_rand( 0, 0x0fff ) | 0x4000,
            mt_rand( 0, 0x3fff ) | 0x8000,
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data, int $options = 0, int $depth = 512 ) {
        return json_encode( $data, $options, $depth );
    }
}

if ( ! function_exists( 'wp_parse_url' ) ) {
    function wp_parse_url( string $url, int $component = -1 ) {
        if ( $component === -1 ) {
            return parse_url( $url );
        }
        return parse_url( $url, $component );
    }
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( string $str ): string {
        return strip_tags( trim( $str ) );
    }
}

if ( ! function_exists( 'esc_url_raw' ) ) {
    function esc_url_raw( string $url ): string {
        return filter_var( $url, FILTER_SANITIZE_URL );
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( string $text ): string {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_attr' ) ) {
    function esc_attr( string $text ): string {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_textarea' ) ) {
    function esc_textarea( string $text ): string {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( string $url ): string {
        return filter_var( $url, FILTER_SANITIZE_URL );
    }
}

if ( ! function_exists( 'wp_remote_get' ) ) {
    function wp_remote_get( string $url, array $args = array() ) {
        // Test stub: return a mock response.
        global $_wp_remote_responses;
        if ( isset( $_wp_remote_responses[ $url ] ) ) {
            return $_wp_remote_responses[ $url ];
        }
        return new WP_Error( 'http_request_failed', 'Stubbed: no response configured for ' . $url );
    }
}

if ( ! function_exists( 'wp_remote_post' ) ) {
    function wp_remote_post( string $url, array $args = array() ) {
        global $_wp_remote_responses;
        if ( isset( $_wp_remote_responses[ $url ] ) ) {
            return $_wp_remote_responses[ $url ];
        }
        return new WP_Error( 'http_request_failed', 'Stubbed: no response configured for ' . $url );
    }
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
    function wp_remote_retrieve_body( $response ): string {
        if ( is_array( $response ) && isset( $response['body'] ) ) {
            return $response['body'];
        }
        return '';
    }
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
    function wp_remote_retrieve_response_code( $response ): int {
        if ( is_array( $response ) && isset( $response['response']['code'] ) ) {
            return (int) $response['response']['code'];
        }
        return 0;
    }
}

if ( ! function_exists( 'is_wp_error' ) ) {
    function is_wp_error( $thing ): bool {
        return $thing instanceof WP_Error;
    }
}

if ( ! function_exists( 'wp_schedule_single_event' ) ) {
    function wp_schedule_single_event( int $timestamp, string $hook, array $args = array() ): bool {
        return true;
    }
}

if ( ! function_exists( 'wp_schedule_event' ) ) {
    function wp_schedule_event( int $timestamp, string $recurrence, string $hook, array $args = array() ): bool {
        return true;
    }
}

if ( ! function_exists( 'wp_next_scheduled' ) ) {
    function wp_next_scheduled( string $hook, array $args = array() ) {
        return false;
    }
}

if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
    function wp_clear_scheduled_hook( string $hook, array $args = array() ): int {
        return 0;
    }
}

if ( ! function_exists( 'wp_unschedule_event' ) ) {
    function wp_unschedule_event( int $timestamp, string $hook, array $args = array() ): bool {
        return true;
    }
}

if ( ! function_exists( 'spawn_cron' ) ) {
    function spawn_cron(): void {}
}

if ( ! function_exists( 'wp_raise_memory_limit' ) ) {
    function wp_raise_memory_limit( string $context = '' ): string {
        return ini_get( 'memory_limit' );
    }
}

if ( ! function_exists( 'current_time' ) ) {
    function current_time( string $type, bool $gmt = false ): string {
        return gmdate( 'Y-m-d H:i:s' );
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): bool {
        return true;
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( string $hook, $callback, int $priority = 10, int $accepted_args = 1 ): bool {
        return true;
    }
}

if ( ! function_exists( 'register_rest_route' ) ) {
    function register_rest_route( string $namespace, string $route, array $args = array() ): bool {
        return true;
    }
}

if ( ! function_exists( 'is_admin' ) ) {
    function is_admin(): bool {
        return false;
    }
}

if ( ! function_exists( '__' ) ) {
    function __( string $text, string $domain = 'default' ): string {
        return $text;
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( string $text, string $domain = 'default' ): string {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_html_e' ) ) {
    function esc_html_e( string $text, string $domain = 'default' ): void {
        echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( string $file ): string {
        return dirname( $file ) . '/';
    }
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( string $file ): string {
        return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
    }
}

if ( ! function_exists( 'plugin_basename' ) ) {
    function plugin_basename( string $file ): string {
        return 'cachewarmer/cachewarmer.php';
    }
}

if ( ! function_exists( 'register_activation_hook' ) ) {
    function register_activation_hook( string $file, $callback ): void {}
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
    function register_deactivation_hook( string $file, $callback ): void {}
}

if ( ! function_exists( 'flush_rewrite_rules' ) ) {
    function flush_rewrite_rules(): void {}
}

if ( ! function_exists( 'current_user_can' ) ) {
    function current_user_can( string $capability ): bool {
        return true;
    }
}

if ( ! function_exists( 'hash_equals' ) ) {
    // PHP 5.6+ has this built-in.
}

if ( ! function_exists( 'get_bloginfo' ) ) {
    function get_bloginfo( string $show ): string {
        return '6.7';
    }
}

if ( ! function_exists( 'http_build_query' ) ) {
    // Built-in PHP function.
}

// WP_Error stub.
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        private string $code;
        private string $message;
        private $data;

        public function __construct( string $code = '', string $message = '', $data = '' ) {
            $this->code    = $code;
            $this->message = $message;
            $this->data    = $data;
        }

        public function get_error_message(): string {
            return $this->message;
        }

        public function get_error_code(): string {
            return $this->code;
        }
    }
}

// WP_REST_Request stub.
if ( ! class_exists( 'WP_REST_Request' ) ) {
    class WP_REST_Request {
        private array $params = array();
        private array $headers = array();

        public function __construct( string $method = 'GET', string $route = '' ) {}

        public function set_param( string $key, $value ): void {
            $this->params[ $key ] = $value;
        }

        public function get_param( string $key ) {
            return $this->params[ $key ] ?? null;
        }

        public function set_header( string $key, string $value ): void {
            $this->headers[ strtolower( $key ) ] = $value;
        }

        public function get_header( string $key ): ?string {
            return $this->headers[ strtolower( $key ) ] ?? null;
        }
    }
}

// WP_REST_Response stub.
if ( ! class_exists( 'WP_REST_Response' ) ) {
    class WP_REST_Response {
        public $data;
        public int $status;

        public function __construct( $data = null, int $status = 200 ) {
            $this->data   = $data;
            $this->status = $status;
        }

        public function get_data() {
            return $this->data;
        }

        public function get_status(): int {
            return $this->status;
        }
    }
}

// Global for HTTP mocking.
$_wp_remote_responses = array();
