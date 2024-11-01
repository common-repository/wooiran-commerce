<?php 

	class WIRC_Settings extends WC_Settings_Page {
		
		public static $instance;
		
		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id    = 'wirc_settings';
			$this->label = __( 'WooIRAN', 'wooiran-commerce' );
			
			add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20 );
			add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
			add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
		}
		
		
		public static function get_instance(){
			if( ! self::$instance ){
				self::$instance = new self;
			}
			
			return self::$instance;
		}
		
			public function get_settings() {
				if( ( $ip = get_option("wirc_site_ip_address", "") ) == "" ){
					$url = wp_remote_get("http://merchant.parspal.com/IpShower/");
					if ( !is_wp_error( $url ) ) {
						$ip = trim( $url['body'], " " );
						update_option("wirc_site_ip_address", $ip);
					}  
				}
				
				$settings = array(
					array( "title"	=>	__( 'WooIRAN Commerce Settings', 'wooiran-commerce' ), "type"	=> "title", "id" => "wooiran_commerce_settings" ),
					array(
							'title' 	=> __( 'Site IP Address', 'wooiran-commerce' ),
							'desc_tip'  => __( 'Get your site IP address. This can be helpful for your merchant gateway account.', 'wooiran-commerce' ),
							'id'        => 'wirc_site_ip_address',
							'default'   => $ip,
							'type'      => 'text',
					),
					array(
							'title' 	=> __( 'BACS', 'wooiran-commerce' ),
							'desc'  	=> __( 'Remove BACS gateway', 'wooiran-commerce' ),
							'id'        => 'wirc_remove_bacs_gateway',
							'default'   => "no",
							'type'      => 'checkbox',
					),
					array(
							'title' 	=> __( 'Cash on Delivery', 'wooiran-commerce' ),
							'desc'  	=> __( 'Remove cash on delivery', 'wooiran-commerce' ),
							'id'        => 'wirc_remove_cod_gateway',
							'default'   => "no",
							'type'      => 'checkbox',
					),
					array(
							'title' 	=> __( 'Cheque', 'wooiran-commerce' ),
							'desc'  	=> __( 'Remove Cheque gateway', 'wooiran-commerce' ),
							'id'        => 'wirc_remove_cheque_gateway',
							'default'   => "no",
							'type'      => 'checkbox',
					),
					array(
							'title' 	=> __( 'Mijireh Checkout', 'wooiran-commerce' ),
							'desc'  	=> __( 'Remove the mijireh checkout', 'wooiran-commerce' ),
							'id'        => 'wirc_remove_mijireh_gateway',
							'default'   => "no",
							'type'      => 'checkbox',
					),
					array(
							'title' 	=> __( 'PayPal', 'wooiran-commerce' ),
							'desc'  	=> __( 'Remove PayPal gateway', 'wooiran-commerce' ),
							'id'        => 'wirc_remove_paypal_gateway',
							'default'   => "no",
							'type'      => 'checkbox',
					),
					array(
							'title' 	=> __( 'ParsPal', 'wooiran-commerce' ),
							'desc'  	=> __( 'Remove ParsPal gateway', 'wooiran-commerce' ),
							'id'        => 'wirc_remove_parspal_gateway',
							'default'   => "no",
							'type'      => 'checkbox',
					),
					
					array( 'type' => 'sectionend', 'id' => 'wooiran_commerce_settings')
				);
		
				return $settings;
			}
	}
	
	WIRC_Settings::get_instance();
?>