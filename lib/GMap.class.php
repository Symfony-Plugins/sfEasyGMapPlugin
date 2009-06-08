<?php

/**
 * Google Map class
 * @author Fabrice Bernhard
 */

class GMap
{
  
  protected $default_options = array(
      'double_click_zoom' => true,
      'control' => array('new google.maps.LargeMapControl()'),
      'zoom' => 10,
      'center_lat' => 48.845398,
      'center_lng' => 2.34258,
      'js_name' => 'map'
  );
  // The API key provided by Google
  protected $api_key;

  // id of the Google Map div container
  protected $container_attributes = array(
  		'id' =>'map'
  );
  // style of the container
  protected $container_style=array('width'=>'512px','height'=>'512px');

  // objects linked to the map
  protected $icons=array();
  protected $markers=array();
  protected $events=array();

  // customise the javascript generated
  protected $after_init_js=array();
  protected $global_variables=array();

  // options
  protected $options = array();
  
  protected $zoom = null;
  protected $center_coord = null;

  /**
   * Constructs a Google Map PHP object
   *
   * @param array $options
   * @param array $attributes 
   */
  public function __construct($options=array(),$container_attributes=array())
  {
    $this->options = array_merge($this->default_options,$options);
    $this->container_attributes = array_merge($this->container_attributes,$container_attributes);
    // sets the starting zoom and center parameters
    $this->zoom = $this->options['zoom'];
    $this->setCenter($this->options['center_lat'], $this->options['center_lng']);

    // delcare the Google Map Javascript object as global
    $this->addGlobalVariable($this->getJsName(),'null');

    // set the Google Map API key for the current domain
    $this->guessAndSetAPIKey();

  }

  /**
   * Guesses and sets the API Key
   * @author Fabrice
   *
   */
  protected function guessAndSetAPIKey()
  {
    $this->setAPIKey(self::guessAPIKey());
  }
  
  /**
   * Sets the Google Map API Key using the array_google_keys defined in the app.yml of your application
   * @param string $domain The domaine name
   * @author Fabrice
   * 
   */
  public function setAPIKeyByDomain($domain)
  {
    $this->setAPIKey(self::getAPIKeyByDomain($domain));
  }
    
  /**
   * Guesses the GoogleMap key for the current domain
   * @return string $api_key
   * @author Fabrice
   *
   */
  public static function guessAPIKey()
  {
    if (isset($_SERVER['SERVER_NAME']))
    {
      return self::getAPIKeyByDomain($_SERVER['SERVER_NAME']);
    }
    else if (isset($_SERVER['HTTP_HOST']))
    {
      return self::getAPIKeyByDomain($_SERVER['HTTP_HOST']);
    }

    return self::getAPIKeyByDomain('default');
  }

  /**
   * Static method to retrieve API key
   *
   * @param unknown_type $domain
   * @return unknown
   */
  public static function getAPIKeyByDomain($domain)
  {
    $api_keys = sfConfig::get('app_google_maps_api_keys');
    if (is_null($api_keys))
    {
      return '';
    }
    
    if (is_array($api_keys) && array_key_exists($domain,$api_keys))
    {
      $api_key=$api_keys[$domain];
    }
    else
    {
      if (array_key_exists('default',$api_keys))
      {
        $api_key=$api_keys['default'];
      }
      else
      {
        throw new sfException('No Google Map API key defined in the app.yml file of your application');
      }
    }
    
    return $api_key;
  }


  /**
   * Geocodes an address
   * @param string $address
   * @return GMapGeocodedAddress
   * @author Fabrice Bernhard
   */
  public function geocode($address)
  {
    $gMapGeocodedAddress = new GMapGeocodedAddress($address);
    $gMapGeocodedAddress->geocode($this->getAPIKey());

    return $gMapGeocodedAddress;
  }
  /**
   * Geocodes an address and returns additional normalized information
   * @param string $address
   * @return GMapGeocodedAddress
   * @author Fabrice Bernhard
   */
  public function geocodeXml($address)
  {
    $gMapGeocodedAddress = new GMapGeocodedAddress($address);
    $gMapGeocodedAddress->geocodeXml($this->getAPIKey());

    return $gMapGeocodedAddress;
  }

