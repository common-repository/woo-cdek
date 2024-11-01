<?php


namespace WBCR\Delivery\ZaberiTovar;


use SimpleXMLElement;

class Helper extends \WBCR\Delivery\Base\Helper
{
    /**
     * @param array $array
     * @param SimpleXMLElement|null $xml
     */
    public static function array_to_xml( $array, &$xml ) {
        foreach( $array as $key => $value ) {
            if( is_array( $value ) ) {
                if( is_int( $key ) ) {
                    $key = 'e';
                }

                $label = $xml->addChild($key);
                self::array_to_xml( $value, $label );
            } else {
                $xml->addChild( $key, $value );
            }
        }
    }
}
