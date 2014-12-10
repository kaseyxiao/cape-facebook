<?php
require_once('my-facebook.php');
require_once('db.php');
require_once('pages.php');

session_start();

if ( !isset($_SESSION['PAGE_LOAD']) || $_SESSION['PAGE_LOAD'] == 'NOT-LOGGED' ) {
  $_SESSION['PAGE_LOAD'] = 'first';
}

// no cache
header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1.
header('Pragma: no-cache'); // HTTP 1.0.
header('Expires: 0'); // Proxies.

$scheme = "http://";
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
  $scheme = "https://";
}

$self = $scheme . $_SERVER['HTTP_HOST'];// . "//" . $_SERVER['REQUEST_URI'];


$fb = new MyFacebook;
$db = new DB;

// user requested to end

if ( isset($_GET['end']) && $_GET['end'] ) {
  $_SESSION['PAGE_LOAD'] = 'last';
}

// user requested a logout

if ( isset($_GET['logout']) && $_GET['logout'] ) {
  $fb->fb->destroySession();
  session_unset();
  header("Location: $self/");
  exit(0);
}


// user must login

if( ! $fb->isLogged() ) {
  $_SESSION['PAGE_LOAD'] = 'NOT-LOGGED';
  printLogin( $fb->fb->getLoginUrl( array( 'scope' => 'public_profile, user_friends, user_photos, friends_photos') ) );
  exit(0);
}

// here the user is logged
$db->addUser( $fb->getUserID(), $fb->getUserName() ) ; // add this user as app user


// submitting data

if ( count($_POST) > 0 ) { 

  // submitting the feedback
  foreach ( $_POST as $k => $v ) {
    if ( preg_match('/^f-(.+)$/', $k, $matches) ) {
      $db->addFeedback($fb->getUserID(), $matches[1], $v);
    }
  }
  
  // submitting PE Score
  if ( isset($_POST['update']) && $_POST['update'] ) {
    $peScoreFunc = Array( $db, 'updatePEScore');
  }
  else {
    $peScoreFunc = Array( $db, 'addPEScore');
  }
  foreach ( $_POST as $k => $v ) {
    if ( preg_match('/^pe-([0-9]+)$/', $k, $matches) ) {
      $peScoreFunc($fb->getUserID(), $matches[1], $v);
    }
  }

  // submitting I Score
  $iscore = array();
  foreach ( $_POST as $k => $v ) {
    if ( preg_match('/^i-([^-]+)-(.*)$/', $k, $matches) ) {
      $photoID = $matches[2];
      $group = $matches[1];
      $iscore[$photoID][$group] = $v;
    }
  }
  foreach($iscore as $pid => $v) {
    $db->addIScore($fb->getUserID(), $pid, $v['0'], $v['1'], $v['2'], $v['inf']);
  }

  // we don't want to repost data if user refreshes the page
  header("Location: $self/"); 
  exit(0);
}


// setup the page

$logoutUrl = $fb->fb->getLogoutUrl(array( 'next' => "$self/?logout=true" )); 
$page = new Page($fb->getUserName(), $fb->getUserID(), $logoutUrl, $self);



if ( count($_GET) > 0 ) {
  if ( isset($_GET['end']) ) {
    $page->requireUpdate();
    $page->render();
  }
  if ( isset($_GET['updatePE']) ) {
    $scores = $db->getPeScores($fb->getUserID());
    $page->debug("scores", $scores);
    $page->updatePeScore($scores);
    $page->render();      
  }
  header("Location: $self/"); // we like clean URLs
  exit(0);
}


// ask for the feedback

if ( $_SESSION['PAGE_LOAD'] == 'first' || $_SESSION['PAGE_LOAD'] == 'last' ) {
  $_SESSION['PAGE_LOAD'] = ( $_SESSION['PAGE_LOAD'] == 'first' ) ? 'middle' : 'first';

  $toFeedBack = $db->getPicturesToFeedBack( $fb->getUserID() );
  if ( count($toFeedBack) > 0 ) {
    $photoUrls = $fb->getPhotos( $toFeedBack );
    foreach ($photoUrls as $k => $v) {
      $photoUrls[$k]['decision'] = $toFeedBack[$v['pid']];
    }
    $page->evaluatePhotos( $photoUrls );
    $page->render();
  }
  else {
    if ($_SESSION['PAGE_LOAD'] == 'first') {
      $page->nothingToEvaluate();
      $page->render();
    }
  }
}

// access friend list

$friends = $fb->getFriends();
if ( count($friends) == 0 ) {
  $page->error('Seems that you have no friends, please submit a bug to the administrator');
  $page->render();
}

$page->debug("friend list", $friends);

$enabledFriends = $db->getEnabledFriends( array_keys($friends) );
if ( count($enabledFriends) == 0 ) {
  $page->info('Thank you', 'Thank you for your support. 
     Unfortunately, seems that no one of your friends is using this app, please came back later when some of your friend join this app.');
  $page->render();
}

$page->debug("enabled friends", $enabledFriends);

// PE-Score

$newFriends = $db->getNewFriends($fb->getUserID(), $enabledFriends);
if ( count($newFriends) > 0 ) {
  $page->peScore($newFriends);
  $page->render();
}

// I-Score

// 1) select pictures with me already rakned by my friends
// 2) select pictures with me and friends using this app not ranked yet

$photos = $fb->getPhotosWithFriends( $enabledFriends );
$alreadyTagged = $db->getAlreadyTaggedPhotos( $fb->getUserID() );
$friendsPhotos = $db->getPhotosTaggedByFriends( $enabledFriends );

$unscoredPhotos = array();
$toScore = array();
foreach($photos as $photo) {
  $id = $photo['pid'];
  if ( isset($alreadyTagged[$id]) ) {
    continue;
  }
  if ( isset($friendsPhotos["$id"]) ) {
    $toScore[] = $photo;
    continue;
  }
  $unscoredPhotos []= $photo;
}

shuffle($toScore);
shuffle($unscoredPhotos);
$toScore = array_merge($toScore, $unscoredPhotos);
$toScore = array_slice($toScore, 0 , 5);

if ( count($toScore ) > 0 ) {
  $page->iScore($toScore);
  $page->render();  
  exit(0);
}

// no more pictures to rank, go to the feedback page

$_SESSION['PAGE_LOAD'] = 'last';
header("Location: $self/");
exit(0);

//$page->info("Thank you", "Thank you, now you tagged all the pictures we need, please came back later if some of your friend join this app.");
//$page->render();  
  
?>
