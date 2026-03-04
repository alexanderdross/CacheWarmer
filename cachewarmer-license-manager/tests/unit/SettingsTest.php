<?php

declare( strict_types=1 );

use PHPUnit\Framework\TestCase;

/**
 * Unit Tests für CWLM_Settings.
 */
class SettingsTest extends TestCase {

    protected function setUp(): void {
        // wp_options simulieren
        global $cwlm_test_options;
        $cwlm_test_options = [];
    }

    public function test_get_fields_returns_array(): void {
        $fields = CWLM_Settings::get_fields();
        $this->assertIsArray( $fields );
        $this->assertNotEmpty( $fields );
    }

    public function test_all_fields_have_required_keys(): void {
        $required = [ 'constant', 'default', 'type', 'encrypted', 'section', 'label', 'help' ];
        foreach ( CWLM_Settings::get_fields() as $key => $field ) {
            foreach ( $required as $req ) {
                $this->assertArrayHasKey( $req, $field, "Feld '$key' fehlt Schlüssel '$req'" );
            }
        }
    }

    public function test_fields_have_valid_types(): void {
        $valid_types = [ 'text', 'password', 'number' ];
        foreach ( CWLM_Settings::get_fields() as $key => $field ) {
            $this->assertContains( $field['type'], $valid_types, "Feld '$key' hat ungültigen Typ '{$field['type']}'" );
        }
    }

    public function test_fields_have_valid_sections(): void {
        $sections = array_keys( CWLM_Settings::get_sections() );
        foreach ( CWLM_Settings::get_fields() as $key => $field ) {
            $this->assertContains( $field['section'], $sections, "Feld '$key' hat ungültige Sektion '{$field['section']}'" );
        }
    }

    public function test_all_sections_have_required_keys(): void {
        $required = [ 'title', 'description', 'icon' ];
        foreach ( CWLM_Settings::get_sections() as $key => $section ) {
            foreach ( $required as $req ) {
                $this->assertArrayHasKey( $req, $section, "Sektion '$key' fehlt Schlüssel '$req'" );
            }
        }
    }

    public function test_encrypted_fields_are_password_type(): void {
        foreach ( CWLM_Settings::get_fields() as $key => $field ) {
            if ( $field['encrypted'] ) {
                $this->assertEquals( 'password', $field['type'], "Verschlüsseltes Feld '$key' sollte Typ 'password' haben" );
            }
        }
    }

    public function test_get_returns_default_for_unknown_key(): void {
        $this->assertNull( CWLM_Settings::get( 'nonexistent_key_12345' ) );
    }

    public function test_get_returns_default_for_unset_field(): void {
        // jwt_expiry_days hat Default 30
        $value = CWLM_Settings::get( 'jwt_expiry_days' );
        // Wenn keine Konstante gesetzt und nichts gespeichert, erwarten wir den Default
        if ( ! defined( 'CWLM_JWT_EXPIRY_DAYS' ) ) {
            $this->assertEquals( 30, $value );
        } else {
            $this->assertEquals( CWLM_JWT_EXPIRY_DAYS, $value );
        }
    }

    public function test_is_constant_defined_for_existing_constants(): void {
        // CWLM_JWT_SECRET ist im Bootstrap definiert
        if ( defined( 'CWLM_JWT_SECRET' ) ) {
            $this->assertTrue( CWLM_Settings::is_constant_defined( 'jwt_secret' ) );
        }
    }

    public function test_is_constant_defined_returns_false_for_unknown_key(): void {
        $this->assertFalse( CWLM_Settings::is_constant_defined( 'nonexistent_key_12345' ) );
    }

    public function test_get_all_returns_all_fields(): void {
        $all    = CWLM_Settings::get_all();
        $fields = CWLM_Settings::get_fields();

        $this->assertIsArray( $all );
        $this->assertCount( count( $fields ), $all );

        foreach ( array_keys( $fields ) as $key ) {
            $this->assertArrayHasKey( $key, $all, "get_all() fehlt Schlüssel '$key'" );
        }
    }

    public function test_constant_names_follow_convention(): void {
        foreach ( CWLM_Settings::get_fields() as $key => $field ) {
            $this->assertStringStartsWith( 'CWLM_', $field['constant'], "Konstante für '$key' muss mit CWLM_ beginnen" );
        }
    }

    public function test_no_duplicate_constant_names(): void {
        $constants = [];
        foreach ( CWLM_Settings::get_fields() as $key => $field ) {
            $this->assertNotContains( $field['constant'], $constants, "Doppelte Konstante: {$field['constant']}" );
            $constants[] = $field['constant'];
        }
    }

    public function test_number_fields_have_numeric_defaults(): void {
        foreach ( CWLM_Settings::get_fields() as $key => $field ) {
            if ( 'number' === $field['type'] ) {
                $this->assertIsNumeric( $field['default'], "Feld '$key' (number) hat nicht-numerischen Default" );
            }
        }
    }
}
