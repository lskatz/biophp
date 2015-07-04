<?
/**
 for reading in multiple sequence alignments or other alignments
*/
class AlignIO extends Bio{
  var $option;
  var $filename;
  var $align; #alignment objects
  var $numAlignments;
  var $alignmentCounter=0; # keeping track of which alignment is current

  function AlignIO($filename,$option=array()){
    $defaultOption=array('type'=>'clustalw');
    $option=array_merge($defaultOption,$option);

    $this->option=$option;
    if($option['type']=='clustalw'){
      $seqOption=array('fasta');

      # split between alignments (separated by = )
      $alignLine=file($filename,FILE_SKIP_EMPTY_LINES);
      $alignLine=array_map("trim",$alignLine);
      $numLines=count($alignLine);
      for($i=0;$i<$numLines;$i++){
        # alignments are separated by = in these files, so grab the alignment
        # if it is the end of file or if there is an equals sign
        if(preg_match('/=/',$alignLine[$i]) || $i+1==$numLines ){
          $thisFastaSet=join("\n",array_splice($alignLine,0,$i+1));
	  $thisFastaSet=preg_replace('/(^\s*=+)|(=+\s*$)/','',$thisFastaSet);
	  $i=0;
	  $numLines=count($alignLine);
	  $seqio=new SeqIO($thisFastaSet,$seqOption);
	  $this->align[]=new Align($seqio);
	}
      }
      $this->numAlignments=count($this->align); 
    }
  }
  /**
   * Returns the next alignment as an align object.
   * Example usage: while(false!==($align=$alignio->nextAlignment())){...}
   */
  function nextAlignment(){
    if($this->alignmentCounter < $this->numAlignments){
      $this->alignmentCounter++;
      $i=$this->alignmentCounter-1;
      return $this->align($i);
    }

    $this->alignmentCounter=0;
    return false;
  }
  /**
   * returns all alignments or an indexed alignment
   */
  function align($i=false){
    if($i===false)
      return $this->align;

    return $this->align[$i];
  }
}
