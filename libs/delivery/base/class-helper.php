<?php

namespace WBCR\Delivery\Base;

use DVDoug\BoxPacker\ItemTooLargeException;
use DVDoug\BoxPacker\Packer;
use Exception;
use WC_Log_Levels;
use WC_Product;

/**
 * Class Helper
 *
 * @package WBCR\Delivery\Base
 *
 * @author  Alexander Gorenkov <g.a.androidjc2@ya.ru> <Tg:@alex_brin>
 * @version 1.0.0
 * @since   1.0.0
 */
class Helper {
	public static $log;
	private static $precisionSetting = null;

	/**
	 * @type Delivery
	 */
	const DELIVERY_OBJECT = Delivery::class;

	public static function log( $message, $context = [], $level = WC_Log_Levels::INFO ) {
		wc_get_logger()->log( $level, sprintf( "%s %s\n%s", static::getPrefix(), $message, json_encode( $context ) ) );
	}

	protected static function getPrefix() {
		return "[WBCR Delivery]";
	}

	public static function getOrderSizes() {
		$orderWeight           = WC()->cart->get_cart_contents_weight();
		$orderLength           = 0;
		$orderWidth            = 0;
		$orderHeight           = 0;
		$orderCustomSize       = false;
		$calculateCustomWeight = false;

		/** @var Delivery $delivery_object */
		$delivery_object = static::DELIVERY_OBJECT;

		$settings = $delivery_object::settings();

		if ( ! empty( $settings['packages'] ) ) {

			$packer = new Packer();

			foreach ( $settings['packages'] as $packageKey => $package ) {
				$packageBox = new PackageBox( '#' . $packageKey, $package['width']['outer'], $package['length']['outer'], $package['height']['outer'], $package['weight']['package'], $package['width']['inner'], $package['length']['inner'], $package['height']['inner'], $package['weight']['max'] );
				$packer->addBox( $packageBox );
			}

			foreach ( WC()->cart->get_cart() as $item ) {
				if ( $item['data'] instanceof WC_Product ) {
					for ( $quantity = 0; $quantity < $item['quantity']; $quantity ++ ) {
						$packageItem = new PackageItem( $item['data']->get_title(), $item['data']->get_width( 'package' ) ?: (float) $settings['default_width'], $item['data']->get_length( 'package' ) ?: (float) $settings['default_length'], $item['data']->get_height( 'package' ) ?: (float) $settings['default_height'], $item['data']->get_weight( 'package' ) ?: (float) $settings['default_weight'], false );
						$packer->addItem( $packageItem );
					}
				}
			}

			try {
				$packer = $packer->pack();

				if ( $packer->count() == 1 ) {
					$orderWeight = $packer->getMeanWeight();
					$packageBox  = $packer->current()->getBox();

					if ( $packageBox instanceof PackageBox ) {
						$orderLength = $packageBox->getOuterLength();
						$orderWidth  = $packageBox->getOuterWidth();
						$orderHeight = $packageBox->getOuterDepth();
					} else {
						$orderCustomSize = true;
					}
				} else {
					$orderCustomSize = true;
				}
			} catch ( ItemTooLargeException $exception ) {
				$orderCustomSize = true;
			} catch ( Exception $exception ) {
				self::log( $exception->getMessage(), [ 'data' => $_POST, 'settings' => $settings['packages'] ] );
				$orderCustomSize = true;
			}
		} else {
			$orderCustomSize = true;
		}

		if ( $orderCustomSize ) {
			$dimensions      = [ 'width' => 0, 'height' => 0, 'length' => 0 ];
			$totalDimensions = [ 'width' => 0, 'height' => 0, 'length' => 0 ];

			if ( $orderWeight == 0 ) {
				$calculateCustomWeight = true;
			}

			foreach ( WC()->cart->get_cart() as $item ) {
				if ( $item['data'] instanceof WC_Product ) {
					for ( $quantity = 0; $quantity < $item['quantity']; $quantity ++ ) {
						$itemWidth  = $item['data']->get_width( 'package' ) ?: (float) $settings['default_width'];
						$itemHeight = $item['data']->get_height( 'package' ) ?: (float) $settings['default_height'];
						$itemLength = $item['data']->get_length( 'package' ) ?: (float) $settings['default_length'];

						if ( $calculateCustomWeight ) {
							$orderWeight += $item['data']->get_weight( 'package' ) ?: (float) $settings['default_weight'];
						}

						if ( $itemWidth > $dimensions['width'] ) {
							$dimensions['width'] = $itemWidth;
						}

						if ( $itemHeight > $dimensions['height'] ) {
							$dimensions['height'] = $itemHeight;
						}

						if ( $itemLength > $dimensions['length'] ) {
							$dimensions['length'] = $itemLength;
						}

						$totalDimensions['width']  += $itemWidth;
						$totalDimensions['height'] += $itemHeight;
						$totalDimensions['length'] += $itemLength;
					}
				}
			}

			asort( $dimensions );
			$dimensionsOrder = array_keys( $dimensions );
			$firstDimension  = array_shift( $dimensionsOrder );
			$secondDimension = array_shift( $dimensionsOrder );

			if ( 'width' === $firstDimension || 'width' === $secondDimension ) {
				$orderWidth = $dimensions['width'];
			} else {
				$orderWidth = $totalDimensions['width'];
			}

			if ( 'height' === $firstDimension || 'height' === $secondDimension ) {
				$orderHeight = $dimensions['height'];
			} else {
				$orderHeight = $totalDimensions['height'];
			}

			if ( 'length' === $firstDimension || 'length' === $secondDimension ) {
				$orderLength = $dimensions['length'];
			} else {
				$orderLength = $totalDimensions['length'];
			}
		}

		$weightUnit    = get_option( 'woocommerce_weight_unit' );
		$dimensionUnit = get_option( 'woocommerce_dimension_unit' );

		switch ( $weightUnit ) {
			case 'g':
				$orderWeight = $orderWeight / 1000;
				break;
			case 'lbs':
				$orderWeight = $orderWeight * 0.453592;
				break;
			case 'oz':
				$orderWeight = $orderWeight * 0.0283495;
				break;
			case 'kg':
			default:
				// Nothing
				break;
		}

		switch ( $dimensionUnit ) {
			case 'm':
				$orderLength *= 100;
				$orderHeight *= 100;
				$orderWidth  *= 100;
				break;
			case 'mm':
				$orderLength /= 10;
				$orderHeight /= 10;
				$orderWidth  /= 10;
				break;
			case 'in':
				$orderLength *= 2.54;
				$orderHeight *= 2.54;
				$orderWidth  *= 2.54;
				break;
			case 'yd':
				$orderLength *= 91.44;
				$orderHeight *= 91.44;
				$orderWidth  *= 91.44;
				break;
			case 'cm':
			default:
				// Nothing
				break;
		}

		if ( $orderWeight < 0.01 ) {
			$orderWeight = 0.1;
		}

		if ( $orderHeight < 1 ) {
			$orderHeight = 10;
		}

		if ( $orderWidth < 1 ) {
			$orderWidth = 20;
		}

		if ( $orderLength < 1 ) {
			$orderLength = 10;
		}

		return [
			'length' => (int) $orderLength,
			'height' => (int) $orderHeight,
			'width'  => (int) $orderWidth,
			'weight' => (float) $orderWeight
		];
	}

