<?
class NCBI extends Run{
  function fastacmd($search,$database){
    $command="fastacmd -d '$database' -s '$search' 2>&1";
    exec($command,$out,$err);
    if($err) $fasta=$this->fastacmdFromFasta($search,$database);
    else $fasta=trim(join("\n",$out))."\n";
    return $fasta;
  }
  /**
   * perform a search for a fasta entry in a fasta file using grep, head/tail
   * assumes a fasta entry is only two lines: the defline and the seq
   * However, this can probably just be done with grep -A 1
   */
  function fastacmdFromFasta($search,$fastaFilename){
    $command="grep -n '$search' '$fastaFilename'";
    exec($command,$out);
    $out=join("\n",$out);
    $defLineNumber=preg_replace('/\:.+/','',$out); # line is the first digits before the colon
    #print "$command\n$defLineNumber\n";exit;

    $command="grep -n '>' '$fastaFilename'";
    unset($out);
    exec($command,$out);
    $numLines=count($out);
    $endLineNumber=0;
    for($i=0;$i<$numLines;$i++){
      $endLineNumber=preg_replace('/\:.+/','',$out[$i]);
      if($endLineNumber>$defLineNumber) break;
    }
    $numFastaLines=$endLineNumber-$defLineNumber;
    $command="head -".($defLineNumber+$numFastaLines-1)." $fastaFilename|tail -$numFastaLines";
    unset($out);
    exec($command,$out);
    $out=join("\n",$out);
    $out=trim($out)."\n";
    return $out;
  }
  function megablast($fastaFilename,$db,$option=array()){
    die("need to convert megablast so that it uses the blasthit class.");
    $seq="";
    if(is_object($fastaFilename) && get_class($fastaFilename)=='Seq'){
      $seq=$fastaFilename;
      $seq->writeTemporaryFile();
      $fastaFilename=$seq->filename;
    }
    else{
      $seq=new Seq($fastaFilename);
    }
    $command="megablast -i '$fastaFilename' -d '$db' ";
    $defaultOption=array('m'=>0);
    $option=array_merge($defaultOption,$option);
    $command.=$this->formatParameters($option);
    $output=$this->command($command,$option['debug']);
    if($option['m']==8 || $option['m']==9){
      $output=$this->parseTabularBlastResult($output);
      $queryLength=strlen($seq->sequence());
      $numResults=count($output);
      for($r=0;$r<$numResults;$r++){
        $output[$r]['queryCoverage']=$output[$r]['length']/$queryLength;
      }
    }
    elseif($option['m']==7)
      $output=$this->parseXmlBlastResult($output);
    if(is_object($seq)) $seq->__destruct();
    return $output;
  }
  /**
    * finds islands in an SeqIO object.
    * Returns array of Align objects.
    */
  /*
  function findIslandsFromTabularResults($seqio,$result){
    $gap='-';
    $return=array();
    $numResults=count($result);
    for($i=0;$i<$numResults;$i++){
      for($j=$i+1;$j<$numResults;$j++){
	if($result[$i]['sid']==$result[$j]['sid'] && $result[$i]['qid']==$result[$j]['qid']
	&& $result[$i]['orientation']==$result[$j]['orientation']){
	  $orientation=$result[$i]['orientation'];

	  $result1=$result[$i];
	  $result2=$result[$j];
	  // make the first result the first one
	  if($result[$i]['send']<$result[$j]['sstart']){
	    // $i is the first result
	    if($orientation=='-')
	      $this->swap($result1,$result2);
	  }
	  else{
	    // $j is the first result
	    if($orientation=='+')
	      $this->swap($result1,$result2);
	  }

	  // does the query or the subject in the blast results have the gap?
	  $whichHasGap='q';
	  $whichOther='s';
	  if($result1["${whichHasGap}end"]+1!=$result2["${whichHasGap}start"]){
	    $this->swap($whichHasGap,$whichOther);
	  }
	  $seq[${whichHasGap}]=$seqio->findSeq($result1["${whichHasGap}id"]);
	  $seq[${whichOther}]=$seqio->findSeq($result1["${whichOther}id"]);
	  $islandLength=abs($result1["${whichOther}end"]-$result2["${whichOther}start"])-1;
	  $sequence1=$seq[${whichOther}]->sequence();
	  $sequence2=$seq[${whichHasGap}]->subseq($result1["${whichHasGap}start"]-1,$result1["${whichHasGap}end"]-1).str_repeat($gap,$islandLength).$seq[${whichHasGap}]->subseq($result2["${whichHasGap}start"]-1,$result2["${whichHasGap}end"]-1);
	  $fasta=$seq[$whichOther]->defline()."\n$sequence1\n".$seq[$whichHasGap]."\n$sequence2\n";
	  $return[]=new Align($fasta);
	}
      }
    }
    return $return;
  }
  */
  function formatdb($filename,$option=array()){
    $params=$this->formatParameters($option);
    $command="formatdb -i $filename $params";
    $out=$this->command($command);
    return $out;
  }
  /**
   * bl2seq
   */
  function bl2seq($fasta1,$fasta2,$parameters=array()){
    $maxIdLength=50;
    $fasta1=new Seq($fasta1,array('truncateSeqId'=>$maxIdLength));
    $fasta2=new Seq($fasta2,array('truncateSeqId'=>$maxIdLength));
    //unset($fasta1->fastaArr['sequence']);
    //print_r($fasta1);exit;
    
    $fasta1->writeTemporaryFile();
    $fasta2->writeTemporaryFile();

    $defaultParameters=array('D'=>0);
    $parameters=array_merge($defaultParameters,$parameters);
    if(!$this->getParameter('p',$parameters))
      $parameters['p']=$this->blastProgram($fasta1,$fasta2);

    $command="bl2seq -i ".$fasta1->filename." -j ".$fasta2->filename." ";
    $command.=$this->formatParameters($parameters);
    $output=$this->command($command,$parameters['debug']);
    if($this->getParameter('D',$parameters)==1)
      $m=9;
    else
      $m=0;

    $output=new BlastHit($output,$m,$fasta1);

    // to be sure it is destroyed
    $fasta1->__destruct(); 
    $fasta2->__destruct();
    return $output;
  }
  /**
   * returns an array of blast results
   * can accept a string or a Seq object for a fasta parameter
   */
  function blastAll($fasta,$program,$db, $option=array()){
    $error=array(); # value to return on error

    if(is_object($fasta)){
      if(get_class($fasta)=='Seq'){
        $fasta=$fasta->fasta();
      }
      else{
        $this->warn("Error: cannot recognize fasta:\n".print_r($fasta,true));
        return $error;
      }
    }
    elseif(file_exists($fasta)){
      $fasta=file_get_contents($fasta);
    }
    # extract information from the sequence
    $fastaObj=new Seq($fasta);
    $queryLength=strlen($fastaObj->sequence());

    # options
    $defaultOption=array('tries'=>0,'debug'=>false);
    $option=array_merge($defaultOption,$option);

    // if there have been plenty of tries, then give up
    if($option['tries']>3) return $error;

    $mode=$option['m'];
    $echoFasta=addslashes($fasta);
    $command="echo \"$echoFasta\" | blastall -p $program -d '$db' ";
      $param=$option;
      unset($param['tries']);
    $command.=$this->formatParameters($param);
    $output=$this->command($command,$option['debug']);

    // if there is a segmentation fault, try again
    if(preg_match('/segmentation/i',$output) || $errorCode){
      $a=(int)($option['a']/3); # reduce the processors by 1/3
      if($a<1) $a=1;
      $this->warn("Error: there was a BLAST error.  Trying again with only $a processor(s).\nThis is trial number $option[tries].\n\n");
      $option['a']=$a;
      $option['tries']++;
      $result=$this->blastAll($fasta,$program,$db,$option);
    }
    if(!$output) return $error;
    $result=new BlastHit($output,$this->getParameter('m',$option),$fastaObj);
   
    return $result;
  }
  /**
   * based on $protein/$protein (T/F) matches, 
   * determines which blast program to use.
   * Probably only good for bl2seq but who knows what else.
   */
  function blastProgram($seq1,$seq2,$tblastx=false){
    if(is_object($seq1)&&get_class($seq1)=='Seq') $protein1=$seq1->protein();
    elseif(is_bool($seq1)) $protein1=$seq1;
    else $this->warn("Bad parameters for blastProgram (seq1).","Error",1);
    if(is_object($seq2)&&get_class($seq2)=='Seq') $protein2=$seq2->protein();
    elseif(is_bool($seq2)) $protein2=$seq2;
    else $this->warn("Bad parameters for blastProgram (seq2).","Error",1);
    if($protein1 && $protein2){
      return "blastp";
    }
    if($protein1 && !$protein2){
      return "tblastn";
    }
    if(!$protein1 && $protein2){
      return "blastx";
    }
    if(!$protein1 && !$protein2 && $tblastx){
      return "tblastx";
    }
    if(!$protein1 && !$protein2){
      return "blastn";
    }

    return false;
  }
}
?>
