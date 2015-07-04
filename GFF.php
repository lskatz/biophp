<?
class GFF{
  var $GFF; #array of lines from the GFF file
  var $version; # version number of the GFF (3 is default)
  var $lineNumber=0;
  var $numLines;
  function GFF($filename,$version=3){
    $line=file($filename);
    $line=array_map("trim",$line);
    $this->GFF=$line;
    $this->numLines=count($this->GFF);
    $this->version=$version;
  }
  function parseGffLine($line){
    $cell=split("\t",$line);
    
    # create an associative array for the GFF
    $info['seqid']=$cell[0];
    $info['source']=$cell[1];
    $info['type']=$cell[2];
    $info['start']=$cell[3];
    $info['end']=$cell[4];
    $info['score']=$cell[5];
    $info['strand']=$cell[6];
    $info['phase']=$cell[7];
    $attributes=$cell[8];

    # parse the attributes
    $attributes=split(';',$attributes);
    # each attribute should be in key=value format
    $numAttributes=count($attributes);
    for($i=0;$i<$numAttributes;$i++){
      $attribute=$attributes[$i];
      list($key,$value)=split("=",$attribute);
      $key=urldecode($key);
      $value=urldecode($value);
      $attributeArr[$key]=$value;
    }
    $info['attributes']=$attributeArr;

    return $info;
  }
  /**
   * retrieves a gff entry, given the row number
   */ 
  function entry($i){
    return $this->parseGffLine($this->GFF[$i]);
  }
  function nextEntry(){
    $i=$this->lineNumber;
    if($i>=$this->numLines) return false;
    $this->lineNumber++;
    return $this->entry($i);
  }
}
