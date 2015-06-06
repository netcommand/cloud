(function ($) {

  var uuid = "";
  var online = 0;
  var onlineseek = 0;
  var cmd = "";
  var timer;
  var timer_online;

  offline = function(){
    online = 0;
    onlineseek = 0;
    $.ajax({
      type: "POST",
      url: "./",
      data: "uuid="+uuid,
      success: function(result){
        if(result != '') view(result,'start');
      }
    });
    if($('#upload ul li').length) {
      $('#upload ul li').remove();
    }
  };

  autologout = function() {
    clearTimeout(timer_online);
    if(online > 0) {
      onlineseek++;
      if(onlineseek == online) {
        offline();
      };
    }
    timer_online = setTimeout('autologout()',60000);
  }

  notifytrigger = function(t) {
    if(t == 0) {
      clearTimeout(timer);
      $('#notify').remove();
      return true;
    } timer = setTimeout('notifytrigger(0)',t);
  };

  notify = function(c){
    clearTimeout(timer);
    $('#notify').remove();
    notifytrigger(1500);
    $('#con').append("<div id='notify'>"+c+"</div>");
    onlineseek = 0;
  };

  view = function(result,id){
    if(result.charAt(0) == '$') {
      $('#process').html(result.substr(1)).promise().done(function(){
        if($('#view').is(':visible')) {
          $('#view').hide();
          $('#process').show();
        }
        cmd = id;
      });
    } else $('#view').html(result).promise().done(function(){
      if($('#process').is(':visible')) {
        $('#process').hide();
        $('#view').show();
      }
      if(id == 'start') {
        uuid = $('#uuid').val();
        $('#uuid').remove();
        if($('#upload').length) {
          var a = $('#upload').attr('action');
          $('#upload').attr('action',a.substr(a,a.length - 32)+uuid);
        }
      } else if(id == 'explorer') { }
        else online = parseInt($('#online').val());
    });
  };

  reload = function(f){
    $.ajax({
      type: "POST",
      url: "./",
      data: "cmd=explorer&file="+f+"&uuid="+uuid,
      success: function(result){
        if(result != '') {
          view(result,'explorer');
        } else offline();
      }
    });
  };

  command = function(cmd,data){
    $.ajax({
      type: "POST",
      url: "./",
      data: data+"&uuid="+uuid,
      success: function(result){
        notify("");
        if(result != '') {
          if(result.charAt(0) == '#') {
            if(result.length > 1) {
              $('#i').attr('src',result.substr(1));
              if($('.sub').is(':visible')) $('.sub:visible').hide('fast');
            } else reload('uploads');
          } else {
            if(cmd == 'save' || cmd == 'deletenow') {
              reload('setup');
            } else if(cmd == 'chmod' || cmd == 'rm') {
              reload('uploads');
            } else view(result,cmd);
          }
        } else offline();
      }
    });
  };

  $(document).on('click','.send',function(){
    var cmd,data;
    if($(this).attr('alt') != undefined) {
      cmd = "explorer";
      data = "cmd="+cmd+"&file="+$(this).attr('alt');
    } else if($(this).hasClass('chmod')) {
      cmd = "chmod";
      data = "cmd="+cmd+"&file="+$(this).parent().children(':first').attr('alt');
    } else if($(this).hasClass('rm')) {
      cmd = "rm";
      data = "cmd="+cmd+"&file="+$(this).parent().children(':first').attr('alt');
    } else if($(this).hasClass('save')) {
      cmd = "save";
      data = "cmd="+cmd+"&access="+$(this).parent().find('input[type=checkbox]').is(':checked')+
      "&id="+$(this).parent().parent().parent().prev().attr('alt')+
      "&name="+$(this).parent().parent().prev().find('input[type=text]').val()+
      "&pass="+$(this).parent().parent().prev().find('input[type=password]').val();
    } else if($(this).hasClass('delete')) {
      cmd = "delete";
      data = "cmd="+cmd+"&user="+$(this).parent().parent().parent().prev().attr('alt');
    } else {
      var id = $(this).attr('id');
      if(id == 'yes' || id == 'no') return false;
      cmd = id;
      data = "cmd="+cmd;
      if(cmd == 'login') data += "&user="+$('#user').val()+"&pass="+$('#pass').val();
      else if(cmd == 'reg') data += "&user="+$('#user').val()+"&pass="+$('#pass').val()+"&pass2="+$('#pass2').val();
    }
    command(cmd,data);
  });

  $(document).on('click','.sel',function(){
    var sub = $(this).next();
    if(sub.is(':visible')) {
      sub.hide('fast');
    } else {
      if($('.sub').is(':visible')) {
        $('.sub:visible').hide('fast',function() {
          sub.show('fast');
        });
      } else {
        sub.show('fast');
      }
    }
  });

  $(document).on('click','#yes',function(){
    if(cmd == 'login') {
      $('#pass2').show();
      $('#login').hide();
      $('#reg').show();
    } else if(cmd == 'delete') {
      cmd = "deletenow";
      command(cmd,"cmd="+cmd+"&id="+$('#Qid').val());
    }
    if($('#process').is(':visible')) {
      $('#process').hide();
      $('#view').show();
    }
  });

  $(document).on('click','#no',function(){
    if(cmd == 'login' || cmd == 'reg') {
      $('#pass2').hide();
      if(cmd == 'reg') {
        $('#reg').hide();
        $('#login').show();
      }
    }
    if($('#process').is(':visible')) {
      $('#process').hide();
      $('#view').show();
    }
  });

  $(document).on('click','#logout',function(){
    offline();
  });

  $(document).on('change','.e input:checkbox',function(){
    if($(this).is(':checked')) {
      $(this).parent().parent().parent().prev().css('background-image','url(css/access.png)');
    } else {
      $(this).parent().parent().parent().prev().css('background-image','url(css/denied.png)');
    }
  });

  $(document).on('keyup','html',function(e){
    var key = e.which;
    if(key == 13) {
      $('.send').filter(':visible:first:not(.folder):not(.file)').trigger('click');
    } else if(key == 27) {
      offline();
    }
  });

  $(document).ready(function(){
    uuid = $('#uuid').val();
    $('#uuid').remove();
    $('#upload').attr('action',$('#upload').attr('action')+"?key="+uuid);
    autologout();
  });

  $(function(){
    var ul = $('#upload ul');
    $(document).on('click','#drop a',function(){
      $(this).parent().find('input').click();
    });
    if(typeof $('#upload').fileupload === 'function')
    $('#upload').fileupload({
      dropZone: $('#drop'),
      add: function (e, data) {
        var tpl = $('<li class="working"><input type="text" value="0" data-width="48" data-height="48"'+
        ' data-fgColor="#0788a5" data-readOnly="1" data-bgColor="#3e4043" /><p></p><span></span></li>');
        tpl.find('p').text(data.files[0].name).append('<i>' + formatFileSize(data.files[0].size) + '</i>');
        data.context = tpl.appendTo(ul);
        tpl.find('input').knob();
        tpl.find('span').click(function(){
          if(tpl.hasClass('working')){
            jqXHR.abort();
          }
          tpl.fadeOut(function(){
            tpl.remove();
          });
        });
        var jqXHR = data.submit();
      },
      progress: function(e, data){
        var progress = parseInt(data.loaded / data.total * 100, 10);
        data.context.find('input').val(progress).change();
        if(progress == 100){
          data.context.removeClass('working');
        }
        onlineseek = 0;
      },
      fail:function(e, data){
        data.context.addClass('error');
      },
      done:function(e, data){
        if(data.result != 1) {
          data.context.addClass('error');
        } else {
          $.ajax({
            type: "POST",
            url: "./",
            data: "cmd=explorer&file=uploads&uuid="+uuid,
            success: function(result){
              notify("");
              if(result != '') {
                view(result,'explorer');
              } else offline();
            }
          });
        }
      }
    });
    $(document).on('drop dragover', function (e) {
      e.preventDefault();
    });
    function formatFileSize(bytes) {
      if(typeof bytes !== 'number') {
        return '';
      }
      if(bytes >= 1000000000) {
        return (bytes / 1000000000).toFixed(2) + ' GB';
      }
      if(bytes >= 1000000) {
        return (bytes / 1000000).toFixed(2) + ' MB';
      }
      return (bytes / 1000).toFixed(2) + ' KB';
    }
  });

})(jQuery);
