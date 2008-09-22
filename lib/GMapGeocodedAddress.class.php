<?php

/**
 * A class to geocode addresses
 * @author Fabrice Bernhard
 */

class GMapGeocodedAddress
{
  protected $raw_address           = null;
  protected $lat                   = null;
  protected $lng                   = null;
  protected $accuracy              = null;
  protected $geocoded_city         = null;
  protected $geocoded_country_code = null;
  protected $geocoded_country      = null;
  protected $geocoded_address      = null;
  
  /**
   * Constructs a gMapGeocodedAddress object from a given $raw_address String
   *
   * @param String $raw_address
   * @author Fabrice Bernhard
   */
  public function __construct($raw_address)
  {
    $this->raw_address = $raw_address;
  }
  
  /**
   * Geocodes the address using Google Maps CSV webservice
   *
   * @param String $api_key
   * @return Integer $accuracy
   * @author Fabrice Bernhard
   */
  public function geocode($api_key)
  {
    $apiURL = "http://maps.google.com/maps/geo?&output=csv&key=".$api_key."&q=";
    $raw_data = file_get_contents($apiURL.urlencode($this->raw_address));
    $geocoded_array = explode(',',$raw_data);
    if ($geocoded_array[0]!=200)
    {
      
      return false;
    }
    $this->lat      = $geocoded_array[2];
    $this->lng      = $geocoded_array[3];
    $this->accuracy = $geocoded_array[1];
    
    return $this->accuracy;
  }

  /**
   * Geocodes the address using Google Mapx XML webservice, which has more information
   *
   * @param String $api_key
   * @return Integer $accuracy
   * @author Fabrice Bernhard
   */
  public function geocodeXml($api_key)
  {
    $apiURL = "http://maps.google.com/maps/geo?&output=xml&key=".$this->getGoogleKey()."&q=";
    $raw_data = utf8_encode(file_get_contents($apiURL.urlencode($this->raw_address)));
    
    $p = xml_parser_create('UTF-8');
    xml_parse_into_struct($p, $raw_data, $vals, $index);
    xml_parser_free($p);
    
    if ($vals[$index['CODE'][0]]['value'] != 200)
    {
      
      return false;
    }
    
    $coordinates = $vals[$index['COORDINATES'][0]]['value'];
    $coordArray = explode(',',$coordinates);
    $this->lat = $coordArray[1];
    $this->lng = $coordArray[0];    
    $this->accuracy = $vals[$index['ADDRESSDETAILS'][0]]['attributes']['ACCURACY'];
    $this->geocoded_address = $vals[$index['ADDRESS'][0]]['value'];
    $this->geocoded_country_code = $vals[$index['COUNTRYNAMECODE'][0]]['value'];
    $this->geocoded_city = $vals[$index['LOCALITYNAME'][0]]['value'];
    if ($this->geocoded_city == '')
    {
      $this->geocoded_city = $vals[$index['SUBADMINISTRATIVEAREANAME'][0]]['value'];
    }
    if ($this->geocoded_city == '')
    {
      $this->geocoded_city = $vals[$index['ADMINISTRATIVEAREANAME'][0]]['value'];
    }
    
    return $this->accuracy;
  }
  
  
  /**
   * Returns Latitude
   * @return Decimal $latitude
   */
  public function getLat()
  {
    
    return $this->lat;
  }
  /**
   * Returns longitude
   * @return Decimal $longitude
   */
  public function getLng()
  {
    
    return $this->lng;
  }
  /**
   * Returns Geocoding accuracy
   * @return Integer $accuracy
   */
  public function getAccuracy()
  {
    
    return $this->accuracy;
  }
  /**
   * Returns address as normalized by the Google Maps web service
   * @return String $geocoded_address
   */
  public function getGeocodedAddress()
  {
    
    return $this->geocoded_address;
  }
  /**
   * Returns city as normalized by the Google Maps web service
   * @return String $geocoded_city
   */
  public function getGeocodedCity()
  {
    
    return $this->geocoded_city;
  }
  /**
   * Returns country code as normalized by the Google Maps web service
   * @return String $geocoded_country_code
   */
  public function getGeocodedCountryCode()
  {
    
    return $this->geocoded_country_code;
  }
  
}