<?php

class DB {
  private $username, $password, $host, $dbname;
  private $dbconn;
  
  public function __construct() {
    $this->username = 'wqjuclpagbdpyf';
    $this->password = 'FMht_CqliNs6MTV22QHUmVjOgS';
    $this->host = 'ec2-184-73-194-196.compute-1.amazonaws.com';
    // 5432
    $this->dbname = 'd4agdaqc1h97l5';

    $this->connect();
  }

  public function __destruct() {
    pg_close($this->dbconn);
  }

  public function connect() {
    $conn_string = "host=$this->host port=5432 dbname=$this->dbname user=$this->username password=$this->password";
    $this->dbconn = pg_connect($conn_string);
    if ( ! $this->dbconn )
      die("database connection error");
  }

  public function addUser($userID, $userName) {
    $userID = pg_escape_string($userID);
    $query = "INSERT INTO users (id, name) VALUES ($1, $2)";
    @pg_query_params($this->dbconn, $query, array($userID, $userName)); //suppress error
  }
  
  public function getNewFriends($userID, $friendList) {
    $userID = pg_escape_string($userID);
    $list = array();
    foreach ($friendList as $id => $name) {
      $list []= "'".pg_escape_string($id)."'";
    }
    $list = implode(",", $list);
    $query = "SELECT user2 FROM pescore WHERE user1 = '$userID' AND user2 IN ( $list )"; //todo reverse
    
    $result = pg_query($this->dbconn, $query);
    if ($result) {
      while ($row = pg_fetch_row($result)) {
	unset( $friendList[ $row[0] ] );
      }
    }
    return $friendList;
    
  }

  public function getEnabledFriends($friendsID) {
    $list = array();
    foreach ($friendsID as $id) {
      $list []= "'".pg_escape_string($id)."'";
    }
    $list = implode(",", $list);
    $query = "SELECT id, name FROM users WHERE id IN  ( $list )";
    
    $res = array();
    $result = pg_query($this->dbconn, $query);
    if ($result) {
      while ($row = pg_fetch_row($result)) {
	$res[ $row[0] ]= $row[1];
      }
    }
    return $res;
  }
  
  public function getPEScores($user){
    $query = "SELECT s.user2 AS id, u.name AS name, score AS pescore FROM users u, pescore s WHERE s.user2 = u.id AND s.user1 = $1";
    $res = array();
    $result = pg_query_params($this->dbconn, $query, array($user) );
    if ($result) {
      while ($row = pg_fetch_row($result)) {
	$res[] = array('id' => $row[0], 'name' => $row[1], 'pescore' => $row[2]);
      }
    }
    return $res;
  }
  public function addPEScore($user1, $user2, $score){
    pg_query_params($this->dbconn, 'INSERT INTO pescore(user1, user2, score) VALUES($1, $2, $3)', array($user1, $user2, $score));
  }
  public function updatePEScore($user1, $user2, $score){
    pg_query_params($this->dbconn, 'UPDATE pescore SET score = $3 WHERE user1 = $1 AND user2 = $2', array($user1, $user2, $score));
  }

  public function addIScore($user, $photo, $private, $friends, $friendsOfFriends, $public){
    $query = 'INSERT INTO iscore(userid, photo, private, friends, fof, everyone) VALUES ($1, $2, $3, $4, $5, $6)';
    pg_query_params($this->dbconn, $query, array($user, $photo, $private, $friends, $friendsOfFriends, $public));
  }
  
  public function getAlreadyTaggedPhotos($user) {
    $query = "SELECT DISTINCT photo FROM iscore WHERE userid = $1";
    $res = array();
    $result = pg_query_params($this->dbconn, $query, array($user) );
    if ($result) {
      while ($row = pg_fetch_row($result)) {
	$res[$row[0]] = true;
      }
    }
    return $res;
  }

  public function getPhotosTaggedByFriends($friends) {
    $list = array();
    foreach ($friends as $id => $name) {
      $list []= "'".pg_escape_string($id)."'";
    }
    $list = implode(",", $list);
    
    $query = "SELECT DISTINCT photo FROM iscore WHERE userid in ( $list )";
    $result = pg_query($this->dbconn, $query );
    $res = array();
    if ($result) {
      while ($row = pg_fetch_row($result)) {
	$res[$row[0]] = true;
      }
    }
    return $res;
  }

  public function addFeedback($userID, $photoID, $value) {
    $query = "UPDATE results SET feedback = $3 WHERE userid = $1 AND photoid = $2";
    pg_query_params($this->dbconn, $query, array($userID, $photoID, $value) );
  }
  
  public function getPicturesToFeedBack( $userID ) {
    $query = "SELECT photoid, decision FROM results WHERE userid = $1 AND feedback IS NULL";
    $res = array();
    $result = pg_query_params($this->dbconn, $query, array($userID) );
    if ($result) {
      while ($row = pg_fetch_row($result)) {
	$res[$row[0]] = $row[1];
      }
    }
    return $res;
  }
}


/*


DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id VARCHAR(20) NOT NULL PRIMARY KEY ,
  name VARCHAR(30) NOT NULL 
);

DROP TABLE IF EXISTS pescore;
CREATE TABLE pescore (
    user1 VARCHAR(20) NOT NULL ,
    user2 VARCHAR(20) NOT NULL ,
    score INTEGER NOT NULL ,
    UNIQUE (user1, user2)
);
-- CREATE INDEX ON pescore USING hash (user1);

DROP TABLE IF EXISTS iscore;
CREATE TABLE iscore (
    userid VARCHAR(20) NOT NULL ,
    photo VARCHAR(50) NOT NULL ,
    private INTEGER NOT NULL ,
    friends INTEGER NOT NULL ,
    fof INTEGER NOT NULL ,
    everyone INTEGER NOT NULL ,
    UNIQUE (userid, photo)
);
-- CREATE INDEX ON iscore USING hash (photo);

DROP TABLE IF EXISTS results;
CREATE TABLE results (
    photoid VARCHAR(50) NOT NULL ,
    decision VARCHAR(20) NOT NULL,
    userid VARCHAR(20) NOT NULL ,
    feedback BOOLEAN DEFAULT NULL ,

    UNIQUE (userid, photoid)
);


*/

?>
