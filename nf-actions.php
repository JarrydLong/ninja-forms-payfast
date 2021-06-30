<?php if ( ! defined( 'ABSPATH' ) || ! class_exists( 'NF_Abstracts_Action' )) exit;

session_start();

/**
 * Class NF_Action_PayfastExample
 */
final class NF_Payfast_Actions_Payfast extends NF_Abstracts_Action
{
    /**
     * @var string
     */
    protected $_name  = 'payfast';

    /**
     * @var array
     */
    protected $_tags = array();

    /**
     * @var string
     */
    protected $_timing = 'normal';

    /**
     * @var int
     */
    protected $_priority = '10';


    /**
     * Constructor
     */
    public function __construct(){

        parent::__construct();

        $this->environment = Ninja_Forms()->get_setting( 'yoohoo_payfast_env' );

        $this->merchant_id = ( $this->environment === 'Live' ) ? Ninja_Forms()->get_setting( 'yoohoo_payfast_merchant_id' ) : Ninja_Forms()->get_setting( 'yoohoo_payfast_merchant_id_test' );
        
        $this->merchant_key = ( $this->environment === 'Live' ) ? Ninja_Forms()->get_setting( 'yoohoo_payfast_merchant_key' ) : Ninja_Forms()->get_setting( 'yoohoo_payfast_merchant_key_test' );

        $this->passphrase = ( $this->environment === 'Live' ) ? Ninja_Forms()->get_setting( 'yoohoo_payfast_passphrase' ) : Ninja_Forms()->get_setting( 'yoohoo_payfast_passphrase_test' );

        $this->payfast_url = ( $this->environment === 'Live' ) ? 'https://www.payfast.co.za/eng/process' : 'https://sandbox.payfast.co.za/eng/process';

        $this->_nicename = __( 'Payfast', 'ninja-forms' );

        $this->_settings = array(
            'payfast_email' => array(
                'name' => 'payfast_email',
                'type' => 'textbox',
                'label' => __( 'Email Address', 'ninja-forms'),
                'width' => 'full',
                'group' => 'primary',
                'value' => '',
                'help' => __( 'Specify which field relates to the customer\'s email address for invoicing purposes.', 'ninja-forms' ),
                'use_merge_tags' => true
            ),
            'payfast_amount' => array(
                'name' => 'payfast_amount',
                'type' => 'textbox',
                'label' => __( 'Billing Amount', 'ninja-forms'),
                'width' => 'full',
                'group' => 'primary',
                'value' => '',
                'help' => __( 'Specify which field relates to the total amount to bill the customer.', 'ninja-forms' ),
                'use_merge_tags' => true
            ),
            'payfast_thankyou' => array(
                'name' => 'payfast_thankyou',
                'type' => 'textbox',
                'label' => __( 'Thank You URL', 'ninja-forms'),
                'width' => 'full',
                'group' => 'primary',
                'value' => '',
                'help' => __( 'Specify which field the payment should be redirected after a transaction is successfully processed.', 'ninja-forms' ),
            ),

        );

        add_action( 'init', array( $this, 'verify_transaction' ) );

        add_action( 'ninja_forms_save_sub', array( $this, 'save_sub' ) );

        add_filter( 'the_content', array( $this, 'thank_you_content' ), 10, 1 );
        
        add_filter( 'manage_nf_sub_posts_columns', array( $this, 'set_custom_edit_book_columns' ) );

        add_action( 'manage_posts_custom_column' , array( $this, 'custom_book_column' ), 10, 2 );

    }  

    /*
    * PUBLIC METHODS
    */

    public function set_custom_edit_book_columns( $columns ){

        $columns['nf_payfast_status'] = __( 'Payfast Payment', 'your_text_domain' );

        return $columns;

    }

