<?php
/**
 * Settings Management – UI-basiert mit wp-config.php Override.
 *
 * Einstellungen werden in wp_options gespeichert. Sensible Werte (Secrets)
 * werden mit AES-256-CBC verschlüsselt. Konstanten in wp-config.php haben
 * IMMER Vorrang und überschreiben gespeicherte Werte.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLM_Settings {

    /** @var string Option-Key in wp_options. */
    private const OPTION_KEY = 'cwlm_settings';

    /** @var string Cipher-Methode für Verschlüsselung. */
    private const CIPHER = 'aes-256-cbc';

    /**
     * Alle verfügbaren Einstellungen mit Metadaten.
     *
     * @return array<string, array{constant: string, default: mixed, type: string, encrypted: bool, section: string}>
     */
    public static function get_fields(): array {
        return [
            // Sicherheit & Authentifizierung
            'jwt_secret' => [
                'constant'  => 'CWLM_JWT_SECRET',
                'default'   => '',
                'type'      => 'password',
                'encrypted' => true,
                'section'   => 'security',
                'label'     => __( 'JWT Secret', 'cwlm' ),
                'help'      => __( 'Geheimer Schlüssel für die Token-Signierung. Mindestens 32 Zeichen. Wird automatisch generiert wenn leer gespeichert.', 'cwlm' ),
            ],
            'jwt_expiry_days' => [
                'constant'  => 'CWLM_JWT_EXPIRY_DAYS',
                'default'   => 30,
                'type'      => 'number',
                'encrypted' => false,
                'section'   => 'security',
                'label'     => __( 'JWT Gültigkeitsdauer (Tage)', 'cwlm' ),
                'help'      => __( 'Wie lange ein ausgestelltes Installations-Token gültig bleibt, bevor es erneuert werden muss.', 'cwlm' ),
            ],

            // Stripe
            'stripe_webhook_secret' => [
                'constant'  => 'CWLM_STRIPE_WEBHOOK_SECRET',
                'default'   => '',
                'type'      => 'password',
                'encrypted' => true,
                'section'   => 'stripe',
                'label'     => __( 'Stripe Webhook Secret', 'cwlm' ),
                'help'      => __( 'Beginnt mit "whsec_". Zu finden unter Stripe Dashboard → Developers → Webhooks → Signing Secret.', 'cwlm' ),
            ],

            // Lizenz-Verhalten
            'grace_period_days' => [
                'constant'  => 'CWLM_GRACE_PERIOD_DAYS',
                'default'   => 14,
                'type'      => 'number',
                'encrypted' => false,
                'section'   => 'license',
                'label'     => __( 'Karenzzeit (Tage)', 'cwlm' ),
                'help'      => __( 'Zeitraum nach Lizenzablauf, in dem die Software noch funktioniert. Gibt dem Kunden Zeit zur Verlängerung.', 'cwlm' ),
            ],
            'heartbeat_interval_hours' => [
                'constant'  => 'CWLM_HEARTBEAT_INTERVAL_HOURS',
                'default'   => 24,
                'type'      => 'number',
                'encrypted' => false,
                'section'   => 'license',
                'label'     => __( 'Heartbeat-Intervall (Stunden)', 'cwlm' ),
                'help'      => __( 'Wie oft eine Installation sich beim Dashboard melden muss. Installationen ohne Heartbeat werden nach 7 Tagen als inaktiv markiert.', 'cwlm' ),
            ],
            'dev_domains' => [
                'constant'  => 'CWLM_DEV_DOMAINS',
                'default'   => 'localhost,*.local,*.dev,*.test,127.0.0.1',
                'type'      => 'text',
                'encrypted' => false,
                'section'   => 'license',
                'label'     => __( 'Development-Domains', 'cwlm' ),
                'help'      => __( 'Kommaseparierte Liste von Domain-Mustern, die als Entwicklungsumgebung erkannt werden. Wildcards (*.local) sind erlaubt.', 'cwlm' ),
            ],

            // Rate Limiting
            'rate_limit_per_minute' => [
                'constant'  => 'CWLM_RATE_LIMIT_PER_MINUTE',
                'default'   => 60,
                'type'      => 'number',
                'encrypted' => false,
                'section'   => 'ratelimit',
                'label'     => __( 'Allgemeines Rate Limit (pro Minute)', 'cwlm' ),
                'help'      => __( 'Maximale Anzahl API-Anfragen pro IP-Adresse und Minute für Standard-Endpoints (validate, check, health).', 'cwlm' ),
            ],
            'rate_limit_activate' => [
                'constant'  => 'CWLM_RATE_LIMIT_ACTIVATE',
                'default'   => 10,
                'type'      => 'number',
                'encrypted' => false,
                'section'   => 'ratelimit',
                'label'     => __( 'Aktivierung Rate Limit (pro Minute)', 'cwlm' ),
                'help'      => __( 'Maximale Anzahl Aktivierungs-/Deaktivierungs-Anfragen pro IP-Adresse und Minute. Niedrig halten um Missbrauch zu verhindern.', 'cwlm' ),
            ],

            // GeoIP
            'maxmind_db_path' => [
                'constant'  => 'CWLM_MAXMIND_DB_PATH',
                'default'   => '',
                'type'      => 'text',
                'encrypted' => false,
                'section'   => 'geoip',
                'label'     => __( 'MaxMind GeoLite2 Datenbankpfad', 'cwlm' ),
                'help'      => __( 'Absoluter Pfad zur GeoLite2-City.mmdb Datei. Optional – ermöglicht Geolokalisierung von Installationen.', 'cwlm' ),
            ],

            // CORS
            'cors_allowed_origins' => [
                'constant'  => 'CWLM_CORS_ALLOWED_ORIGINS',
                'default'   => '',
                'type'      => 'text',
                'encrypted' => false,
                'section'   => 'security',
                'label'     => __( 'Erlaubte CORS Origins', 'cwlm' ),
                'help'      => __( 'Kommaseparierte Liste erlaubter Origins für Cross-Origin-Anfragen. Leer = keine CORS-Header. "*" = alle Origins erlauben.', 'cwlm' ),
            ],
        ];
    }

    /**
     * Alle gespeicherten Einstellungen laden.
     *
     * @return array<string, mixed>
     */
    public static function get_all(): array {
        $saved  = get_option( self::OPTION_KEY, [] );
        $fields = self::get_fields();
        $result = [];

        foreach ( $fields as $key => $field ) {
            $result[ $key ] = self::get( $key );
        }

        return $result;
    }

    /**
     * Einzelne Einstellung lesen.
     *
     * Priorität: wp-config.php Konstante > wp_options > Standardwert.
     */
    public static function get( string $key ): mixed {
        $fields = self::get_fields();
        if ( ! isset( $fields[ $key ] ) ) {
            return null;
        }

        $field    = $fields[ $key ];
        $constant = $field['constant'];

        // wp-config.php Konstante hat immer Vorrang
        if ( defined( $constant ) ) {
            return constant( $constant );
        }

        // Aus wp_options lesen
        $saved = get_option( self::OPTION_KEY, [] );
        if ( isset( $saved[ $key ] ) ) {
            $value = $saved[ $key ];
            // Verschlüsselte Werte entschlüsseln
            if ( $field['encrypted'] && is_string( $value ) && '' !== $value ) {
                $decrypted = self::decrypt( $value );
                return false !== $decrypted ? $decrypted : '';
            }
            return $value;
        }

        return $field['default'];
    }

    /**
     * Prüfe ob ein Wert aus wp-config.php kommt (nicht editierbar).
     */
    public static function is_constant_defined( string $key ): bool {
        $fields = self::get_fields();
        if ( ! isset( $fields[ $key ] ) ) {
            return false;
        }
        return defined( $fields[ $key ]['constant'] );
    }

    /**
     * Einstellungen speichern.
     *
     * @param array<string, mixed> $values Key-Value-Paare.
     */
    public static function save( array $values ): bool {
        $fields = self::get_fields();
        $saved  = get_option( self::OPTION_KEY, [] );

        foreach ( $values as $key => $value ) {
            if ( ! isset( $fields[ $key ] ) ) {
                continue;
            }

            $field = $fields[ $key ];

            // Nicht speichern wenn Konstante gesetzt (wäre sinnlos)
            if ( defined( $field['constant'] ) ) {
                continue;
            }

            // Type-Casting
            if ( 'number' === $field['type'] ) {
                $value = (int) $value;
            } else {
                $value = sanitize_text_field( (string) $value );
            }

            // Password-Felder: Leerer Wert bei bestehender Einstellung = nicht ändern
            if ( 'password' === $field['type'] && '' === $value && isset( $saved[ $key ] ) ) {
                continue;
            }

            // JWT Secret: Auto-Generieren wenn leer
            if ( 'jwt_secret' === $key && '' === $value ) {
                $value = wp_generate_password( 64, true, true );
            }

            // Sensible Werte verschlüsseln
            if ( $field['encrypted'] && '' !== $value ) {
                $value = self::encrypt( $value );
            }

            $saved[ $key ] = $value;
        }

        return update_option( self::OPTION_KEY, $saved, false ); // autoload = false
    }

    /**
     * Wert mit AES-256-CBC verschlüsseln.
     */
    private static function encrypt( string $plaintext ): string {
        $key = self::get_encryption_key();
        $iv  = openssl_random_pseudo_bytes( openssl_cipher_iv_length( self::CIPHER ) );

        $encrypted = openssl_encrypt( $plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );
        if ( false === $encrypted ) {
            return $plaintext; // Fallback: unverschlüsselt speichern
        }

        // IV + Ciphertext Base64-kodiert speichern
        return 'enc:' . base64_encode( $iv . $encrypted );
    }

    /**
     * AES-256-CBC entschlüsseln.
     *
     * @return string|false
     */
    private static function decrypt( string $ciphertext ): string|false {
        // Nicht verschlüsselt (Legacy oder Fallback)
        if ( ! str_starts_with( $ciphertext, 'enc:' ) ) {
            return $ciphertext;
        }

        $key  = self::get_encryption_key();
        $data = base64_decode( substr( $ciphertext, 4 ) );

        if ( false === $data ) {
            return false;
        }

        $iv_length = openssl_cipher_iv_length( self::CIPHER );
        if ( strlen( $data ) < $iv_length ) {
            return false;
        }

        $iv        = substr( $data, 0, $iv_length );
        $encrypted = substr( $data, $iv_length );

        return openssl_decrypt( $encrypted, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );
    }

    /**
     * Encryption-Key aus WordPress Auth-Salts ableiten.
     */
    private static function get_encryption_key(): string {
        $salt = '';
        if ( defined( 'AUTH_KEY' ) ) {
            $salt .= AUTH_KEY;
        }
        if ( defined( 'SECURE_AUTH_KEY' ) ) {
            $salt .= SECURE_AUTH_KEY;
        }
        // Fallback wenn keine Salts gesetzt (sollte nie passieren)
        if ( '' === $salt ) {
            $salt = 'cwlm-default-key-' . DB_NAME;
        }

        return hash( 'sha256', $salt, true );
    }

    /**
     * Sektions-Definitionen mit Titeln und Beschreibungen.
     *
     * @return array<string, array{title: string, description: string, icon: string}>
     */
    public static function get_sections(): array {
        return [
            'security' => [
                'title'       => __( 'Sicherheit & Authentifizierung', 'cwlm' ),
                'description' => __( 'JWT-Tokens sichern die Kommunikation zwischen CacheWarmer-Installationen und diesem Dashboard. Jede Installation erhält bei der Aktivierung ein signiertes Token, das bei jedem Heartbeat validiert wird. CORS-Einstellungen kontrollieren, welche Domains API-Anfragen stellen dürfen.', 'cwlm' ),
                'icon'        => 'dashicons-shield',
            ],
            'stripe' => [
                'title'       => __( 'Stripe-Integration', 'cwlm' ),
                'description' => __( 'Verbindet das License Dashboard mit Stripe für automatische Lizenz-Erstellung bei Kauf und Deaktivierung bei Kündigung. Das Webhook Secret verifiziert, dass eingehende Events tatsächlich von Stripe stammen. Ohne Webhook Secret werden Stripe-Zahlungen nicht automatisch verarbeitet.', 'cwlm' ),
                'icon'        => 'dashicons-money-alt',
            ],
            'license' => [
                'title'       => __( 'Lizenz-Verhalten', 'cwlm' ),
                'description' => __( 'Steuert wie Lizenzen sich verhalten: die Karenzzeit gibt Kunden nach Ablauf noch Zugang zur Software, der Heartbeat-Intervall bestimmt wie oft sich Installationen melden müssen, und Development-Domains definieren welche Umgebungen als Entwicklung gelten (zählen nicht gegen das Site-Limit).', 'cwlm' ),
                'icon'        => 'dashicons-admin-network',
            ],
            'ratelimit' => [
                'title'       => __( 'Rate Limiting', 'cwlm' ),
                'description' => __( 'Schützt die API vor Überlastung und Brute-Force-Angriffen. Jede IP-Adresse hat ein Budget pro Minute. Aktivierungs-Endpoints haben ein niedrigeres Limit, da sie sensible Operationen durchführen. Bei Überschreitung erhält der Client einen 429 Too Many Requests Fehler.', 'cwlm' ),
                'icon'        => 'dashicons-dashboard',
            ],
            'geoip' => [
                'title'       => __( 'Geolokalisierung (optional)', 'cwlm' ),
                'description' => __( 'Ermöglicht die geografische Zuordnung von Installationen anhand ihrer IP-Adresse. Benötigt eine lokal gespeicherte MaxMind GeoLite2-City Datenbank (.mmdb). Registrierung unter maxmind.com erforderlich (kostenlos). Ohne GeoIP werden Installationen ohne Standortdaten angezeigt.', 'cwlm' ),
                'icon'        => 'dashicons-location-alt',
            ],
        ];
    }
}
