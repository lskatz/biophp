<?
class Phred extends Run{
  public $option=array();
  public $filename="";
  function __construct($filename,$option=array()){
    $this->filename=$filename;
    parent::__construct();
  }
  function run($option=array()){
    $filename=$this->filename;
    $phredPath=($this->biophpConstant['phredPath'])?$this->biophpConstant['phredPath']:'phred';

    $command="$phredPath $filename ";
    $command.=$this->formatParameters($option);
    $command.=" 2>&1";
    $output=$this->command($command);
    return $output;
  }
}
?>
