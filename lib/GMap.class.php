<?php

/**
 * Google Map class
 * @author Fabrice Bernhard
 */

class GMap
{
  // The API key provided by Google
  protected $api_key;
  // the name of the javascript Google Map object
  protected $js_name = 'map';
  // Starting zoom and center parameters.
  protected $zoom = 1;
  protected $center_lat=26.43;
  protected $center_lng=0;
  // id of the Google Map div container
  protected $container_id='map';
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

  /**
   * Constructs a Google Map PHP object
   *
   * @param Integer $zoom
   * @param Decimal $lat
   * @param Decimal $lng
   * @param array $options
   */
  public function __construct($zoom=null,$lat=null,$lng=null,$options=array())
  {
    // sets the starting zoom and center parameters
    if (!is_null($zoom))
    {
      $this->zoom=$zoom;
    }
    if (!is_null($lat) && !is_null($lng))
    {
      $this->setCenter($lat, $lng);
    }

    // delcare the Google Map Javascript object as global
    $this->addGlobalVariable($this->getJsName(),'null');

    // set the Google Map API key for the current domain
    $this->guessAPIKey();

    $default_options = array(
      'double_click_zoom'=>true,
      'control'=>'new google.maps.LargeMapControl()'
    );
    $this->options = array_merge($default_options,$options);

  }

  /**
   * Guesses the GoogleMap key for the current domain
   *
   */
  protected function guessAPIKey()
  {
    if (isset($_SERVER['SERVER_NAME']))
    {
      $this->setAPIKeyByDomain($_SERVER['SERVER_NAME']);
    }
    else if (isset($_SERVER['HTTP_HOST']))
    {
      $this->setAPIKeyByDomain($_SERVER['HTTP_HOST']);
    }
    else
    {
      $this->setAPIKeyByDomain('default');
    }
  }

  /**
   * Sets the Google Map API Key using the array_google_keys defined in the app.yml of your application
   * @param String $domain The domaine name
   */
  public function setAPIKeyByDomain($domain)
  {
    $api_keys = sfConfig::get('app_google_maps_api_keys');
    if (is_array($api_keys) && array_key_exists($domain,$api_keys))
    {
      $this->api_key=$api_keys[$domain];
    }
    else
    {
      if (array_key_exists('default',$api_keys))
      {
        $this->api_key=$api_keys['default'];
      }
      else
      {
        throw new sfException('No Google Map API key defined in the app.yml file of your application');
      }
    }
  }

  /**
   * Geocodes an address
   * @param String $address
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
   * @param String $address
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
   * @return String $js_name Javascript name of the googlemap
   */
  public function getJsName()
  {

    return $this->js_name;
  }

  /**
   * Sets the Google Maps API key
   * @param String $key
   */
  public function setAPIKey($key)
  {
    $this->api_key=$key;
  }
  /**
   * Gets the Google Maps API key
   * @return String $key
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
   * @param String $style_tag name of css tag
   * @param String $style_value value of css tag
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
   * @param String $style_tag name of css tag
   */
  public function getContainerStyle($style_tag)
  {

    return $this->container_style[$style_tag];
  }

  /**
   * returns the Html for the Google map container
   * @param Array $options Style options of the HTML container
   * @return String $container
   * @author Fabrice Bernhard
   */
  public function getContainer($options=array())
  {
    if (count($options)>0)
    {
      $this->setContainerStyles($options);
    }
    $style="";
    foreach ($this->container_style as $tag=>$val)
    {
      $style.=$tag.":".$val.";";
    }
    return
	  	'
	  	<div id="'.$this->container_id.'" style="'.$style.'">
	  	</div>
        ';
  }

  /**
   * Returns the Javascript for the Google map
   * @param Array $options
   * @return $string
   * @author Fabrice Bernhard
   */
  public function getJavascript()
  {
    sfContext::getInstance()->getResponse()->addJavascript($this->getGoogleJsUrl());

    $options = $this->options;

    $return ='';
    $init_events = array();
    $init_events[] = $this->getJsName().' = new google.maps.Map2(document.getElementById("'.$this->container_id.'"));';
    $init_events[] = $this->getJsName().'.setCenter(new google.maps.LatLng('.$this->getCenterLat().', '.$this->getCenterLng().'), '.$this->getZoom().');';
    if ($options['double_click_zoom'])
    {
      $init_events[] = $this->getJsName().'.enableDoubleClickZoom();';
    }
    if ($options['control']!='')
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
  google.load("maps", "2.x");
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
   * @return String $js_url
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
   * @return String
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
   * @return String
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
   * @return String
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
   * @return String $after_init_js
   */
  public function getAfterInitJs()
  {
    return $this->after_init_js;
  }
  /*
   * Sets the Code to execute after Js initialization
   * @param String $after_init_js Code to execute
   */
  public function addAfterInitJs($after_init_js)
  {
    array_push($this->after_init_js,$after_init_js);
  }

  public function getContainerId()
  {
    return $this->container_id;
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
   * @param Decimal $lat
   * @param Decimal $lng
   */
  public function setCenter($lat=null,$lng=null)
  {
    if (!is_null($lat))
    {
      $this->center_lat = $lat;
    }
    if (!is_null($lng))
    {
      $this->center_lng = $lng;
    }
  }
  public function getCenterLat()
  {

    return $this->center_lat;
  }
  public function getCenterLng()
  {
    return $this->center_lng;
  }
  public function getZoom()
  {

    return $this->zoom;
  }

  /**
   * Returns the url for a static version of the map (when javascript is not active)
   * Supports only markers and basic parameters: centre, zoom, size
   *
   * @param string $map_type = 'mobile'
   * @param string $hl Language (fr, en...)
   * @return string Url of the picture
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
   * @return String
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

}