  /**
   * @return string $this->options['js_name'] Javascript name of the googlemap
   */
  public function getJsName()
  {

    return $this->options['js_name'];
  }

  /**
   * Sets the Google Maps API key
   * @param string $key
   */
  public function setAPIKey($key)
  {
    $this->api_key=$key;
  }
  /**
   * Gets the Google Maps API key
   * @return string $key
   */
  public function getAPIKey()
  {

    return $this->api_key;
  }

  /**
   * Defines the style of the Google Map div
   * @param Array $style Associative array with the style of the div container
   */
  public function setContainerStyles($style)
  {
    $this->container_style=$style;
  }
  /**
   * Defines one style of the div container
   * @param string $style_tag name of css tag
   * @param string $style_value value of css tag
   */
  public function setContainerStyle($style_tag,$style_value)
  {
    $this->container_style[$style_tag]=$style_value;
  }
  /**
   * Gets the style Array of the div container
   */
  public function getContainerStyles()
  {

    return $this->container_style;
  }

  /*
   * Gets one style of the Google Map div
   * @param string $style_tag name of css tag
   */
  public function getContainerStyle($style_tag)
  {

    return $this->container_style[$style_tag];
  }
  
  public function getContainerId()
  {
    
    return $this->container_attributes['id'];
  }

  /**
   * returns the Html for the Google map container
   * @param Array $options Style options of the HTML container
   * @return string $container
   * @author Fabrice Bernhard
   */
  public function getContainer($styles=array(),$attributes=array())
  {
    $this->container_style = array_merge($this->container_style,$styles);
    $this->container_attributes = array_merge($this->container_attributes,$attributes);

    $style="";
    foreach ($this->container_style as $tag=>$val)
    {
      $style.=$tag.":".$val.";";
    }
    
    $attributes = $this->container_attributes;
    $attributes['style'] = $style;
    
    return RenderTag::renderContent('div',null,$attributes);
  }


  /**
   * Returns the Javascript for the Google map
   * @param Array $options
   * @return $string
   * @author Fabrice Bernhard
   * @since 2009-04-23 tomr changed control from string to array
   * @since 2009-05-03 fabriceb added backwards compatibility
   */
  public function getJavascript()
  {
    sfContext::getInstance()->getResponse()->addJavascript($this->getGoogleJsUrl());

    $options = $this->options;

    $return ='';
    $init_events = array();
    $init_events[] = $this->getJsName().' = new google.maps.Map2(document.getElementById("'.$this->getContainerId().'"));';
    $init_events[] = $this->getJsName().'.setCenter(new google.maps.LatLng('.$this->getCenterLat().', '.$this->getCenterLng().'), '.$this->getZoom().');';
    if ($options['double_click_zoom'])
    {
      $init_events[] = $this->getJsName().'.enableDoubleClickZoom();';
    }
    if (is_array($options['control']))
    {
      foreach ($options['control'] as $control)
      {
        $init_events[] = $this->getJsName().'.addControl('.$control.');';
      }
    }
    else if ($options['control'] != '')
    {
      $init_events[] = $this->getJsName().'.addControl('.$options['control'].');';
    }
    $init_events[] = $this->getEventsJs();
    $this->loadMarkerIcons();
    $init_events[] = $this->getIconsJs();
    $init_events[] = $this->getMarkersJs();
    foreach ($this->after_init_js as $after_init)
    {
      $init_events[] = $after_init;
    }

    $return .= '
  google.load("maps", "2");
   	';
    foreach($this->global_variables as $name=>$value)
    {
      $return .= '
  var '.$name.' = '.$value.';';
    }
    $return .= '
  //  Call this function when the page has been loaded
  function initialize()
  {
    if (GBrowserIsCompatible())
    {';
    foreach($init_events as $init_event)
    {
      $return .= '
      '.$init_event;
    }
    $return .= '
    }
  }
  google.setOnLoadCallback(initialize);
  document.onunload="GUnload()";
';

    return $return;
  }

  /**
   * returns the URLS for the google map Javascript file
   * @return string $js_url
   */
  public function getGoogleJsUrl()
  {

    return 'http://www.google.com/jsapi?key='.$this->getAPIKey();
  }