	/**
	 * @param $product_id
	 *
	 * @return array cm and kg
	 */
	public static function getProductSizes( $product ) {
		if ( ! $product instanceof WC_Product ) {
			if ( is_int( $product ) ) {
				$product = wc_get_product( $product );
			} else {
				return [];
			}
		}

		$orderWeight = (float) $product->get_weight();
		$orderLength = (int) $product->get_length();
		$orderWidth  = (int) $product->get_width();
		$orderHeight = (int) $product->get_height();

		$weightUnit    = get_option( 'woocommerce_weight_unit' );
		$dimensionUnit = get_option( 'woocommerce_dimension_unit' );

		switch ( $weightUnit ) {
			case 'g':
				$orderWeight = $orderWeight / 1000;
				break;
			case 'lbs':
				$orderWeight = $orderWeight * 0.453592;
				break;
			case 'oz':
				$orderWeight = $orderWeight * 0.0283495;
				break;
			case 'kg':
			default:
				// Nothing
				break;
		}

		switch ( $dimensionUnit ) {
			case 'm':
				$orderLength *= 100;
				$orderHeight *= 100;
				$orderWidth  *= 100;
				break;
			case 'mm':
				$orderLength /= 10;
				$orderHeight /= 10;
				$orderWidth  /= 10;
				break;
			case 'in':
				$orderLength *= 2.54;
				$orderHeight *= 2.54;
				$orderWidth  *= 2.54;
				break;
			case 'yd':
				$orderLength *= 91.44;
				$orderHeight *= 91.44;
				$orderWidth  *= 91.44;
				break;
			case 'cm':
			default:
				// Nothing
				break;
		}
		/** @var Delivery $delivery_object */
		$delivery_object = static::DELIVERY_OBJECT;

		$settings = $delivery_object::settings();
		if ( isset( $settings['packages'] ) && ! empty( $settings['packages'] ) ) {
			$package = array_shift( $settings['packages'] );
		} else {
			$package['weight']['package'] = 0.1;
			$package['height']['outer']   = 10;
			$package['width']['outer']    = 10;
			$package['length']['outer']   = 10;
		}

		if ( $orderWeight < 0.01 ) {
			$orderWeight = $package['weight']['package'];
		}

		if ( $orderHeight < 1 ) {
			$orderHeight = $package['height']['outer'];
		}

		if ( $orderWidth < 1 ) {
			$orderWidth = $package['width']['outer'];
		}

		if ( $orderLength < 1 ) {
			$orderLength = $package['length']['outer'];
		}

		return [
			'length' => (int) $orderLength,
			'height' => (int) $orderHeight,
			'width'  => (int) $orderWidth,
			'weight' => (float) $orderWeight
		];
	}

	public static function precisionCheck( $beforeRequest = true ) {
		try {
			if ( is_null( self::$precisionSetting ) ) {
				self::$precisionSetting = ini_get( 'serialize_precision' );
			}

			if ( ! self::$precisionSetting || self::$precisionSetting === '-1' ) {
				return;
			}

			if ( $beforeRequest ) {
				ini_set( 'serialize_precision', '-1' );
			} else {
				ini_set( 'serialize_precision', self::$precisionSetting );
			}
		} catch ( Exception $e ) {
		}
	}

	private function __construct() {
	}

	private function __clone() {
	}
}
