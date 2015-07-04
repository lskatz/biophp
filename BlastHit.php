<?
class BlastHit extends Bio{
  var $rawHit; # the raw text from a blast hit
  var $parsedHit=array(); # the array'd hit from parsing it
  var $mode; # 0 is blast default, 8,9 are tabular, 7 is xml, etc
  var $querySeq; # the original query sequence object
  /**
   * Construct with the raw text from the hit
   * and the mode.
   * Give the seq for additional blast information
   */
  function __construct($rawHit,$mode,$querySeq=false){
    parent::__construct();

    $this->rawHit=$rawHit;
    if(!$mode)
      $mode=0;
    $this->mode=$mode;
    if($querySeq!==false){
      $querySeq=new Seq($querySeq);
      $this->querySeq=$querySeq;
    }

    $this->parse();
  }
  
  /**
   * parses the result based on the mode
   */
  public function parse(){
    $mode=$this->mode;
    $rawHit=$this->rawHit;

    switch($mode){
    case 0:
      $this->parsedHit=$rawHit;
      return true;
      break;
    case 7:
      $this->parsedHit=$this->parseXmlBlastResult($rawHit);
      return true;
      break;
    case 8:
    case 9:
      $this->parsedHit=$this->parseTabularBlastResult($rawHit);
      return true;
      break;
    default:
      $this->warn("$mode is not a recognized mode yet.");
    }
    return false;
  }
  /**
   * parses an xml result and returns an array
   * consisting of the data and its index
   */
  private function parseXmlBlastResult($res){
    if(is_array($res))
      $res=join("\n",$res);
    $parser=xml_parser_create();
    xml_parse_into_struct($parser,$res,$data,$index);
    return array('index'=>$index,'data'=>$data);
  }
  /**
   * parses tabular blast result and returns an array
   * modes 8 and 9 for blastall
   */
  private function parseTabularBlastResult($res){
    if(!is_array($res)){
      $res=split("\n",$res);
    }
    $res=array_map('trim',$res);
    $numResults=count($res);
    // remove commented lines
    for($i=0;$i<$numResults;$i++){
      if(preg_match('/^#/',$res[$i])){
        array_shift($res);
        $i--;
        $numResults--;
      }
    }
    // Load up a results array
    if($this->querySeq!==false)
      $queryLength=strlen($this->querySeq->sequence());
    $result=array();
    for($i=0;$i<$numResults;$i++){
      if(!$res[$i]) continue;
      $thisResult=split("\t",$res[$i]);
      list($result[$i]['qid'],$result[$i]['sid'],$result[$i]['identity'],$result[$i]['length'],$result[$i]['mismatches'],$result[$i]['gaps'],$result[$i]['qstart'],$result[$i]['qend'],$result[$i]['sstart'],$result[$i]['send'],$result[$i]['e'],$result[$i]['score'])=$thisResult;

      $result[$i]['orientation']='-';
      if($result[$i]['send']-$result[$i]['sstart']>0 && $result[$i]['qend']-$result[$i]['qstart']>0){
        $result[$i]['orientation']='+';
      }

      if($queryLength){
        $result[$i]['queryLength']=$result[$i]['length']/$queryLength;
      }
    }
    return $result;
  }
  /**
   * perform a sort for tabular results
   */
  public function sort($sortOn){
    $numResults=count($this->parsedHit);
    for($i=0;$i<$numResults;$i++){
      for($j=$i+1;$j<$numResults;$j++){
        if($this->parsedHit[$i][$sortOn]>$this->parsedHit[$j][$sortOn]){
          $this->swap($this->parsedHit[$i],$this->parsedHit[$j]);
	}
      }
    }
  }
  /**
   * convert all coordinates to base 0
   */
  public function toBase0(){
    $mode=$this->mode;
    if($mode==8 || $mode==9){
      $headerToFix=array('sstart','send','qstart','qend');
      $numResults=count($this->hit());
      for($i=0;$i<$numResults;$i++){
        foreach($headerToFix as $header){
	  $this->parsedHit[$i][$header]--;
	}
      }
    }
  }

  function __toString(){
    $return=$this->rawHit;
    return $return;
  }
  function hit(){
    return $this->parsedHit;
  }
}
?>
