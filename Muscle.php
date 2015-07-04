<?
class Muscle extends Run{
  var $option;
  var $alignment; # resultant alignment object
  var $seqio;
  var $tempId;
  function __construct($seqio,$option=array()){
    parent::__construct();

    $defaultOption=array('runNow'=>true);
    $option=array_merge($defaultOption,$option);
    $this->option=$option;
    $this->seqio=new SeqIO($seqio);
    $this->seqio->writeTemporaryFile();
    
    $this->tempId=rand(0,99999999);

    if($option['runNow']==true){
      $this->execute();
    }
  }
  function execute(){
    $option=$this->option;

    // ensure the alignment outfile will exist
    $alnFilename=$this->getParameter('out',$option);
    if(!$alnFilename){
      $alnFilename=$this->biophpDir().'/temp/'.$this->tempId.'.muscle.aln';
    }
    $option['out']=$alnFilename;

    $command="muscle -in ".$this->seqio->filename." ";
    unset($option['runNow']);
    $command.=$this->formatParameters($option);
    $command.=" 2>&1";
    $output=$this->command($command);
    $this->alignment=new Align($alnFilename);
    $this->output=$output;
    return $output;
  }
  public function result(){
    return $this->alignment;
  }
  function writeTemporaryFile($filename,$content){
    $dir=$this->biophpDir().'/temp';
    $path="$dir/$filename";
    return parent::writeTemporaryFile($path,$content);
  }
}
?>
