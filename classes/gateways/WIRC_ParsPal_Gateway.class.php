<?php 
	
	/**
	* WooIRAN ParsPal Gateway 
	*/
	class WIRC_ParsPal_Gateway extends WIRC_Gateway {
		 
		
		public function __construct(){
			
			$this->id = "wirc_parspal_gateway";
			//$this->icon = "";
			$this->has_fields = true;
			$this->method_title = __("ParsPal", "wirc-gateways");
			$this->method_description = __("ParsPal Payment Gateway", "wirc-gateways");
			
			$this->title 				= $this->get_option("title", $this->method_title );
			$this->description 			= $this->get_option("description", $this->method_description );
			$this->enabled 				= $this->get_option("enabled");
			$this->parspal_merchant_id 	= $this->get_option("parspal_merchant_id");
			$this->parspal_merchant_pass= $this->get_option("parspal_merchant_pass");
			
			$this->Soap_WSDL = new Soap_WSDL("http://merchant.parspal.com/WebService.asmx?wsdl", array(
				"MerchantID"	=>	$this->parspal_merchant_id,
				"Password"		=>	$this->parspal_merchant_pass,
			));
			
			// Set default ParsPal gateway messages
			$this->set_parspal_messages();
			
			# Initialize Gateway
			$this->init_gateway();
		}
		
		
		
		public function set_parspal_messages(){
			
			$this->set_message( "Ready", __("No action has not yet been done.", "wirc-gateways") );
			$this->set_message( "NotMatchMoney", __("The amount paid is not identical with the requested amount.", "wirc-gateways") );
			$this->set_message( "Verifyed", __("Previously has been paid.", "wirc-gateways") );
			$this->set_message( "InvalidRef", __("The receipt number is invalid.", "wirc-gateways") );
			$this->set_message( "success", __("Payment completed successfully.", "wirc-gateways") );
			$this->set_message( "GetwayUnverify", __("Your payment gateway is disabled.", "wirc-gateways") );
			$this->set_message( "GetwayIsExpired", __("Your payment gateway is expired.", "wirc-gateways") );
			$this->set_message( "GetwayIsBlocked", __("Your payment gateway is blocked.", "wirc-gateways") );
			$this->set_message( "GatewayInvalidInfo", __("Your payment gateway merchent id or password is invalid.", "wirc-gateways") );
			$this->set_message( "UserNotActive", __("The user is inactive.", "wirc-gateways") );
			$this->set_message( "InvalidServerIP", __("The server IP-address is invalid.", "wirc-gateways") );
			$this->set_message( "Succeed", __("The action was successful.", "wirc-gateways") );
			$this->set_message( "Failed", __("The action was fails.", "wirc-gateways") );
		
		}
		
		
		
		public function init_form_fields(){
			
			$this->form_fields = array(
				'enabled' => array(
					'title' 		=> __( 'Enable/Disable', "wirc-gateways" ),
					'type' 			=> 'checkbox',
					'label' 		=> __( 'Enable ParsPal Gateway.', 'wirc-gateways' ),
					'default'		=>	'no'
				),
				'title' => array(
					'title' 		=> __( 'Title', 'wirc-gateways' ),
					'type' 			=> 'text',
					'description' 	=>  __( 'This will be shown in the order table.', 'wirc-gateways' ),
					'default'		=>	$this->method_title
				),
				'description' => array(
					'title' 		=> __( 'Description', 'wirc-gateways' ),
					'type' 			=> 'textarea',
					'description' 	=> __( 'Description about ParsPal gateway, this will be shown in the order table.', 'wirc-gateways' ),
					'default'		=> $this->method_description 
				),
				'parspal_merchant_id' => array(
					'title' 		=> __( 'Merchant Gateway ID', 'wirc-gateways' ),
					'type' 			=> 'text',
					'default'		=>	''
				),
				'parspal_merchant_pass' => array(
					'title' 		=> __( 'Merchant Gateway Password', 'wirc-gateways' ),
					'type' 			=> 'password',
					'default'		=>	''
				),
			);
		
			
		}
		
		
		
		function process_payment( $order_id ) {
			global $woocommerce;
			$order = new WC_Order( $order_id );
							
			$response = $this->Soap_WSDL->call( "RequestPayment", array(
					"Price" 		=> (int) $order->get_total(),
					"ReturnPath" 	=> $this->get_verify_payment_url( $order_id ),
					"ResNumber" 	=> $order->id,
					"Description" 	=> @$order->customer_note,
					"Paymenter" 	=> @$order->billing_first_name . " " . @$order->billing_last_name ,
					"Email" 		=> @$order->billing_email,
			)); 
			
			
			# SoapClient errors
			if( is_wp_error( $response ) ){
			
				$error_message = $response->get_error_code() . " : " . $response->get_error_message() . ".\n";
				$order->add_order_note( esc_html( $error_message ) );
				
				wc_add_notice( esc_html( $error_message ), "error" );
				return;
			}
			
			$result = $response->RequestPaymentResult;
			$status = ( isset( $result->ResultStatus ) )? $result->ResultStatus : "Failed" ; 
			
			# Success
			if( strtolower( $status ) == "succeed" ){
				
				// Return thankyou redirect
				return array(
					'result' 	=> 'success',
					'redirect'	=> $result->PaymentPath
				); 
				
			# Failure
			}else{
			
				$error_message = $status . " : " . $this->get_message( $status ) . ".\n";
				$order->add_order_note( esc_html( $error_message ) );
				
				wc_add_notice( esc_html( $error_message ), "error" );
				return;
			}
		}
		
		
		
		public function verify_payment(){
			global $woocommerce;
			
			$order_id 	=  isset( $_GET['order_id'] )? (int) $_GET['order_id'] : null;
			$refnumber  = isset( $_POST['refnumber'] )? (int) $_POST['refnumber'] : "" 	;
			$order = new WC_Order( $order_id );
			
			if( !isset( $_POST['status'] ) ){
				return;
				
			}elseif( (int) $_POST['status']  == -99 ){ 
			
				$error_message = __("Cancel the payment action.", "wirc-gateways") . ".\n";
				wc_add_notice( "cancel_payment" . " : " .  esc_html( $error_message ), "notice" );
				
				$order->update_status('Cancelled', esc_html( $error_message ) );
				
				do_action("wirc_parspal_cancel_payment");
				
				wp_redirect( esc_url( $order->get_cancel_order_url() ) );
				exit;
			}
			
			$response = $this->Soap_WSDL->call( "VerifyPayment", array(
				"Price" 		=> (int) $order->get_total(),
				"RefNum" 		=> $refnumber,	
			));
			
			# SoapClient errors
			if( is_wp_error( $response ) ){
				
				$error_message = $response->get_error_code() . " : " . $response->get_error_message() . ".\n";
				wc_add_notice( esc_html( $error_message ), "error" );
				
				$receipt_number_message = sprintf( __("ParsPal Receipt Number: %d", "wirc-gateways" ), $refnumber ) . ".\n";
				$order->update_status('failed',  esc_html( $error_message ) );
				$order->add_order_note( $receipt_number_message , 1 );
				
				wp_redirect( esc_url( $this->get_return_url( $order ) ) );
				exit;
			}
			
			$result = $response->verifyPaymentResult;
			$status = isset( $result->ResultStatus )? $result->ResultStatus : "Failed" ;
			
			# Success
			if( strtolower( $status ) == "success"  ){
			
				$order->add_order_note( sprintf( __("ParsPal Receipt Number: %d", "wirc-gateways" ), $refnumber ), 1 );
				$order->payment_complete();

				// Remove cart
				$woocommerce->cart->empty_cart();
				
				do_action("wirc_parspal_success_payment");
				
				// Send to success page
				wp_redirect( esc_url( $this->get_return_url( $order ) ) );
				exit;
				
			# Failure
			}else{
				// Record the error's
				$error_message = $status . " : " . $this->get_message( $status ) . ".\n";
				wc_add_notice( $error_message, "error" );
				
				$error_message .= sprintf( __("ParsPal Receipt Number: %d", "wirc-gateways" ), $refnumber ). ".\n";
				$order->update_status('failed', $error_message );
				
				do_action("wirc_parspal_failure_payment");
				
				wp_redirect( esc_url( $this->get_return_url( $order ) ) );
				exit;
			}
		
		}
		
		
	}

?>