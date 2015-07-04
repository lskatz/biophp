<?
class Clustal extends Run{
  var $alnio; # alignio object
  var $dnd; # dnd file contents
  function Clustal(){
    parent::__construct();
  }
  function __destruct(){
    parent::__destruct();
  }
  /**
   * runs clustalw
   * Probably needs to be refined according to possible options.
   * Returns an alignio object for further analysis.
   */
  function clustalw($option){
    if(!$option['INFILE']){
      $this->warn("INFILE parameter missing for clustalw.");
      return false;
    }
    // explicitly get the fasta option because alignio is not
    // that sophisticated
    $option['output']='fasta';
    // add - to each option
    foreach($option as $key=>$value){
      if(substr($key,0,1)!='-'){
        unset($option[$key]);
	$key='-'.$key;
	$option[$key]=$value;
      }
    }
    $parameter=$this->formatParameters($option,'=');
    $command="clustalw $parameter 2>&1";
    $this->command($command);

    $infileExt=preg_replace('/^.+\./','.',$option['-INFILE']);
    $baseFilename=basename($option['-INFILE'],$infileExt);
    $baseDir=dirname($option['-INFILE']);
    $basePath="$baseDir/$baseFilename";
    $this->dnd=file_get_contents("$basePath.dnd");
    $this->alnio=new AlignIO("$basePath.fasta");

    # adding a file to the temp list will ensure its deletion later
    # through the __destruct function
    $this->addFileToTempList("$basePath.dnd");
    $this->addFileToTempList("$basePath.fasta");

    return $this->alnio;
  }
}
?>
