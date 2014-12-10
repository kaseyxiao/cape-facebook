<?php 

function renderPage($body) {
  echo <<<ENDL
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
      <title>CAPE - Collaborative Access control by considering Peer Effects</title>

      <script type="text/javascript" src="jquery-ui/js/jquery-1.10.2.js"></script>
      <script type="text/javascript" src="jquery-ui/js/jquery-ui-1.10.4.custom.js"></script>
      <script type="text/javascript" src="jquery-ui/js/jquery.cookie.js"></script>

      <link href="jquery-ui/css/redmond/jquery-ui-1.10.4.custom.css" rel="stylesheet" type="text/css" media="all" />
      <link href="style.css" rel="stylesheet" type="text/css" media="all" />

      <script type="text/javascript" src="colorbox/jquery.colorbox.js"></script>
      <link href="colorbox/colorbox.css" rel="stylesheet" type="text/css" media="all" />
      <script type="text/javascript">
	$( function() {
	   $('table a:has(img)').colorbox();
	});
      </script>

      <!-- <link rel="shortcut icon" href="images/favicon.png" /> -->
  </head>
  <body>
     $body
  </body>
</html>
ENDL;
}

function printLogin($login_url) {
$body = <<<ENDL
    <div id="header"><div id="headercontent"><h1>CAPE</h1></div></div>
    <div id="content">
    <h2>Collaborative Access control by considering Peer Effects</h2>
    <p>
      Please enable sharing <em>My photos</em> to <em>Apps others use</em> 
      in the Facebook 
      <a href="https://www.facebook.com/settings?tab=applications&section=friends_share&view#u_0_4">privacy settings</a> 
      and let your friends to see pictures you are tagged enabling 
      <em>Who can see posts you have been tagged in on your timeline?</em>
      in <a href="https://www.facebook.com/settings?tab=timeline&section=tagging&view">Timeline and Tagging settings</a>.
    </p><p>
      This app needs to find pictures where you are tagged together with friends. 
      Wihtout these permissions your friends that are running this app are not allowed to find pictures with you.
    </p><p>
      You can safely disable these permissions again after your friends collaborate tagging their pictures with you.
    </p><p>
      Thank you for your collaboration.
    </p>
    <p id="login">
    Please <a href="$login_url"><img src="img/login.png" alt="login with Facebook" /></a>
    </p>
    </div>
ENDL;
renderPage($body);
}

class Page {
  private $name, $id, $logoutUrl, $self;
  private $body;
  
  public function __construct($name, $id, $logoutUrl, $selfUrl) {
    $this->name = $name;
    $this->id = $id;
    $this->logoutUrl = $logoutUrl;
    $this->self = $selfUrl;
  }

  private function getUserHeader() {
    return <<<ENDL
     <div id="header">
       <div id="headercontent">
         <ul id="menubar">
           <li><a href="$this->self/?updatePE">Update PEScore</a></li>
         </ul>
         <div id="logged">
           <img src="https://graph.facebook.com/$this->id/picture" alt=""/>
           Hi $this->name. <br/>
           If you are not $this->name, please <a href="$this->logoutUrl">logout</a>
         </div>
       </div>
     </div>
ENDL;
  }

  public function render(){
    $body = $this->getUserHeader() . "<div id=\"content\">$this->body</div>";
    renderPage($body);
    exit(0);
  }

  public function info($msg, $msg2 = NULL) {
    if ( $msg2 == NULL ) {
      $this->body .= "<p>$msg</p>";
    }
    else {
      $this->body .= "<h4>$msg</h4>";
      $this->body .= "<p>$msg2</p>";      
    }
  }

  public function debug($msg, $arr = "") {
    return false; // comment this line to enable debug message
    if ( is_array($arr) ) {
      $this->body .= "<div class=\"debug\">";
      $this->body .= "<h4>$msg</h4>";
      $this->body .= '<pre>';
      $this->body .= print_r($arr, true);
      $this->body .= '</pre>';
      $this->body .= "</div>";
    }
    else
      $this->body .= "<p class=\"debug\">$msg<p>";
  }

  public function error($msg){
    $this->body .= "<p class=\"error\">$msg</p>";
  }

  public function requireUpdate() {
    $this->body .= <<<EOF
      <h3>Refreshing data</h3>
      <p>We are updading data, please wait <img src="img/ajax-loader.gif" alt="" /></p>
      <script type="text/javascript">
        $.ajax({
          url: 'refreshData.php',
          data: { 'uid' : '$this->id' },
          cache : false,
	  type: 'GET',
	  complete : function() { window.location.href = '$this->self'; }
        });
      </script>
EOF;
  }
  
  public function nothingToEvaluate() {
    $this->body .= <<<EOF
      <h3>Evaluate results</h3>
      <p>There is nothing to evaluate. Thank you for your effort.</p>
EOF;
  }

  public function evaluatePhotos($photoUrls) {
    $this->body .= <<<EOF
      <h3>Evaluate results</h3>
      <p>According to your and your friends scores, this is the privacy we suggest for your photos.</p>
      <p>Please, give us your feedback.</p>
      <form method="POST" action="$this->self/">
        <table id="feedback">
          <tr>
            <th>Photo</th>
            <th>Suggested privacy</th>
            <th>Do you agree?</th>
          </tr>
EOF;
    foreach($photoUrls as $photo) {
      $thumb = $photo['src_small'];
      $img = $photo['src_big'];
      $id = $photo['pid'];
      $decision = $photo['decision'];
    $this->body .= <<<EOF
      <tr>
	  <td><a href="$img"><img src="$thumb" alt="missing thumb" /></a></td>
          <td>$decision</td> 
          <td>
             <input type="radio" name="f-$id" id="f-$id-Y" value="t" /><label for="f-$id-Y">Yes</label>
             <br/>
             <input type="radio" name="f-$id" id="f-$id-N" value="f" /><label for="f-$id-N">No</label>
          </td>
      </tr>
EOF;
    }
    $this->body .= <<<EOF
        </table>
        <input type="submit" value="Submit"/>
      </form>
EOF;
  }

