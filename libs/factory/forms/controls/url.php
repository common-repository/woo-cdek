<?php

	/**
	 * Url Control
	 *
	 * Main options:
	 * @see FactoryForms434_TextboxControl
	 *
	 * @author Alex Kovalev <alex.kovalevv@gmail.com>
	 * @copyright (c) 2018, Webcraftic Ltd
	 *
	 * @package factory-forms
	 * @since 1.0.0
	 */

	// Exit if accessed directly
	if( !defined('ABSPATH') ) {
		exit;
	}

	if( !class_exists('Wbcr_FactoryForms434_UrlControl') ) {

		class Wbcr_FactoryForms434_UrlControl extends Wbcr_FactoryForms434_TextboxControl {

			public $type = 'url';

			/**
			 * Adding 'http://' to the url if it was missed.
			 *
			 * @since 1.0.0
			 * @return string
			 */
			public function getSubmitValue($name, $sub_name)
			{
				$value = parent::getSubmitValue($name, $sub_name);
				if( !empty($value) && substr($value, 0, 4) != 'http' ) {
					$value = 'http://' . $value;
				}

				return $value;
			}
		}
	}
