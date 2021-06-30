<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Example_Admin_Settings
 *
 * This is an example implementation of a Settings Class for a Ninja Forms Add-on.
 * The Ninja Forms Settings Submenu page handles registering, rendering, and saving settings.
 * Settings handled by Ninja Forms can be access using the Ninja Forms API.
 * Multiple WordPress Hooks are available for interacting with settings processing.
 */
final class NF_Example_Admin_Settings
{
    /**
     * NF_Example_Admin_Settings constructor.
     *
     * The following WordPress hooks are listed in processing order.
     */
    public function __construct()
    {
        /*
         * On Settings Page Load
         */
        add_filter( 'ninja_forms_plugin_settings',                array( $this, 'plugin_settings'        ), 10, 1 );
        add_filter( 'ninja_forms_plugin_settings_groups',         array( $this, 'plugin_settings_groups' ), 10, 1 );
        add_filter( 'ninja_forms_check_setting_example_setting',  array( $this, 'check_example_setting'  ), 10, 1 );

        /*
         * On Settings Page Save (Submit)
         */
        add_filter( 'ninja_forms_update_setting_example_setting', array( $this, 'update_example_setting' ), 10, 1 );
        add_action( 'ninja_forms_save_setting_example_setting',   array( $this, 'save_example_setting'   ), 10, 1 );
    }

    /**
     * Add Plugin Settings
     *
     * Add a new setting within a defined setting group.
     * The setting's configuration is similar to Action Settings and Fields Settings.
     *
     * @param array $settings
     * @return array $settings
     */
    public function plugin_settings( $settings )
    {
        $settings[ 'paystack_jl' ] = array(
            'yoohoo_payfast_env' => array(
                'id'    => 'yoohoo_payfast_env',
                'type'  => 'select',
                'label'  => __( 'Gateway Environment', 'ninja-forms-example' ),
                'options' => array(
                    array(
                        'label' => 'Sandbox',
                        'value' => 'sandbox'
                    ),
                    array(
                        'label' => 'Live',
                        'value' => 'Live'
                    )
                )
            ),
            'yoohoo_payfast_merchant_id' => array(
                'id'    => 'yoohoo_payfast_merchant_id',
                'type'  => 'textbox',
                'label'  => __( 'Live Merchant ID', 'ninja-forms-example' ),
            ),
            'yoohoo_payfast_merchant_key' => array(
                'id'    => 'yoohoo_payfast_merchant_key',
                'type'  => 'textbox',
                'label'  => __( 'Live Merchant Key', 'ninja-forms-example' ),
            ),
            'yoohoo_payfast_passphrase' => array(
                'id'    => 'yoohoo_payfast_passphrase',
                'type'  => 'textbox',
                'label'  => __( 'Live Passphrase', 'ninja-forms-example' ),
            ),
            'yoohoo_payfast_merchant_id_test' => array(
                'id'    => 'yoohoo_payfast_merchant_id_test',
                'type'  => 'textbox',
                'label'  => __( 'Sandbox Merchant ID', 'ninja-forms-example' ),
            ),
            'yoohoo_payfast_merchant_key_test' => array(
                'id'    => 'yoohoo_payfast_merchant_key_test',
                'type'  => 'textbox',
                'label'  => __( 'Sandbox Merchant Key', 'ninja-forms-example' ),
            ),
            'yoohoo_payfast_passphrase_test' => array(
                'id'    => 'yoohoo_payfast_passphrase_test',
                'type'  => 'textbox',
                'label'  => __( 'Sandbox Passphrase', 'ninja-forms-example' ),
            ),
            'yoohoo_payfast_success_message' => array(
                'id'    => 'yoohoo_payfast_success_message',
                'type'  => 'textbox',
                'label'  => __( 'Thank You Success Message', 'ninja-forms-example' ),
                'desc'  => __( 'Displayed on the Thank You page upon success payment.', 'ninja-forms-example' ),
            ),
            'yoohoo_payfast_error_message' => array(
                'id'    => 'yoohoo_payfast_error_message',
                'type'  => 'textbox',
                'label'  => __( 'Thank You Error Message', 'ninja-forms-example' ),
                'desc'  => __( 'Displayed on the Thank You page upon failed payment.', 'ninja-forms-example' ),
            ),

        );
        return $settings;
    }

    /**
     * Add Plugin Settings Groups
     *
     * Add a new Settings Groups for this plugin's settings.
     * The grouped settings will be rendered as a metabox in the Ninja Forms Settings Submenu page.
     *
     * @param array $groups
     * @return array $groups
     */
    public function plugin_settings_groups( $groups )
    {
        $groups[ 'paystack_jl' ] = array(
            'id' => 'paystack_jl',
            'label' => __( 'Payfast Integration', 'ninja-forms-example' ),
        );
        return $groups;
    }

    /**
     * Check Example Setting for Errors
     *
     * Before the Example Setting is disaplyed, check for / add errors for display.
     * Note: This check is performed when the Ninja Forms Settings Submenu page is loaded.
     *
     * @param array $setting
     * @return array $setting
     */
    public function check_example_setting( $setting )
    {
        
        // if( $has_errors ) {
        //     $setting['errors'][] = __('The value you have entered appears to be invalid.', 'ninja-forms-example');
        // }
        return $setting;
    }

    /**
     * Update Example Setting Value
     *
     * Before the Example Setting is saved, update the value.
     *
     * @param $setting
     * @return mixed
     */
    public function update_example_setting( $setting_value )
    {
        $setting_value = trim( $setting_value );
        return $setting_value;
    }

    /**
     * Save Example Setting
     *
     * After the Example Setting is saved, do something with the saved value.
     *
     * @param $setting
     * @return void
     */
    public function save_example_setting( $setting_value )
    {
        if( strpos( $setting_value, '_' ) ){
            $parts = explode( '_', $setting_value );

            foreach( $parts as $key => $value ){
                Ninja_Forms()->update_setting( 'example_part_' . $key, $value );
            }
        }
    }

} // End Class NF_Example_Admin_Settings

new NF_Example_Admin_Settings();