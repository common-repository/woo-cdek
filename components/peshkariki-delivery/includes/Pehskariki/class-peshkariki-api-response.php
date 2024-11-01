<?php

namespace WDPK\Delivery\Peshkariki;

class PeshkarikiApiResponse
{
    public $success;
    public $code;
    public $response;
    public $additional;

    /**
     * @param $json
     *
     * @return \WDPK\Delivery\Peshkariki\PeshkarikiApiResponse
     */
    public function getObjectFromJson($json){
        $jsonObject = json_decode($json);
        foreach ($jsonObject AS $key => $value) $this->{$key} = $value;
        return $this;
    }
}
