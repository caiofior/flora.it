<?php
namespace florasearch;
/**
 * Search if flora taxa
 *
 * @author caiofior
 */
class Search {
    /**
    * Zend DB
    * @var Zend_Db
    */
    private $db;
    /**
     * Request parameters
     * @var array
     */
    private $request=array();
    /**
     * taxa reference
     * @var \flora\taxa\Taxa
     */
    private $content;
    /**
     * Reference to complete region Collection
     * @var \flora\region\RegionColl
     */
    private $regionColl;
    /**
     * Altitude array
     * @var array
     */
    private $altitude=array();
    /**
     * Flowering array
     * @var array
     */
    private $flowering=array();
    /**
     *Posture array
     * @var array
     */
    private $postureArray=null;
    /**
     *Biologic form array
     * @var array
     */
    private $biologicFormArray=null;
    /**
     *Community array
     * @var array
     */
    private $communityArray=null;
    /**
     *Array of attributes ids
     * @var array
     */
    private $attributeId=array();
    
    
    /**
     * Instantiates the search
     * @param \Zend\Db\Adapter\Adapter $db
     */
    public function __construct(\Zend\Db\Adapter\Adapter $db) {
        $this->db = $db;
        $this->content = new \flora\taxa\TaxaSearch($this->db);
        $this->regionColl = new \flora\region\RegionColl($this->db);
        $this->regionColl->loadAll();
        $this->altitude= array_flip(range(0,2500,$this->db->config->attributes->altitudeStep));
        foreach($this->altitude as $altitude=>$value) {
            $this->altitude[$altitude]=array('count'=>0);
        }
        foreach($this->db->config->attributes->floweringNames->toArray() as $flowerLabel=>$flowerCode) {
            $this->flowering[$flowerCode]=array('label'=>$flowerLabel);
        }
        foreach($this->db->query('SELECT `name`,`id` FROM `taxa_attribute`'
        , \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE)->toArray() as $attribute) {
            $this->attributeId[$attribute['name']]=$attribute['id'];
        }
    }
    /**
     * Sets the request/*
     * @param array $request
     */
    public function setRequest (array $request) {
        $this->request = $request;
    }
    /**
     * Count all the items
     * @throws \Exception
     */
    public function getTaxaCountAll() {
        $select=$this->createSelect();
        $select->columns(array('count'=>new \Zend\Db\Sql\Expression('COUNT(`taxa_search`.`taxa_id`)')));
        try{
                $statement = $this->content->getTable()->getSql()->prepareStatementForSqlObject($select);
                $results = $statement->execute();
                $resultSet = new \Zend\Db\ResultSet\ResultSet();
                $resultSet->initialize($results);
                return $resultSet->current()->count;
        }
        catch (\Exception $e) {
               $mysqli = $this->db->getDriver()->getConnection()->getResource();  
               if (array_key_exists('firephp', $GLOBALS) && !headers_sent())
                   $GLOBALS['firephp']->error('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error);
               throw new \Exception('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error,1401301242);
        }
        
    }
    /**
     * Gets the taxa collection
     * @return \flora\taxa\TaxaColl
     */
    public function getTaxaColl() {
        $data = array();
        $select=$this->createSelect();
        $select->join('taxa', 'taxa.id=taxa_search.taxa_id',array(), \Zend\Db\Sql\Select::JOIN_LEFT);
        $select->join('taxa_kind', 'taxa_kind.id=taxa.taxa_kind_id',array(), \Zend\Db\Sql\Select::JOIN_LEFT);
        $select->columns(array(
            'id'=>new \Zend\Db\Sql\Expression('`taxa`.`id`'),
            'name'=>new \Zend\Db\Sql\Expression('`taxa`.`name`'),
            'taxa_kind_initials'=>new \Zend\Db\Sql\Expression('`taxa_kind`.`initials`'),
            'taxa_kind_id_name'=>new \Zend\Db\Sql\Expression('`taxa_kind`.`name`'),
            'status' => new \Zend\Db\Sql\Predicate\Expression('
                (               
                    IFNULL(LENGTH(taxa.description),0)+
                    IFNULL((SELECT COUNT(`value`) FROM `taxa_attribute_value` WHERE `taxa_attribute_value`.`taxa_id`=`taxa`.`id`),0)+
                    IFNULL((SELECT COUNT(`filename`) FROM `taxa_image` WHERE `taxa_image`.`taxa_id`=`taxa`.`id`),0)+
                    IFNULL((SELECT COUNT(`id`) FROM `dico_item` WHERE `dico_item`.`parent_taxa_id`=`taxa`.`id`),0)
                ) > 0
               ')
        ));
        if (
                array_key_exists('start', $this->request) &&
                $this->request['start']!= '' &&
                array_key_exists('pagelength', $this->request) &&
                $this->request['pagelength']!= ''
            ) {
             $select->offset($this->request['start']);
        }
        if (
                array_key_exists('pagelength', $this->request) &&
                $this->request['pagelength']!= ''
            ) {
             $select->limit($this->request['pagelength']);
        }
        try{
                $statement = $this->content->getTable()->getSql()->prepareStatementForSqlObject($select);
                $results = $statement->execute();
                $resultSet = new \Zend\Db\ResultSet\ResultSet();
                $resultSet->initialize($results);
                $data = $resultSet->toArray(); 
            }
            catch (\Exception $e) {
               $mysqli = $this->db->getDriver()->getConnection()->getResource();  
               if (array_key_exists('firephp', $GLOBALS) && !headers_sent())
                   $GLOBALS['firephp']->error('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error);
               throw new \Exception('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error,1401301242);
            }
        $taxaColl = new \flora\taxa\TaxaColl($this->db);
        foreach($data as $taxaData) {
            $taxa = $taxaColl->addItem();
            $taxa->setData($taxaData);
        }
        return $taxaColl;
    }
     /**
     * Gets the parent taxa collection
     * @return \flora\taxa\TaxaColl
     */
    public function getTaxaParentColl() {
        $select = $this->content->getTable()->getSql()->select();
        $select->join('taxa', 'taxa.id=taxa_search.taxa_id',array(), \Zend\Db\Sql\Select::JOIN_LEFT);
        $select->join('taxa_kind', 'taxa_kind.id=taxa.taxa_kind_id',array(), \Zend\Db\Sql\Select::JOIN_LEFT);
        
        $selectCount = new \Zend\Db\Sql\Select();
        $selectCount->from(array('p'=>'taxa_search'));
        $selectCount->columns(array('count'=>new \Zend\Db\Sql\Expression('COUNT(DISTINCT `p`.`taxa_id`)')));
        $selectCount->where('`p`.`lft` >= `taxa_search`.`lft`');
        $selectCount->where('`p`.`rgt` <= `taxa_search`.`rgt`');
        $selectCount->where('`p`.`text` <> ""');
        
        $select->limit(10);
        $select->where(' (`taxa_search`.`rgt` - `taxa_search`.`lft`) > 2 ');
        $select->where(' `taxa_id` != 1'); 
        if (array_key_exists('term', $this->request) && $this->request['term'] != '') {
           $select->where(' ( `taxa`.`name` LIKE "'.addslashes($this->request['term']).'%" OR `taxa`.`description` LIKE "'.addslashes($this->request['term']).'%" ) '); 
        }
        if (array_key_exists('sSearch', $this->request) && $this->request['sSearch'] != '') {          
           $select->having('`count` > 0');
           $selectCount->where('MATCH (`p`.`text`) AGAINST ( "'.addslashes($this->request['sSearch']).'" IN NATURAL LANGUAGE MODE)');
        }
    
        $select->columns(array(
            'id'=>new \Zend\Db\Sql\Expression('`taxa`.`id`'),
            'name'=>new \Zend\Db\Sql\Expression('`taxa`.`name`'),
            'taxa_kind_initials'=>new \Zend\Db\Sql\Expression('`taxa_kind`.`initials`'),
            'taxa_kind_id_name'=>new \Zend\Db\Sql\Expression('`taxa_kind`.`name`'),
            'count'=>new \Zend\Db\Sql\Expression('( '.$selectCount->getSqlString($this->db->getPlatform()).' )')
        ));
        try{
               $statement = $this->content->getTable()->getSql()->prepareStatementForSqlObject($select);
               $results = $statement->execute();
               $resultSet = new \Zend\Db\ResultSet\ResultSet();
               $resultSet->initialize($results);
               $data = $resultSet->toArray();
        }
        catch (\Exception $e) {
           $mysqli = $this->db->getDriver()->getConnection()->getResource();  
           if (array_key_exists('firephp', $GLOBALS) && !headers_sent())
               $GLOBALS['firephp']->error('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error);
           throw new \Exception('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error,1401301242);
        }
        $taxaColl = new \flora\taxa\TaxaColl($this->db);
        foreach($data as $taxaData) {
            $taxa = $taxaColl->addItem();
            $taxa->setData($taxaData);
        }
        return $taxaColl;
    }
     /**
     * Gets a collection of regions
     * @return \flora\region\RegionColl
     * @throws \Exception
     */
    public function getRegionColl() {
        return $this->regionColl;
    }
    /**
     * Gets a collection of filtered regions
     * @param $removeEmpty bool Remove empty items
     * @return \flora\region\RegionColl
     * @throws \Exception
     */
    public function getFilteredRegionColl($removeEmpty=false){
        $select=$this->createSelect(array('region'));
        $select->columns(array(
            'taxa_id'
        ));
        $sql = $select->getSqlString($this->db->getPlatform());
        $table = new \Zend\Db\TableGateway\TableGateway('taxa_region',$this->db);
        $select = $table->getSql()->select();
        $select->join('region', 'region.id=taxa_region.region_id',array(), \Zend\Db\Sql\Select::JOIN_LEFT);
        $select->columns(array(
            'id'=>new \Zend\Db\Sql\Expression('`taxa_region`.`region_id`'),
            'name'=>new \Zend\Db\Sql\Expression('`region`.`name`'),
            'count'=>new \Zend\Db\Sql\Expression('COUNT(`taxa_region`.`taxa_id`)')
        ));
        $select->where('`taxa_region`.`taxa_id` IN ('.$sql.')');
        $select->group('taxa_region.region_id');
        try{
               $statement = $this->content->getTable()->getSql()->prepareStatementForSqlObject($select);
               $results = $statement->execute();
               $resultSet = new \Zend\Db\ResultSet\ResultSet();
               $resultSet->initialize($results);
               $data = $resultSet->toArray(); 
           }
           catch (\Exception $e) {
              $mysqli = $this->db->getDriver()->getConnection()->getResource();  
              if (array_key_exists('firephp', $GLOBALS) && !headers_sent())
                  $GLOBALS['firephp']->error('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error);
              throw new \Exception('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error,1401301242);
           }
        foreach($data as $regionData) {
            if ($regionData['name'] == '') continue;
            if (
                    array_key_exists('region', $this->request) && 
                    is_array($this->request['region']) && 
                    in_array($regionData['id'], $this->request['region'])
                ) {
                   $regionData['selected']=true;
                }
            foreach($this->regionColl->getItems() as $key=>$region) {
                if ($region->getData('id')== $regionData['id']) {
                    $region->setData($regionData);
                }
            }    
        }
        $regionColl = clone $this->regionColl;
        if ($removeEmpty == true) {
            foreach($regionColl->getItems() as $key=>$region) {
                if ($region->getRawData('count') < 1) {
                    $regionColl->deleteByKey($key);
                } 
            }
        }
        return $regionColl;
    }
    /**
     * Gets altitude array
     * @return array
     */
    public function getAltitudeArray() {
        return array_keys($this->altitude);
    }
    /**
     * Gets filtered altitude array
     * @param $removeEmpty bool Remove empty items
     * @return array
     */
    public function getFilteredAltitudeArray($removeEmpty=false) {
        $select=$this->createSelect(array('altitude'));
        $select->columns(array(
            'taxa_id'
         ));
        $sql = $select->getSqlString($this->db->getPlatform());
        $table = new \Zend\Db\TableGateway\TableGateway('taxa_search_attribute',$this->db);
        $select = $table->getSql()->select();
        $select->columns(array(
            'count'=>new \Zend\Db\Sql\Expression('COUNT(`taxa_search_attribute`.`taxa_id`)'),
            'altitude'=>'value',
        ));
        $select->where('`attribute_id` = 1');
        $select->where('`taxa_id` IN ('.$sql.')');
        $select->group('value');
        
        try {
               $statement = $this->content->getTable()->getSql()->prepareStatementForSqlObject($select);
               $results = $statement->execute();
               $resultSet = new \Zend\Db\ResultSet\ResultSet();
               $resultSet->initialize($results);
               foreach($resultSet->toArray() as $data) {
                   $this->altitude[$data['altitude']]=array('count'=>$data['count']);
               } 
        }
        catch (\Exception $e) {
              $mysqli = $this->db->getDriver()->getConnection()->getResource();  
              if (array_key_exists('firephp', $GLOBALS) && !headers_sent())
                  $GLOBALS['firephp']->error('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error);
              throw new \Exception('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error,1401301242);
        }    
        if(array_key_exists('altitude', $this->request) && is_array($this->request['altitude'])) {
             foreach ($this->request['altitude'] as $altitude) {
                 if (array_key_exists($altitude, $this->altitude)) {
                     if (!is_array($this->altitude[$altitude])) {
                         $this->altitude[$altitude]=array();
                     }
                     $this->altitude[$altitude]['selected']=true;
                 }
             }
        }
        $altitude = $this->altitude;
        if ($removeEmpty == true) {
            foreach($altitude as $key => $values) {
                if (!array_key_exists('count', $values) || $values['count'] <1) {
                    unset($altitude[$key]);
                }
            }
        }   
        return $altitude;
    }
    /**
     * Gets flowering array
     * @return array
     */
    public function getFloweringArray() {
        return array_keys($this->flowering);
    }
    /**
     * Gets filtered flowering array
     * @param $removeEmpty bool Remove empty items
     * @return array
     */
    public function getFilteredFloweringArray($removeEmpty=false) {
        $select=$this->createSelect(array('flowering'));
        $select->columns(array(
            'taxa_id'
         ));
        $sql = $select->getSqlString($this->db->getPlatform());
        $table = new \Zend\Db\TableGateway\TableGateway('taxa_search_attribute',$this->db);
        $select = $table->getSql()->select();
        $select->columns(array(
            'count'=>new \Zend\Db\Sql\Expression('COUNT(`taxa_search_attribute`.`taxa_id`)'),
            'flowering'=>'value',
        ));
        $select->where('`attribute_id` = 2');
        $select->where('`taxa_id` IN ('.$sql.')');
        $select->group('value');
        
        try {
               $statement = $this->content->getTable()->getSql()->prepareStatementForSqlObject($select);
               $results = $statement->execute();
               $resultSet = new \Zend\Db\ResultSet\ResultSet();
               $resultSet->initialize($results);
               foreach($resultSet->toArray() as $data) {
                   if (!is_array($this->flowering[intval($data['flowering'])])) {
                       $this->flowering[intval($data['flowering'])]=array();
                   }
                   $this->flowering[intval($data['flowering'])]['count']=$data['count'];
               } 
        }
        catch (\Exception $e) {
              $mysqli = $this->db->getDriver()->getConnection()->getResource();  
              if (array_key_exists('firephp', $GLOBALS) && !headers_sent())
                  $GLOBALS['firephp']->error('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error);
              throw new \Exception('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error,1401301242);
        }
        if(array_key_exists('flowering', $this->request) && is_array($this->request['flowering'])) {
             foreach ($this->request['flowering'] as $flowering) {
                 if (array_key_exists($flowering, $this->flowering)) {
                     if (!is_array($this->flowering[$flowering])) {
                         $this->flowering[$flowering]=array();
                     }
                     $this->flowering[$flowering]['selected']=true;
                 }
             }
        }
        $flowering = $this->flowering;
        if ($removeEmpty == true) {
            foreach($flowering as $key => $values) {
                if (!array_key_exists('count', $values) || $values['count'] <1) {
                    unset($flowering[$key]);
                }
            }
        }
        return $flowering;
    }
    /**
     * Gets posture array
     * @return array
     */
    public function getPostureArray() {
        if (!is_array($this->postureArray)) {
            $this->postureArray=array();
            $table = new \Zend\Db\TableGateway\TableGateway('taxa_attribute_value',$this->db);
            $select = $table->getSql()->select();
            $select->columns(array(
                'value'
            ));
            $select->where('`taxa_attribute_id` = '.$this->attributeId['Portamento']);
            $select->group('value');
            $select->order('value');

            try {
                   $statement = $this->content->getTable()->getSql()->prepareStatementForSqlObject($select);
                   $results = $statement->execute();
                   $resultSet = new \Zend\Db\ResultSet\ResultSet();
                   $resultSet->initialize($results);
                   foreach($resultSet->toArray() as $data) {
                       $this->postureArray[]=$data['value'];
                   }
            }
            catch (\Exception $e) {
                  $mysqli = $this->db->getDriver()->getConnection()->getResource();  
                  if (array_key_exists('firephp', $GLOBALS) && !headers_sent())
                      $GLOBALS['firephp']->error('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error);
                  throw new \Exception('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error,1401301242);
            }
        }
        return $this->postureArray;
    }
    /**
     * Gets filtered posture array
     * @param $removeEmpty bool Remove empty items
     * @return array
     */
    public function getFilteredPostureArray($removeEmpty=false) {
        $posture = array();
        if (
            !array_key_exists('Portamento',$this->attributeId) ||
            !is_numeric($this->attributeId['Portamento'])
            ) {
            return $posture;
        }
        $select=$this->createSelect(array('posture'));
        $select->columns(array(
            'taxa_id'
         ));
        $sql = $select->getSqlString($this->db->getPlatform());
        $table = new \Zend\Db\TableGateway\TableGateway('taxa_attribute_value',$this->db);
        $select = $table->getSql()->select();
        $select->columns(array(
            'count'=>new \Zend\Db\Sql\Expression('COUNT(`taxa_attribute_value`.`taxa_id`)'),
            'posture'=>'value',
        ));
        $select->where('`taxa_attribute_id` = '.$this->attributeId['Portamento']);
        $select->where('`taxa_id` IN ('.$sql.')');
        $select->group('value');
        $select->order('value');
        
        try {
               $statement = $this->content->getTable()->getSql()->prepareStatementForSqlObject($select);
               $results = $statement->execute();
               $resultSet = new \Zend\Db\ResultSet\ResultSet();
               $resultSet->initialize($results);
               foreach($resultSet->toArray() as $data) {
                   if (array_key_exists($data['posture'],$posture)) {
                       $posture[$data['posture']]=array();
                   }
                   $posture[$data['posture']]['label']=$data['posture'];
                   $posture[$data['posture']]['count']=$data['count'];
               } 
        }
        catch (\Exception $e) {
              $mysqli = $this->db->getDriver()->getConnection()->getResource();  
              if (array_key_exists('firephp', $GLOBALS) && !headers_sent())
                  $GLOBALS['firephp']->error('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error);
              throw new \Exception('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error,1401301242);
        }
        if(array_key_exists('posture', $this->request) && is_array($this->request['posture'])) {
             foreach ($this->request['posture'] as $postureData) {
                 if (array_key_exists($postureData, $posture)) {
                     if (!is_array($posture[$postureData])) {
                         continue;
                     }
                     $posture[$postureData]['selected']=true;
                 }
             }
        }
        if ($removeEmpty == true) {
            foreach($posture as $key => $values) {
                if (!array_key_exists('count', $values) || $values['count'] <1) {
                    unset($posture[$key]);
                }
            }
        } 
        return $posture;
    }
    /**
     * Gets community array
     * @return array
     */
    public function getCommunityArray() {
        if (!is_array($this->communityArray)) {
            $this->communityArray=array();
            $table = new \Zend\Db\TableGateway\TableGateway('taxa_attribute_value',$this->db);
            $select = $table->getSql()->select();
            $select->columns(array(
                'value'
            ));
            $select->where('`taxa_attribute_id` = '.$this->attributeId['Tipo di vegetazione']);
            $select->group('value');
            $select->order('value');
            $select->limit(10);

            try {
                   $statement = $this->content->getTable()->getSql()->prepareStatementForSqlObject($select);
                   $results = $statement->execute();
                   $resultSet = new \Zend\Db\ResultSet\ResultSet();
                   $resultSet->initialize($results);
                   foreach($resultSet->toArray() as $data) {
                       $this->communityArray[]=$data['value'];
                   }
            }
            catch (\Exception $e) {
                  $mysqli = $this->db->getDriver()->getConnection()->getResource();  
                  if (array_key_exists('firephp', $GLOBALS) && !headers_sent())
                      $GLOBALS['firephp']->error('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error);
                  throw new \Exception('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error,1401301242);
            }
        }
        return $this->communityArray;
    }
    /**
     * Gets filtered community array
     * @param $removeEmpty bool Remove empty items
     * @return array
     */
    public function getFilteredCommunityArray($removeEmpty=false) {
        $community = array();
        $select=$this->createSelect(array('community'));
        $select->columns(array(
            'taxa_id'
         ));
        $sql = $select->getSqlString($this->db->getPlatform());
        $table = new \Zend\Db\TableGateway\TableGateway('taxa_attribute_value',$this->db);
        $select = $table->getSql()->select();
        $select->columns(array(
            'count'=>new \Zend\Db\Sql\Expression('COUNT(`taxa_attribute_value`.`taxa_id`)'),
            'community'=>'value',
        ));
        $select->where('`taxa_attribute_id` = '.$this->attributeId['Tipo di vegetazione']);
        $select->where('`taxa_id` IN ('.$sql.')');
        $select->group('value');
        $select->order('value');
        $select->limit(10);
        try {
               $statement = $this->content->getTable()->getSql()->prepareStatementForSqlObject($select);
               $results = $statement->execute();
               $resultSet = new \Zend\Db\ResultSet\ResultSet();
               $resultSet->initialize($results);
               foreach($resultSet->toArray() as $data) {
                   if (array_key_exists($data['community'],$community)) {
                       $community[$data['community']]=array();
                   }
                   $community[$data['community']]['label']=$data['community'];
                   $community[$data['community']]['count']=$data['count'];
               } 
        }
        catch (\Exception $e) {
              $mysqli = $this->db->getDriver()->getConnection()->getResource();  
              if (array_key_exists('firephp', $GLOBALS) && !headers_sent())
                  $GLOBALS['firephp']->error('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error);
              throw new \Exception('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error,1401301242);
        }
        if(array_key_exists('community', $this->request) && is_array($this->request['community'])) {
             foreach ($this->request['community'] as $communityData) {
                 if (array_key_exists($communityData, $community)) {
                     if (!is_array($community[$communityData])) {
                         continue;
                     }
                     $community[$communityData]['selected']=true;
                 }
             }
        }
        if ($removeEmpty == true) {
            foreach($community as $key => $values) {
                if (!array_key_exists('count', $values) || $values['count'] <1) {
                    unset($community[$key]);
                }
            }
        } 
        return $community;
    }
    /**
     * Gets biologic form array
     * @return array
     */
    public function getBiologicFormArray() {
        if (!is_array($this->biologicFormArray)) {
            $this->postureArray=array();
            $table = new \Zend\Db\TableGateway\TableGateway('taxa_attribute_value',$this->db);
            $select = $table->getSql()->select();
            $select->columns(array(
                'value'
            ));
            $select->where('`taxa_attribute_id` = '.$this->attributeId['Forma biologica']);
            $select->group('value');
            $select->order('value');

            try {
                   $statement = $this->content->getTable()->getSql()->prepareStatementForSqlObject($select);
                   $results = $statement->execute();
                   $resultSet = new \Zend\Db\ResultSet\ResultSet();
                   $resultSet->initialize($results);
                   foreach($resultSet->toArray() as $data) {
                       $this->biologicFormArray[]=$data['value'];
                   }
            }
            catch (\Exception $e) {
                  $mysqli = $this->db->getDriver()->getConnection()->getResource();  
                  if (array_key_exists('firephp', $GLOBALS) && !headers_sent())
                      $GLOBALS['firephp']->error('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error);
                  throw new \Exception('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error,1401301242);
            }
        }
        return $this->biologicFormArray;
    }
    /**
     * Gets filtered biologic form array
     * @param $removeEmpty bool Remove empty items
     * @return array
     */
    public function getFilteredBiologicFormArray($removeEmpty=false) {
        $biologicForm = array();
        if (
            !array_key_exists('Forma biologica',$this->attributeId) ||
            !is_numeric($this->attributeId['Forma biologica'])
            ) {
            return $biologicForm;
        }
        $select=$this->createSelect(array('biologicForm'));
        $select->columns(array(
            'taxa_id'
         ));
        $sql = $select->getSqlString($this->db->getPlatform());
        $table = new \Zend\Db\TableGateway\TableGateway('taxa_attribute_value',$this->db);
        $select = $table->getSql()->select();
        $select->columns(array(
            'count'=>new \Zend\Db\Sql\Expression('COUNT(`taxa_attribute_value`.`taxa_id`)'),
            'biologicForm'=>'value',
        ));
        $select->where('`taxa_attribute_id` = '.$this->attributeId['Forma biologica']);
        $select->where('`taxa_id` IN ('.$sql.')');
        $select->group('value');
        $select->order('value');
        
        try {
               $statement = $this->content->getTable()->getSql()->prepareStatementForSqlObject($select);
               $results = $statement->execute();
               $resultSet = new \Zend\Db\ResultSet\ResultSet();
               $resultSet->initialize($results);
               foreach($resultSet->toArray() as $data) {
                   if (array_key_exists($data['biologicForm'],$biologicForm)) {
                       $biologicForm[$data['biologicForm']]=array();
                   }
                   $biologicForm[$data['biologicForm']]['label']=$data['biologicForm'];
                   $biologicForm[$data['biologicForm']]['count']=$data['count'];
               } 
        }
        catch (\Exception $e) {
              $mysqli = $this->db->getDriver()->getConnection()->getResource();  
              if (array_key_exists('firephp', $GLOBALS) && !headers_sent())
                  $GLOBALS['firephp']->error('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error);
              throw new \Exception('Error in '. get_called_class().' on query '.$select->getSqlString($this->db->getPlatform()).' '.$e->getMessage().' '.$mysqli->errno.' '.$mysqli->error,1401301242);
        }
        if(array_key_exists('biologicForm', $this->request) && is_array($this->request['biologicForm'])) {
             foreach ($this->request['biologicForm'] as $biologicFormData) {
                 if (array_key_exists($biologicFormData, $biologicForm)) {
                     if (!is_array($biologicForm[$biologicFormData])) {
                         continue;
                     }
                     $biologicForm[$biologicFormData]['selected']=true;
                 }
             }
        }
        if ($removeEmpty == true) {
            foreach($biologicForm as $key => $values) {
                if (!array_key_exists('count', $values) || $values['count'] <1) {
                    unset($biologicForm[$key]);
                }
            }
        } 
        return $biologicForm;
    }
    /**
     * Create the select
     * @return \Zend\Db\Sql\Select
     */
    private function createSelect(array $avoid=array()) {
        $select = $this->content->getTable()->getSql()->select();
        if (array_key_exists('text', $this->request) && $this->request['text']!= '') {
            $select->where('
                MATCH (`taxa_search`.`text`) AGAINST ( "'.addslashes($this->request['text']).'" IN NATURAL LANGUAGE MODE)
                ');            
        }
        if  (
                array_key_exists('taxasearchid', $this->request) && 
                is_numeric($this->request['taxasearchid'])
            ) {
            $select->where('`taxa_search`.`lft` >= (SELECT `lft` FROM `taxa_search` WHERE `taxa_id` = '.intval($this->request['taxasearchid']).')');
            $select->where('`taxa_search`.`rgt` <= (SELECT `rgt` FROM `taxa_search` WHERE `taxa_id` = '.intval($this->request['taxasearchid']).')');
        }
        if  (
                array_key_exists('region_all', $this->request) && 
                $this->request['region_all'] == 0 &&
                array_key_exists('region', $this->request) && 
                is_array($this->request['region']) && 
                !in_array('region',$avoid)
            ) {
            $this->request['region']=array_map('intval',$this->request['region']);
            $select->where('`taxa_search`.`taxa_id` IN (SELECT `taxa_id` FROM `taxa_region` WHERE `region_id` IN('.implode(',',$this->request['region']).'))');
        }
        if  (
                array_key_exists('altitude_all', $this->request) &&
                $this->request['altitude_all'] == 0 &&
                array_key_exists('altitude', $this->request) && 
                is_array($this->request['altitude']) && 
                !in_array('altitude',$avoid)
            ) {
            $this->request['altitude']=array_map('intval',$this->request['altitude']);
            $select->where('
                  `taxa_search`.`taxa_id` IN (SELECT `taxa_id` FROM `taxa_search_attribute` WHERE `attribute_id`=1 AND `value` IN ('.implode(',',$this->request['altitude']).'))
            ');
        }
        if  (
                array_key_exists('flowering_all', $this->request) && 
                $this->request['flowering_all'] == 0 &&
                array_key_exists('flowering', $this->request) && 
                is_array($this->request['flowering']) && 
                !in_array('flowering',$avoid)
            ) {
            $this->request['flowering']=array_map('intval',$this->request['flowering']);
            $select->where('
                  `taxa_search`.`taxa_id` IN (SELECT `taxa_id` FROM `taxa_search_attribute` WHERE `attribute_id`=2 AND `value` IN ('.implode(',',$this->request['flowering']).'))
            ');
        }
        if  (
                array_key_exists('posture_all', $this->request) &&
                $this->request['posture_all'] == 0 &&
                array_key_exists('posture', $this->request) &&
                is_array($this->request['posture']) &&
                !in_array('posture',$avoid)
            ) {
            $this->request['posture']=array_map('addslashes',$this->request['posture']);
            $select->where('
                  `taxa_search`.`taxa_id` IN (SELECT `taxa_id` FROM `taxa_attribute_value` WHERE `taxa_attribute_id`='.$this->attributeId['Portamento'].' AND `value` IN ("'.implode('","',$this->request['posture']).'"))
            ');
        }
        if  (
                array_key_exists('biologicForm_all', $this->request) && 
                $this->request['biologicForm_all'] == 0 &&
                array_key_exists('biologicForm', $this->request) && 
                is_array($this->request['biologicForm']) &&
                !in_array('biologicForm',$avoid)
            ) {
            $this->request['biologicForm']=array_map('addslashes',$this->request['biologicForm']);
            $select->where('
                  `taxa_search`.`taxa_id` IN (SELECT `taxa_id` FROM `taxa_attribute_value` WHERE `taxa_attribute_id`='.$this->attributeId['Forma biologica'].' AND `value` IN ("'.implode('","',$this->request['biologicForm']).'"))
            ');
        }
        if  (
                array_key_exists('community_all', $this->request) &&
                $this->request['comunity_all'] == 0 &&
                array_key_exists('community', $this->request) &&
                is_array($this->request['community']) &&
                !in_array('community',$avoid)
            ) {
            $this->request['community']=array_map('addslashes',$this->request['community']);
            $select->where('
                  `taxa_search`.`taxa_id` IN (SELECT `taxa_id` FROM `taxa_attribute_value` WHERE `taxa_attribute_id`='.$this->attributeId['Tipo di vegetazione'].' AND `value` IN ("'.implode('","',$this->request['community']).'"))
            ');
        }
        $select->where('
                (               
                    IFNULL(LENGTH(`taxa_search`.`text`),0)+
                    IFNULL((SELECT COUNT(`filename`) FROM `taxa_image` WHERE `taxa_image`.`taxa_id`=`taxa_search`.`taxa_id`),0)
                ) > 0
             '); 
        $select->where('
                `taxa_search`.`taxa_id` <> 1
             '); 
        return $select;
    }
}
