<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output method="html"/>
  <xsl:template match="/">
    <style>
      .wrap .wp-filter .period{padding:10px 0;line-height:27px;}
      .wrap .wp-filter .period .text{line-height:28px;margin-right:20px;}
      .pressthis a:after{width: 130px;}
    </style>
    <link rel="stylesheet" href="{//REPORT/PATHTOASSET}daterangepicker.min.css" />
    <script type="text/javascript" src="{//REPORT/PATHTOASSET}moment.min.js"></script>
    <script type="text/javascript" src="{//REPORT/PATHTOASSET}jquery.daterangepicker.js"></script>
    <div class="wrap">
      <h2><xsl:value-of select="//REPORT/TITLE"/></h2>
      <p class="pressthis"><a><span><xsl:value-of select="//REPORT/SUBTITLE"/></span></a></p>
      <div class="wp-filter">
        <div class="period"><span class="text"><xsl:value-of select="//REPORT/TRANSLATE/PERIODREPORT"/></span> <a class="button" data-range="{//REPORT/PERIOD/START} - {//REPORT/PERIOD/END}" id="dataselectrange"><span class="data"><xsl:value-of select="//REPORT/PERIOD/START"/> - <xsl:value-of select="//REPORT/PERIOD/END"/></span> <span class="spinner"></span></a></div>
      </div>
      
      <div class="postbox">
        
      </div>
    
    
    
    </div>
    <script type="text/javascript"><![CDATA[
      jQuery(document).ready(function($){
        $('#dataselectrange').dateRangePicker({
          shortcuts: {
            'next-days': null,
            'next': null,
            'prev-days': [1,7,30],
            'prev' : ['week','month']
          },
          separator: ' - ',
          language: 'auto',
          format: 'DD.MM.YYYY',
          endDate: new Date(),
          showPrevMonth: true,
          startOfWeek: 'monday',
          minDays: 1,
          maxDays: 365,
          getValue: function(){
            return $('#dataselectrange .data').html();
          },
          setValue: function(s){
            $('#dataselectrange .data').html(s);
          }
        }).bind('datepicker-close',function(event,obj){
          if(obj.value!=$('#dataselectrange').attr('data-range')){
            $('#dataselectrange .spinner').show();
            $.ajax({
              url: ajaxurl,
              data: {
                action: 'mystat',
                page: 'dashboard',
                datestart: moment(obj.date1).format('DD.MM.YYYY'),
                dateend: moment(obj.date2).format('DD.MM.YYYY')
              },
              dataType: 'html',
              type: 'POST',
              success: function(data, textStatus){
                $('#mystat').replaceWith(data);
              },
              error: function(){
                document.location.reload();
              }
            });
          }
        });
      });
    ]]></script>
  </xsl:template>
</xsl:stylesheet>