<?php

/*
 * 
 * A GoogleMap Marker
 * @author Fabrice Bernhard
 * 
 */
class GMapMarker
{
   /**
   * javascript name of the marker
   *
   * @var string
   */
  private $js_name        = null;
  /**
   * Latitude - deprecated
   *
   * @var float
   */
  private $lat            = null;
  /**
   * Longitude - deprecated
   *
   * @var float
   */
  private $lng            = null;
  /**
   * Coordinates
   *
   * @var GMapCoord
   */
  private $coord          = null;
  private $icon           = null;
  private $events         = array();
  private $custom_properties = array();
  
  /**
   * @param string $js_name Javascript name of the marker
   * @param float $lat Latitude
   * @param float $lng Longitude
   * @param GMapIcon $icon
   * @param GmapEvent[] array of GoogleMap Events linked to the marker
   * @author Fabrice Bernhard
   */
  public function __construct($lat,$lng,$js_name='marker',$icon=null,$events=array())
  {
    $this->js_name = $js_name;
    $this->coord   = new GMapCoord($lat,$lng);
    $this->icon    = $icon;
    $this->events  = $events;    
  }
  
  /**
   * Construct from a GMapGeocodedAddress object
   *
   * @param string $js_name
   * @param GMapGeocodedAddress $gmap_geocoded_address
   * @return GMapMarker
   */
  public static function constructFromGMapGeocodedAddress($gmap_geocoded_address,$js_name='marker')
  {
    if (!$gmap_geocoded_address instanceof GMapGeocodedAddress)
    {
      throw new sfException('object passed to constructFromGMapGeocodedAddress is not a GMapGeocodedAddress');
    }
    
    return new GMapMarker($js_name,$gmap_geocoded_address->getLat(),$gmap_geocoded_address->getLng());
  }
  
  /**
  * @return string $js_name Javascript name of the marker  
  */
  public function getName()
  {
    
    return $this->js_name;
  }
  /**    
  * @return GMapIcon $icon  
  */
  public function getIcon()
  {
    return $this->icon;
  }
  
  /**
   * returns the coordinates object of the marker
   * 
   * @return GMapCoord
   * @author fabriceb
   * @since 2009-05-02
   */
  public function getGMapCoord()
  {
  
    return $this->coord;
  }
  
  /**
  * @return float $lat Javascript latitude  
  */
  public function getLat()
  {
    
    return $this->getGMapCoord()->getLatitude();
  }
  /**
  * @return float $lng Javascript longitude  
  */
  public function getLng()
  {
    
    return $this->getGMapCoord()->getLongitude();
  }
  
  public function getIconName()
  {
    if ($this->getIcon() instanceof GMapIcon)
    {
      
      return $this->getIcon()->getName();
    }
    
    return $this->getIcon();
  }
  /**
  * @return string Javascript code to create the marker
  * @author Fabrice Bernhard
  */
  public function getMarkerJs()
  {
    if ($this->getIconName() != '')
    {
      $markerOptionsJs = ', { icon:'.$this->getIconName().' }';
    }
    else
    {
      $markerOptionsJs = '';
    }
    $pointJs = 'new google.maps.LatLng('.$this->getLat().','.$this->getLng().')';
    $return = '';
    $return .= $this->getName().' = new google.maps.Marker('.$pointJs.$markerOptionsJs.');';
    foreach ($this->custom_properties as $attribute=>$value)
    {
      $return .= $this->getName().".".$attribute." = '".$value."';";
    }
    foreach ($this->events as $event)
    {
      $return .= $event->getEventJs($this->getName());
    }   
    
    return $return;
  }
  
  /**
   * Adds an event listener to the marker
   *
   * @param GMapEvent $event
   */
  public function addEvent($event)
  {
    array_push($this->events,$event);
  }
  /**
   * Adds an onlick listener that open a html window with some text 
   *
   * @param string $html_text
   * @author fabriceb
   * @since Feb 20, 2009 fabriceb removed the escape_javascript function which made the plugin incompatible with symfony 1.2 
   */
  public function addHtmlInfoWindow($html_text)
  {
    $javascript = preg_replace('/\r\n|\n|\r/', "\\n", $html_text);
    $javascript = preg_replace('/(["\'])/', '\\\\\1', $javascript);
    
    $this->addEvent(new GMapEvent('click',"this.openInfoWindowHtml('".$javascript."')"));
  }

  /**
   * Returns the code for the static version of Google Maps
   * @TODO Add support for color and alpha-char
   * @author Laurent Bachelier
   * @return string
   */
  public function getMarkerStatic()
  {
    
    return $this->getLat().','.$this->getLng();
  }
  public function setCustomProperties($custom_properties)
  {
    $this->custom_properties=$custom_properties;
  }
  public function getCustomProperties()
  {
    
    return $this->custom_properties;
  }
  /**
   * Sets a custom property to the generated javascript object
   *
   * @param string $name
   * @param string $value
   */
  public function setCustomProperty($name,$value)
  {
    $this->custom_properties[$name] = $value;
  }
  
  /**
  *
  * @param GMapMarker[] $markers array of MArkers
  * @return GMapCoord
  * @author fabriceb
  * @since 2009-05-02
  *
  **/
  public static function getCenterCoord($markers)
  {
    $coords = array();
    foreach($markers as $marker)
    {
      array_push($coords, $marker->getGMapCoord());
    }
   
    return GMapCoord::getCenterCoord($coords);
  }
	
}