    public function custom_book_column( $column, $post_id ){

        switch ( $column ) {
            case 'nf_payfast_status' :
                $request = get_post_meta( $post_id , 'payfast_status' , true ); 
                if( !empty( $request->status ) && $request->status === true && $request->data->status == 'success' ){
                    echo "<span style='padding: 3px 6px; background-color: green; color: #FFF; border-radius: 3px;'>Paid</span>";
                } else {
                    echo "<span style='padding: 3px 6px; background-color: red; color: #FFF; border-radius: 3px;'>Unpaid</span>";
                }
                break;

        }

    }

    public function thank_you_content( $content ){

        if( !empty( $_REQUEST['nf-payfast'] ) ){

            if( $_REQUEST['nf-payfast'] == 'success' ){
                return Ninja_Forms()->get_setting( 'yoohoo_payfast_success_message' ).$content;
            } 

            return Ninja_Forms()->get_setting( 'yoohoo_payfast_error_message' ).$content;

        }

        return $content;

    }
   
    public function save_sub( $sub_id ){

        $_SESSION['nf_payfast_sub'] = $sub_id;

    }


    public function verify_transaction(){

        if( !empty( $_REQUEST['trxref'] ) ){

            $request = $this->payfast_request( 'GET', '/transaction/verify/'.$_REQUEST['trxref'] );
            if( !empty( $request->status ) ){

                if( !empty( $_SESSION['nf_payfast_sub'] ) ){
                    update_post_meta( intval( $_SESSION['nf_payfast_sub'] ), 'payfast_status', $request );
                } 

                if( $request->status === true ){

                    if( $request->data->status === 'success' ){
                        if( !empty( $_SESSION['nf_payfast_thankyou'] ) ){
                            header("Location: ".$_SESSION['nf_payfast_thankyou']."?nf-payfast=success");
                        }
                    } else {
                        if( !empty( $_SESSION['nf_payfast_thankyou'] ) ){
                            header("Location: ".$_SESSION['nf_payfast_thankyou']."?nf-payfast=error");
                        }
                    }
                }
            }
            
        }

    }

    public function save( $action_settings ){
    
    }

    public function process( $action_settings, $form_id, $data ){

        $billing_email = ( !empty( $action_settings['payfast_email'] ) ) ? sanitize_text_field( $action_settings['payfast_email'] ) : "";

        $billing_amount = ( !empty( $action_settings['payfast_amount'] ) ) ? floatval( $action_settings['payfast_amount'] ) : 0;
        
        if( $billing_email !== "" && $billing_amount !== 0 ){

            $body = array(
                'merchant_id'   => $this->merchant_id,
                'merchant_key'  => $this->merchant_key,
                'return_url'    => add_query_arg( 'payfast-listener', 'return', admin_url( 'admin-ajax.php') ),
                'cancel_url'    => add_query_arg( 'payfast-listener', 'cancel', admin_url( 'admin-ajax.php') ),
                'notify_url'    => add_query_arg( 'payfast-listener', 'notify', admin_url( 'admin-ajax.php') ),
                'name_first'    => '',
                'name_last'     => '',
                'email_address' => $billing_email,        
                'm_payment_id'  => 'NF-' . $data['form_id'] . '-' . time(),
                'amount'        => number_format( sprintf( '%.2f', $billing_amount ) ),
                'item_name'     => $data['settings']['title'],                
            );

            $signature = $this->generate_signature( $body, $this->passphrase );
            
            $body['signature'] = $signature;

            $data[ 'actions' ][ 'redirect' ] = add_query_arg( $body, $this->payfast_url );
                    
        }

        return $data;
    }

    function generate_signature($data, $passPhrase = null) {
        // Create parameter string
        $pfOutput = '';
        foreach( $data as $key => $val ) {
            if(!empty($val) || $val === 0 ) {
                $pfOutput .= $key .'='. urlencode( trim( $val ) ) .'&';
            }
        }

        // Remove last ampersand
        $getString = substr( $pfOutput, 0, -1 );
        if( $passPhrase !== null ) {
            $getString .= '&passphrase='. urlencode( trim( $passPhrase ) );
        }
        
        return md5( $getString );
    }     

}