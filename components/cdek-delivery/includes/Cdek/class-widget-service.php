<?php

namespace WBCR\Delivery\Cdek;

use WBCR\Delivery\Cdek\Cdek;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Add endpoint /wdcd-cdek-service/ for example: https://site.dev/wdcd-cdek-service/
 */
class Service_Bridge {
	public function __construct() {
		add_action( 'init', function () {
			add_rewrite_endpoint( 'wdcd-cdek-service', EP_ROOT );
		} );

		add_action( 'wp_loaded', function () {
			$rules = get_option( 'rewrite_rules' );
			if ( ! isset( $rules['wdcd-cdek-service(/(.*))?/?$'] ) ) {
				flush_rewrite_rules( $hard = false );
			}
		} );

		add_action( 'template_redirect', array( $this, 'request' ) );
	}

	public function request() {
		$call = get_query_var( 'wdcd-cdek-service', false );
		if ( $call === false ) {
			return;
		}
		$defaults = [
			'courier_tariffs' => Cdek::courier_tariffs,
			'pickup_tariffs'  => Cdek::pickup_tariffs
		];
		$settings = Cdek::settings();
		$settings = wp_parse_args( $settings, $defaults );
		header( 'Access-Control-Allow-Origin: *' );
		new ISDEKservice();
		ISDEKservice::setTarifPriority( $settings['courier_tariffs'], $settings['pickup_tariffs'] );

		$action = $_REQUEST['isdek_action'];
		if ( method_exists( __NAMESPACE__ . '\ISDEKservice', $action ) ) {
			ISDEKservice::$action( $_REQUEST );
		}

		exit;
	}
}

new Service_Bridge();


class ISDEKservice {
	protected static $account = false;
	protected static $key = false;


	protected static $tarifPriority = false;

	function __construct() {
		$settings      = Cdek::settings();
		self::$account = $settings['api_account'];
		self::$key     = $settings['api_password'];

		self::setTarifPriority( $settings['courier_tariffs'], $settings['pickup_tariffs'] );
	}

	// Workout
	public static function setTarifPriority( $arCourier, $arPickup ) {
		self::$tarifPriority = array(
			'courier' => $arCourier,
			'pickup'  => $arPickup
		);
	}

	public static function getPVZ() {
		$arPVZ = self::getPVZFile();
		if ( $arPVZ ) {
			self::toAnswer( array( 'pvz' => $arPVZ ) );
		}
		self::printAnswer();
	}

	public static function getLang() {
		self::toAnswer( array( 'LANG' => self::getLangArray() ) );
		self::printAnswer();
	}

	public static function calc( $data ) {
		if ( ! isset( $data['shipment']['tarifList'] ) ) {
			$data['shipment']['tariffList'] = self::$tarifPriority[ $data['shipment']['type'] ];
		}

		if ( ! $data['shipment']['cityToId'] ) {
			$cityTo = self::sendToCity( $data['shipment']['cityTo'] );
			if ( $cityTo && $cityTo['code'] === 200 ) {
				$pretendents = json_decode( $cityTo['result'] );
				if ( $pretendents && isset( $pretendents->geonames ) ) {
					$data['shipment']['cityToId'] = $pretendents->geonames[0]->id;
				}
			}
		}

		if ( $data['shipment']['cityToId'] ) {
			$answer = self::calculate( $data['shipment'] );

			if ( $answer ) {
				$answer['type'] = $data['shipment']['type'];
				if ( $data['shipment']['timestamp'] ) {
					$answer['timestamp'] = $data['shipment']['timestamp'];
				}
				self::toAnswer( $answer );
			}
		} else {
			self::toAnswer( array( 'error' => 'City to not found' ) );
		}

		self::printAnswer();
	}

	public static function getCity( $data, $return = false ) {
		if ( $data['city'] ) {
			$result = self::sendToCity( $data['city'] );
			if ( $result && $result['code'] == 200 ) {
				$result = json_decode( $result['result'] );
				if ( ! isset( $result->geonames ) ) {
					self::toAnswer( array( 'error' => 'No cities found' ) );
				} else {
					self::toAnswer( array(
						'id'      => $result->geonames[0]->id,
						'city'    => $result->geonames[0]->cityName,
						'region'  => $result->geonames[0]->regionName,
						'country' => $result->geonames[0]->countryName
					) );
				}
			} else {
				self::toAnswer( array( 'error' => 'Wrong answer code from server : ' . $result['code'] ) );
			}
		} else {
			self::toAnswer( array( 'error' => 'No city to search given' ) );
		}

		if ( $return ) {
			return self::printAnswer( false );
		} else {
			self::printAnswer();
		}
	}

	// PVZ
	protected static function getPVZFile() {

		$arPVZ = self::requestPVZ();

		return $arPVZ;
	}

