<?php
$this->getTemplate()->setBlock('middle','observation/detail.phtml');
$taxaObservation = new \floraobservation\TaxaObservation($GLOBALS['db']);
if (!array_key_exists('action', $_REQUEST)) {
   $_REQUEST['action']=null;
}
if (!array_key_exists('start', $_REQUEST)) {
    $_REQUEST['start']=0;
}
if (!array_key_exists('pagelength', $_REQUEST)) {
    $_REQUEST['pagelength']=10;
}
if (array_key_exists('id', $_REQUEST) && $_REQUEST['id'] != '') {
    $taxaObservation->loadFromId($_REQUEST['id']);
    if(!$taxaObservation->getData('valid')) {
        $handle = curl_init();
        curl_setopt($handle, CURLOPT_URL, $GLOBALS['config']->baseUrl.'/undefined_location.html');
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($handle);
        header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found'); 
        echo $response;
        exit;
    }
    $this->getTemplate()->setObjectData($taxaObservation);
}
if ($taxaObservation->getData('id') == '') {
    $taxaObservationColl = new \floraobservation\TaxaObservationColl($GLOBALS['db']);
    $taxaObservationColl->loadAll(array(
        'iDisplayStart'=>$_REQUEST['start'],
        'iDisplayLength'=>$_REQUEST['pagelength'],
        'sColumns'=>'datetime',
        'iSortingCols'=>'1',
        'iSortCol_0'=>'0',
        'sSortDir_0'=>'DESC',
        'valid'=>true
    ));
    $this->getTemplate()->setObjectData($taxaObservationColl);
    $this->getTemplate()->setBlock('middle','observation/middle.phtml');
}
if (array_key_exists('xhr',$_REQUEST) && $_REQUEST['xhr'] == 1) {
   require __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'view'.DIRECTORY_SEPARATOR.'observation'.DIRECTORY_SEPARATOR.'middleContent.phtml';
   exit;
}
$this->getTemplate()->setBlock('head','observation/head.phtml');
$this->getTemplate()->setBlock('footer','observation/footer.phtml');

