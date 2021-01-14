<?php

namespace WP2StaticAzure;

class Controller {
    public function run() : void {
        // add_action(
        //     'admin_post_wp2static_zip_delete',
        //     [ $this, 'deleteZip' ],
        //     15,
        //     1
        // );

        add_action(
            'wp2static_deploy',
            [ $this, 'deploy' ],
            15,
            2
        );

        // add_action(
        //     'admin_menu',
        //     [ $this, 'addOptionsPage' ],
        //     15,
        //     1
        // );

        add_filter( 'parent_file', [ $this, 'setActiveParentMenu' ] );

        do_action(
            'wp2static_register_addon',
            'wp2static-addon-azure',
            'deploy',
            'Azure Deployment',
            'https://wp2static.com/addons/azure/',
            'Deploys to Azure'
        );

        // if ( defined( 'WP_CLI' ) ) {
        //     \WP_CLI::add_command(
        //         'wp2static zip',
        //         [ CLI::class, 'zip' ]
        //     );
        // }
    }

    public static function renderAzurePage() : void {
        
    }

    

    public function deleteZip( string $processed_site_path ) : void {
        
    }

    public function deploy( string $processed_site_path, string $enabled_deployer ) : void {
        if ( $enabled_deployer !== 'wp2static-addon-azure' ) {
            return;
        }

        $azure_deployer = new Deployer();
        $azure_deployer->upload_files( $processed_site_path );
    }

    public static function activate_for_single_site() : void {
    }

    public static function deactivate_for_single_site() : void {
    }

    public static function deactivate( bool $network_wide = null ) : void {
        if ( $network_wide ) {
            global $wpdb;

            $query = 'SELECT blog_id FROM %s WHERE site_id = %d;';

            $site_ids = $wpdb->get_col(
                sprintf(
                    $query,
                    $wpdb->blogs,
                    $wpdb->siteid
                )
            );

            foreach ( $site_ids as $site_id ) {
                switch_to_blog( $site_id );
                self::deactivate_for_single_site();
            }

            restore_current_blog();
        } else {
            self::deactivate_for_single_site();
        }
    }

    public static function activate( bool $network_wide = null ) : void {
        if ( $network_wide ) {
            global $wpdb;

            $query = 'SELECT blog_id FROM %s WHERE site_id = %d;';

            $site_ids = $wpdb->get_col(
                sprintf(
                    $query,
                    $wpdb->blogs,
                    $wpdb->siteid
                )
            );

            foreach ( $site_ids as $site_id ) {
                switch_to_blog( $site_id );
                self::activate_for_single_site();
            }

            restore_current_blog();
        } else {
            self::activate_for_single_site();
        }
    }

    public static function addSubmenuPage( array $submenu_pages ) : array {
        $submenu_pages['azure'] = [ 'WP2StaticAzure\Controller', 'renderAzurePage' ];

        return $submenu_pages;
    }

    
    /**
     * Get option value
     *
     * @return string option value
     */
    public static function getValue( string $name ) : string {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addon_azure_options';

        $sql = $wpdb->prepare(
            "SELECT value FROM $table_name WHERE" . ' name = %s LIMIT 1',
            $name
        );

        $option_value = $wpdb->get_var( $sql );

        if ( ! is_string( $option_value ) ) {
            return '';
        }

        return $option_value;
    }

    public function addOptionsPage() : void {
        add_submenu_page(
            '',
            'Azure Deployment Options',
            'Azure Deployment Options',
            'manage_options',
            'wp2static-addon-azure',
            [ $this, 'renderAzurePage' ]
        );
    }

    // ensure WP2Static menu is active for addon
    public function setActiveParentMenu() : void {
            global $plugin_page;

        if ( 'wp2static-addon-azure' === $plugin_page ) {
            // phpcs:ignore
            $plugin_page = 'wp2static-options';
        }
    }
}
