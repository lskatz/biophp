<?
class Align extends SeqIO{
  //var $seqio; # I recently changed the name for this var w/out testing...
  //var $type;
  //var $numSequences;
  function __construct($alignment){
    parent::__construct($alignment);
    return;
    $alignmentClass=get_class($alignment);
    if($alignmentClass=='SeqIO'){
      $this->seqio=$alignment;
      $this->type='fasta';
      $this->numSequences=$alignment->numSequences;
    }
    elseif(is_string($alignment)){
      $alignio=new AlignIO($alignment);
      $align=$alignio->nextAlignment();
    }
    else{
      die("Error: invalid argument given for class Align.");
    }
  }
  /**
   * trims away sequence information where there are gaps
   * in every seq but one
   * UNTESTED
   */
  function getTrimmed(){
    $gap='-';
    $seq=$this->seq();
    $numBases=strlen($seq[0]->sequence());
    $numSequences=count($seq);
    $numGapsForTrimming=5; # if this many gaps are found, then trim
    for($i=0;$i<$numBases;$i++){
      $numGaps=0;
      $shouldBeTrimmed=false;
      $numTrimmableBases=0; # if this number gets up to $numGapsForTrimming, then that number of bases should be trimmed
      for($j=0;$j<$numSequences;$j++){
        $base=$seq[$j]->substr($i,1);
        if($base==$gap) $numGaps++;
      }
      if($numGaps>=$numSequences-1){
        $shouldBeTrimmed=true;
      }

      if($shouldBeTrimmed==true){
        for($j=0;$j<$numSequences;$j++){
          $seq[$j]->removeBase($i);
	}
	$i--;
	$numBases--;
      }
    }
    return $seq;
  }
  /**
   * finds indels and mismatches at loci where all other 
   * sequences are different than the 0th sequence
   */
  function findIndels($backboneId){
    $gap='-';

    $i=0;
    $backboneIndex=-1;
    while(false!==($seq=$this->seqio->nextSeq())){
      $sequence[$i]=$seq->sequence().$gap;
      $seqid[$i]=$seq->id();
      if($seqid[$i]==$backboneId) $backboneIndex=$i;
      $i++;
    }
    #exit;
    if($backboneIndex<0){
      die("Error: backbone sequence not found for $backboneId\n");
    }

    # start checking the alignment for indels
    $numBases=max(array_map('strlen',$sequence)); #max strlen for each seq
    $numSequences=$this->numSequences;
    $indel=array();
    for($pos=0;$pos<$numBases;$pos++){
      $backboneBase=$sequence[$backboneIndex]{$pos};
      $del=$ins=$mismatch=0;
      for($j=0;$j<$numSequences;$j++){
        if($j==$backboneIndex) continue;
        $seqId=$seqid[$i];
	$thisBase=$sequence[$j]{$pos};
	if($backboneBase!=$gap){
	  # insertion count: backbone is a base, others are gaps
	  if($thisBase==$gap){
	    $ins++;
	  }
	  # mismatch count: all others are different bases than backbone
	  elseif($backboneBase!=$thisBase){
	    $mismatch++;
	  }
	}
	elseif($backboneBase==$gap){
	  # deletion count: backbone is gap, others aren't
	  if($sequence[$j]{$pos}!=$gap){
	    $del++;
	  }
	}
      }
      $feature="";
      if($del==$numSequences-1){
	$feature='deletion';
      }
      if($ins>$numSequences-2){
	$feature='insertion';
      }
      if($mismatch==$numSequences-1){
	$feature='mismatch';
      }
      $lastFeature=$currentFeature;
      $currentFeature=$feature;

      # combine ranges
      # if the last and current features are the same, extend the end range
      if($currentFeature==$lastFeature){
	$endRange=$pos;
      }
      # if they are different, record the start/end and reset the start/end
      elseif($lastFeature!=""){
        #$endRange++;
        $thisRange="$startRange-$endRange";
	if($startRange==$endRange) $thisRange=$startRange;
        $range[]="$lastFeature: $thisRange";
        $startRange=$pos;
      }
      else{
        $startRange=$endRange=$pos;
      }
    }
    return $range;
  }
}
?>
