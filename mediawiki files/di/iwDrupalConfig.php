<?php
if (!defined('MEDIAWIKI'))
{
    echo <<< EOT
To install iwDrupal extension, put the following line in LocalSettings.php:
include_once ($IP . '/extensions/di/iwDrupal.php');
EOT;
    exit(1);
}
//initialize parameters array if it is not initialized yet.
//this is needed to avoid notices of array_key_exists invokations below
//if it is not set.
if (!isset($iwParameters))
    $iwParameters = array();
//Installation parameters
//Drupal config
if (!array_key_exists('DrupalDBserver', $iwParameters))
    $iwParameters['DrupalDBserver'] = "localhost";
if (!array_key_exists('DrupalDBuser', $iwParameters))
    $iwParameters['DrupalDBuser'] = "dRUPAL DB USER";
if (!array_key_exists('DrupalDBpassword', $iwParameters))
    $iwParameters['DrupalDBpassword'] = "DRUPAL DB PASSWORD";
if (!array_key_exists('DrupalDBname', $iwParameters))
    $iwParameters['DrupalDBname'] = "DB NAME";
if (!array_key_exists('DrupalDBprefix', $iwParameters))
    $iwParameters['DrupalDBprefix'] = "";

/* This can be moved/copied to LocalSettings.php to override
values that are set here above. This might be needed if you 
have multiple web sites and want to reuse the code of this extension
from one place.

    $iwParameters['DrupalDBserver'] = "localhost";
    $iwParameters['DrupalDBuser'] = "";
    $iwParameters['DrupalDBpassword'] = "";
    $iwParameters['DrupalDBname'] = "";
    $iwParameters['DrupalDBprefix'] = "";
*/