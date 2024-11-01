<?php 
	/*
		Plugin Name: WooIran Commerce
		Plugin URI: https://khosroblog.com
		Description: A wordpress plugin for integrate woocommerce with popular iranian payment gateways and shipping services.
		Version: 0.1.0
		Author: Hadi Khosrojerdi
		Author URI: http://khosroblog.com
		License: GNU General Public License v2 or later 
	*/
	
	
	
	class WooIRAN_Gateways {
		
		
		public static $instance;
		
		
		public function __construct(){
			$this->init();
		}
		
		
		
		public static function get_instance(){
			if( ! self::$instance ){
				self::$instance = new self;
			}
			return self::$instance;
		}
		
		
		
		public function init(){
		
			# Constants
			add_action("plugins_loaded", array($this, "define_constants"));
			
			# Classes
			add_action("plugins_loaded", array($this, "load_classes"));
			
			# Localization
			add_action("plugins_loaded", array($this, "localization"));
			
			# Register / Unregister WooIRAN Commerce gateways
			add_action( 'woocommerce_payment_gateways', array($this, "register_payment_gateways"));
			add_action( 'woocommerce_payment_gateways', array($this, "unregister_payment_gateways"));
			
			# Register WooIRAN Commerce Settings
			add_action("woocommerce_get_settings_pages", array($this, "register_wooiran_commerce_settings"));
			
			# Add Iran currency codes
			add_filter("woocommerce_currencies", array($this, "add_iran_currency_codes"));
			
			# Add Iran currency symbols
			add_filter("woocommerce_currency_symbol", array($this, "add_iran_currency_symbols"), 20, 2);
			
			do_action("wirc_init");
		}
		
		
		
		
		public function define_constants(){
			defined("WIRC_URL") 			? null : define("WIRC_URL", plugin_dir_url( __FILE__ ) );
			defined("WIRC_DIR") 			? null : define("WIRC_DIR", plugin_dir_path( __FILE__ ) );
			defined("WIRC_CLASSES_DIR")		? null : define("WIRC_CLASSES_DIR", WIRC_DIR . trailingslashit("classes") );
			defined("WIRC_GATEWAYS_DIR") 	? null : define("WIRC_GATEWAYS_DIR", WIRC_CLASSES_DIR . trailingslashit("gateways") );
			defined("WIRC_SHIPPINGS_DIR") 	? null : define("WIRC_SHIPPINGS_DIR", WIRC_CLASSES_DIR . trailingslashit("shippings") );
			defined("WIRC_LANG_DIR") 		? null : define("WIRC_LANG_DIR", WIRC_DIR . trailingslashit("languages") );
		}
		
		
		
		public function load_classes(){
			# nusoap.class.php
			if( !extension_loaded("soap") && !class_exists("soapclient") ){
				require_once( WIRC_CLASSES_DIR . "nusoap.class.php");
			}
			# Soap_WSDL.class.php
			if( !class_exists("Soap_WSDL") ){
				require_once( WIRC_CLASSES_DIR . "Soap_WSDL.class.php");
			}
			# WIRC_Gateway.class.php
			if( !class_exists("WIRC_Gateway") ){
				require_once( WIRC_CLASSES_DIR . "WIRC_Gateway.class.php");
			}
			# WIRC_ParsPal_Gateway.class.php
			if( !class_exists("WIRC_ParsPal_Gateway") ){
				require_once( WIRC_GATEWAYS_DIR . "WIRC_ParsPal_Gateway.class.php");
			}
			
		}
		
		
		
		public function register_wooiran_commerce_settings( $settings ){
			# WIRC_Settings.class.php
			if( !class_exists("WIRC_Settings") ){
				$settings[] = require_once( WIRC_CLASSES_DIR . "WIRC_Settings.class.php");
			}
			return $settings;
		}
		
		
		
		public function localization(){
			$lang_dir = plugin_basename( WIRC_LANG_DIR );
			
			load_plugin_textdomain('wooiran-commerce', false, $lang_dir );
			load_plugin_textdomain('wirc-gateways', false, $lang_dir . "/gateways" );
		}
		
		
		
		public function register_payment_gateways( $methods ){
			# ParsPal Gateway
			$methods[] = 'WIRC_ParsPal_Gateway';
			
			return $methods;
		}
		
		
		
		public function unregister_payment_gateways( $methods ){
			
			$gateways = array(
				"WC_Gateway_BACS"		=>	get_option("wirc_remove_bacs_gateway", "no" ),		
				"WC_Gateway_COD"		=>	get_option("wirc_remove_cod_gateway", "no" ),		
				"WC_Gateway_Cheque"		=>	get_option("wirc_remove_cheque_gateway", "no" ),	
				"WC_Gateway_Mijireh"	=>	get_option("wirc_remove_mijireh_gateway", "no" ),	
				"WC_Gateway_Paypal"		=>	get_option("wirc_remove_paypal_gateway", "no" ),
				"WooIRAN_ParsPal"		=>	get_option("wirc_remove_parspal_gateway", "no" ) // WIRC_ParsPal_Gateway
			);
			
			foreach( $gateways as $gateway_id => $is_remove ){
				if( $is_remove == "no" ){
					continue;
					
				}elseif( ( $index = array_search( $gateway_id, $methods ) ) !== false ){
					unset( $methods[$index] );
					
				}
			}
			
			return $methods;
		}
		
		
		
		public function add_iran_currency_codes( $currency_codes ){
			$currency_codes["IRR"] = __("Iran Rial", "wooiran-commerce");
			$currency_codes["IRT"] = __("Iran Toman", "wooiran-commerce");
			asort( $currency_codes );
			
			return $currency_codes;
		}
		
		
		
		public function add_iran_currency_symbols( $currency_symbol, $currency ){
			$currency = strtoupper( trim( $currency, " " ) );
			$lang = get_bloginfo('language');
			
			if( $currency == "IRR" ){
				$currency_symbol = ( $lang == "fa-IR" )? "ريال" : "IRR" ; // 
				
			}elseif( $currency == "IRT" ){
				$currency_symbol = ( $lang == "fa-IR" )? "تومان" : "IRT" ; // 
			}
			
			return $currency_symbol;
		}
		
		
	}
	
	
	# Load WooIRAN Gateways plugin
	WooIRAN_Gateways::get_instance();
	
?>