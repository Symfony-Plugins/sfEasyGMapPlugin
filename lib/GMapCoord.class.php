<?php

/**
 * 
 * GoogleMap Bounds
 * @author Fabrice Bernhard
 * 
 */
class GMapCoord
{
  /**
   * Latitude
   *
   * @var float
   */
  protected $latitude;
  /**
   * Longitude
   *
   * @var float
   */
  protected $longitude;
  
  public function __construct($latitude = null, $longitude = null)
  {
    $this->latitude     = floatval($latitude);
    $this->longitude    = floatval($longitude);
  }
  
  public function getLatitude()
  {

    return $this->latitude;
  }
  public function getLongitude()
  {
    
    return $this->longitude;
  }
  public function setLatitude($latitude)
  {
    $this->latitude = floatval($latitude);
  }
  public function setLongitude($longitude)
  {
    $this->longitude = floatval($longitude);
  }
  public static function createFromString($string)
  {
    $coord_array = explode(',',$string);
    if (count($coord_array)==2)
    {
      $latitude = floatval(trim($coord_array[0]));
      $longitude = floatval(trim($coord_array[1]));
      
      return new GMapCoord($latitude,$longitude);
    }

    return null;
  }
  
  /**
   * Lng to Pix
   * cf. a World's map according to Google http://mt0.google.com/mt/v=ap.92&hl=en&x=0&y=0&z=0&s=
   *
   * @param float $lng
   * @param integer $zoom
   * @return integer
   * @author fabriceb
   * @since Feb 18, 2009 fabriceb
   */
  public static function fromLngToPix($lng,$zoom)
  {
    $lngrad = $lng / 180 * 3.14159;
    $mercx = $lngrad;
    $cartx = $mercx + 3.14159;
    $pixelx = $cartx * 256/(2*3.14159);
    $pixelx_zoom =  $pixelx * pow(2,$zoom);    
    
    return $pixelx_zoom;
  }
  
  /**
   * Lat to Pix
   * cf. a World's map according to Google http://mt0.google.com/mt/v=ap.92&hl=en&x=0&y=0&z=0&s=
   *
   * @param float $lat
   * @param integer $zoom
   * @return integer
   * @author fabriceb
   * @since Feb 18, 2009 fabriceb
   */
  public static function fromLatToPix($lat,$zoom)
  {
    $latrad = $lat / 180 * 3.14159;
    $mercy = log(tan($latrad)+1/cos($latrad));
    $carty = 3.14159 / 2 - $mercy;
    $pixely = $carty * 256/(3.14159);
    $pixely_zoom = $pixely * pow(2,$zoom);
    
    return $pixely_zoom;
  }
  
  /**
   * Pix to Lng
   * cf. a World's map according to Google http://mt0.google.com/mt/v=ap.92&hl=en&x=0&y=0&z=0&s=
   *
   * @param integer $pix
   * @param integer $zoom
   * @return float
   * @author fabriceb
   * @since Feb 18, 2009 fabriceb
   */
  public static function fromPixToLng($pixelx_zoom,$zoom)
  {
    $pixelx = $pixelx_zoom / pow(2,$zoom);    
    $cartx = $pixelx / 256 * 2 * 3.14159;    
    $mercx = $cartx - 3.14159;
    $lngrad = $mercx;
    $lng = 180 * $lngrad / 3.14159;
    
    return $lng;
  }
  
  /**
   * Pix to Lat
   * cf. a World's map according to Google http://mt0.google.com/mt/v=ap.92&hl=en&x=0&y=0&z=0&s=
   *
   * @param integer $pix
   * @param integer $zoom
   * @return float
   * @author fabriceb
   * @since Feb 18, 2009 fabriceb
   */
  public static function fromPixToLat($pixely_zoom,$zoom)
  {
    $pixely = $pixely_zoom / pow(2,$zoom);
    $carty = $pixely / 256 * 3.14159;
    $mercy = 3.14159 / 2 - $carty;
    $latrad = 2 * atan(exp($mercy))-3.14159/2;
    $lat = 180 * $latrad / 3.14159;
        
    return $lat;
  }
}