  /**
   * Adds an icon to be loaded
   * @param GMapIcon $icon A google Map Icon
   */
  public function addIcon($icon)
  {
    $this->icons[$icon->getName()]=$icon;
  }
  
  /**
   * Retourne l'objet GMapIcon à partir du nom de l'icone
   *
   * @param string $name
   * @return GMapIcon
   * 
   * @author Vincent
   * @since 2008-12-02
   */
  public function getIconByName($name)
  {
    
    return $this->icons[$name];
  }
  
  /**
   * @param GMapMarker $marker a marker to be put on the map
   */
  public function addMarker($marker)
  {
    array_push($this->markers,$marker);
  }
  /**
   * @param GMapMarker[] $markers marker to be put on the map
   */
  public function setMarkers($markers)
  {
    $this->markers = $markers;
  }
  /**
   * @param GMapEvent $event an event to be attached to the map
   */
  public function addEvent($event)
  {
    array_push($this->events,$event);
  }

  public function loadMarkerIcons()
  {
    foreach($this->markers as $marker)
    {
      if ($marker->getIcon() instanceof GMapIcon)
      {
        $this->addIcon($marker->getIcon());
      }
    }
  }
  /**
   * Returns the javascript string which defines the icons
   * @return string
   */
  public function getIconsJs()
  {
    $return = '';
    foreach ($this->icons as $icon)
    {
      $return .= $icon->getIconJs();
    }

    return $return;
  }
  /**
   * Returns the javascript string which defines the markers
   * @return string
   */
  public function getMarkersJs()
  {
    $return = '';
    foreach ($this->markers as $marker)
    {
      $return .= $marker->getMarkerJs();
      $return .= $this->getJsName().'.addOverlay('.$marker->getName().');';
      $return .= "\n      ";
    }

    return $return;
  }

  /*
   * Returns the javascript string which defines events linked to the map
   * @return string
   */
  public function getEventsJs()
  {
    $return = '';
    foreach ($this->events as $event)
    {
      $return .= $event->getEventJs($this->getJsName());
      $return .= "\n";
    }
    return $return;
  }

  /*
   * Gets the Code to execute after Js initialization
   * @return string $after_init_js
   */
  public function getAfterInitJs()
  {
    return $this->after_init_js;
  }
  /*
   * Sets the Code to execute after Js initialization
   * @param string $after_init_js Code to execute
   */
  public function addAfterInitJs($after_init_js)
  {
    array_push($this->after_init_js,$after_init_js);
  }

  public function addGlobalVariable($name, $value='null')
  {
    $this->global_variables[$name]=$value;

  }
  public function setZoom($zoom)
  {
    $this->zoom = $zoom;
  }
  /**
   * Sets the center of the map at the beginning
   *
   * @param float $lat
   * @param float $lng
   */
  public function setCenter($lat=null,$lng=null)
  {
    $this->center_coord = new GMapCoord($lat, $lng);
  }
  
  /**
   * 
   * @return GMapCoord
   * @author fabriceb
   * @since 2009-05-02
   */
  public function getCenterCoord()
  {

    return $this->center_coord;
  }
   /**
   * 
   * @return float
   * @author fabriceb
   * @since 2009-05-02
   */
  public function getCenterLat()
  {

    return $this->getCenterCoord()->getLatitude();
  }
    /**
   * 
   * @return float
   * @author fabriceb
   * @since 2009-05-02
   */
  public function getCenterLng()
  {
    return $this->getCenterCoord()->getLongitude();
  }
  public function getZoom()
  {

    return $this->zoom;
  }
  
  /**
   * gets the width of the map in pixels according to container style
   * @return integer
   * @author fabriceb
   * @since 2009-05-03
   */
  public function getWidth()
  {
  
    return intval(substr($this->getContainerStyle('width'),0,-2));
  }
  
  /**
   * gets the width of the map in pixels according to container style
   * @return integer
   * @author fabriceb
   * @since 2009-05-03
   */
  public function getHeight()
  {
  
    return intval(substr($this->getContainerStyle('height'),0,-2));
  }
  
