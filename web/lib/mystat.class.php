<?php
if(!defined('MYSTAT_VERSION')){
  throw new Exception('File not exist 404');
}

class myStat{

  protected $engine = false;

  public function getEngine(){
    return $this->engine;
  }

  public function setEngine($engine){
    if(!preg_match('/^[A-z0-9]{1,}$/',$engine) or !file_exists(dirname(__FILE__).'/../engine/'.$engine.'.class.php')){
      throw new Exception('Wrong ENGINE param in setEngine()');
    }
    require_once(dirname(__FILE__).'/../engine/'.$engine.'.class.php');
    $this->engine = new $engine();
    if(true !== $error = $this->engine->isEngineRun()){
      throw new Exception('ENGINE START ERROR: '.$error);
    }
    $this->engine->setCodeHook($this,function($mystat){
      $id = $this->setStatisticFirst();
      echo $this->getJsCode($id);
    });
    return $this;
  }

  public function run(){
    if($this->getEngine()===false){
      throw new Exception('Set ENGINE before run run()');
    }
    $this->getEngine()->setRunHook($this,function($mystat){
      echo '<div id="mystat">'.$mystat->getReportPage().'</div>';
    });
  }

  public function getReportPage(){
    $page = (string)$this->getEngine()->getParam('page','dashboard');
    if(!preg_match('/^[A-z0-9]{1,}$/',$page) or !file_exists(dirname(__FILE__).'/../report/'.$page.'.class.php')){
      throw new Exception('No report found');
    }
    $xml = $this->getXMLPage($page);
    return $this->getXSLTranform($page,$xml);
  }

  protected function getXSLTranform($page,$xml){
    if(!file_exists(dirname(__FILE__).'/../theme/'.$this->getEngine()->getName().'.'.$page.'.xsl')){
      throw new Exception('No theme found for this page or engine');
    }
    $doc = new DOMDocument();
    $doc->preserveWhiteSpace = false;
    $xsl = new XSLTProcessor();
    $doc->load(dirname(__FILE__).'/../theme/'.$this->getEngine()->getName().'.'.$page.'.xsl');
    $xsl->importStyleSheet($doc);
    $doc->loadXML($xml);
    return $xsl->transformToXML($doc);
  }

  protected function getXMLPage($page){
    require_once(dirname(__FILE__).'/../report/'.$page.'.class.php');
    $report = new $page($this);
    $xml = $report->getXML();
    return $xml;
  }

  public function getPeriod(){
    $ret = Array(
      'start' => strtotime('-30 days'),
      'end' => time()
    );
    if((''!=$date1 = $this->getEngine()->getParam('datestart','')) and (''!=$date2 = $this->getEngine()->getParam('dateend',''))){
      if(!preg_match('/^[0-9]{2}\.[0-9]{2}\.[0-9]{4}$/',$date1) or !preg_match('/^[0-9]{2}\.[0-9]{2}\.[0-9]{4}$/',$date2)){
        throw new Exception('Wrong date format in request');
      }
      $ret['start'] = strtotime((string)$date1);
      $ret['end'] = strtotime((string)$date2);
    }
    return $ret;
  }

  public function __($text){
    return $this->getEngine()->__($text);
  }

  public function getPathAsset(){
    return trim($this->getEngine()->getWebPath(),'/').'/';
  }

  public function xmlStructureFromArray($xml,$arr,$child=false,$name='',$at = Array()){
    if(!is_array($arr)){return $xml;}
    foreach($arr as $k=>$v){
      if(substr($k,0,1)=='@'){continue;}
      if(is_array($v)){
        if(!isset($v[0])){
          $el = !$child?$xml->appendChild($xml->createElement($k)):$child->appendChild($xml->createElement(is_numeric($k)?$name:$k));
          if(isset($arr['@'.$k])){
            foreach($arr['@'.$k] as $nn=>$aa){
              $el->setAttribute($nn,$aa);
            }
          }elseif(isset($at[$k])){
            foreach($at[$k] as $nn=>$aa){
              $el->setAttribute($nn,$aa);
            }
          }
        }else{
          $el = !$child?$xml:$child;
          $attr = Array();
          if(isset($arr['@'.$k])){
            $attr = $arr['@'.$k];
          }
        }
        $this->xmlStructureFromArray($xml,$v,$el,$k,isset($attr)?$attr:Array());
      }else{
        if(!$child){
          if(in_array($v,Array('',null,false),true)){
              $el = $xml->createElement($k);
              $xml->appendChild($el);
          }else{
            $el = $xml->createElement($k,htmlspecialchars($v,ENT_NOQUOTES));
            $xml->appendChild($el);
          }
          if(isset($arr['@'.$k])){
            foreach($arr['@'.$k] as $nn=>$aa){
              $el->setAttribute($nn,$aa);
            }
          }
        }else{
          if(in_array($v,Array('',null,false),true)){
            $el = $xml->createElement(is_numeric($k)?$name:$k);
            $child->appendChild($el);
          }else{
            $el = $xml->createElement(is_numeric($k)?$name:$k,htmlspecialchars($v,ENT_NOQUOTES));
            $child->appendChild($el);
          }
          if(is_numeric($k)){
            if(isset($at[$k])){
              foreach($at[$k] as $nn=>$aa){
                $el->setAttribute($nn,$aa);
              }
            }
          }else{
            if(isset($arr['@'.$k])){
              foreach($arr['@'.$k] as $nn=>$aa){
                $el->setAttribute($nn,$aa);
              }
            }
          }
        }
      }
    }
    return $xml;
  }

