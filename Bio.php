<?
# class Bio.  Lee's version of biophp
if(!defined('BIOPHP_VERSION')){
  define('BIOPHP_VERSION','1.01');
}

class Bio{
  public $tempFile=array();
  // array of biophp "constants"
  public $biophpConstant=array();
  function __construct(){
    $dir=dirname(__FILE__);
    $this->biophpConstant['biophpDir']=$dir;
    // config is loaded after any default constants in case the user wants to override
    $this->loadConfig();
    
    // add onto the include path
	set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).PATH_SEPARATOR);
  }
  function __destruct(){
    foreach($this->tempFile as $filename){
      #print "Unsetting temporary file $filename.\n";
      if(file_exists($filename)){
        unlink($filename);
      }
    }
  }
  /**
   * writes temporary files and keeps track of them
   * for future deletion.
   */
  function writeTemporaryFile($content){
  	$filename=tempnam('/tmp','BIOPHP_');
    $bool=file_put_contents($filename,$content);
    if(!$bool){
      $this->warn("File could not be written to $filename");
      return false;
    }
    $this->addFileToTempList($filename);
    return $filename;
  }
  function addFileToTempList($filename){
    $this->tempFile[]=$filename;
  }
  function biophpDir(){
    return $this->biophpConstant['biophpDir'];
  }

  // utility functions
  function swap(&$i,&$j){
    $temp=$i;
    $i=$j;
    $j=$temp;
  }
    // write to stderr, and possibly exit with an exit code
  function warn($str,$prefix="Warning: ",$exitCode=0,$newline=true){
    $str="$prefix$str";
    if($newline) $str.="\n";
    $outFilename='php://output';
    /*STDERR;
    if($exitCode>0)
      $outFilename=STDOUT;
    */
    file_put_contents($outFilename,$str);
    
    if($exitCode>0){
      die($exitCode);
    }
  }
  // load the config file
  function loadConfig($filename='config.bio.txt'){
    $constant=array();
    $path=$this->biophpDir().DIRECTORY_SEPARATOR."$filename";
    if(!file_exists($path))
      return;
    
    // open the config file and read it
    $line=file($path);
    foreach($line as $varLine){
    	$varLine=trim($varLine);
    	// skip commented lines
    	if(!$varLine || preg_match('/^(#|\/\/|;)/',$varLine))
    	  continue;
    	  
    	list($var,$value)=preg_split('/=/',$varLine);
    	$constant[$var]=$value;
    }
    $this->biophpConstant+=$constant;
  }
}

// autoloading http://us3.php.net/manual/en/language.oop5.autoload.php
if(function_exists('__autoload')){
  Bio::warn("The __autoload function has previously been declared. biophp may not be able to load classes appropriately.");
}
else{
  function __autoload($class_name) {
    $include_path = get_include_path();
    $include_path_tokens = explode(PATH_SEPARATOR, $include_path);
    
    foreach($include_path_tokens as $prefix){
      $path[0] = $prefix . DIRECTORY_SEPARATOR . $class_name . '.php';
      $path[1]= $prefix . DIRECTORY_SEPARATOR . $class_name . '.class.php';
      foreach($path as $thisPath){
        if(file_exists($thisPath)){
          require_once $thisPath;
          return;
        }
      }
    }
  }
}

// constants
if(!defined('STDERR')){
	define('STDERR',"php://stderr");
}
if(!defined('STDOUT')){
	define('STDERR',"php://stdout");
}
if(!defined('STDIN')){
	define('STDERR',"php://stdin");
}
?>
