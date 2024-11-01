<?php

namespace WBCR\Delivery\Advanced;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the matching rules for Shipping methods.
 *
 * @class      MatchConditions
 * @version    1.0.0
 */
class MatchConditions {


	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		/*
		add_filter( 'wdad/match_condition/subtotal', array( $this, 'condition_subtotal' ), 10, 4 );
		add_filter( 'wdad/match_condition/subtotal_ex_tax', array(
			$this,
			'condition_subtotal_ex_tax'
		), 10, 4 );
		add_filter( 'wdad/match_condition/tax', array( $this, 'condition_tax' ), 10, 4 );
		add_filter( 'wdad/match_condition/quantity', array( $this, 'condition_quantity' ), 10, 4 );
		add_filter( 'wdad/match_condition/contains_product', array(
			$this,
			'condition_contains_product'
		), 10, 4 );
		add_filter( 'wdad/match_condition/coupon', array( $this, 'condition_coupon' ), 10, 4 );
		add_filter( 'wdad/match_condition/weight', array( $this, 'condition_weight' ), 10, 4 );
		add_filter( 'wdad/match_condition/contains_shipping_class', array(
			$this,
			'condition_contains_shipping_class'
		), 10, 4 );

		add_filter( 'wdad/match_condition/zipcode', array( $this, 'condition_zipcode' ), 10, 4 );
		add_filter( 'wdad/match_condition/city', array( $this, 'condition_city' ), 10, 4 );
		add_filter( 'wdad/match_condition/state', array( $this, 'condition_state' ), 10, 4 );
		add_filter( 'wdad/match_condition/country', array( $this, 'condition_country' ), 10, 4 );
		add_filter( 'wdad/match_condition/role', array( $this, 'condition_role' ), 10, 4 );

		add_filter( 'wdad/match_condition/width', array( $this, 'condition_width' ), 10, 4 );
		add_filter( 'wdad/match_condition/height', array( $this, 'condition_height' ), 10, 4 );
		add_filter( 'wdad/match_condition/length', array( $this, 'condition_length' ), 10, 4 );
		add_filter( 'wdad/match_condition/stock', array( $this, 'condition_stock' ), 10, 4 );
		add_filter( 'wdad/match_condition/stock_status', array( $this, 'condition_stock_status' ), 10, 4 );
		add_filter( 'wdad/match_condition/category', array( $this, 'condition_category' ), 10, 4 );
		*/
	}

	/**
	 * @param $match
	 * @param $pattern
	 * @param $operator
	 * @param $value
	 *
	 * @return bool|mixed
	 */
	public function match( $match, $pattern, $operator, $value ) {
		switch ( $operator ) {
			case '==':
				$match = ( $pattern == $value );
				break;
			case '!=':
				$match = ( $pattern != $value );
				break;
			case '=*':
				$match = ( $pattern >= $value );
				break;
			case '*=':
				$match = ( $pattern <= $value );
				break;
		}

		return $match;
	}

	/**
	 * Check if conditions match, if all conditions in one condition group
	 * matches it will return TRUE and the shipping method will display.
	 *
	 * @param array $condition_groups List of condition groups containing their conditions.
	 * @param array $package List of shipping package data.
	 *
	 * @return bool TRUE if all the conditions in one of the condition groups matches true.
	 * @since 1.0.0
	 *
	 */
	public function match_conditions( $condition_groups = [], $package = [] ) {
		if ( empty( $condition_groups ) ) {
			return false;
		}

		$match = false;
		foreach ( $condition_groups as $condition_group => $conditions ) {
			$match_condition_group = true;
			foreach ( $conditions as $condition ) {
				$func = 'condition_' . $condition['condition'];
				if ( method_exists( $this, $func ) ) {
					$match = $this->$func( false, $condition['operator'], $condition['value'], $package );
				}
				//$match = apply_filters( 'wdad/match_condition/' . $condition['condition'], false, $condition['operator'], $condition['value'], $package );
				if ( false == $match ) {
					$match_condition_group = false;
				}
			}

			// return true if one condition group matches
			if ( true == $match_condition_group ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Subtotal.
	 *
	 * Match the condition value against the cart subtotal.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_subtotal( $match, $operator, $value, $package ) {
		if ( ! isset( WC()->cart ) ) {
			return $match;
		}

		// Make sure its formatted correct
		$value = str_replace( ',', '.', $value );

		// WPML multi-currency support
		$value = apply_filters( 'wcml_shipping_price_amount', $value );

		$subtotal = WC()->cart->subtotal;

		return $this->match( $match, $subtotal, $operator, $value );
	}


	/**
	 * Subtotal excl. taxes.
	 *
	 * Match the condition value against the cart subtotal excl. taxes.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_subtotal_ex_tax( $match, $operator, $value, $package ) {
		if ( ! isset( WC()->cart ) ) {
			return $match;
		}

		// Make sure its formatted correct
		$value = str_replace( ',', '.', $value );

		// WPML multi-currency support
		$value = apply_filters( 'wcml_shipping_price_amount', $value );

		$subtotal = WC()->cart->subtotal_ex_tax;

		return $this->match( $match, $subtotal, $operator, $value );
	}


	/**
	 * Taxes.
	 *
	 * Match the condition value against the cart taxes.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_tax( $match, $operator, $value, $package ) {
		if ( ! isset( WC()->cart ) ) {
			return $match;
		}

		$taxes = array_sum( (array) WC()->cart->taxes );

		// WPML multi-currency support
		$value = apply_filters( 'wcml_shipping_price_amount', $value );

		return $this->match( $match, $taxes, $operator, $value );
	}


	/**
	 * Quantity.
	 *
	 * Match the condition value against the cart quantity.
	 * This also includes product quantities.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_quantity( $match, $operator, $value, $package ) {
		if ( ! isset( WC()->cart ) ) {
			return $match;
		}

		$quantity = 0;
		foreach ( $package['contents'] as $item_key => $item ) {
			$quantity += $item['quantity'];
		}

		return $this->match( $match, $quantity, $operator, $value );
	}


	/**
	 * Contains product.
	 *
	 * Matches if the condition value product is in the cart.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_contains_product( $match, $operator, $value, $package ) {
		$product_ids = array();
		foreach ( $package['contents'] as $product ) {
			$product_ids[] = $product['product_id'];
			if ( isset( $product['variation_id'] ) ) {
				$product_ids[] = $product['variation_id'];
			}
		}

		if ( '==' == $operator ) {
			$match = ( in_array( $value, $product_ids ) );
		} elseif ( '!=' == $operator ) {
			$match = ( ! in_array( $value, $product_ids ) );
		}

		return $match;
	}


	/**
	 * Coupon.
	 *
	 * Match the condition value against the applied coupons.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 *
	 * @since 1.0.0
	 */
	public function condition_coupon( $match, $operator, $value, $package ) {
		if ( ! isset( WC()->cart ) ) {
			return $match;
		}

		$coupons = array( 'percent' => array(), 'fixed' => array() );
		foreach ( WC()->cart->get_coupons() as $coupon ) {
			/** @var $coupon \WC_Coupon */
			$type               = str_replace( '_product', '', $coupon->get_discount_type() );
			$type               = str_replace( '_cart', '', $type );
			$coupons[ $type ][] = $coupon->get_amount();
		}

		if ( strpos( $value, '%' ) !== false ) {
			$percentage_value = str_replace( '%', '', $value );
			if ( '==' == $operator ) {
				$match = in_array( $percentage_value, $coupons['percent'] );
			} elseif ( '!=' == $operator ) {
				$match = ! in_array( $percentage_value, $coupons['percent'] );
			} elseif ( '=*' == $operator ) {
				$match = empty( $coupons['percent'] ) ? $match : ( min( $coupons['percent'] ) >= $percentage_value );
			} elseif ( '*=' == $operator ) {
				$match = ! is_array( $coupons['percent'] ) ? false : ( max( $coupons['percent'] ) <= $percentage_value );
			}
		} elseif ( strpos( $value, '$' ) !== false ) {
			$amount_value = str_replace( '$', '', $value );
			if ( '==' == $operator ) {
				$match = in_array( $amount_value, $coupons['fixed'] );
			} elseif ( '!=' == $operator ) {
				$match = ! in_array( $amount_value, $coupons['fixed'] );
			} elseif ( '=*' == $operator ) {
				$match = empty( $coupons['fixed'] ) ? $match : ( min( $coupons['fixed'] ) >= $amount_value );
			} elseif ( '*=' == $operator ) {
				$match = ! is_array( $coupons['fixed'] ) ? $match : ( max( $coupons['fixed'] ) <= $amount_value );
			}
		} else {
			if ( '==' == $operator ) {
				$match = ( in_array( $value, WC()->cart->get_applied_coupons() ) );
			} elseif ( '!=' == $operator ) {
				$match = ( ! in_array( $value, WC()->cart->get_applied_coupons() ) );
			}

		}

		return $match;
	}


	/**
	 * Weight.
	 *
	 * Match the condition value against the cart weight.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_weight( $match, $operator, $value, $package ) {
		$weight = 0;
		foreach ( $package['contents'] as $key => $item ) {
			/** @var $product \WC_Product */
			$product = $item['data'];
			$weight  += ( (float) $product->get_weight() * (int) $item['quantity'] );
		}

		$value = (string) $value;

		// Make sure its formatted correct
		$value = str_replace( ',', '.', $value );

		return $this->match( $match, $weight, $operator, $value );
	}


	/**
	 * Shipping class.
	 *
	 * Matches if the condition value shipping class is in the cart.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.1
	 *
	 */
	public function condition_contains_shipping_class( $match, $operator, $value, $package ) {
		// True until proven false
		if ( $operator == '!=' ) {
			$match = true;
		}

		foreach ( $package['contents'] as $key => $product ) {
			$id      = ! empty( $product['variation_id'] ) ? $product['variation_id'] : $product['product_id'];
			$product = wc_get_product( $id );

			if ( $operator == '==' ) {
				if ( $product->get_shipping_class() == $value ) {
					return true;
				}
			} elseif ( $operator == '!=' ) {
				if ( $product->get_shipping_class() == $value ) {
					return false;
				}
			}

		}

		return $match;
	}


	/******************************************************
	 * User conditions
	 *****************************************************/


	/**
	 * Zipcode.
	 *
	 * Match the condition value against the users shipping zipcode.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 *
	 * @since 1.0.0
	 */
	public function condition_zipcode( $match, $operator, $value, $package ) {

		$user_zipcode = $package['destination']['postcode'];
		// Prepare allowed values.
		$zipcodes = (array) preg_split( '/,+ */', $value );

		// Remove all non- letters and numbers
		foreach ( $zipcodes as $key => $zipcode ) {
			$zipcodes[ $key ] = preg_replace( '/[^0-9a-zA-Z\-\*]/', '', $zipcode );
		}
		if ( '==' == $operator ) {
			foreach ( $zipcodes as $zipcode ) {
				if ( strpos( $zipcode, '*' ) !== false ) {
					$user_zipcode = preg_replace( '/[^0-9a-zA-Z]/', '', $user_zipcode );
					$zipcode      = str_replace( '*', '', $zipcode );

					if ( empty( $zipcode ) ) {
						continue;
					}

					$parts = explode( '-', $zipcode );
					if ( count( $parts ) > 1 ) {
						$match = ( $user_zipcode >= min( $parts ) && $user_zipcode <= max( $parts ) );
					} else {
						$match = preg_match( '/^' . preg_quote( $zipcode, '/' ) . '/i', $user_zipcode );
					}
				} else {
					// BC when not using asterisk (wildcard)
					$match = ( (double) $user_zipcode == (double) $zipcode );
				}

				if ( $match == true ) {
					return true;
				}
			}
		} elseif ( '!=' == $operator ) {
			$match = true;
			foreach ( $zipcodes as $zipcode ) {
				if ( strpos( $zipcode, '*' ) !== false ) {
					$user_zipcode = preg_replace( '/[^0-9a-zA-Z]/', '', $user_zipcode );
					$zipcode      = str_replace( '*', '', $zipcode );

					if ( empty( $zipcode ) ) {
						continue;
					}

					$parts = explode( '-', $zipcode );
					if ( count( $parts ) > 1 ) {
						$zipcode_match = ( $user_zipcode >= min( $parts ) && $user_zipcode <= max( $parts ) );
					} else {
						$zipcode_match = preg_match( '/^' . preg_quote( $zipcode, '/' ) . '/i', $user_zipcode );
					}

					if ( $zipcode_match == true ) {
						return $match = false;
					}
				} else {
					// BC when not using asterisk (wildcard)
					$zipcode_match = ( (double) $user_zipcode == (double) $zipcode );

					if ( $zipcode_match == true ) {
						return $match = false;
					}
				}
			}

		} elseif ( '=*' == $operator ) {
			$match = ( (double) $user_zipcode >= (double) $value );
		} elseif ( '*=' == $operator ) {
			$match = ( (double) $user_zipcode <= (double) $value );
		}

		return $match;
	}


	/**
	 * City.
	 *
	 * Match the condition value against the users shipping city.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_city( $match, $operator, $value, $package ) {
		if ( ! isset( WC()->customer ) ) {
			return $match;
		}

		$customer_city = strtolower( WC()->customer->get_shipping_city() );
		$value         = strtolower( $value );

		if ( '==' == $operator ) {
			if ( preg_match( '/\, ?/', $value ) ) {
				$match = ( in_array( $customer_city, preg_split( '/\, ?/', $value ) ) );
			} else {
				$match = ( $value == $customer_city );
			}
		} elseif ( '!=' == $operator ) {
			if ( preg_match( '/\, ?/', $value ) ) {
				$match = ( ! in_array( $customer_city, preg_split( '/\, ?/', $value ) ) );
			} else {
				$match = ! ( $value == $customer_city );
			}
		}

		return $match;
	}


	/**
	 * State.
	 *
	 * Match the condition value against the users shipping state
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_state( $match, $operator, $value, $package ) {
		if ( ! isset( WC()->customer ) ) {
			return $match;
		}

		$state = WC()->customer->get_shipping_country() . '_' . WC()->customer->get_shipping_state();

		if ( '==' == $operator ) {
			$match = ( $state == $value );
		} elseif ( '!=' == $operator ) {
			$match = ( $state != $value );
		}

		return $match;
	}


	/**
	 * Country.
	 *
	 * Match the condition value against the users shipping country.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_country( $match, $operator, $value, $package ) {
		if ( ! isset( WC()->customer ) ) {
			return $match;
		}

		$user_country = WC()->customer->get_shipping_country();

		if ( method_exists( WC()->countries, 'get_continent_code_for_country' ) ) {
			$user_continent = WC()->countries->get_continent_code_for_country( $user_country );
		}

		if ( '==' == $operator ) {
			$match = stripos( $user_country, $value ) === 0;

			// Check for continents if available
			if ( ! $match && isset( $user_continent ) && strpos( $value, 'CO_' ) === 0 ) {
				$match = stripos( $user_continent, str_replace( 'CO_', '', $value ) ) === 0;
			}
		} elseif ( '!=' == $operator ) {
			$match = stripos( $user_country, $value ) === false;

			// Check for continents if available
			if ( $match && isset( $user_continent ) && strpos( $value, 'CO_' ) === 0 ) {
				$match = stripos( $user_continent, str_replace( 'CO_', '', $value ) ) === false;
			}
		}

		return $match;
	}


	/**
	 * User role.
	 *
	 * Match the condition value against the users role.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_role( $match, $operator, $value, $package ) {
		global $current_user;

		if ( '==' == $operator ) {
			$match = ( array_key_exists( $value, $current_user->caps ) );
		} elseif ( '!=' == $operator ) {
			$match = ( ! array_key_exists( $value, $current_user->caps ) );
		}

		return $match;
	}


	/******************************************************
	 * Product conditions
	 *****************************************************/


	/**
	 * Width.
	 *
	 * Match the condition value against the widest product in the cart.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_width( $match, $operator, $value, $package ) {
		if ( ! isset( WC()->cart ) ) {
			return $match;
		}

		$product_widths = array();
		foreach ( WC()->cart->get_cart() as $item ) {
			/** @var $product \WC_Product */
			$product          = $item['data'];
			$product_widths[] = $product->get_width();
		}

		$max_width = max( $product_widths );

		return $this->match( $match, $max_width, $operator, $value );
	}


	/**
	 * Height.
	 *
	 * Match the condition value against the highest product in the cart.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_height( $match, $operator, $value, $package ) {
		if ( ! isset( WC()->cart ) ) {
			return $match;
		}

		$product_heights = array();
		foreach ( WC()->cart->get_cart() as $item ) {
			/** @var $product \WC_Product */
			$product           = $item['data'];
			$product_heights[] = $product->get_height();
		}

		$max_height = max( $product_heights );

		return $this->match( $match, $max_height, $operator, $value );
	}


	/**
	 * Length.
	 *
	 * Match the condition value against the lenghtiest product in the cart.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_length( $match, $operator, $value, $package ) {
		if ( ! isset( WC()->cart ) ) {
			return $match;
		}

		$product_lengths = array();
		foreach ( WC()->cart->get_cart() as $item ) {
			/** @var $product \WC_Product */
			$product           = $item['data'];
			$product_lengths[] = $product->get_length();
		}

		$max_length = max( $product_lengths );

		return $this->match( $match, $max_length, $operator, $value );
	}


	/**
	 * Product stock.
	 *
	 * Match the condition value against all cart products stock.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_stock( $match, $operator, $value, $package ) {
		if ( ! isset( WC()->cart ) ) {
			return $match;
		}

		$product_stocks = array();
		foreach ( WC()->cart->get_cart() as $item ) {
			/** @var $product \WC_Product */
			$product          = $item['data'];
			$product_stocks[] = $product->get_stock_quantity();
		}

		// Get lowest value
		$min_stock = min( $product_stocks );

		return $this->match( $match, $min_stock, $operator, $value );
	}


	/**
	 * Stock status.
	 *
	 * Match the condition value against all cart products stock statuses.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_stock_status( $match, $operator, $value, $package ) {
		if ( '==' == $operator ) {
			$match = true;
			foreach ( $package['contents'] as $item ) {
				/** @var $product \WC_Product */
				$product = $item['data'];

				if ( method_exists( $product, 'get_stock_status' ) ) { // WC 2.7 compatibility
					$stock_status = $product->get_stock_status();
				} else {
					$id           = $product->variation_has_stock ? $product->variation_id : $item['product_id'];
					$stock_status = ( get_post_meta( $id, '_stock_status', true ) );
				}

				if ( $stock_status != $value ) {
					$match = false;
				}

			}
		} elseif ( '!=' == $operator ) {
			$match = true;
			foreach ( $package['contents'] as $item ) {
				/** @var $product \WC_Product */
				$product = $item['data'];

				if ( method_exists( $product, 'get_stock_status' ) ) { // WC 2.7 compatibility
					$stock_status = $product->get_stock_status();
				} else {
					$id           = $product->variation_has_stock ? $product->variation_id : $item['product_id'];
					$stock_status = ( get_post_meta( $id, '_stock_status', true ) );
				}

				if ( $stock_status != $value ) {
					$match = false;
				}
			}
		}

		return $match;
	}


	/**
	 * Category.
	 *
	 * Match the condition value against all the cart products category.
	 * With this condition, all the products in the cart must have the given class.
	 *
	 * @param bool $match Current match value.
	 * @param string $operator Operator selected by the user in the condition row.
	 * @param mixed $value Value given by the user in the condition row.
	 * @param array $package List of shipping package details.
	 *
	 * @return bool Matching result, TRUE if results match, otherwise FALSE.
	 * @since 1.0.0
	 *
	 */
	public function condition_category( $match, $operator, $value, $package ) {
		if ( ! isset( WC()->cart ) ) {
			return $match;
		}

		$match = true;
		if ( '==' == $operator ) {
			foreach ( WC()->cart->get_cart() as $product ) {
				if ( ! has_term( $value, 'product_cat', $product['product_id'] ) ) {
					$match = false;
				}
			}
		} elseif ( '!=' == $operator ) {
			foreach ( WC()->cart->get_cart() as $product ) {
				if ( has_term( $value, 'product_cat', $product['product_id'] ) ) {
					$match = false;
				}
			}
		}

		return $match;
	}

}