  protected function getJsCode($id){
    $MYSTAT_VERSION = MYSTAT_VERSION;
    $ret = '<script type="text/javascript" charset="utf-8">';
      $ret.= <<<JS
        function runStatisticMyStat(){
          var myStat_ver='{$MYSTAT_VERSION}';
          var myStat = {
            geolocation: !!navigator.geolocation,
            offline: !!window.applicationCache,
            webworker: !!window.Worker,
            localStorage: ('localStorage' in window) && window['localStorage'] !== null,
            canvas: {
              enable: !!document.createElement('canvas').getContext,
              text2d: !!document.createElement('canvas').getContext?(typeof document.createElement('canvas').getContext('2d').fillText == 'function'):false
            },
            video: {
              enable: !!document.createElement('video').canPlayType,
              captions: 'track' in document.createElement('track'),
              poster: 'poster' in document.createElement('video'),
              mp4: !!(document.createElement('video').canPlayType && document.createElement('video').canPlayType('video/mp4; codecs="avc1.42E01E, mp4a.40.2"').replace(/no/, '')),
              webm: !!(document.createElement('video').canPlayType && document.createElement('video').canPlayType('video/webm; codecs="vp8, vorbis"').replace(/no/, '')),
              theora: !!(document.createElement('video').canPlayType && document.createElement('video').canPlayType('video/ogg; codecs="theora, vorbis"').replace(/no/, ''))
            },
            microdata: !!document.getItems,
            history: !!(window.history && window.history.pushState && window.history.popState),
            undo: typeof UndoManager !== 'undefined',
            audio: {
              enable: !!document.createElement('audio').canPlayType,
              mp3: !!(document.createElement('audio').canPlayType && document.createElement('audio').canPlayType('audio/mpeg;').replace(/no/, '')),
              vorbis: !!(document.createElement('audio').canPlayType && document.createElement('audio').canPlayType('audio/ogg; codecs="vorbis"').replace(/no/, '')),
              wav: !!(document.createElement('audio').canPlayType && document.createElement('audio').canPlayType('audio/wav; codecs="1"').replace(/no/, '')),
              aac: !!(document.createElement('audio').canPlayType && document.createElement('audio').canPlayType('audio/mp4; codecs="mp4a.40.2"').replace(/no/, ''))
            },
            command: 'type' in document.createElement('command'),
            datalist: 'options' in document.createElement('datalist'),
            details: 'open' in document.createElement('details'),
            device: 'type' in document.createElement('device'),
            validation: 'noValidate' in document.createElement('form'),
            iframe: {
              sandbox: 'sandbox' in document.createElement('iframe'),
              srcdoc: 'srcdoc' in document.createElement('iframe')
            },
            input: {
              autofocus: 'autofocus' in document.createElement('input'),
              placeholder: 'placeholder' in document.createElement('input'),
              type: {}
            },
            meter: 'value' in document.createElement('meter'),
            output: 'value' in document.createElement('output'),
            progress: 'value' in document.createElement('progress'),
            time: 'valueAsDate' in document.createElement('time'),
            editable: 'isContentEditable' in document.createElement('span'),
            dragdrop: 'draggable' in document.createElement('span'),
            documentmessage: !!window.postMessage,
            fileapi: typeof FileReader != 'undefined',
            serverevent: typeof EventSource !== 'undefined',
            sessionstorage: false,
            svg: !!(document.createElementNS && document.createElementNS('http://www.w3.org/2000/svg', 'svg').createSVGRect),
            simpledb: !!window.indexedDB,
            websocket: !!window.WebSocket,
            websql: !!window.openDatabase,
            cookies: true,
            flash: false,
            java: !!navigator.javaEnabled(),
            title: document.title,
            appname: navigator.appName,
            screen: {
              width: screen.width,
              height: screen.height,
              depth: (navigator.appName.substring(0,2)=='Mi')?screen.colorDepth:screen.pixelDepth
            }

          };
          var inputlist = ['color','email','number','range','search','tel','url','date','time','datetime','datetime-local','month','week'];
          var i = document.createElement('input');
          for(var key in inputlist){
            var el = inputlist[key];
            i.setAttribute('type', el);
            myStat.input.type[el] = i.type !== 'text';
          }
          try{myStat.sessionstorage = (('sessionStorage' in window) && window['sessionStorage'] !== null);}catch(e){}
          if(!document.cookie){
            document.cookie = "testCookie=1; path=/";
            myStat.cookies = document.cookie?1:0;
          }
          if(navigator.plugins && navigator.plugins.length){
            for(var ii=0;ii<navigator.plugins.length;ii++){
              if(navigator.plugins[ii].name.indexOf('Shockwave Flash')!=-1){
                myStat.flash=parseFloat(navigator.plugins[ii].description.split('Shockwave Flash ')[1],10)>0;
                break;
              }
            }
          }else if(window.ActiveXObject){
            for(var ii=10;ii>=2;ii--){
              try{
                var f=eval("new ActiveXObject('ShockwaveFlash.ShockwaveFlash."+ii+"');");
                if(f){myStat.flash=parseFloat(ii+'.0')>0;break;}
              }catch(ee){}
            }
            if((myStat.flash=='')&&(navigator.appVersion.indexOf("MSIE 5")>-1||navigator.appVersion.indexOf("MSIE 6")>-1)){
              FV=clientInformation.appMinorVersion;
              if(FV.indexOf('SP2') != -1)myStat.flash = true;
            }
          }
          console.info(myStat);
        }
        jQuery(document).ready(function($){
          runStatisticMyStat();
        });
JS;
    $ret.= '</script>';
    return $ret;
  }

  protected function setStatisticFirst(){
    return '';
  }

}

