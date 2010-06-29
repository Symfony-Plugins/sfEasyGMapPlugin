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
  protected $geocoded_street       = null;
  protected $geocoded_postal_code  = null;

  /**
   * Constructs a gMapGeocodedAddress object from a given $raw_address String
   *
   * @param string $raw_address
   * @param string $country_code ccTLD (county code top-level domain)
   * @author Fabrice Bernhard
   * @since 2010-06-28 Ludovic Vigouroux : Add country_code attribute
   */
  public function __construct($raw_address, $country_code = null)
  {
    $this->raw_address = $raw_address;
    $this->country_code = $country_code;
  }

  /**
   * Geocodes the address using the Google Maps CSV webservice
   *
   * @param string $api_key
   * @return integer $accuracy
   * @author Fabrice Bernhard
   * @since 2010-06-28 Ludovic Vigouroux : Add country code biasing
   */
  public function geocode($api_key)
  {
    $apiURL = "http://maps.google.com/maps/geo?";
    $apiURL .= "&output=csv";
    $apiURL .= "&key=".$api_key;
    if (null !== $this->country_code)
    {
      $apiURL .= "&gl=".$this->country_code;
    }
    $apiURL .= "&q=";

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
   * Geocodes the address using the Google Maps XML webservice, which has more information.
   * Unknown values will be set to NULL.
   * @param string $api_key
   * @return integer $accuracy
   * @author Fabrice Bernhard
   */
  public function geocodeXml($api_key)
  {
    $apiURL = "http://maps.google.com/maps/geo?&output=xml&key=".$api_key."&q=";
    $raw_data = utf8_encode(file_get_contents($apiURL.urlencode($this->raw_address)));

    $p = xml_parser_create('UTF-8');
    xml_parse_into_struct($p, $raw_data, $vals, $index);
    xml_parser_free($p);

    if ($vals[$index['CODE'][0]]['value'] != 200)
    {

      return false;
    }

    $coordinates = $vals[$index['COORDINATES'][0]]['value'];
    list($this->lng, $this->lat) = explode(',', $coordinates);

    $this->accuracy = $vals[$index['ADDRESSDETAILS'][0]]['attributes']['ACCURACY'];

    // We voluntarily silence errors, the values will still be set to NULL if the array indexes are not defined
    @$this->geocoded_address = $vals[$index['ADDRESS'][0]]['value'];
    @$this->geocoded_street = $vals[$index['THOROUGHFARENAME'][0]]['value'];
    @$this->geocoded_postal_code = $vals[$index['POSTALCODENUMBER'][0]]['value'];
    @$this->geocoded_country = $vals[$index['COUNTRYNAME'][0]]['value'];
    @$this->geocoded_country_code = $vals[$index['COUNTRYNAMECODE'][0]]['value'];

    @$this->geocoded_city = $vals[$index['LOCALITYNAME'][0]]['value'];
    if (empty($this->geocoded_city))
    {
      @$this->geocoded_city = $vals[$index['SUBADMINISTRATIVEAREANAME'][0]]['value'];
    }
    if (empty($this->geocoded_city))
    {
      @$this->geocoded_city = $vals[$index['ADMINISTRATIVEAREANAME'][0]]['value'];
    }

    return $this->accuracy;
  }


  /**
   * Returns the latitude
   * @return float $latitude
   */
  public function getLat()
  {

    return $this->lat;
  }

  /**
   * Returns the longitude
   * @return float $longitude
   */
  public function getLng()
  {

    return $this->lng;
  }

  /**
   * Returns the Geocoding accuracy
   * @return integer $accuracy
   */
  public function getAccuracy()
  {

    return $this->accuracy;
  }

  /**
   * Returns the address normalized by the Google Maps web service
   * @return string $geocoded_address
   */
  public function getGeocodedAddress()
  {

    return $this->geocoded_address;
  }

  /**
   * Returns the city normalized by the Google Maps web service
   * @return string $geocoded_city
   *
   * @since 2010-05-19 Ludovic Vigouroux : Add decoding of double-encoding utf8
   */
  public function getGeocodedCity()
  {

    return utf8_decode($this->geocoded_city);
  }

  /**
   * Returns the country code normalized by the Google Maps web service
   * @return string $geocoded_country_code
   */
  public function getGeocodedCountryCode()
  {

    return $this->geocoded_country_code;
  }

  /**
   * Returns the country normalized by the Google Maps web service
   * @return string $geocoded_country
   */
  public function getGeocodedCountry()
  {

    return $this->geocoded_country;
  }

  /**
   * Returns the postal code normalized by the Google Maps web service
   * @return string $geocoded_postal_code
   */
  public function getGeocodedPostalCode()
  {

    return $this->geocoded_postal_code;
  }

  /**
   * Returns the street name normalized by the Google Maps web service
   * @return string $geocoded_country_code
   */
  public function getGeocodedStreet()
  {

    return $this->geocoded_street;
  }

}
