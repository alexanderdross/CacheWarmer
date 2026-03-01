<?php
/**
 * POST /stripe/webhook – Stripe Webhook Handler.
 *
 * @package CacheWarmer_License_Manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLM_Stripe_Webhook extends CWLM_REST_Controller {

    public function register_routes(): void {
        register_rest_route( $this->namespace, '/stripe/webhook', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle' ],
            'permission_callback' => '__return_true',
        ] );
    }

    public function handle( \WP_REST_Request $request ): \WP_REST_Response {
        $payload   = $request->get_body();
        $sig       = $request->get_header( 'stripe-signature' );
        $secret    = defined( 'CWLM_STRIPE_WEBHOOK_SECRET' ) ? CWLM_STRIPE_WEBHOOK_SECRET : '';

        // Signatur prüfen
        if ( ! $this->verify_signature( $payload, $sig, $secret ) ) {
            return $this->error( 'INVALID_SIGNATURE', 'Stripe Webhook Signatur ungültig.', 400 );
        }

        $event = json_decode( $payload, false );
        if ( ! $event || empty( $event->id ) || empty( $event->type ) ) {
            return $this->error( 'INVALID_PAYLOAD', 'Ungültiger Webhook-Payload.', 400 );
        }

        global $wpdb;
        $prefix = $wpdb->prefix . CWLM_DB_PREFIX;

        // Idempotenz-Check
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$prefix}stripe_events WHERE stripe_event_id = %s",
                $event->id
            )
        );

        if ( $existing ) {
            return $this->success( [ 'received' => true, 'processed' => false, 'reason' => 'duplicate' ] );
        }

        // Event loggen
        $wpdb->insert(
            $prefix . 'stripe_events',
            [
                'stripe_event_id'   => $event->id,
                'event_type'        => $event->type,
                'payload_json'      => $payload,
                'processing_status' => 'pending',
            ],
            [ '%s', '%s', '%s', '%s' ]
        );
        $event_db_id = (int) $wpdb->insert_id;

        // Event verarbeiten
        $audit      = new CWLM_Audit_Logger();
        $manager    = new CWLM_License_Manager();
        $license_id = null;

        try {
            switch ( $event->type ) {
                case 'checkout.session.completed':
                    $license_id = $this->handle_checkout_completed( $event->data->object, $manager );
                    break;

                case 'invoice.payment_succeeded':
                    $license_id = $this->handle_payment_succeeded( $event->data->object, $manager );
                    break;

                case 'invoice.payment_failed':
                    $license_id = $this->handle_payment_failed( $event->data->object, $manager );
                    break;

                case 'customer.subscription.deleted':
                    $license_id = $this->handle_subscription_deleted( $event->data->object, $manager );
                    break;

                case 'customer.subscription.updated':
                    $license_id = $this->handle_subscription_updated( $event->data->object, $manager );
                    break;

                case 'charge.refunded':
                case 'charge.dispute.created':
                    $license_id = $this->handle_refund_or_dispute( $event->data->object, $manager );
                    break;

                default:
                    // Unbekannter Event-Typ – ignorieren
                    $wpdb->update(
                        $prefix . 'stripe_events',
                        [ 'processing_status' => 'ignored', 'processed_at' => gmdate( 'Y-m-d H:i:s' ) ],
                        [ 'id' => $event_db_id ],
                        [ '%s', '%s' ],
                        [ '%d' ]
                    );
                    return $this->success( [ 'received' => true, 'processed' => false, 'reason' => 'ignored' ] );
            }

            // Erfolg
            $wpdb->update(
                $prefix . 'stripe_events',
                [
                    'processing_status' => 'processed',
                    'license_id'        => $license_id,
                    'processed_at'      => gmdate( 'Y-m-d H:i:s' ),
                ],
                [ 'id' => $event_db_id ],
                [ '%s', '%d', '%s' ],
                [ '%d' ]
            );

            $audit->log( 'stripe.webhook.processed', 'stripe', $event->id, $license_id, null, [
                'event_type' => $event->type,
            ] );

        } catch ( \Exception $e ) {
            $wpdb->update(
                $prefix . 'stripe_events',
                [
                    'processing_status' => 'failed',
                    'error_message'     => $e->getMessage(),
                    'processed_at'      => gmdate( 'Y-m-d H:i:s' ),
                ],
                [ 'id' => $event_db_id ],
                [ '%s', '%s', '%s' ],
                [ '%d' ]
            );

            $audit->log( 'stripe.webhook.failed', 'stripe', $event->id, $license_id, null, [
                'event_type' => $event->type,
                'error'      => $e->getMessage(),
            ] );
        }

        return $this->success( [ 'received' => true, 'processed' => true ] );
    }

    /**
     * Checkout abgeschlossen → Lizenz erstellen.
     */
    private function handle_checkout_completed( object $session, CWLM_License_Manager $manager ): ?int {
        global $wpdb;
        $prefix = $wpdb->prefix . CWLM_DB_PREFIX;

        // Produkt-Mapping finden
        $product_id = $session->metadata->product_id ?? null;
        $price_id   = $session->metadata->price_id ?? null;

        $product_map = null;
        if ( $product_id ) {
            $product_map = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$prefix}stripe_product_map WHERE stripe_product_id = %s AND is_active = 1 LIMIT 1",
                    $product_id
                )
            );
        }

        $tier          = $product_map->tier ?? 'professional';
        $plan          = $product_map->plan ?? 'starter';
        $max_sites     = $product_map->max_sites ?? 1;
        $duration_days = $product_map->duration_days ?? 365;

        $license_id = $manager->create_license( [
            'customer_email'         => $session->customer_details->email ?? '',
            'customer_name'          => $session->customer_details->name ?? null,
            'tier'                   => $tier,
            'plan'                   => $plan,
            'max_sites'              => $max_sites,
            'stripe_customer_id'     => $session->customer ?? null,
            'stripe_subscription_id' => $session->subscription ?? null,
            'expires_at'             => gmdate( 'Y-m-d H:i:s', strtotime( "+{$duration_days} days" ) ),
        ] );

        // E-Mail senden
        if ( $license_id ) {
            $license = $wpdb->get_row(
                $wpdb->prepare( "SELECT * FROM {$prefix}licenses WHERE id = %d", $license_id )
            );
            if ( $license ) {
                $email_handler = new CWLM_Email();
                $email_handler->send_license_created(
                    $license->customer_email,
                    $license->license_key,
                    $license->tier,
                    $license->plan
                );
            }
        }

        return $license_id ?: null;
    }

    /**
     * Zahlung erfolgreich → Lizenz verlängern.
     */
    private function handle_payment_succeeded( object $invoice, CWLM_License_Manager $manager ): ?int {
        if ( empty( $invoice->subscription ) ) {
            return null;
        }

        $license = $manager->find_by_subscription( $invoice->subscription );
        if ( ! $license ) {
            return null;
        }

        global $wpdb;
        $prefix = $wpdb->prefix . CWLM_DB_PREFIX;

        $product_map = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$prefix}stripe_product_map
                 WHERE stripe_product_id = (
                     SELECT stripe_product_id FROM {$prefix}stripe_product_map
                     WHERE tier = %s AND plan = %s AND is_active = 1 LIMIT 1
                 ) LIMIT 1",
                $license->tier,
                $license->plan
            )
        );

        $days = $product_map->duration_days ?? 365;
        $manager->extend_license( (int) $license->id, $days );

        return (int) $license->id;
    }

    /**
     * Zahlung fehlgeschlagen → Warnung.
     */
    private function handle_payment_failed( object $invoice, CWLM_License_Manager $manager ): ?int {
        if ( empty( $invoice->subscription ) ) {
            return null;
        }

        $license = $manager->find_by_subscription( $invoice->subscription );
        if ( ! $license ) {
            return null;
        }

        return (int) $license->id;
    }

    /**
     * Subscription gelöscht → Lizenz ablaufen lassen.
     */
    private function handle_subscription_deleted( object $subscription, CWLM_License_Manager $manager ): ?int {
        $license = $manager->find_by_subscription( $subscription->id );
        if ( ! $license ) {
            return null;
        }

        global $wpdb;
        $prefix = $wpdb->prefix . CWLM_DB_PREFIX;

        $wpdb->update(
            $prefix . 'licenses',
            [ 'status' => 'expired', 'updated_at' => gmdate( 'Y-m-d H:i:s' ) ],
            [ 'id' => $license->id ],
            [ '%s', '%s' ],
            [ '%d' ]
        );

        return (int) $license->id;
    }

    /**
     * Subscription aktualisiert → Plan-Änderung.
     */
    private function handle_subscription_updated( object $subscription, CWLM_License_Manager $manager ): ?int {
        $license = $manager->find_by_subscription( $subscription->id );
        if ( ! $license ) {
            return null;
        }

        return (int) $license->id;
    }

    /**
     * Rückerstattung oder Dispute → Lizenz sperren.
     */
    private function handle_refund_or_dispute( object $charge, CWLM_License_Manager $manager ): ?int {
        global $wpdb;
        $prefix = $wpdb->prefix . CWLM_DB_PREFIX;

        $customer_id = $charge->customer ?? null;
        if ( ! $customer_id ) {
            return null;
        }

        $license = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$prefix}licenses WHERE stripe_customer_id = %s AND status != 'revoked' LIMIT 1",
                $customer_id
            )
        );

        if ( ! $license ) {
            return null;
        }

        $manager->revoke_license( (int) $license->id, 'Stripe: Rückerstattung oder Dispute' );

        return (int) $license->id;
    }

    /**
     * Stripe Webhook-Signatur prüfen.
     *
     * Nutzt das Stripe PHP SDK (Composer-Dependency) wenn verfügbar,
     * fällt auf manuelle HMAC-SHA256-Prüfung zurück.
     */
    private function verify_signature( string $payload, ?string $sig_header, string $secret ): bool {
        if ( empty( $secret ) || empty( $sig_header ) ) {
            return false;
        }

        // Autoloader wird zentral in cachewarmer-license-manager.php geladen
        // Stripe SDK verwenden wenn verfügbar
        if ( class_exists( '\Stripe\Webhook' ) ) {
            try {
                \Stripe\Webhook::constructEvent( $payload, $sig_header, $secret );
                return true;
            } catch ( \Stripe\Exception\SignatureVerificationException $e ) {
                return false;
            } catch ( \Exception $e ) {
                return false;
            }
        }

        // Fallback: Manuelle Signaturprüfung
        $elements = [];
        foreach ( explode( ',', $sig_header ) as $element ) {
            $parts = explode( '=', $element, 2 );
            if ( count( $parts ) === 2 ) {
                $elements[ trim( $parts[0] ) ] = trim( $parts[1] );
            }
        }

        if ( empty( $elements['t'] ) || empty( $elements['v1'] ) ) {
            return false;
        }

        // Replay-Schutz: Timestamp darf max. 5 Minuten alt sein
        $tolerance = defined( 'CWLM_STRIPE_WEBHOOK_TOLERANCE' ) ? (int) CWLM_STRIPE_WEBHOOK_TOLERANCE : 300;
        $timestamp = (int) $elements['t'];
        if ( abs( time() - $timestamp ) > $tolerance ) {
            return false;
        }

        $signed_payload   = $elements['t'] . '.' . $payload;
        $expected_sig     = hash_hmac( 'sha256', $signed_payload, $secret );

        return hash_equals( $expected_sig, $elements['v1'] );
    }
}