  const PeScoreSliderScript = <<<EOF
    <script type="text/javascript">
      $(function(){
	  $( '#pescore .slider' ).slider({
	    'step'  :   1, 
	    'min'   :   0, 
	    'max'   : 100,
	    'change': function( event, ui ) {
		var val = $(this).slider('value');
		$(this).parents('tr:first').find('input:text').val(val);
	      }
	  });
	  $( '#pescore .slider' ).each(function(){
	      $(this).slider('value', $(this).parents('tr:first').find('input:text').val() );
	  });
	  
      });
    </script>
EOF;

  function peScoreRowEntry($id, $name, $value = 50) {
    return <<<EOF
      <tr>
         <td><img src="https://graph.facebook.com/$id/picture" alt=""/></td>
         <td>$name</td>
         <td><div class="slider"></div></td>
	 <td><input type="text" readonly="readonly" name="pe-$id" value="$value"/></td>
      </tr>
EOF;
  }

  public function peScore($newFriends) {
    $script = self::PeScoreSliderScript;
    $this->body .= <<<EOF
      <h3>PE-Score</h3>
      <p>Change your decision to adapt to your friend's privacy preference</p>
    $script
    <form method="POST" action="$this->self/">
	<table id="pescore">
EOF;
  foreach ( $newFriends as $id => $name ) {
    $this->body .= $this->peScoreRowEntry($id, $name);
  }
  $this->body .= <<<EOF
        </table>
        <input type="submit" value="Submit"/>
    </form>
EOF;

  }

  public function updatePeScore($scores) {
    $script = self::PeScoreSliderScript;
    $this->body .= <<<EOF
      <h3>Update PE-Score</h3>
      <p>Change your decision to adapt to your friend's privacy preference</p>
      $script
      <form method="POST" action="$this->self/">
        <input type="hidden" name="update" value="true"/>
	<table id="pescore">
EOF;
  foreach ( $scores as $score ) {
    $this->body .= $this->peScoreRowEntry($score['id'], $score['name'], $score['pescore']);
  }
  $this->body .= <<<EOF
          </table>
          <input type="submit" value="Submit"/>
      </form>
EOF;
  }

  function iScore($photos) {
    $this->body .= <<<EOF
      <h3>I-Score</h3>
      <p>Select how much you agree to share each photo with the reference group from 0 to 5 where:<br/>
       <em>0</em>: Strongly Disagree <br/>
       <em>1</em>: Disagree <br/>
       <em>2</em>: Slightly Disagree <br/>
       <em>3</em>: Slightly Agree <br/>
       <em>4</em>: Agree <br/>
       <em>4</em>: Strongly Agree <br/>      
      </p>
      <script type="text/javascript">
      $(function(){
	  $( '#iscore .slider' ).slider({
	    'step'  : 1,
	    'min'   : 0,
	    'max'   : 5,
	    'change': function( event, ui ) {
	  	var val = $(this).slider('value');
	  	$(this).parents('tr:first').find('input:text').val(val);
	      }
	  });
	  $( '#iscore .slider' ).slider('value', 3);

          var eachPhoto = function(tr, vals) {
             tr.each(function(i){
                 $(this).find('.slider' ).each( function(i, v){ 
                       $(this).slider('value', vals[i]);
                 });
             });
          }

          $( '.makeDefault' ).click(function(){
             var vals = [];
             var inputs = $(this).parents('tr:first').find('input:text');
             inputs.each( function(i, v){ vals[i]=$(v).val(); } );
             eachPhoto( $(this).parents('tr:first').nextAll('tr') , vals);
             $.cookie('iscore', vals)
             return false;
          });

          var vals = $.cookie('iscore');
          if (vals) {
              vals = vals.split(',');
              eachPhoto($('#iscore table'), vals);
          }

      });
    </script>
    <form method="POST" action="$this->self/">
	<table id="iscore">
        <thead><tr><th colspan="3"><a href="/?end=true">I have had enough for today, let me finish</a></th></tr></thead>
	<tbody>
EOF;
    foreach($photos as $photo) {
      $thumb = $photo['src_small'];
      $img = $photo['src_big'];
      $id = $photo['pid'];
      $this->body .= <<<EOF
	<tr>
	  <td><a href="$img"><img src="$thumb" alt="missing thumb" /></a></td>
 	  <td>
            <table>
              <tr>
                <td>Private (0)</td>
  	        <td><div class="slider"></div></td>
  	        <td><input type="text" readonly="readonly" name="i-0-$id" value="0"/></td>
  	      </tr>
  	      <tr>
                <td>Friends (1)</td>
  	        <td><div class="slider"></div></td>
  	        <td><input type="text" readonly="readonly" name="i-1-$id" value="0"/></td>
  	      </tr>
  	      <tr>
                <td>Friends of Friends (2)</td>
  	        <td><div class="slider"></div></td>
  	        <td><input type="text" readonly="readonly" name="i-2-$id" value="0"/></td>
  	      </tr>
  	      <tr>
                <td>Public (+&infin;)</td>
  	        <td><div class="slider"></div></td>
  	        <td><input type="text" readonly="readonly" name="i-inf-$id" value="0"/></td>
  	      </tr>
            </table>
          </td>
          <td><a href="#" class="makeDefault">make<br/>default</a></td>
        </tr>

EOF;
    }
  $this->body .= <<<EOF
        <tbody>
        </table>
        <input type="submit" value="Submit"/>
    </form>
EOF;

  }
}