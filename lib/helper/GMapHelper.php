<?php

/**
 * includes the html of the Map container
 *
 * @param GMap $gMap
 * @param array style options of the container
 * @author fabriceb
 */
function include_map($gMap,$options=array())
{
  echo $gMap->getContainer($options);
}

/**
 * includes the javascript that initializes the Map
 *
 * @param GMap $gMap
 * @param array style options of the container
 * @author fabriceb
 */
function include_map_javascript($gMap)
{
  echo javascript_tag($gMap->getJavascript()); 
}

function include_search_location_form()
{
  sfContext::getInstance()->getResponse()->addJavascript('/sfEasyGMapPlugin/js/sfEasyGMapPlugin.js');
  ?>
  <form onsubmit="geocode_and_show(document.getElementById('search_location_input').value);return false;">
    <input type="text" id="search_location_input" />
    <input type="submit" id="search_location_submit" value="Search" />
  </form>
  <?php
}