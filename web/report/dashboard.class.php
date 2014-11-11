<?php
if(!defined('MYSTAT_VERSION')){
  throw new Exception('File not exist 404');
}

class dashboard{
  
  protected $context;

  public function __construct($context){
    $this->context = $context;
  }

  public function getXML(){
    $report = Array();
    $period = $this->context->getPeriod();
    $report['REPORT'] = Array(
      'TITLE' => $this->context->__('Сводная панель'),
      'SUBTITLE' => $this->context->__('Краткая информация по различным показателям статистики посещения вашего сайта'),
      'PERIOD' => Array(
        'START' => date('d.m.Y',$period['start']),
        'END' => date('d.m.Y',$period['end'])
      ),
      'PATHTOASSET' => $this->context->getPathAsset(),
      'TRANSLATE' => Array(
        'PERIODREPORT' => $this->context->__('Период отображения отчёта'),
      ),
      'INDICATORS' => Array(
        'INDICATOR' => Array(
          Array(
            'NAME' => $this->context->__('Уникальных посетителей'),
            'TYPE' => 'integer',
            'VALUE' => 0
          ),
          Array(
            'NAME' => $this->context->__('Просмотров страниц'),
            'TYPE' => 'integer',
            'VALUE' => 0
          ),
        )
      )
    );
    $xml = new DOMDocument('1.0', 'UTF-8');
    $xml->formatOutput = true;
    $xml->preserveWhiteSpace = false;
    $this->context->xmlStructureFromArray($xml,$report);
    return $xml->saveXML();
  }


}