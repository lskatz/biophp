<?
class Seq extends Bio{
  var $fastaArr; # assoc array with fasta information

  // $options: ignoreGaps
  var $option;
  function __construct($fastaArr,$option=array()){
    parent::__construct();
    
    # if $fastaArr is an object, turn it into a string
    if(is_object($fastaArr) && get_class($fastaArr)=='Seq'){
      $seqio=new SeqIO($fastaArr->fasta(),$fastaArr->option);
      $seq=$seqio->nextSeq();
      $fastaArr=$seq->fastaArr;
    }

    $defaultOption=array(
       'ignoreGaps'=>false,
	   'protein'=>false,
    );
    $option=array_merge($defaultOption,$option);

    # straightforward way of constructing a Seq is from an array
    if(is_array($fastaArr)){
      $this->fastaArr=$fastaArr;
    }
    # another way to construct a Seq is by passing it a parameter 
    # that would normally be passed to SeqIO
    elseif(is_string($fastaArr)){
      $seqio=new SeqIO($fastaArr);
      $seq=$seqio->nextSeq();
      $this->__construct($seq->toArray(),$option);
    }
    else{
      Bio::warn("Object of class Seq is empty");
      $this->fastaArr=array();
    }

    /* //I've decided not to check
    // if it's false for protein, check anyway
    if(!$option['protein']){
      if(preg_match('/[^ATGCNX\-\*\s]/i',$fastaArr['sequence'])){
        $option['protein']=true;
      }
      elseif(preg_match('/>/',$fastaArr['sequence'])){
        $this->warn($fastaArr['sequence'],"Is a defline, not a sequence");
      }
      else{
        $option['protein']=false;
      }
    }
    */
    $this->option=$option;
  }
  
  /**
   * Reverse compliments the sequence.
   * Returns nothing
   */
  function revcomp(){
    $sequence=$this->sequence();
    // construct reverse complements
    if($this->protein()===false){
      $tr_from='acgtrymkswhbvdnxACGTRYMKSWHBVDNX';
      $tr_to = 'tgcayrkmswdvbhnxTGCAYRKMSWDVBHNX';
    }
    else{
      $this->warn("Cannot reverse complement an amino acid sequence.");
      return false;
    }
    $complement=array();
    $numBases=strlen($tr_from);
    for($i=0;$i<$numBases;$i++){
      $complement[substr($tr_from,$i,1)]=substr($tr_to,$i,1);
    }
    
    // construct complement
    $newSequence="";
    $length=strlen($sequence);
    for($i=0;$i<$length;$i++){
      $base=substr($sequence,$i,1);
      $newSequence.=$complement[$base];
    }
    $newSequence=strrev($newSequence);
    $this->fastaArr['sequence']=$newSequence;

    // be sure that any temporary file reflects the new change
    $this->writeTemporaryFile();
  }
  function sequence(){
    $sequence=$this->fastaArr['sequence'];
    return $sequence;
  }
  /**
   * return a substr of the sequence
   * zero-based coordinates
   */
  function subseq($start=false,$end=false){
    if(!$start) $start=0;
    if(!$end) $end=strlen($this->sequence());
    if($end<$start) $this->swap($start,$end);
    return $this->substr($start,($end-$start+1));
  }
  function substr($start,$length){
    if($start<0) $start=0;
    $return="";
    $stop=$start+$length;
    $return.=substr($this->sequence(),$start,$length);
    if($this->option['ignoreGaps']==true){
      $return=preg_replace('/[\-\*]+/','',$return);
    }
    return $return;
  }

  function protein(){
    return $this->option['protein'];
  }
  function defline(){
    return $this->fastaArr['defline'];
  }
  function id(){
    return $this->fastaArr['id'];
  }
  function removeBase($pos){
    $this->fastaArr['sequence']=$this->subseq(0,$pos-1).$this->subseq($pos+1);
    return true;
  }
  function fasta($start=false,$stop=false){
    $fasta=">".$this->defline();
    if($start)
      $fasta.="  start|$start|stop|$stop";
    $fasta.="\n".$this->subseq($start,$stop);
    return $fasta;
  }
  /**
   * return a clustal style sequence
   */
  function clustal($start=false,$stop=false){
    $return=$this->id()."  ".$this->subseq($start,$stop);
    return $return;
  }
  
  function toArray(){
    return $this->fastaArr;
  }
  function writeTemporaryFile(){
    /*
  	$dir=$this->biophpDir();
    $filename=rand(0,9999999) . ".seq.fna";
    $path="$dir/temp/$filename";
	*/
    $fasta=$this->fasta();
    $path=parent::writeTemporaryFile($fasta);
    $this->filename=$path;
    return $path;
  }

  # magic functions
  function __toString(){
    return $this->fasta();
  }
  function __destruct(){
    parent::__destruct();
  }
}
?>
