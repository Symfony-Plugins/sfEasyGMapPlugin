<?php

/*
 * 
 * A GoogleMap Marker
 * @author Fabrice Bernhard
 * 
 */
class GMapMarker
{
  private $js_name        = null;
  private $lat            = null;
  private $lng            = null;
  private $icon           = null;
  private $events         = array();
  private $custom_properties = array();
  
  /**
   * @param String $js_name Javascript name of the marker
   * @param Decimal $lat Latitude
   * @param Decimal $lng Longitude
   * @param GMapIcon $icon
   * @param GmapEvent[] array of GoogleMap Events linked to the marker
   * @author Fabrice Bernhard
   */
  public function __construct($lat,$lng,$js_name='marker',$icon=null,$events=array())
  {
    $this->js_name = $js_name;
    $this->lat     = $lat;
    $this->lng     = $lng;
    $this->icon    = $icon;
    $this->events  = $events;    
  }
  
  /**
   * Construct from a GMapGeocodedAddress object
   *
   * @param String $js_name
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
  * @return String $js_name Javascript name of the marker  
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
  * @return Decimal $lat Javascript latitude  
  */
  public function getLat()
  {
    
    return $this->lat;
  }
  /**
  * @return Decimal $lng Javascript longitude  
  */
  public function getLng()
  {
    
    return $this->lng;
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
  * @return String Javascript code to create the marker
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
   * @param String $html_text
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
   * @author laurentb
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
   * @param String $name
   * @param String $value
   */
  public function setCustomProperty($name,$value)
  {
    $this->custom_properties[$name] = $value;
  }
	
}
