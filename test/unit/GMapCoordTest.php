<?php
/**
 * Teste la sauvegarde d'équipes dans le backend
 * @author fabriceb
 * @since Feb 16, 2009 fabriceb
 */
include(dirname(__FILE__).'/../bootstrap/unit.php');
//$app='frontend';
//include(dirname(__FILE__).'/../bootstrap/functional.php');


$t = new lime_test(4, new lime_output_color());

$t->diag('GMapCoords Tests');

$lat = 0;
$lng =  0;
$zoom = 0;
$pix = GMapCoord::fromLatToPix($lat, $zoom);
$t->is($pix,128,'Latitude 0 is at the middle of the map for zoom 0');
$pix = GMapCoord::fromLngToPix($lng, $zoom);
$t->is($pix,128,'Longitude 0 is at the middle of the map for zoom 0');


$lat = 0;
$lng =  -180;
$zoom = 12;
$pix = GMapCoord::fromLatToPix($lat, $zoom);
$t->is($pix,256*pow(2,$zoom-1),'Latitude 0 is at the middle of the map whatever the zoom');
$pix = GMapCoord::fromLngToPix($lng, $zoom);
$t->is($pix,0,'Longitude -180 is at the left of the map whathever the zoom');