	protected static function requestPVZ() {
		if ( ! function_exists( 'simplexml_load_string' ) ) {
			self::toAnswer( array( 'error' => 'No php simplexml-library installed on server' ) );

			return false;
		}

		$request = self::sendToSDEK( 'pvzlist', false, 'type=ALL' );
		$arLL    = array();
		if ( $request && $request['code'] == 200 ) {
			$xml = simplexml_load_string( $request['result'] );

			$arList = array(
				'PVZ'       => array(),
				'CITY'      => array(),
				'REGIONS'   => array(),
				'CITYFULL'  => array(),
				'COUNTRIES' => array()
			);

			foreach ( $xml as $key => $val ) {

				if ( $_REQUEST['country'] && $_REQUEST['country'] != 'all' && ( (string) $val['CountryName'] != $_REQUEST['country'] ) ) {
					continue;
				}

				$cityCode = (string) $val['CityCode'];
				$type     = (string) $val['Type'];
				$city     = (string) $val["City"];
				if ( strpos( $city, '(' ) !== false ) {
					$city = trim( substr( $city, 0, strpos( $city, '(' ) ) );
				}
				if ( strpos( $city, ',' ) !== false ) {
					$city = trim( substr( $city, 0, strpos( $city, ',' ) ) );
				}
				$code = (string) $val["Code"];

				$arList[ $type ][ $cityCode ][ $code ] = array(
					'Name'           => (string) $val['Name'],
					'WorkTime'       => (string) $val['WorkTime'],
					'Address'        => (string) $val['Address'],
					'Phone'          => (string) $val['Phone'],
					'Note'           => (string) $val['Note'],
					'cX'             => (string) $val['coordX'],
					'cY'             => (string) $val['coordY'],
					'Dressing'       => (string) $val['IsDressingRoom'],
					'Cash'           => (string) $val['HaveCashless'],
					'Station'        => (string) $val['NearestStation'],
					'Site'           => (string) $val['Site'],
					'Metro'          => (string) $val['MetroStation'],
					'AddressComment' => (string) $val['AddressComment'],
				);
				if ( $val->WeightLimit ) {
					$arList[ $type ][ $cityCode ][ $code ]['WeightLim'] = array(
						'MIN' => (float) $val->WeightLimit['WeightMin'],
						'MAX' => (float) $val->WeightLimit['WeightMax']
					);
				}

				$arImgs = array();
				if ( ! is_array( $val->OfficeImage ) ) {
					$arToCheck = array( array( 'url' => (string) $val->OfficeImage['url'] ) );
				} else {
					$arToCheck = $val->OfficeImage;
				}

				foreach ( $val->OfficeImage as $img ) {
					if ( strstr( $_tmpUrl = (string) $img['url'], 'http' ) === false ) {
						continue;
					}
					$arImgs[] = (string) $img['url'];
				}

				if ( count( $arImgs = array_filter( $arImgs ) ) ) {
					$arList[ $type ][ $cityCode ][ $code ]['Picture'] = $arImgs;
				}
				if ( $val->OfficeHowGo ) {
					$arList[ $type ][ $cityCode ][ $code ]['Path'] = (string) $val->OfficeHowGo['url'];
				}

				if ( ! array_key_exists( $cityCode, $arList['CITY'] ) ) {
					$arList['CITY'][ $cityCode ]     = $city;
					$arList['CITYFULL'][ $cityCode ] = (string) $val['CountryName'] . ' ' . (string) $val['RegionName'] . ' ' . $city;
					$arList['REGIONS'][ $cityCode ]  = implode( ', ', array_filter( array(
						(string) $val['RegionName'],
						(string) $val['CountryName']
					) ) );
				}

			}

			krsort( $arList['PVZ'] );

			return $arList;
		} elseif ( $request ) {
			self::toAnswer( array( 'error' => 'Wrong answer code from server : ' . $request['code'] ) );

			return false;
		}
	}

	// Calculation
	protected static function calculate( $shipment ) {
		$headers = self::getHeaders();

		$arData = array(
			'dateExecute'    => $headers['date'],
			'version'        => '1.0',
			'authLogin'      => $headers['account'],
			'secure'         => $headers['secure'],
			'senderCityId'   => $shipment['cityFromId'],
			'receiverCityId' => $shipment['cityToId'],
			'tariffId'       => isset( $shipment['tariffId'] ) ? $shipment['tariffId'] : false
		);

		if ( $shipment['tariffList'] ) {
			foreach ( $shipment['tariffList'] as $priority => $tarif ) {
				$tarif                   = intval( $tarif );
				$arData['tariffList'] [] = array(
					'priority' => $priority + 1,
					'id'       => $tarif
				);
			}
		}

		if ( $shipment['goods'] ) {
			$arData['goods'] = array();
			foreach ( $shipment['goods'] as $arGood ) {
				$arData['goods'] [] = array(
					'weight' => $arGood['weight'],
					'length' => $arGood['length'],
					'width'  => $arGood['width'],
					'height' => $arGood['height']
				);
			}
		}

		$result = self::sendToCalculate( $arData );

		if ( $result && $result['code'] == 200 ) {
			if ( ! is_null( json_decode( $result['result'] ) ) ) {
				return json_decode( $result['result'], true );
			} else {
				self::toAnswer( array( 'error' => 'Wrong server answer' ) );

				return false;
			}
		} else {
			self::toAnswer( array( 'error' => 'Wrong answer code from server : ' . $result['code'] ) );

			return false;
		}
	}

