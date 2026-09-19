<?php
/**
 * Plugin Name: FluentCRM Tag to Woo Membership
 * Description: Map FluentCRM tags to WooCommerce Memberships plans and automatically enroll or unenroll WordPress users when tags are added or removed.
 * Version: 1.0.1
 * Author: Mike@KitMage
 * Author URI: https://kitmage.com
 * URI: https://kitmage.com
 * License: GPL-2.0-or-later
 * Text Domain: fluentcrm-tag-to-woo-membership
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class FCTWM_Plugin {
    private const OPTION = 'fctwm_rules';
    private const MENU_SLUG = 'fctwm-rules';

    public static function init(): void {
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );
        add_action( 'admin_post_fctwm_save_rules', array( __CLASS__, 'save_rules' ) );
        add_action( 'fluent_crm/contact_added_to_tags', array( __CLASS__, 'handle_tags_added' ), 10, 2 );
        add_action( 'fluent_crm/contact_removed_from_tags', array( __CLASS__, 'handle_tags_removed' ), 10, 2 );
        add_action( 'fluentcrm_contact_added_to_tags', array( __CLASS__, 'handle_legacy_tags_added' ), 10, 2 );
        add_action( 'fluentcrm_contact_removed_from_tags', array( __CLASS__, 'handle_legacy_tags_removed' ), 10, 2 );
    }

    public static function register_admin_page(): void {
        add_submenu_page(
            'woocommerce',
            __( 'FluentCRM Membership Rules', 'fluentcrm-tag-to-woo-membership' ),
            __( 'FluentCRM Membership Rules', 'fluentcrm-tag-to-woo-membership' ),
            'manage_woocommerce',
            self::MENU_SLUG,
            array( __CLASS__, 'render_admin_page' )
        );
    }

    public static function render_admin_page(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage these rules.', 'fluentcrm-tag-to-woo-membership' ) );
        }

        $rules = self::get_rules();
        $tags  = self::get_fluentcrm_tags();
        $plans = self::get_membership_plans();
        $rows  = max( count( $rules ) + 1, 1 );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'FluentCRM Tag to WooCommerce Membership Rules', 'fluentcrm-tag-to-woo-membership' ); ?></h1>
            <p><?php esc_html_e( 'Create rules that enroll a WordPress user in a WooCommerce Memberships plan when a FluentCRM tag is added, and unenroll the user when that tag is removed.', 'fluentcrm-tag-to-woo-membership' ); ?></p>

            <?php if ( empty( $tags ) || empty( $plans ) ) : ?>
                <div class="notice notice-warning inline"><p><?php esc_html_e( 'FluentCRM tags and WooCommerce Memberships plans must both exist before rules can be configured.', 'fluentcrm-tag-to-woo-membership' ); ?></p></div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="fctwm_save_rules" />
                <?php wp_nonce_field( 'fctwm_save_rules' ); ?>
                <table class="widefat striped" style="max-width: 900px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'FluentCRM Tag', 'fluentcrm-tag-to-woo-membership' ); ?></th>
                            <th><?php esc_html_e( 'Membership Plan', 'fluentcrm-tag-to-woo-membership' ); ?></th>
                            <th><?php esc_html_e( 'Enabled', 'fluentcrm-tag-to-woo-membership' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php for ( $i = 0; $i < $rows; $i++ ) :
                        $rule = $rules[ $i ] ?? array( 'tag_id' => 0, 'plan_id' => 0, 'enabled' => true );
                        ?>
                        <tr>
                            <td><?php self::render_select( "rules[$i][tag_id]", $tags, (int) $rule['tag_id'], __( 'Select a tag', 'fluentcrm-tag-to-woo-membership' ) ); ?></td>
                            <td><?php self::render_select( "rules[$i][plan_id]", $plans, (int) $rule['plan_id'], __( 'Select a plan', 'fluentcrm-tag-to-woo-membership' ) ); ?></td>
                            <td><label><input type="checkbox" name="rules[<?php echo esc_attr( (string) $i ); ?>][enabled]" value="1" <?php checked( ! empty( $rule['enabled'] ) ); ?> /> <?php esc_html_e( 'Active', 'fluentcrm-tag-to-woo-membership' ); ?></label></td>
                        </tr>
                    <?php endfor; ?>
                    </tbody>
                </table>
                <p class="description"><?php esc_html_e( 'Save to add another blank row. Leave either dropdown empty to remove that row.', 'fluentcrm-tag-to-woo-membership' ); ?></p>
                <?php submit_button( __( 'Save Rules', 'fluentcrm-tag-to-woo-membership' ) ); ?>
            </form>
        </div>
        <?php
    }

    private static function render_select( string $name, array $options, int $selected, string $placeholder ): void {
        echo '<select name="' . esc_attr( $name ) . '">';
        echo '<option value="0">' . esc_html( $placeholder ) . '</option>';
        foreach ( $options as $id => $label ) {
            echo '<option value="' . esc_attr( (string) $id ) . '" ' . selected( $selected, (int) $id, false ) . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
    }

    public static function save_rules(): void {
        if ( ! current_user_can( 'manage_woocommerce' ) ) {
            wp_die( esc_html__( 'You do not have permission to manage these rules.', 'fluentcrm-tag-to-woo-membership' ) );
        }
        check_admin_referer( 'fctwm_save_rules' );

        $posted = isset( $_POST['rules'] ) && is_array( $_POST['rules'] ) ? wp_unslash( $_POST['rules'] ) : array();
        $rules  = array();
        foreach ( $posted as $rule ) {
            $tag_id  = isset( $rule['tag_id'] ) ? absint( $rule['tag_id'] ) : 0;
            $plan_id = isset( $rule['plan_id'] ) ? absint( $rule['plan_id'] ) : 0;
            if ( ! $tag_id || ! $plan_id ) {
                continue;
            }
            $rules[] = array(
                'tag_id'  => $tag_id,
                'plan_id' => $plan_id,
                'enabled' => ! empty( $rule['enabled'] ),
            );
        }

        update_option( self::OPTION, $rules, false );
        wp_safe_redirect( add_query_arg( array( 'page' => self::MENU_SLUG, 'updated' => 'true' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function handle_tags_added( $subscriber, $tag_ids ): void {
        self::sync_memberships( $subscriber, (array) $tag_ids, 'add' );
    }

    public static function handle_tags_removed( $subscriber, $tag_ids ): void {
        self::sync_memberships( $subscriber, (array) $tag_ids, 'remove' );
    }

    public static function handle_legacy_tags_added( $tag_ids, $subscriber ): void {
        self::handle_tags_added( $subscriber, $tag_ids );
    }

    public static function handle_legacy_tags_removed( $tag_ids, $subscriber ): void {
        self::handle_tags_removed( $subscriber, $tag_ids );
    }

    private static function sync_memberships( $subscriber, array $tag_ids, string $action ): void {
        if ( ! function_exists( 'wc_memberships_create_user_membership' ) || ! function_exists( 'wc_memberships_get_user_membership' ) ) {
            return;
        }

        $user_id = self::get_subscriber_user_id( $subscriber );
        if ( ! $user_id ) {
            return;
        }

        $tag_ids = array_map( 'intval', $tag_ids );
        foreach ( self::get_rules() as $rule ) {
            if ( empty( $rule['enabled'] ) || ! in_array( (int) $rule['tag_id'], $tag_ids, true ) ) {
                continue;
            }
            if ( 'add' === $action ) {
                self::enroll_user( $user_id, (int) $rule['plan_id'] );
            } else {
                self::unenroll_user( $user_id, (int) $rule['plan_id'] );
            }
        }
    }

    private static function enroll_user( int $user_id, int $plan_id ): void {
        $membership = wc_memberships_get_user_membership( $user_id, $plan_id );
        if ( $membership ) {
            if ( method_exists( $membership, 'update_status' ) ) {
                $membership->update_status( 'active' );
            }
            return;
        }
        wc_memberships_create_user_membership(
            array(
                'plan_id' => $plan_id,
                'user_id' => $user_id,
            )
        );
    }

    private static function unenroll_user( int $user_id, int $plan_id ): void {
        $membership = wc_memberships_get_user_membership( $user_id, $plan_id );
        if ( $membership && method_exists( $membership, 'get_id' ) ) {
            wp_delete_post( $membership->get_id(), true );
        }
    }

    private static function get_subscriber_user_id( $subscriber ): int {
        foreach ( array( 'user_id', 'wp_user_id' ) as $property ) {
            if ( isset( $subscriber->{$property} ) && $subscriber->{$property} ) {
                return absint( $subscriber->{$property} );
            }
        }
        if ( is_callable( array( $subscriber, 'getWpUserId' ) ) ) {
            return absint( $subscriber->getWpUserId() );
        }
        return 0;
    }

    private static function get_rules(): array {
        $rules = get_option( self::OPTION, array() );
        return is_array( $rules ) ? $rules : array();
    }

    private static function get_fluentcrm_tags(): array {
        global $wpdb;
        $table = $wpdb->prefix . 'fc_tags';
        $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        if ( $found !== $table ) {
            return array();
        }
        $rows = $wpdb->get_results( "SELECT id, title FROM {$table} ORDER BY title ASC" );
        return wp_list_pluck( $rows, 'title', 'id' );
    }

    private static function get_membership_plans(): array {
        $posts = get_posts(
            array(
                'post_type'      => 'wc_membership_plan',
                'post_status'    => array( 'publish', 'private', 'draft' ),
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );
        return wp_list_pluck( $posts, 'post_title', 'ID' );
    }
}

FCTWM_Plugin::init();
