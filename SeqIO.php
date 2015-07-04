<?
class SeqIO extends Bio{
  var $option;
  var $filename; # filename can either be a filename or an actual sequence
  var $seq;
  var $numSequences;
  private $sequenceCounter=0; # keeping track of which sequence is current
  function __construct($filename,$optionArr=array()){
    parent::__construct();

    $defaultOption=array('type'=>'fasta','truncateSeqId'=>PHP_INT_MAX);
    $option=array_merge($defaultOption,$optionArr);
    // if $filename is already a seqio
    if(is_object($filename) && get_class($filename)=='SeqIO'){
      $option=array_merge($option,$filename->option);
      $filename=$filename->writeTemporaryFile();
    }
    
    $this->filename=substr($filename,0,200); #limited to x chars
    $this->setOptions($option);

    if($option['type']=='fasta'){
      $this->seq=$this->getFasta($filename);
    }
    else{
      $this->warn("unrecognized type: $option[type]\n");
    }
    
    if(isset($this->seq)){
      $this->numSequences=count($this->seq);
      # if the sequences are set, then the index can be built.
      $this->buildIndex();
    }
  }
  /**
   * writes to a temporary file and renames the filename
   */
  function writeTemporaryFile(){
    $fasta=$this->fasta();
    return parent::writeTemporaryFile($fasta);
  }
  function fasta(){
    $fasta="";
    while(false!==($seq=$this->nextSeq())){
      $fasta.=$seq->fasta()."\n";
    }
    return $fasta;
  }
  /**
   * Returns the next sequence as a seq object
   * Example usage: while(false!==($seq=$seqio->nextSeq())){ ... }
   *
   */
  function nextSeq(){
    if($this->sequenceCounter < $this->numSequences){
      $this->sequenceCounter++; # advance to the next sequence
      $i=$this->sequenceCounter-1;
      return $this->seq($i); # return next's previous sequence
    }
    $this->sequenceCounter=0; #untested statement
    return false;
  }
  /** reset resets the internal pointer of the seqio object
   */
  function reset(){
    $this->buildIndex();
    $this->sequenceCounter=0;
    $this->numSequences=count($this->seq);
  }
  /**
   * remove sequences from the seqio.
   * $id can be a string or an array
   * Probably can be optimized using the SeqIO index
   */
  function removeSeq($id){
    if(!is_array($id)){
      $id=array($id);
    }
    for($i=0;$i<count($this->seq);$i++){
      if(in_array($this->seq[$i]->id(),$id)){
        array_splice($this->seq,$i,1);
	$i=0;
      }
    }
    $this->reset();
  }
  function setOptions($option){
    $this->option=$option;
  }
  function getFasta($filename){
    # either it's a real filename or it's an actual fasta string
    if(file_exists($filename)){
      $fasta=trim(file_get_contents($filename));
      $fasta=file($filename);
    }
    else{
      $fasta=explode("\n",$filename);
    }
    $fasta=array_map("trim",$fasta);
    
    # remove commented lines
    $numLines=count($fasta);
    $fasta=array_map('trim',$fasta);
    for($i=0;$i<$numLines;$i++){
      $fasta[$i]=preg_replace('/[#;].*/s','',$fasta[$i]);
      if(!$fasta[$i]){
        array_splice($fasta,$i,1);
        $i--;
        $numLines=count($fasta);
      }
    }
    $fasta=join("\n",$fasta);
    
    // just split on > at the beginning of each line
    $seq=preg_split('/^\s*>/m',$fasta);
	$numFastas=count($seq);
    $fastaArr=array(); // default return value
	for($i=0;$i<$numFastas;$i++){
		$tmpFasta=split("\n",$seq[$i]);
		$fastaArr[$i]['defline']=array_shift($tmpFasta);
		$fastaArr[$i]['sequence']=join("",$tmpFasta);
		$fastaArr[$i]['id']=$fastaArr[$i]['defline'];
		
		if(preg_match('/(\S+)\s*/',$fastaArr[$i]['defline'],$match)){
			$fastaArr[$i]['id']=$match[1];
		}
		
		$fastaArr[$i]=new Seq($fastaArr[$i],$this->option);
	}
	array_shift($fastaArr); // there will always be an empty one at first due to preg_split
	
    return $fastaArr;
  }
  /**
   * builds an index based on the object's sequences
   */
  function buildIndex(){
    $length=$this->option['truncateSeqId']; // should build based off of truncated ids
    $num=count($this->seq);
    $index=array();
    for($i=0;$i<$num;$i++){
      $id=$this->seq[$i]->id();
      $id=substr($id,0,$length);
      $index[$id]=$i;
    }
    $this->idIndex=$index;
  }
  /**
   * find a sequence given its id (only if an index is built)
   */
  function findSeq($id){
    if(!isset($this->idIndex[$id]))
      return false;
    $i=$this->idIndex[$id];
    return $this->seq($i);
  }
  /**
   * return all sequence objects or just one
   */
  function seq($i=false){
    if($i===false)
      return $this->seq;

    // need the while loop in case $i is negative.
    // therefore a negative index can be accessed.
    // For example, -1 + $numSequences gives the last index of $seq.
    while($i<0){
      $numSequences=$this->numSequences;
      $i+=$numSequences-1;
    }
    return $this->seq[$i];
  }
  function __toString(){
    $fasta="";
    for($i=0;$i<$this->numSequences;$i++){
      $seq=$this->seq($i);
      $fasta.=$seq->fasta()."\n";
    }
    return $fasta;
  }
  // internalizing blast programs
  /**
   * performs bl2seq on first two sequences.
   * i and j can be passed into the parameters for indexes of 
   * seq1 and seq2 optionally.
   */
  function bl2seq($bl2seqParam=array()){
    $n=new NCBI();
    if(false===($i=$n->getParameter('i',$bl2seqParam)))
      $i=0;
    if(false===($j=$n->getParameter('j',$bl2seqParam)))
      $j=1;

    return $n->bl2seq($this->seq($i),$this->seq($j),$bl2seqParam);
  }
}
?>
