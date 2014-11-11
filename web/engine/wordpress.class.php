<?php
if(!defined('MYSTAT_VERSION') and !defined( 'WP_UNINSTALL_PLUGIN' )){
  throw new Exception('File not exist 404');
}

class wordpress{

  protected $run = false;
  protected $php = false;

  public function getName(){
    return 'wordpress';
  }

  public function isEngineRun(){
    if(!function_exists('register_activation_hook')){
      return 'Engine can not run without WordPress CMS';
    }
    register_activation_hook(realpath(dirname(__FILE__).'/../index.php'),array($this,'installPlugin'));
    register_deactivation_hook(realpath(dirname(__FILE__).'/../index.php'),array($this,'unstallPlugin'));
    register_uninstall_hook(basename(dirname(__FILE__)).'/'.basename(__FILE__),Array(__CLASS__,'removePlugin'));
    add_action('plugins_loaded',Array($this,'updatePlugin'));
    add_action('admin_menu',Array($this,'addMenu'));
    add_action('wp_ajax_mystat',Array($this,'ajaxRun'));
    add_action('wp_footer',Array($this,'addHookCode'));
    return true;
  }

  public function setRunHook($el,$func){
    $this->run = Array($func,$el);
  }

  public function getParam($name,$default){
    return isset($_POST[$name])?$_POST[$name]:$default;
  }

  public function __($text){
    return __($text,'mystat');
  }

  public function getWebPath(){
    return plugins_url('asset/', dirname(__FILE__));
  }

  public function setCodeHook($el,$func){
    $this->php = Array($func,$el);
  }

##############################################################

  public function addHookCode(){
    call_user_func(array_shift($this->php),array_shift($this->php));
  }

  public function installPlugin(){
    if(!current_user_can('activate_plugins')){return;}
    $plugin = isset($_REQUEST['plugin'])?$_REQUEST['plugin']:'';
    check_admin_referer('activate-plugin_'.$plugin);

    global $wpdb;

    $charset_collate = '';
    if(!empty($wpdb->charset)){
      $charset_collate = 'DEFAULT CHARACTER SET '.$wpdb->charset;
    }
    if(!empty( $wpdb->collate ) ) {
      $charset_collate.= ' COLLATE '.$wpdb->collate;
    }
    require_once(ABSPATH.'wp-admin/includes/upgrade.php');
    $table_name = $wpdb->prefix.'mystatdata';
    $sql = 'CREATE TABLE '.$table_name.' ('."\n".
      "id int(11) UNSIGNED NOT NULL AUTO_INCREMENT,"."\n".
      "type enum('1','2','3','4','5','6') NOT NULL default '1',"."\n".
      "value1 varchar(255) NOT NULL default '',"."\n".
      "value2 varchar(255) NOT NULL default '',"."\n".
      "value3 varchar(255) NOT NULL default '',"."\n".
      "value4 varchar(255) NOT NULL default '',"."\n".
      'UNIQUE KEY id (id),'."\n".
      "KEY indextype (type)"."\n".
    ') '.$charset_collate.';';
    dbDelta($sql);
    if(get_site_option('mystat_version')){
      update_site_option('mystat_version',MYSTAT_VERSION);
    }else{
      add_site_option('mystat_version',MYSTAT_VERSION);
    }
  }

  public function unstallPlugin(){
    if(!current_user_can('activate_plugins')){return;}
    $plugin = isset($_REQUEST['plugin'])?$_REQUEST['plugin']:'';
    check_admin_referer('deactivate-plugin_'.$plugin);

    global $wpdb;
    $wpdb->query('DROP TABLE '.$wpdb->prefix.'mystatdata;');
  }
  
  public static function removePlugin(){
    if(!current_user_can('activate_plugins') or __FILE__ != WP_UNINSTALL_PLUGIN){return;}
    check_admin_referer('bulk-plugins');
    $f = fopen(realpath(dirname(__FILE__).'/../../log.log'),'a+');
    fwrite($f,'REMOVE'."\n");
    fclose($f);
  }

  public function updatePlugin(){
    if(get_site_option('mystat_version') != MYSTAT_VERSION){
      $this->installPlugin();
    }
  }

  public function addMenu(){
    add_menu_page('pagetitle','<small>Моя</small> <sup>Статистика</sup>','update_plugins','statistics.html',function(){$this->setOpenPage();},'dashicons-chart-bar',4);
  }

  public function setOpenPage(){
    call_user_func(array_shift($this->run),array_shift($this->run));
  }

  public function ajaxRun(){
    $this->setOpenPage();
    exit;
  }

}
