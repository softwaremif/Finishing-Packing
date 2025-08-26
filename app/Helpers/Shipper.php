<?php

namespace App\Helpers;

class Shipper
{
  public static function alamat($fu) {
    $result = array();

    $company = "PT MORICH INDO FASHION";
    if (!empty($fu)) $company .= " -- ATTN : $fu";
    
    $st = "JL RAYA KARANGJATI KM. 25, GEMBONGAN – BERGAS";

    $city = "KAB SEMARANG – JAWA TENGAH";

    $country = "INDONESIA 50552";

    $telp = "TELP. 62-298-525105";

    $result['company'] = $company;
    $result['st'] = $st;
    $result['city'] = $city;
    $result['country'] = $country;
    $result['telp'] = $telp;
    $result['fu'] = $fu;

    return $result;
  }
}