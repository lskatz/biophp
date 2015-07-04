<?
class Run extends Bio{
  function __construct(){
    parent::__construct();
  }
  function __destruct(){
    parent::__destruct();
  }
  // maybe I should eventually make a mauve object
  function mauveAligner($inputFastaFilenameArr,$option=array()){
    $defaultOption=array(
	'--backbone-size'=>50,
	'--max-backbone-gap'=>50,
      );
    $option=array_merge($defaultOption,$option);

    /*
    # check for required options
    $requiredParameters=array('--output-alignment');
    foreach($requiredParameters as $required){
      if(!in_array($required,$option)){
        print_r($option);
	print "=>$option[$required]\n";
        Bio::warn("You did not specify a required option ($required) for ".__FUNCTION__.".","Error: ",1);
      }
    }
    */

    #format parameters
    $command="mauveAligner ";
    $command.=$this->formatParameters($option,"=");

    # format command
    foreach($inputFastaFilenameArr as $value){
      $command.="$value $value.sml ";
    }

    $this->command($command);   
  }

  /*
   * formats parameters for commands.
   * Parameters with a false value will not have a value.
   *
   */
  function formatParameters($paramArr,$flagLink=" ",$flagPrefix='-'){
    $prefixLength=strlen($flagPrefix);
    unset($paramArr['debug']);
    $out="";
    foreach($paramArr as $key=>$value){
      if(substr($key,0,$prefixLength)!=$flagPrefix) $key=$flagPrefix.$key;
      $out.="$key$flagLink$value ";
    }
    return $out;
  }
  function getParameter($needle,$haystack,$flagPrefix='-'){
    $needle=preg_replace('/^'.$flagPrefix.'/','',$needle);
    foreach($haystack as $parameter=>$value){
      if(preg_replace('/^'.$flagPrefix.'/','',$parameter)==$needle)
        return $value;
    }
    return false;
  }
  /*
   * executes a command using exec
   * Issues a warning if there was an error in the command
   * Returns a string from the output
   */
  function command($command,$debug=false){
    if($debug==true) print trim($command)."\n";
    exec($command,$out,$exitCode);
    $out=join("\n",$out);
    if($exitCode>0){
      $this->warn("The command was\n  $command\nThe system's response was\n  $out","Biophp executing error (error code $exitCode):\n",$exitCode);
    }
    return $out;
  }
}
?>
