<?php
/**
 * iwDrupal.php
 * 
 * @version 0.0.7 - 2009-03-16
 * Drupal Integration to MediaWiki. MediaWiki is a master for user accounts and logging in.
 *
 * @author Anton Naumenko 2009
 * @copyright Copyright (c) 2009, Anton Naumenko
 * The following code was ananlyzed and reused:
 *
 * - Make a Drupal site use Basic Auth/ldap instead of the normal login block
 *   http://drupal.org/node/111768
 *
 * - AuthDrupal - Signin integration for MediaWiki as slave of Drupal.
 *   http://www.mediawiki.org/wiki/Extension:AuthDrupal
 *
 * @license GPLv2
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 *  59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 */

if (!defined('MEDIAWIKI')) {
    echo <<< EOT
To install iwDrupal extension, put the following line in LocalSettings.php:
include_once ($IP . '/extensions/di/iwDrupal.php');
EOT;
    exit(1);
}
require ("iwDrupalConfig.php");

$wgExtensionCredits['other'][] = array('name' => 'Drupal Integration',
    'author' => 'Anton Naumenko', 'description' =>
    'Drupal Integration to MediaWiki. MediaWiki is a master for user accounts and logging in.',
    'url' => 'http://designvmeste.com.ua/DrupalIntegration', 'version' => '0.0.7', );

$wgExtensionFunctions[] = 'wfDrupalIntegration';
/**
 * wfDrupalIntegration() subscribes for needed hooks
 *
 * @return none
 */
function wfDrupalIntegration() {
    global $wgHooks;
    $wgHooks['UserRights'][] = 'IwDrupal::userRights';
    $wgHooks['UserSetCookies'][] = 'IwDrupal::userSetCookies';
}

/**
 * IwDrupal serves as a namespace for functions and facade for manipulation of Drupal from MediaWiki.
 *
 * @access public
 */
class IwDrupal {
  /**
   * IwDrupal::userRights() delegates to process 
   * 1) addition of user to groups and 
   * 2) removal of user from groups
   * @see http://www.mediawiki.org/wiki/Manual:Hooks/UserRights
   *
   * @return true
   */
    static function userRights($user, $add, $remove) {
        self::addUserToGroupsInDrupal($user, $add);
        self::removeUserToGroupsInDrupal($user, $remove);
        return true;
    }
  /**
   * IwDrupal::addUserToGroupsInDrupal() propogates assignment of user-to-groups 
   * to user-to-roles relation in Drupal
   *
   * @todo move names of tables of Drupal to configuration parameters
   * @return Boolean true - when some groups added, false - when no groups to add
   */
    static function addUserToGroupsInDrupal($user, $add) {
        global $iwParameters;
        if (!$add)//no groups to process
            return false;
        $link = mysql_connect($iwParameters['DrupalDBserver'], $iwParameters['DrupalDBuser'],
            $iwParameters['DrupalDBpassword']);
        mysql_select_db($iwParameters['DrupalDBname'], $link) or die("cannot select db");
        //for each group in the array insert it as role into drupal and add user-to-role relation's record
        foreach ($add as $group_name) {
        	// first try to insert user. User can be created in MW but not yet propogated to drupal
            mysql_query("REPLACE INTO " . $iwParameters['DrupalDBprefix'] .
                "users (uid,wid,name,mail,status,created) values (" . (int)$user->getId() . ",'" .
                (int)$user->getId() . "','" . mysql_real_escape_string($user->getName()) . "','" .
                mysql_real_escape_string($user->getEmail()) . "',1," . time() . ")", $link);
            //second we insert group as role. It will not be inserted if exists in drupal.
            mysql_query("INSERT into " . $iwParameters['DrupalDBprefix'] .
                "role (name) values ('" . mysql_real_escape_string($group_name) . "')", $link);
            //last insert user-to-group assignment to user-to-role relation on Drupal
			mysql_query("INSERT into " . $iwParameters['DrupalDBprefix'] .
                "users_roles (uid, rid) SELECT " . (int)$user->getId() .
                ",rid from role r where r.name='" . mysql_real_escape_string($group_name) . "'",
                $link);
        }
        return true;
    }
  /**
   * IwDrupal::removeUserToGroupsInDrupal() removes corresponding user-to-roles assignments in Drupal
   *
   * @return Boolean true - when some groups removed, false - when no groups to remove
   */
    static function removeUserToGroupsInDrupal($user, $remove) {
        global $iwParameters;
        if (!$remove)//no groups to process
            return false;
        $link = mysql_connect($iwParameters['DrupalDBserver'], $iwParameters['DrupalDBuser'],
            $iwParameters['DrupalDBpassword']);
        mysql_select_db($iwParameters['DrupalDBname'], $link) or die("cannot select db");
        //for each group in the array remove user-to-role relation's record. We leave roles in Drupal untouched.
        foreach ($remove as $group_name) {
            mysql_query("delete from " . $iwParameters['DrupalDBprefix'] .
                "users_roles where uid = " . (int)$user->getId() .
                " and rid IN(SELECT rid from role r where r.name='" . mysql_real_escape_string($group_name) .
                "')", $link);
        }
        return true;
    }
  /**
   * IwDrupal::userSetCookies() executes each time user logs in. 
   * It creates new token for the user and saves it to DB and to cookies. 
   * The token is used by drupal for automated signing user in.
   * Side effect - the user is persistently logged in even without her intention.
   * If user uses public PC and does not log out, then other user can use her token to access wiki and drupal.
   * Good thing is that token is regenerated each time user signs in.
   * @todo provide token to drupal using session that expires soon instead of persistent cookie.
   * @todo move names of tables of Drupal to configuration parameters
   * @see http://www.mediawiki.org/wiki/Manual:Hooks/userSetCookies
   *
   * @return true
   */
    static function userSetCookies($user, &$session, &$cookies) {
        //$user->setToken();
        //$user->saveSettings();
        $cookies['Token'] = $user->mToken;
        return true;
    }
  /**
   * IwDrupal::createForumContainer() creates forum container. This function is not used currently, but
   * can be used in future to create forum containers for each wiki page instead of wiki discussion page. 
   * @todo move names of tables of Drupal to configuration parameters 
   *
   * @param string $name name for forum container
   * @param integer $parent id of parent forum container. By default it is 0 - no parent.
   * @return integer $forum_id id of created forum container. It can be used futher to link to it from wiki content.
   */
    static function createForumContainer($name, $parent = 0) {
        global $iwParameters;
        //connect to Drupal db and add forum container
        $link = mysql_connect($iwParameters['DrupalDBserver'], $iwParameters['DrupalDBuser'],
            $iwParameters['DrupalDBpassword']);
        mysql_select_db($iwParameters['DrupalDBname'], $link) or die("cannot select db");
        mysql_query("insert into " . $iwParameters['DrupalDBprefix'] .
            "term_data (vid,name) " . " select vid, '" . mysql_real_escape_string($name) .
            "' from " . $iwParameters['DrupalDBprefix'] . "vocabulary where module='forum'",
            $link);
        $forum_id = mysql_insert_id($link);
        if ($forum_id)
        //add information about hierarchy 
            mysql_query("insert into " . $iwParameters['DrupalDBprefix'] .
                "term_hierarchy (tid,parent) values ('" . (int)$forum_id . "','" . (int)$parent .
                "') ", $link);
        return $forum_id;
    }
}
