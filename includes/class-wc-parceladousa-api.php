<?php

if(!defined('ABSPATH')) {
    exit;
}

/**
 * Class WC_Parceladousa_Api
 */
class WC_Parceladousa_Api{

    private $gateway;
    private $payment_order_data;
    private $fail;

    /**
     * WC_Gateway_Parceladousa_Api constructor.
     * @param WC_Gateway_Parceladousa $gateway
     */
    public function __construct($gateway, $environment)
    {
        $this->gateway = $gateway;
        $this->fail = false;

        if($environment == 'sandbox'){
            $this->api_url = 'https://apisandbox.parceladousa.com/v1/paymentapi';
        }
        if($environment == 'production'){
            $this->api_url = 'https://api.parceladousa.com/v1/paymentapi';
        }
    }


    public function do_request_api( $endpoint, $method = 'POST', $data = array(), $headers = array())
    {
        $params = array(
            'method'  => $method,
            'timeout' => 60,
        );

        if($method == 'POST' && !empty($data)) {
            $params['body'] = $data;
        }

        if(!empty($headers)) {
            $params['headers'] = $headers;
        }

        return wp_safe_remote_post($this->api_url.$endpoint, $params);
    }

    public function request_auth_token()
    {
        $data = array (
            'pubKey' => $this->gateway->public_key,
            'merchantCode' => $this->gateway->merchant_code
        );

        return $this->do_request_api('/auth','POST' ,$data);
    }

    public function request_payment_order($order)
    {
        if(!$token = $this->get_auth_token()){
            $this->fail = true;
            return $this;
        }

        /** @var WC_Order $order */

        $document = '';
        $persontype = $order->get_meta('_billing_persontype');
        $phone = (empty($order->get_meta('_billing_cellphone')))?$order->get_billing_phone():'';

        if(!empty($persontype)) {
            $document = ($persontype == '1') ? $order->get_meta('_billing_cpf') : $order->get_meta('_billing_cnpj');
        }

        $data = array(
            'amount' => $order->get_total(),
            "currency" => get_woocommerce_currency(),
            'client' => array(
                'name' => $order->get_formatted_billing_full_name(),
                'email' => $order->get_billing_email(),
                'phone' => $phone,
                'doc' => $document,
                'cep' => $order->get_billing_postcode(),
                'address' => $order->get_billing_address_1(),
                'addressNumber' => '',
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state()
            ),
            'callback' => WC()->api_request_url('WC_Gateway_Parceladousa')
        );
        $this->payment_order_data = $this->do_request_api('/order', 'POST', $data, array ('Authorization' => "Bearer $token") );

        if(400 === wp_remote_retrieve_response_code($this->payment_order_data)){
            $this->fail = true;
            return $this;
        }
        return $this;
    }
    public function consult_payment_order($order_id)
    {
        if(!$token = $this->get_auth_token()){
            $this->fail = true;
            return $this;
        }

        $this->payment_order_data = $this->do_request_api("/order/$order_id", 'GET', null , array ('Authorization' => "Bearer $token") );

        if(400 === wp_remote_retrieve_response_code($this->payment_order_data)){
            $this->fail = true;
            return $this;
        }
        return $this;
    }
    public function get_auth_token()
    {
        $request = $this->request_auth_token();
        $response = $this->get_api_request_body($request);

        if(400 === wp_remote_retrieve_response_code($request)){
            return null;
        }
        return $response->token;
    }
    public function get_api_request_body($request)
    {
        return json_decode(wp_remote_retrieve_body($request));
    }
    public function get_payment_order_data()
    {
        $response = $this->get_api_request_body($this->payment_order_data);
        return $response->data;
    }
    public function get_payment_order_status()
    {
        $response = $this->get_api_request_body($this->payment_order_data);
        return $response->status;
    }
    public function fail(){
        return $this->fail;
    }
}