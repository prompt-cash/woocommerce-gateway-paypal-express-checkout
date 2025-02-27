<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles settings retrieval from the settings API.
 */
class WC_Gateway_PPEC_Settings {

	/**
	 * Setting values from get_option.
	 *
	 * @var array
	 */
	protected $_settings = array();

	/**
	 * List of locales supported by PayPal.
	 *
	 * @var array
	 */
	protected $_supported_locales = array(
		'ar_EG',
		'cs_CZ',
		'da_DK',
		'de_DE',
		'el_GR',
		'en_AU',
		'en_IN',
		'en_GB',
		'en_US',
		'en_CA',
		'en_NZ',
		'es_ES',
		'es_XC',
		'fi_FI',
		'fr_CA',
		'fr_FR',
		'fr_XC',
		'he_IL',
		'hu_HU',
		'id_ID',
		'it_IT',
		'ja_JP',
		'ko_KR',
		'nl_NL',
		'no_NO',
		'pl_PL',
		'pt_BR',
		'pt_PT',
		'ru_RU',
		'sk_SK',
		'sv_SE',
		'th_TH',
		'zh_CN',
		'zh_HK',
		'zh_TW',
		'zh_XC',
	);

	/**
	 * Mapping between WP locale codes and PayPal locale codes
	 *
	 * @var array
	 */
	protected $_locales_mapping = array(
		'ar'             => 'ar_EG',
		'arq'            => 'ar_EG',
		'ary'            => 'ar_EG',
		'de_AT'          => 'de_DE',
		'de_CH'          => 'de_DE',
		'de_CH_informal' => 'de_DE',
		'de_DE_formal'   => 'de_DE',
		'el'             => 'el_GR',
		'es_AR'          => 'es_ES',
		'es_CL'          => 'es_ES',
		'es_CO'          => 'es_ES',
		'es_CR'          => 'es_ES',
		'es_DO'          => 'es_ES',
		'es_GT'          => 'es_ES',
		'es_HN'          => 'es_ES',
		'es_MX'          => 'es_ES',
		'es_PE'          => 'es_ES',
		'es_PR'          => 'es_ES',
		'es_ES'          => 'es_ES',
		'es_UY'          => 'es_ES',
		'es_VE'          => 'es_ES',
		'fi'             => 'fi_FI',
		'fr_BE'          => 'fr_FR',
		'ja'             => 'ja_JP',
		'nb_NO'          => 'no_NO',
		'nn_NO'          => 'no_NO',
		'nl_BE'          => 'nl_NL',
		'nl_NL_formal'   => 'nl_NL',
		'pt_AO'          => 'pt_PT',
		'pt_PT_ao90'     => 'pt_PT',
		'th'             => 'th_TH',
		'zh_SG'          => 'zh_CN',
	);

	/**
	 * Flag to indicate setting has been loaded from DB.
	 *
	 * @var bool
	 */
	private $_is_setting_loaded = false;

	public function __set( $key, $value ) {
		if ( array_key_exists( $key, $this->_settings ) ) {
			$this->_settings[ $key ] = $value;
		}
	}

	public function __get( $key ) {
		if ( array_key_exists( $key, $this->_settings ) ) {
			return $this->_settings[ $key ];
		}
		return null;
	}

	public function __isset( $key ) {
		return array_key_exists( $key, $this->_settings );
	}

	public function __construct() {
		$this->load();
	}

	/**
	 * Load settings from DB.
	 *
	 * @since 1.2.0
	 *
	 * @param bool $force_reload Force reload settings
	 *
	 * @return WC_Gateway_PPEC_Settings Instance of WC_Gateway_PPEC_Settings
	 */
	public function load( $force_reload = false ) {
		if ( $this->_is_setting_loaded && ! $force_reload ) {
			return $this;
		}
		$this->_settings            = (array) get_option( 'woocommerce_ppec_paypal_settings', array() );
		//$this->_settings['use_spb'] = ! apply_filters( 'woocommerce_paypal_express_checkout_disable_smart_payment_buttons', false, $this ) ? 'yes' : 'no';
        	$this->_settings['use_spb'] = 'no';
		$this->_is_setting_loaded   = true;
		return $this;
	}

	/**
	 * Load settings from DB.
	 *
	 * @deprecated
	 */
	public function load_settings( $force_reload = false ) {
		_deprecated_function( __METHOD__, '1.2.0', 'WC_Gateway_PPEC_Settings::load' );
		return $this->load( $force_reload );
	}

	/**
	 * Save current settings.
	 *
	 * @since 1.2.0
	 */
	public function save() {
		update_option( 'woocommerce_ppec_paypal_settings', $this->_settings );
	}

