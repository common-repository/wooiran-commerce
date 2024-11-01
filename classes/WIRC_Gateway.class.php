<?php 
	/**
	* 
	*/
	class WIRC_Gateway extends WC_Payment_Gateway {
		
		
		public $Soap_WSDL;
		
		
		public $messages = array();
		
		
		public function init_gateway(){
			$this->init_form_fields();
			$this->init_settings();
			
			if( isset( $this->id ) ){
				add_action( "woocommerce_update_options_payment_gateways_{$this->id}", array( $this, 'process_admin_options' ) );
				add_action( "woocommerce_api_{$this->id}", array( $this, 'verify_payment' ) );
			}
		}
		
		
		public function get_message( $key="" ){
			if( isset( $this->messages[$key] ) ){
				return $this->messages[$key];
			}
			return "";
		}
		
		
	
		public function set_message( $key=null, $value=null ){
			if( $key ){
				$this->messages[$key] = $value;
				return true;
			}
			return false;
		} 
		
		
		public function get_verify_payment_url( $order_id ){
			return add_query_arg( array(
				'order_id' 	 =>  $order_id, 
				'wc-api' 	 => get_class( $this )
			), home_url( '/' ) );
		}
		
		
		public function verify_payment(){}
		
	}

?>