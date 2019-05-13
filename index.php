<?php  
  // --------------------------------------------------------------------------------------
  // Simple photo gallery script v1.5
  // (c) 2019 Reto Da Forno
  // --------------------------------------------------------------------------------------

  // include config
  require('config.php');
  
  //error_reporting(E_ALL);
  //ini_set('display_errors', 1);
  ini_set('exif.encode_unicode', 'UTF-8');
  
  $photodir = "photo";
  $thumbsdir = "thumbs";
  $itemlistfn = "itemlist";
  $thumblistfn = "thumblist";
  $counterfn = "counter";
  $ipaddrfn = 'ipaddr';
  
  // login required?
  if($password != "") {
    session_start();
    if(!isset($_SESSION['key'])) {
      if(isset($_POST['key']) && ($_POST['key'] == $password)) {
        $_SESSION['key'] = sha1($password);
      } else {
        // display login form
        echo "<style>
                input[type=password] { width: 200px; padding: 6px 12px; margin: 8px 0; display: inline-block; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
                input[type=submit] { width: 80px; background-color: #4CAF50; color: white; padding: 6px 12px; margin-left: 10px; border: none; border-radius: 4px; cursor: pointer; }
                input[type=submit]:hover { background-color: #45a049; }
              </style>
              <div id=loginform><center><br /><br /><form action=index.php method=post><input type=password name=key placeholder=password /> <input type=submit value=login /></form></center></div>";
        exit();
      }
    }
    // if password is used: rename the photo directory and adjust filenames
    $newphotodir = $photodir."_".$_SESSION['key'];
    if(is_dir($photodir)) {
      rename($photodir, $newphotodir);
    }
    $photodir = $newphotodir;
    $thumbsdir .= "_".$_SESSION['key'];
    $itemlistfn .= "_".$_SESSION['key'];
    $thumblistfn .= "_".$_SESSION['key'];
    $counterfn .= "_".$_SESSION['key'];
    $ipaddrfn .= "_".$_SESSION['key'];
  }
  
  // check if the photo folder exists
  if(!is_dir($photodir)) {
    echo "photo folder not found!";
    exit();
  }
  
  // used to create the thumbnails
  class SimpleJPEG {
    var $image;
    function load($filename)                  { if(getimagesize($filename)[2] == IMAGETYPE_JPEG) $this->image = imagecreatefromjpeg($filename); }
    function save($filename, $compression=85) { imagejpeg($this->image, $filename, $compression); }
    function getWidth()                       { return imagesx($this->image); }
    function getHeight()                      { return imagesy($this->image); }
    function orientationLandscape()           { return ($this->getWidth() > $this->getHeight()); }
    function resizeToHeight($height)          { $this->resize($this->getWidth() * ($height / $this->getHeight()), $height); }
    function resizeToWidth($width)            { $this->resize($width, $this->getheight() * ($width / $this->getWidth())); }
    function scale($scale)                    { $this->resize($this->getWidth() * $scale / 100, $this->getheight() * $scale / 100); }
    function resize($width, $height) {
      $new_image = imagecreatetruecolor($width, $height);
      imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
      $this->image = $new_image;   
    }
  }
  
  function incrementCounter($dlCounter) {
    $filecontent = file_get_contents($counterfn);
    $counter = explode(",", $filecontent);
    if(count($counter) == 1) {
      $counter[1] = 0;
    }
    if($dlCounter) {
      $counter[1] = intval($counter[1]) + 1;
    } else {
      $counter[0] = intval($counter[0]) + 1;
    }
    file_put_contents($counterfn, implode(",", $counter));
    return $counter[0];
  }
  
  // to download all photos as a zip file
  if($download_filename != "" && isset($_GET['q']) && $_GET['q'] == 'dl') {
    $zipname = $download_filename;
    $zip = new ZipArchive;
    if(!file_exists($zipname)) {
      if($zip->open($zipname, ZipArchive::CREATE) === true) {
        if($handle = opendir($photodir)) {
          while(false !== ($entry = readdir($handle))) {
            if($entry != "." && $entry != "..") { // && !strstr($entry,'.php')) {
              $zip->addFile("$photodir/".$entry);
            }
          }
          closedir($handle);
        }
        $zip->close();
      } else {
        echo "failed to open zip file";
        exit();
      }
    }
    header("Content-Type: application/zip");
    header("Content-Disposition: attachment; filename='".$zipname."'");
    header("Content-Length: ".filesize($zipname));
    header("Content-Transfer-Encoding: binary");
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Location: ".$zipname);    // alternatively: readfile($zipname);    
    incrementCounter(true);           // update stats
    exit();
  }
   
  // check if thumbs exist, if not -> create thumbnails
  if(!is_dir($thumbsdir)) {
    mkdir($thumbsdir);
    unlink($itemlistfn);
    unlink($thumblistfn);
    $handle = opendir($photodir);
    while(false !== ($filename = readdir($handle))) {
      if($filename != "." && $filename != "..") {
        // filename randomization required?
        if($randomize_filenames && (strlen($filename) != 44)) {   // to avoid rehashing (sha1 generates 40 chars)
          $newfilename = sha1($filename.mt_rand());
          rename("$photodir/$filename", "$photodir/$newfilename.jpg");
          echo "file ".$filename." renamed to ".$newfilename.".jpg<br />";
          $filename = $newfilename.".jpg";
        }
        $image = new SimpleJPEG();
        $image->load("$photodir/$filename");
        // scale short side to $thumbsize
        if($image->orientationLandscape()) {
          $image->resizeToHeight($thumbsize);
        } else {
          $image->resizeToWidth($thumbsize); 
        }
        $image->save("$thumbsdir/$filename");
        echo "thumbnail for file $filename generated<br />";
      }
    }
    echo "thumbs generated!<br />";
  }
  
  // item list existing?
  if(!file_exists($itemlistfn)) {
    $itemlist = "";
    $thumblist = "";
    $files = scandir("$photodir");
    $filecnt = 0;
    foreach($files as $file) {
      if($file == '.' || $file == '..') {
        continue;
      }
      $thumblist .= "<img src='$thumbsdir/$file' onload='$(this).fadeIn(800)' onclick='openPhotoSwipe($filecnt)' class='thumb' \n />";  // don't add newline at the end
      $filecnt = $filecnt + 1;
      list($width, $height) = getimagesize("$photodir/$file");
      $itemlist .= "      { src: '$photodir/$file', w: $width, h: $height";
      $exif = exif_read_data("$photodir/$file", 'IFD0', true);
      $exif_info_string = "";
      $title = pathinfo($file, PATHINFO_FILENAME);
      if($exif !== false && !empty($exif['IFD0']['Make'])) {
        $focal_length = "";
        if(!empty($exif['EXIF']['FocalLength'])) {
          $focal_length = explode("/", $exif['EXIF']['FocalLength']);
          $focal_length = strval($focal_length[0] / $focal_length[1])."mm ";
        }
        if(!empty($exif['IFD0']['Title'])) {
          $title = mb_convert_encoding($exif['IFD0']['Title'] , 'UTF-8' , 'UTF-16LE');
        }
        if(!empty($exif['IFD0']['Comments'])) {
          $title .= " - ".$exif['IFD0']['Comments'];
        }
        if(!empty($exif['IFD0']['Keywords'])) {
          $title .= " - ".$exif['IFD0']['Keywords'];
        }
        $exif_info_string = "- ". /*$exif['IFD0']['Make']." ".*/ trim($exif['IFD0']['Model']).", ".$focal_length.$exif['COMPUTED']['ApertureFNumber'].", ".$exif['EXIF']['ExposureTime']."s, ISO".$exif['EXIF']['ISOSpeedRatings'];
      } 
      $itemlist .= ", title: '$title $exif_info_string' },\n";
    }
    file_put_contents($itemlistfn, $itemlist);
    file_put_contents($thumblistfn, $thumblist);
    echo "item list generated!<br />";
    exit;
  }
  $itemlist = file_get_contents($itemlistfn);
  $thumblist = file_get_contents($thumblistfn);
  
  // keep some stats
  if($collect_ipaddr) {
    $ip_addr = $_SERVER['REMOTE_ADDR']." ".date("Y-m-d H:i:s")."\n";
    file_put_contents($ipaddrfn, $ip_addr, FILE_APPEND | LOCK_EX);
  }
  $visitors = incrementCounter(false);
?>
<!DOCTYPE html>
<html>
<head>
  <title><?php echo $title; ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <!-- Core CSS file -->
  <link rel="stylesheet" href="photoswipe/photoswipe.css"> 
  <!-- Skin CSS file (styling of UI - buttons, caption, etc.)
       In the folder of skin CSS file there are also:
       - .png and .svg icons sprite, 
       - preloader.gif (for browsers that do not support CSS animations) -->
  <link rel="stylesheet" href="photoswipe/default-skin/default-skin.css"> 
  <!-- Core JS file -->
  <script src="photoswipe/photoswipe.min.js"></script>
  <!-- UI JS file -->
  <script src="photoswipe/photoswipe-ui-default.min.js"></script>
  <!-- JQuery -->
  <script src="jquery.js"></script>
  
  <style>
  body {
    font-family: "myriad-pro","Myriad Pro","Helvetica Neue",Helvetica,Arial,sans-serif;
    font-size: 18px;
    line-height: 26px;
    color: #282B30;
    background-color: #000000;
    text-rendering: optimizeLegibility;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    -moz-font-feature-settings: "liga", "kern";
  }
  .title {
    margin-top: 5px;
    margin-bottom: 25px;
    font-size: 24px;
    color: #ccc;
  }
  .header {
    margin-bottom: 30px;
    line-height: 20px;
    font-size: 14px;
    color: #ccc;
    clear: both;
<?php if($infotext == "") { echo "    display: none;"; } ?>
  }
  .footer {
    margin: 20px;
    line-height: 20px;
    font-size: 14px;
    color: #ccc;
    clear: both;
  }
  a {
    text-decoration: none;
    color: #ccc;
  }
  a:hover {
    color: #ffffff;
  }
  img:hover {
    cursor: -moz-zoom-in;
    cursor: -webkit-zoom-in;
    cursor: zoom-in;
  }
  .thumb {
<?php
  echo "    height: ".$thumbsize."px;\n";
  if($withspacing) {
    echo "    margin: 8px;\n    border: 5px #ffffff solid;\n";
  } else {
    echo "    margin: 0px;\n";
  }
  if($squarethumbs) {
    echo "    width: ".$thumbsize."px;\n    object-fit: cover;\n";
  }
?>
    padding: 0px;
    display: none;
  }
  .thumblist {
    display: inline-block;
    clear: both;
    line-height: 0;
  }
  </style>
  
  <script>
    //<!--
    // build items array
    var items = [
<?php echo $itemlist; ?>
    ];
      
    var openPhotoSwipe = function(idx) {
      var pswpElement = document.querySelectorAll('.pswp')[0];
      // define options (if needed)
      var options = {
        // history & focus options are disabled on CodePen
        history: false,
        focus: false,
        index: idx,
        showAnimationDuration: 1000,
        hideAnimationDuration: 500,
        showHideOpacity: true
      };
      var gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options);
      gallery.init();
    };
    //-->
  </script>
</head>
<body>

<br />
<center>
  <div class="title"><?php echo $title; ?></div>
  <div class="header">
  <?php echo $infotext; ?><br />
  </div>
  <div class="thumblist">
<?php echo $thumblist; ?>
  </div>
  <div class="footer">
    <?php if($download_filename != "") echo '<a href="index.php?q=dl" target="new">Download all photos as zip archive.</a><br />'; ?>
    <br />
    <?php echo $copyright; ?><br />
  </div>
  <?php echo $visitors."\n"; ?>
</center>

<!-- Root element of PhotoSwipe. Must have class pswp. -->
<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">
  <!-- Background of PhotoSwipe. 
       It's a separate element as animating opacity is faster than rgba(). -->
  <div class="pswp__bg"></div>
  <!-- Slides wrapper with overflow:hidden. -->
  <div class="pswp__scroll-wrap">
    <!-- Container that holds slides. 
        PhotoSwipe keeps only 3 of them in the DOM to save memory.
        Don't modify these 3 pswp__item elements, data is added later on. -->
    <div class="pswp__container">
      <div class="pswp__item"></div>
      <div class="pswp__item"></div>
      <div class="pswp__item"></div>
    </div>
    <!-- Default (PhotoSwipeUI_Default) interface on top of sliding area. Can be changed. -->
    <div class="pswp__ui pswp__ui--hidden">
      <div class="pswp__top-bar">
        <!--  Controls are self-explanatory. Order can be changed. -->
        <div class="pswp__counter"></div>
        <button class="pswp__button pswp__button--close" title="Close (Esc)"></button>
        <button class="pswp__button pswp__button--share" title="Share"></button>
        <button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>
        <button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>
        <!-- Preloader demo http://codepen.io/dimsemenov/pen/yyBWoR -->
        <!-- element will get class pswp__preloader--active when preloader is running -->
        <div class="pswp__preloader">
          <div class="pswp__preloader__icn">
            <div class="pswp__preloader__cut">
              <div class="pswp__preloader__donut"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
        <div class="pswp__share-tooltip"></div> 
      </div>
      <button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)">
      </button>
      <button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)">
      </button>
      <div class="pswp__caption">
        <div class="pswp__caption__center"></div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