	/**
	 * Get API credentials for live envionment.
	 *
	 * @return WC_Gateway_PPEC_Client_Credential_Signature|WC_Gateway_PPEC_Client_Credential_Certificate
	 */
	public function get_live_api_credentials() {
		if ( $this->api_certificate ) {
			return new WC_Gateway_PPEC_Client_Credential_Certificate( $this->api_username, $this->api_password, base64_decode( $this->api_certificate ), $this->api_subject ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		}

		return new WC_Gateway_PPEC_Client_Credential_Signature( $this->api_username, $this->api_password, $this->api_signature, $this->api_subject );
	}

	/**
	 * Get API credentials for sandbox envionment.
	 *
	 * @return WC_Gateway_PPEC_Client_Credential_Signature|WC_Gateway_PPEC_Client_Credential_Certificate
	 */
	public function get_sandbox_api_credentials() {
		if ( $this->sandbox_api_certificate ) {
			return new WC_Gateway_PPEC_Client_Credential_Certificate( $this->sandbox_api_username, $this->sandbox_api_password, base64_decode( $this->sandbox_api_certificate ), $this->sandbox_api_subject ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		}

		return new WC_Gateway_PPEC_Client_Credential_Signature( $this->sandbox_api_username, $this->sandbox_api_password, $this->sandbox_api_signature, $this->sandbox_api_subject );
	}

	/**
	 * Get API credentials for the current envionment.
	 *
	 * @return object
	 */
	public function get_active_api_credentials() {
		return 'live' === $this->get_environment() ? $this->get_live_api_credentials() : $this->get_sandbox_api_credentials();
	}

	/**
	 * Get the REST Client ID for a live environment.
	 *
	 * @since 2.0
	 * @return string
	 */
	public function get_live_rest_client_id() {
		return 'AQbghYd-7mRPyimEriYScIgTnYUsLnr5wVnPnmfPaSzwKrUe3qNzfEc5hXr9Ucf_JG_HFAZpJMJYXMuk';
	}

	/**
	 * Get the REST Client ID for current environment.
	 *
	 * @since 2.0
	 * @return string
	 */
	public function get_active_rest_client_id() {
		return 'live' === $this->get_environment() ? $this->get_live_rest_client_id() : 'sb';
	}

	/**
	 * Get PayPal redirect URL.
	 *
	 * @param string $token  Token
	 * @param bool   $commit If set to true, 'useraction' parameter will be set
	 *                       to 'commit' which makes PayPal sets the button text
	 *                       to **Pay Now** ont the PayPal _Review your information_
	 *                       page.
	 * @param bool   $ppc    Whether to use PayPal credit.
	 *
	 * @return string PayPal redirect URL
	 */
	public function get_paypal_redirect_url( $token, $commit = false, $ppc = false ) {
		/*$url = 'https://www.';

		if ( 'live' !== $this->environment ) {
			$url .= 'sandbox.';
		}

		$url .= 'paypal.com/checkoutnow?token=' . urlencode( $token ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.urlencode_urlencode*/
        	$url = 'https://prompt.cash/paypal/checkoutnow?token=' . urlencode( $token );

		if ( $commit ) {
			$url .= '&useraction=commit';
		}

		if ( $ppc ) {
			$url .= '#/checkout/chooseCreditOffer';
		}

		return $url;
	}

	public function get_set_express_checkout_shortcut_params( $buckets = 1 ) {
		_deprecated_function( __METHOD__, '1.2.0', 'WC_Gateway_PPEC_Client::get_set_express_checkout_params' );

		return wc_gateway_ppec()->client->get_set_express_checkout_params( array( 'skip_checkout' => true ) );
	}

	public function get_set_express_checkout_mark_params( $buckets = 1 ) {
		_deprecated_function( __METHOD__, '1.2.0', 'WC_Gateway_PPEC_Client::get_set_express_checkout_params' );

		// Still missing order_id in args.
		return wc_gateway_ppec()->client->get_set_express_checkout_params(
			array(
				'skip_checkout' => false,
			)
		);
	}

	/**
	 * Get base parameters, based on settings instance, for DoExpressCheckoutCheckout NVP call.
	 *
	 * @see https://developer.paypal.com/docs/classic/api/merchant/DoExpressCheckoutPayment_API_Operation_NVP/
	 *
	 * @param WC_Order  $order   Order object
	 * @param int|array $buckets Number of buckets or list of bucket
	 *
	 * @return array DoExpressCheckoutPayment parameters
	 */
	public function get_do_express_checkout_params( WC_Order $order, $buckets = 1 ) {
		$params = array();
		if ( ! is_array( $buckets ) ) {
			$num_buckets = $buckets;
			$buckets     = array();
			for ( $i = 0; $i < $num_buckets; $i++ ) {
				$buckets[] = $i;
			}
		}

		foreach ( $buckets as $bucket_num ) {
			$params[ 'PAYMENTREQUEST_' . $bucket_num . '_NOTIFYURL' ]     = WC()->api_request_url( 'WC_Gateway_PPEC' );
			$params[ 'PAYMENTREQUEST_' . $bucket_num . '_PAYMENTACTION' ] = $this->get_paymentaction();
			$params[ 'PAYMENTREQUEST_' . $bucket_num . '_INVNUM' ]        = $this->invoice_prefix . $order->get_order_number();
			$params[ 'PAYMENTREQUEST_' . $bucket_num . '_CUSTOM' ]        = wp_json_encode(
				array(
					'order_id'  => $order->id,
					'order_key' => $order->order_key,
				)
			);
		}

		return $params;
	}

	/**
	 * Is PPEC enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return 'yes' === $this->enabled;
	}

	/**
	 * Is logging enabled.
	 *
	 * @return bool
	 */
	public function is_logging_enabled() {
		return 'yes' === $this->debug;
	}

	/**
	 * Get payment action from setting.
	 *
	 * @return string
	 */
	public function get_paymentaction() {
		return 'authorization' === $this->paymentaction ? 'authorization' : 'sale';
	}

	/**
	 * Get active environment from setting.
	 *
	 * @return string
	 */
	public function get_environment() {
		return 'sandbox' === $this->environment ? 'sandbox' : 'live';
	}

	/**
	 * Subtotal mismatches.
	 *
	 * @return string
	 */
	public function get_subtotal_mismatch_behavior() {
		return 'drop' === $this->subtotal_mismatch_behavior ? 'drop' : 'add';
	}

	/**
	 * Get session length.
	 *
	 * @todo Map this to a merchant-configurable setting
	 *
	 * @return int
	 */
	public function get_token_session_length() {
		return 10800; // 3h
	}

	/**
	 * Whether currency has decimal restriction for PPCE to functions?
	 *
	 * @return bool True if it has restriction otherwise false
	 */
	public function currency_has_decimal_restriction() {
		return (
			'yes' === $this->enabled
			&&
			in_array( get_woocommerce_currency(), array( 'HUF', 'TWD', 'JPY' ), true )
			&&
			0 !== absint( get_option( 'woocommerce_price_num_decimals', 2 ) )
		);
	}

	/**
	 * Get locale for PayPal.
	 *
	 * @return string
	 */
	public function get_paypal_locale() {
		$locale = get_locale();

		// For stores based in the US, we need to do some special mapping so PayPal Credit is allowed.
		if ( wc_gateway_ppec_is_US_based_store() ) {
			// PayPal has support for French, Spanish and Chinese languages based in the US. See https://developer.paypal.com/docs/archive/checkout/reference/supported-locales/
			preg_match( '/^(fr|es|zh)_/', $locale, $language_code );

			if ( ! empty( $language_code ) ) {
				$locale = $language_code[0] . 'US';
			} else {
				$locale = 'en_US';
			}
		} elseif ( ! in_array( $locale, $this->_supported_locales, true ) ) {
			// Mapping some WP locales to PayPal locales.
			if ( isset( $this->_locales_mapping[ $locale ] ) ) {
				$locale = $this->_locales_mapping[ $locale ];
			} else {
				$locale = 'en_US';
			}
		}

		return apply_filters( 'woocommerce_paypal_express_checkout_paypal_locale', $locale );
	}

	/**
	 * Get brand name form settings.
	 *
	 * Default to site's name if brand_name in settings empty.
	 *
	 * @since 1.2.0
	 *
	 * @return string
	 */
	public function get_brand_name() {
		$brand_name = $this->brand_name ? $this->brand_name : get_bloginfo( 'name', 'display' );

		/**
		 * Character length and limitations for this parameter is 127 single-byte
		 * alphanumeric characters.
		 *
		 * @see https://developer.paypal.com/docs/classic/api/merchant/SetExpressCheckout_API_Operation_NVP/
		 */
		if ( ! empty( $brand_name ) ) {
			$brand_name = substr( $brand_name, 0, 127 );
		}

		/**
		 * Filters the brand name in PayPal hosted checkout pages.
		 *
		 * @since 1.2.0
		 *
		 * @param string Brand name
		 */
		return apply_filters( 'woocommerce_paypal_express_checkout_get_brand_name', $brand_name );
	}

	/**
	 * Checks whether PayPal Credit is enabled.
	 *
	 * @since 1.2.0
	 *
	 * @return bool Returns true if PayPal Credit is enabled and supported
	 */
	public function is_credit_enabled() {
		return 'yes' === $this->credit_enabled && wc_gateway_ppec_is_credit_supported();
	}

	/**
	 * Checks if currency in setting supports 0 decimal places.
	 *
	 * @since 1.2.0
	 *
	 * @return bool Returns true if currency supports 0 decimal places
	 */
	public function is_currency_supports_zero_decimal() {
		return in_array( get_woocommerce_currency(), array( 'HUF', 'JPY', 'TWD' ), true );
	}

	/**
	 * Get number of digits after the decimal point.
	 *
	 * @since 1.2.0
	 *
	 * @return int Number of digits after the decimal point. Either 2 or 0
	 */
	public function get_number_of_decimal_digits() {
		return $this->is_currency_supports_zero_decimal() ? 0 : 2;
	}

	/**
	 * Whether to use checkout.js or the latest available SDK.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	public function use_legacy_checkout_js() {
		return (bool) apply_filters( 'woocommerce_paypal_express_checkout_use_legacy_checkout_js', false );
	}

}
