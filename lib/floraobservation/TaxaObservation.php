<?php
namespace floraobservation;
if (!class_exists('\Autoload')) {
   require __DIR__.'/../core/Autoload.php';
   \Autoload::getInstance();
}
/**
 * Taka Observation class
 *
 * @author caiofior
 */
class TaxaObservation extends \Content
{
    /**
     * GeoPHP Point reference
     * @var /Point
     */
    private $point;
    /**
    * Associates the database table
    * @param \Zend\Db\Adapter\Adapter $db
    */
   public function __construct(\Zend\Db\Adapter\Adapter $db) {
      parent::__construct($db, 'taxa_observation');
   }
   /**
    * Load data from id
    * @param int $id
    */
   public function loadFromId($id) {
       $select = $this->table->getSql()->select();
       $select->columns(array('*',
           'point'=>new \Zend\Db\Sql\Expression('asText(position)')
           ))->where(array($this->primary=>$id));
       $data = $this->table->selectWith($select)->current();
       $mysqli = $this->table->getAdapter()->getDriver()->getConnection()->getResource();
       if (is_object($data)) {
            $this->data = $data->getArrayCopy();
            $this->rawData = $this->data;
       }
       else if($mysqli->errno > 0) {
           throw new \Exception('Error on query '.$select->getSqlString($this->table->getAdapter()->getPlatform()).' '.$mysqli->errno.' '.$mysqli->error,1401301242);
       }
       $this->getCoordinates();
   }
   /**
    * Inserts the data and add the coordinates
    */
   public function insert() {
       if (!array_key_exists('datetime',$this->data) || $this->data['datetime'] == '') {
         $this->data['datetime']=date('Y-m-d H:i:s');
       }
       $this->updateCoordinates();
       parent::insert();
   }
   /**
    * Updates data and add the coordinates
    */
   public function update() {
       if (array_key_exists('point',$this->data)) {
           unset($this->data['point']);
       }
       $this->updateCoordinates();
       parent::update();
   }
   /**
    * Update the coordinates
    */
   private function updateCoordinates() {
       if (is_object($this->point) && $this->point instanceof \Point) {
        $this->data['position']=new \Zend\Db\Sql\Expression('PointFromText("POINT('.$this->point->x().' '.$this->point->y().')")');
       }
   }
   /**
    * Gets coordinates from point data
    */
   private function getCoordinates() {
       if (array_key_exists('point', $this->rawData) && $this->rawData['point'] != '') {
         $this->point = \geoPHP::load($this->rawData['point'],'wkt');
       }
   }
    /**
     * Gets the associated taxa observation image collection
     * @return \floraobservation\TaxaObservationImageColl
     */
    public function getTaxaObservationImageColl() {
        $taxaObservationImageColl = new \flora\taxa\TaxaObservationImageColl($this->db);
        if (array_key_exists('id', $this->data) && $this->data['id'] != '') {
            $taxaObservationImageColl->loadAll(array('taxa_observation_id' => $this->data['id']));
        }
        return $taxaObservationImageColl;
    }
     /**
     * Deletes also the associated data
     */
    public function delete() {
        foreach ($this->getTaxaObservationImageColl()->getItems() as $image) {
            $image->delete();
        }
        parent::delete();
    }
    /**
    * Sets the data
    * @param variant $data
    * @param string|null $field
    */
    public function setData($data,$field=null){
        parent::setData($data, $field);
        $this->getCoordinates();
    }
    /**
     * Returns the GEOphp point object
     * @return /Point
     */
    public function getPoint () {
        return $this->point;
    }
    /**
     * Sets the GEOphp point object
     * @return /Point
     */
    public function setPoint (\Point $point) {
        $this->point = $point;
    }
    /**
     * Gets the associated taxa
     * @return \flora\Taxa
     */
    public function getTaxa() {
        $taxa = new \flora\taxa\Taxa($this->db);
        if (array_key_exists('taxa_id', $this->data) && $this->data['taxa_id'] != '') {
            $taxa->loadFromId($this->data['taxa_id']);
        }
        return $taxa;
    }
}