	// API
	protected static function sendToSDEK( $where, $XML = false, $get = false ) {
		$where .= '.php' . ( ( $get ) ? "?" . $get : '' );
		$where = 'https://integration.cdek.ru/' . $where;

		if ( $XML ) {
			$XML = array( 'xml_request' => $XML );
		}

		return self::client( $where, $XML );
	}

	protected static function getHeaders() {
		$date = date( 'Y-m-d' );
		$arHe = array(
			'date' => $date
		);
		if ( self::$account && self::$key ) {
			$arHe = array(
				'date'    => $date,
				'account' => self::$account,
				'secure'  => md5( $date . "&" . self::$key )
			);
		}

		return $arHe;
	}

	protected static function sendToCalculate( $data ) {
		$result = self::client(
			'http://api.cdek.ru/calculator/calculate_price_by_json_request.php',
			array( 'json' => json_encode( $data ) )
		);

		return $result;
	}

	protected static function sendToCity( $data ) {
		$result = self::client(
			'http://api.cdek.ru/city/getListByTerm/json.php?q=' . $data
		);

		return $result;
	}

	protected static function client( $where, $data = false ) {
		$args = array();

		if ( $data ) {
			$args['body']   = $data;
			$args['method'] = 'POST';
		}

		$response = wp_remote_request( $where, $args );

		$code = wp_remote_retrieve_response_code( $response );

		if ( $code == 200 ) {
			$result = array(
				'code'   => $code,
				'result' => wp_remote_retrieve_body( $response )
			);
		} else {
			$result = false;
		}

		return $result;
	}

	// LANG
	protected static function getLangArray() {
		return array(
			'YOURCITY'   => 'Ваш город',
			'COURIER'    => 'Курьер',
			'PICKUP'     => 'Самовывоз',
			'TERM'       => 'Срок',
			'PRICE'      => 'Стоимость',
			'DAY'        => 'дн.',
			'RUB'        => 'руб.',
			'NODELIV'    => 'Нет доставки',
			'CITYSEATCH' => 'Поиск города',
			'CITYSEARCH' => 'Поиск города',
			'ALL'        => 'Все',
			'PVZ'        => 'Пункты выдачи',
			'MOSCOW'     => 'Москва',
			'RUSSIA'     => 'Россия',
			'COUNTING'   => 'Идет расчет',

			'NO_AVAIL'          => 'Нет доступных способов доставки',
			'CHOOSE_TYPE_AVAIL' => 'Выберите способ доставки',
			'CHOOSE_OTHER_CITY' => 'Выберите другой населенный пункт',

			'EST' => 'есть',

			'L_ADDRESS' => 'Адрес пункта выдачи заказов',
			'L_TIME'    => 'Время работы',
			'L_WAY'     => 'Как к нам проехать',
			'L_CHOOSE'  => 'Выбрать',

			'H_LIST'    => 'Список пунктов выдачи заказов',
			'H_PROFILE' => 'Способ доставки',
			'H_CASH'    => 'Расчет картой',
			'H_DRESS'   => 'С примеркой',
			'H_SUPPORT' => 'Служба поддержки',
		);
	}

	// answering
	protected static $answer = false;

	protected static function toAnswer( $wat ) {
		$stucked = array( 'error' );
		if ( ! is_array( $wat ) ) {
			$wat = array( 'info' => $wat );
		}
		if ( ! is_array( self::$answer ) ) {
			self::$answer = array();
		}
		foreach ( $wat as $key => $sign ) {
			if ( in_array( $key, $stucked ) ) {
				if ( ! array_key_exists( $key, self::$answer ) ) {
					self::$answer[ $key ] = array();
				}
				self::$answer[ $key ] [] = $sign;
			} else {
				self::$answer[ $key ] = $sign;
			}
		}
	}

	/**
	 * @param bool $print
	 *
	 * @return array|bool
	 */
	protected static function printAnswer( $print = true ) {
		if ( $print ) {
			echo json_encode( self::$answer );
		} else {
			return self::$answer;
		}
	}

	public static function validate_calc( $data ) {
		if ( ! isset( $data['shipment']['tarifList'] ) ) {
			$data['shipment']['tariffList'] = self::$tarifPriority[ $data['shipment']['type'] ];
		}

		if ( ! $data['shipment']['cityToId'] ) {
			$cityTo = self::sendToCity( $data['shipment']['cityTo'] );
			if ( $cityTo && $cityTo['code'] === 200 ) {
				$pretendents = json_decode( $cityTo['result'] );
				if ( $pretendents && isset( $pretendents->geonames ) ) {
					$data['shipment']['cityToId'] = $pretendents->geonames[0]->id;
				}
			}
		}

		if ( $data['shipment']['cityToId'] ) {
			$answer = self::calculate( $data['shipment'] );

			if ( $answer ) {
				$answer['type'] = $data['shipment']['type'];
				if ( $data['shipment']['timestamp'] ) {
					$answer['timestamp'] = $data['shipment']['timestamp'];
				}
				self::toAnswer( $answer );
			}
		} else {
			self::toAnswer( array( 'error' => 'City to not found' ) );
		}

		return self::printAnswer( false );
	}

}