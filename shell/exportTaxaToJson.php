<?php
if(!array_key_exists('db', $GLOBALS)) {
    require __DIR__.'/../include/pageboot.php';
}
$mysql = $GLOBALS['db']->getDriver()->getConnection()->getResource();
$wordH = fopen(__DIR__.'/words.txt', 'r');
$baseDir = __DIR__.'/../db/search';
if (!is_dir($baseDir)) {
   mkdir($baseDir);
}
$words = array();
while (($rawWord = fgets($wordH)) !== false) {
   $rawWord = trim($rawWord);
   $word = '';
   $error = false;
   $wrongOrd = array();
   for ($c =0; $c < strlen($rawWord); $c++) {
         $char = $rawWord[$c];
         if (ord($char)>127) {
            $error = true;
            $wrongOrd[] = ord($char);
            continue;
         } else if (sizeof($wrongOrd)>0) {
           $char = fixChars($wrongOrd);
           $wrongOrd = array();         
         }
         $word .= $char;
      }
      if (sizeof($wrongOrd)>0) {
         $word .= fixChars($wrongOrd);
      }
   $words[] = $word;
}
fclose($wordH);
$taxaRes = $mysql->query('SELECT name FROM `taxa` WHERE `taxa_kind_id` IN (SELECT `id` FROM `taxa_kind` WHERE `initials` = "Sp" OR `initials` = "Gen." OR `initials` = "Sp." OR `initials` = "Gen")');
while ($word = $taxaRes->fetch_object()) {
   preg_match("/^(\w+)/",strtolower(trim($word->name)),$matches);
   $word = current($matches);
   $words[] = $word;
}
$words = array_unique($words);
foreach($words as $word) {
   $taxaRes = $mysql->query('SELECT taxa.id ,
   taxa.name ,
   (SELECT `initials` FROM `taxa_kind` WHERE `taxa_kind`.`id`=`taxa`.`taxa_kind_id` ) as taxa_kind_initials,
   (SELECT `name` FROM `taxa_kind` WHERE `taxa_kind`.`id`=`taxa`.`taxa_kind_id` ) as taxa_kind_name   
   FROM taxa_search
   LEFT JOIN taxa ON taxa.id=taxa_search.taxa_id
   WHERE MATCH (taxa_search.text) AGAINST ( "'.addslashes($word).'" IN NATURAL LANGUAGE MODE)
   LIMIT 10
   ');
   $baseDir = __DIR__.'/../db/search/'.substr($word, 0,1);
   if (!is_dir($baseDir)) {
      mkdir($baseDir);
   }
   $baseDir .= '/'.substr($word, 1,1);
   if (!is_dir($baseDir)) {
      mkdir($baseDir);
   }
   $baseDir .= '/'.$word.'.json';
   $searchResult=array();
   while ($taxa = $taxaRes->fetch_object()) {
      $searchResult[]=$taxa;
   }
   file_put_contents($baseDir,json_encode($searchResult,JSON_FORCE_OBJECT));
}

$taxaRes = $mysql->query('SELECT * ,
   (SELECT `initials` FROM `taxa_kind` WHERE `taxa_kind`.`id`=`taxa`.`taxa_kind_id` ) as taxa_kind_initials,
   (SELECT `name` FROM `taxa_kind` WHERE `taxa_kind`.`id`=`taxa`.`taxa_kind_id` ) as taxa_kind_name,            
   (               
      IFNULL(LENGTH(taxa.description),0)+
      IFNULL((SELECT COUNT(`value`) FROM `taxa_attribute_value` WHERE `taxa_attribute_value`.`taxa_id`=`taxa`.`id`),0)+
      IFNULL((SELECT COUNT(`filename`) FROM `taxa_image` WHERE `taxa_image`.`taxa_id`=`taxa`.`id`),0)+
      IFNULL((SELECT COUNT(`id`) FROM `dico_item` WHERE `dico_item`.`parent_taxa_id`=`taxa`.`id`),0)
   ) > 0 as status,
   (SELECT `parent_taxa_id` FROM `dico_item` WHERE `dico_item`.`taxa_id`=`taxa`.`id` LIMIT 1) as parent_taxa_id,
   (SELECT pt.name FROM taxa pt WHERE pt.id=(SELECT `parent_taxa_id` FROM `dico_item` WHERE `dico_item`.`taxa_id`=`taxa`.`id` LIMIT 1)) as parent_taxa_name,
   (SELECT name FROM taxa_kind WHERE taxa_kind.id=(SELECT pt.taxa_kind_id FROM taxa pt WHERE pt.id=(SELECT `parent_taxa_id` FROM `dico_item` WHERE `dico_item`.`taxa_id`=`taxa`.`id` LIMIT 1))) as parent_taxa_initials
   FROM taxa');

$baseDir = __DIR__.'/../db/taxa';
if (!is_dir($baseDir)) {
   mkdir($baseDir);
}
while ($taxa = $taxaRes->fetch_object()) {
   $dicoRes = $mysql->query('SELECT 
      dico_item.id,      
      dico_item.taxa_id,      
      dico_item.text,
      taxa.name,
      (SELECT `initials` FROM `taxa_kind` WHERE `taxa_kind`.`id`=`taxa`.`taxa_kind_id` ) as taxa_kind_initials,
      (SELECT `name` FROM `taxa_kind` WHERE `taxa_kind`.`id`=`taxa`.`taxa_kind_id` ) as taxa_kind_name,            
      (               
         IFNULL(LENGTH(taxa.description),0)+
         IFNULL((SELECT COUNT(`value`) FROM `taxa_attribute_value` WHERE `taxa_attribute_value`.`taxa_id`=`taxa`.`id`),0)+
         IFNULL((SELECT COUNT(`filename`) FROM `taxa_image` WHERE `taxa_image`.`taxa_id`=`taxa`.`id`),0)+
         IFNULL((SELECT COUNT(`id`) FROM `dico_item` WHERE `dico_item`.`parent_taxa_id`=`taxa`.`id`),0)
      ) > 0 as status,
      dico_item.photo_id
      FROM dico_item
      LEFT JOIN taxa ON taxa.id=dico_item.taxa_id
      WHERE parent_taxa_id='.$taxa->id);
   $taxa->dico=array();
   while ($dico = $dicoRes->fetch_object()) {
      
      preg_match_all('/{t([[:alnum:]\/]+)}/',$dico->text,$items);
      if (is_array($items) && array_key_exists(1, $items)) {
          $relTaxa = new \flora\taxa\Taxa($GLOBALS['db']);
          foreach ($items[1] as $progessNumber) {
              $relTaxa->loadFromAttributeValue($GLOBALS['db']->config->attributes->progress,$progessNumber);
              if ($relTaxa->getData('id') != '') {
                  $dico->text = str_replace('{t'.$progessNumber.'}', '<a href="#" class="gotoTaxa" data-taxaid="'.$relTaxa->getData('id').'">'.$relTaxa->getData('taxa_kind_initials').' '.$relTaxa->getData('name').'</a>', $dico->text);
              } else {
                  $dico->text = str_replace('{t'.$progessNumber.'}', $progessNumber, $dico->text);
              }
          }
      }
      
	   if ($dico->photo_id > 0) {
		  $dicoObj = new \flora\dico\DicoItem($GLOBALS['db']);
		  $dicoObj->loadFromIdAndDico($taxa->id,$dico->id);
		  $dico->photo_path = str_replace('images/dico/','',$dicoObj->getPhotoUrl());
	  }
	  unset($dico->id);
	  unset($dico->photo_id);
      $taxa->dico[]=$dico;
      
   }
   
   $imageRes = $mysql->query('SELECT * FROM taxa_image WHERE taxa_id='.$taxa->id);
   $taxa->image=array();
   while ($image = $imageRes->fetch_object()) {
      $taxa->image[]=$image;
   }
   
   $attributeRes = $mysql->query('SELECT name,value FROM taxa_attribute_value
      LEFT JOIN taxa_attribute ON taxa_attribute.id=taxa_attribute_value.taxa_attribute_id
      WHERE taxa_id='.$taxa->id);
   $taxa->attribute=array();
   while ($attribute = $attributeRes->fetch_object()) {
      $taxa->attribute[]=$attribute;
   }
   $regionRes = $mysql->query('SELECT region.id,region.name FROM taxa_region
      LEFT JOIN region ON region.id=taxa_region.region_id
      WHERE taxa_id='.$taxa->id);
   $taxa->region=array();
   while ($region = $regionRes->fetch_object()) {
      $taxa->region[]=$region;
   }
   $thousand = intval($taxa->id/1000);
   if (!is_dir($baseDir.'/'.$thousand)) {
      mkdir($baseDir.'/'.$thousand);
   }
   file_put_contents($baseDir.'/'.$thousand.'/'.$taxa->id.'.json',json_encode($taxa,JSON_FORCE_OBJECT));
}
function fixChars ($wrongOrd) {
   $char = '';
   foreach($wrongOrd as $pos=>$ord) {
      $bin = decbin($ord);
      if ($pos == 0 && strlen($bin) == 8 && substr($bin,0,3) == '111') {
         $bin = '110'.substr($bin,3);
      }
      $char .= chr(bindec($bin));
   }
   return $char;
}
