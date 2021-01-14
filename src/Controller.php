<?php

namespace WP2StaticAzure;

class Controller {
    public function run() : void {
        add_action(
            'wp2static_deploy',
            [ $this, 'deploy' ],
            15,
            2
        );

        add_filter(
            'wp2static_add_menu_items',
            [ 'WP2StaticAzure\Controller', 'addSubmenuPage' ]
        );

        add_action(
            'admin_post_wp2static_azure_save_options',
            [ $this, 'saveOptionsFromUI' ],
            15,
            1
        );

        add_action(
            'admin_menu',
            [ $this, 'addOptionsPage' ],
            15,
            1
        );

        // add_filter( 'parent_file', [ $this, 'setActiveParentMenu' ] );

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

    /**
     *  Get all add-on options
     *
     *  @return mixed[] All options
     */
    public static function getOptions() : array {
        global $wpdb;
        $options = [];

        $table_name = $wpdb->prefix . 'wp2static_addon_azure_options';

        $rows = $wpdb->get_results( "SELECT * FROM $table_name" );

        foreach ( $rows as $row ) {
            $options[ $row->name ] = $row;
        }

        return $options;
    }

        /**
     * Seed options
     */
    public static function seedOptions() : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addon_azure_options';

        $query_string =
            "INSERT INTO $table_name (name, value, label, description)
            VALUES (%s, %s, %s, %s);";

        $query = $wpdb->prepare(
            $query_string,
            'storageAccountName',
            '',
            'Azure Storage Account Name',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            'storageContainer',
            '',
            'Azure Blob Container',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            'storageFolder',
            '',
            'Folder in Container',
            ''
        );

        $wpdb->query( $query );

        $query = $wpdb->prepare(
            $query_string,
            'sasToken',
            '',
            'SAS (Shared Access Signature) token',
            ''
        );

        $wpdb->query( $query );
    }

    /**
     * Save options
     *
     * @param mixed $value value to save
     */
    public static function saveOption( string $name, $value ) : void {
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addon_azure_options';

        $query_string = "INSERT INTO $table_name (name, value) VALUES (%s, %s);";
        $query = $wpdb->prepare( $query_string, $name, $value );

        $wpdb->query( $query );
    }


    public static function renderAzurePage() : void {
        $view = [];
        $view['nonce_action'] = 'azure-azure-options';
        $view['options'] = self::getOptions();

        require_once __DIR__ . '/../views/azure-page.php';
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
        // initialize options DB
        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addon_azure_options';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            value VARCHAR(255) NOT NULL,
            label VARCHAR(255) NULL,
            description VARCHAR(255) NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        // check for seed data
        // if deployment_url option doesn't exist, create:
        $options = self::getOptions();

        if ( ! isset( $options['storageAccountName'] ) ) {
            self::seedOptions();
        }
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

    public static function saveOptionsFromUI() : void {
        check_admin_referer( 'wp2static-azure-options' );

        global $wpdb;

        $table_name = $wpdb->prefix . 'wp2static_addon_azure_options';

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['storageAccountName'] ) ],
            [ 'name' => 'storageAccountName' ]
        );

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['storageContainer'] ) ],
            [ 'name' => 'storageContainer' ]
        );

        $wpdb->update(
            $table_name,
            [ 'value' => sanitize_text_field( $_POST['storageFolder'] ) ],
            [ 'name' => 'storageFolder' ]
        );

        $personal_access_token =
            $_POST['sasToken'] ?
            \WP2Static\CoreOptions::encrypt_decrypt(
                'encrypt',
                sanitize_text_field( $_POST['sasToken'] )
            ) : '';
        $wpdb->update(
            $table_name,
            [ 'value' => $personal_access_token ],
            [ 'name' => 'sasToken' ]
        );


        wp_safe_redirect(
            admin_url( 'admin.php?page=wp2static-addon-azure' )
        );

        exit;
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