  /**
   * sets the width of the map in pixels
   * 
   * @param integer
   * @author fabriceb
   * @since 2009-05-03
   */
  public function setWidth($width)
  {
    $this->setContainerStyle('width',$width.'px');
  }
  
  /**
   * sets the width of the map in pixels
   * 
   * @param integer
   * @author fabriceb
   * @since 2009-05-03
   */
  public function setHeight($height)
  {
    $this->setContainerStyle('height',$height.'px');
  }
  

  /**
   * Returns the URL of a static version of the map (when JavaScript is not active)
   * Supports only markers and basic parameters: center, zoom, size.
   * @param string $map_type = 'mobile'
   * @param string $hl Language (fr, en...)
   * @return string URL of the image
   * @author Laurent Bachelier
   */
  public function getStaticMapUrl($maptype='mobile', $hl='fr')
  {
    $params = array(
      'maptype' => $maptype,
      'zoom'    => $this->getZoom(),
      'key'     => $this->getAPIKey(),
      'center'  => $this->getCenterLat().','.$this->getCenterLng(),
      'size'    => $this->getWidth().'x'.$this->getHeight(),
      'hl'      => $hl,
      'markers' => $this->getMarkersStatic()
    );
    $pairs = array();
    foreach($params as $key => $value)
    {
      $pairs[] = $key.'='.$value;
    }

    return 'http://maps.google.com/staticmap?'.implode('&',$pairs);
  }

  /**
   * Returns the static code to create markers
   * @return string
   * @author Laurent Bachelier
   */
  protected function getMarkersStatic()
  {
    $markers_code = array();
    foreach ($this->markers as $marker)
    {
      $markers_code[] = $marker->getMarkerStatic();
    }

    return implode('|',$markers_code);
  }
  
  /**
   * 
   * calculates the center of the markers linked to the map
   * 
   * @return GMapCoord
   * @author fabriceb
   * @since 2009-05-02
   */
  public function getMarkersCenterCoord()
  {
    
    return GMapMarker::getCenterCoord($this->markers);
  }
  
  /**
   * sets the center of the map at the center of the markers
   * 
   * @author fabriceb
   * @since 2009-05-02
   */
  public function centerOnMarkers()
  {
    $center = $this->getMarkersCenterCoord();
  
    $this->setCenter($center->getLatitude(), $center->getLongitude());
  }
  
  /**
   * 
   * calculates the zoom which fits the markers on the map
   * 
   * @param integer $margin a scaling factor around the smallest bound
   * @return integer $zoom
   * @author fabriceb
   * @since 2009-05-02
   */
  public function getMarkersFittingZoom($margin = 0)
  {
    // only one marker ? use a nice default zoom : 14
    if (count($this->markers) < 2)
    {
      
      return 14; 
    }
    
    $bounds = GMapBounds::getBoundsContainingMarkers($this->markers, $margin);
    
    return $bounds->getZoom(min($this->getWidth(),$this->getHeight()));
  }
  
  /**
   * sets the zoom of the map to fit the markers (uses mercator projection to guess the size in pixels of the bounds)
   * WARNING : this depends on the width in pixels of the resulting map
   * 
   * @param integer $margin a scaling factor around the smallest bound
   * @author fabriceb
   * @since 2009-05-02
   */
  public function zoomOnMarkers($margin = 0)
  {
    $this->setZoom($this->getMarkersFittingZoom($margin));
  }

   /**
   * sets the zoom and center of the map to fit the markers (uses mercator projection to guess the size in pixels of the bounds)
   * 
   * @param integer $margin a scaling factor around the smallest bound
   * @author fabriceb
   * @since 2009-05-02
   */
  public function centerAndZoomOnMarkers($margin = 0)
  {
    $this->centerOnMarkers();
    $this->zoomOnMarkers($margin);
  }
  
  /**
   * 
   * @return GMapBounds
   * @author fabriceb
   * @since Jun 2, 2009 fabriceb
   */
  public function getBoundsFromCenterAndZoom()
  {
  
    return GMapBounds::getBoundsFromCenterAndZoom($this->getCenterCoord(),$this->getZoom(),$this->getWidth(),$this->getHeight());
  }

}
