<?php

require_once('fb-sdk/facebook.php');

class MyFacebook {
  private $userID, $userProfile;
  public $fb;
  
  public function __construct() {
    $config = array(
		    'appId' => getenv('FACEBOOK_APP_ID'),
		    'secret' => getenv('FACEBOOK_SECRET'),
		    'fileUpload' => false, // optional
		    'allowSignedRequest' => false, // optional, but should be set to false for non-canvas apps
		    'cookie' => true,    
		    'oauth' => true,
		    );
    $this->fb = new Facebook($config);
    $this->userID = $this->fb->getUser();
  }

  public function getFriends() {
    $page = '/me/friends';
    $ret = array();
    while ( isset($page) ) {
      $friends = $this->fb->api($page);
      if (! isset($friends['data']) ) 
	break;
      foreach ($friends['data'] as $v) {
	$ret[ $v['id'] ] = $v['name']; //  array($v['id'], $v['name']):
      }
      $page = isset($friends['paging']) ? $friends['paging']['next'] : NULL;
    }
    return $ret;
  }
  
  public function getPhotos($toFeedBack) {
    $ids = array();
    foreach ($toFeedBack as $id => $res) {
      $ids []= $id;
    }
    $ids = implode(",", $ids);
    $str_fql = 
      "SELECT pid, src_big, src_small FROM photo WHERE pid IN (
         $ids
      )";
    $photos  = $this->fb->api( array( 'method' => 'fql.query', 'query' => $str_fql ));
    return $photos;
  }
  
  public function getPhotosWithFriends($friends) {
    $friendsID = array();
    foreach ($friends as $id => $name) {
      $friendsID []= $id;
    }
    $friendsID = implode(",", $friendsID);
    $str_fql = 
      "SELECT pid, src_big, src_small FROM photo WHERE pid IN (
         SELECT pid FROM photo_tag WHERE subject=me()
      )
      AND pid IN (
        SELECT pid FROM photo_tag WHERE subject in ( $friendsID )
      )";
    $photos  = $this->fb->api( array( 'method' => 'fql.query', 'query' => $str_fql ));
    return $photos;
  }
  
  public function isLogged() {
    if( ! $this->userID ) {
      return false;
    }
    try {
      $this->userProfile = $this->fb->api('/me','GET');
    } catch(FacebookApiException $e) {
      return false;
    }
    return true;
  }
  public function getUserID() {
    return $this->userProfile['id'];
  }
  public function getUserName() {
    return $this->userProfile['name'];
  } 
}

?>
