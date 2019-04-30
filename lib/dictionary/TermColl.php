<?php
namespace Dictionary;
/**
 * Description of Term Coll
 *
 * @author caiofior
 */
class TermColl extends \ContentColl {
      /**
       * Relation with th content
       * @param type $db
       */
      public function __construct($db) {
         parent::__construct(new \dictionary\Term($db));
      }

      /**
      * Customizes select statement
      * @param Zend_Db_Select $select Zend Db Select
      * @param array $criteria Filtering criteria
      * @return Zend_Db_Select Select is expected
      */
    protected function customSelect( \Zend\Db\Sql\Select $select,array $criteria ) {
       $select ->columns(array('*'));
       $select = $this->setFilter($select,$criteria);
       return $select;
    }
    /**
     * Count items
     * @return int
     */
    public function countAll($criteria = array()) {
      $select = $this->content->getTable()->getSql()->select()->columns(array(new \Zend\Db\Sql\Expression('COUNT(*)')));
      $select = $this->setFilter($select,$criteria);
      $statement = $this->content->getTable()->getSql()->prepareStatementForSqlObject($select);
      $results = $statement->execute();
      $resultSet = new \Zend\Db\ResultSet\ResultSet();
      $resultSet->initialize($results);
      $data = $resultSet->current()->getArrayCopy();
      return intval(array_pop($data));
    }
    /**
     * Sets the filter
     * @param \Zend\Db\Sql\Select $select
     * @param array $criteria
     * @return \Zend\Db\Sql\Select
     */
    private function setFilter ($select,$criteria) {
      if (array_key_exists('term', $criteria) && $criteria['term'] != '') {
         $criteria['sSearch']=$criteria['term'];
      }
      if (array_key_exists('sSearch', $criteria) && $criteria['sSearch'] != '') {
         $select->where(' ( `term`.`term` LIKE "'.addslashes($criteria['sSearch']).'%" OR `term`.`abbreviation` LIKE "'.addslashes($criteria['sSearch']).'%" ) ');
      }
      if (array_key_exists('images', $criteria) && $criteria['images'] != '') {
         if ($criteria['images'] == 0 ) {
            $select->where(' (SELECT COUNT(`term_image`.`id`) FROM `term_image` WHERE `term_image`.`term_id`=`term`.`id`) = 0 ');
         } else {
            $select->where(' (SELECT COUNT(`term_image`.`id`) FROM `term_image` WHERE `term_image`.`term_id`=`term`.`id`) = 1 ');
         }
      }      
      return $select;
    }
}