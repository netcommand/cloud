<?php

@include "cfg.inc.php";
@include "language/".LANGUAGE.".php";
@include "api.php";

if(isset($_GET['key']) AND UPLOAD) {
  if(isset($_FILES['upl'])) {
    CLOUD::connect($_GET['key']);
    if(CLOUD::access()) {
      if($_FILES['upl']['error'] === UPLOAD_ERR_OK) {
        if(is_uploaded_file($_FILES['upl']['tmp_name'])) {
          $file = CLOUD::fileWithOptions($_FILES['upl']['name']);
          if(is_file(FILES.'/uploads/'.$file[0]) === false
          AND is_file(FILES.'/uploads/'.$file[1]) === false) {
            if(CLOUD::userQuota()) {
              $s = @filesize($_FILES['upl']['tmp_name']);
              if(((CLOUD::$quota + $s) / 1048576) <= QUOTA) {
                if(move_uploaded_file($_FILES['upl']['tmp_name'],
                FILES.'/uploads/'.$file[0])) {
                  echo 1;
                }
              }
            }
          }
        }
      }
    }
  }
  exit(0);
}

$cmd = "start";
if(isset($_POST['cmd'])) $cmd = $_POST['cmd'];
if(isset($_POST['uuid'])) {
  if($_POST['uuid']) {
    CLOUD::connect($_POST['uuid']);
    if($cmd == 'start') CLOUD::disconnectUSER();
  }
}
if($cmd != 'start' AND $cmd != 'login' AND $cmd != 'reg') {
  if(!CLOUD::access()) exit(0);
}
if(isset($_GET['d'])) $cmd = "download";

switch ($cmd) {

  case 'start':
    CLOUD::section("<input id='uuid' type='hidden' value='".md5(uniqid(mt_rand(0,65535),true))."' /><input id='user' type='text' placeholder='"._USERNAME."' title='"._USERNAME."' value='' maxlength='20' /><input id='pass' type='password' placeholder='"._PASSWORD."' title='"._PASSWORD."' value='' /><input class='invisible' id='pass2' type='password' placeholder='"._REPEAT."' title='"._REPEAT."' value='' />",50).CLOUD::section("<input class='send' id='login' type='button' value='"._NEXT."' /><input class='send invisible' id='reg' type='button' value='"._REGISTER."' />",25,"");
  break;

  case 'login':
  case 'reg':
    if(isset($_POST['user']) AND isset($_POST['pass']) AND isset($_POST['uuid'])) {
      if($_POST['user'] AND mb_strlen($_POST['pass'],'utf-8') > 5) {
        if(CLOUD::connectUSER($_POST['user'],$_POST['pass'])) {
          CLOUD::section("<input id='online' type='hidden' value='".AUTOLOGOUT."' />"._SUCCESS,100).CLOUD::section("<input class='send' id='explorer' type='button' value='"._EXPLORER."' />",25,"");
        } else {
          if($cmd == 'reg' AND isset($_POST['pass2'])) {
            if($_POST['pass'] == $_POST['pass2']) {
              CLOUD::newUSER($_POST['user'],$_POST['pass']);
              CLOUD::content("$");
              CLOUD::section(_THANKS."<p>"._PATIENCE." <span class='mark'>".SU."</span> "._UNLOCK."</p>",100,"Hinweis").CLOUD::section("<input class='send' id='start' type='button' value='"._LOGIN."' />",25,"");
              break;
            }
          }
          CLOUD::content("$");
          CLOUD::section(_WARNING."<p class='mark'>"._NEWUSER."</p>",100,_QUESTION).CLOUD::section("<input class='send' id='yes' type='button' value='"._YES."' /><br class='clear_' /><input class='send' id='no' type='button' value='"._NO."' />",25,"");
        }
      }
    }
  break;

  case 'explorer':
    $root = FILES;
    if(isset($_POST['file'])) {
      $file = str_replace("..","",$_POST['file']);
      if($file != 'setup' AND !@file_exists($root."/".$file)) die("#");
      if(@is_file($root."/".$file)) {
        if(CLOUD::isVisible($file)) {
          if(!isset($_SERVER['HTTPS'])) { $scheme = "http://"; } else { $scheme = "https://"; }
          die("#".$scheme.$_SERVER['HTTP_HOST'].substr($_SERVER['PHP_SELF'],0,-9)
          ."download-".CLOUD::setDownload($root."/".$file));
        } else die("#");
      } else CLOUD::$folder = $file;
    }
    if(CLOUD::$folder) $root .= "/".CLOUD::$folder;
    $ba = ""; $f = "";
    if(CLOUD::$folder) {
      $pos = strrpos(CLOUD::$folder,"/");
      $ba = "<div class='send folder' alt='".($pos !== false ? substr(CLOUD::$folder,0,$pos) : "")."'>..</div>";
      $f = CLOUD::$folder."/";
    }
    $logout = "<div id='logout'>&nbsp;</div>";
    if(isset($file) AND $file == 'setup') {
      $ac = "";
      $data = array();
      if(CLOUD::getID() == 1) {
        $data = CLOUD::getUser();
      } else $data = CLOUD::getData();
      if($data) {
        for($i=0;$i<sizeof($data);$i++) {
          $ac .= "<div class='sel own ".(($data[$i]['locked'] < LOCKED) ? "access" : "denied")."' alt='#".((CLOUD::getID() == 1) ? $data[$i]['id'] : "")."'>".$data[$i]['cuser'].((CLOUD::getID() == 1) ? " <span class='node'>ID:".$data[$i]['id']."</span>" : "")."</div><div class='sub e'><section class='row50'><div class='wrapper'><input type='text' placeholder='"._USERNAME_UPDATE."' title='"._USERNAME_UPDATE."' value='".$data[$i]['cuser']."' maxlength='20' /><input type='password' placeholder='"._PASSWORD_UPDATE."' title='"._PASSWORD_UPDATE."' value='' /></div></section><section class='row25'><div class='wrapper'>".((CLOUD::getID() == 1 AND $data[$i]['id'] != 1) ? "<input type='checkbox' value='1'".(($data[$i]['locked'] < LOCKED) ? " checked=checked" : "")." />" : "")."<input class='send save' type='button' value='"._SAVE."' />".(($data[$i]['id'] != 1) ? "<input class='send delete' type='button' value='".((CLOUD::getID() == 1) ? _DELETE : _UNREGISTER )."' />" : "")."</div></section></div>";
        }
      }
      CLOUD::section($ba.$ac,100,$logout.CLOUD::$folder);
      CLOUD::section("<input class='send' id='explorer' type='button' value='"._RELOAD."' alt='".CLOUD::$folder."' />",25,"");
      break;
    }
    $files = @scandir($root);
    if($files !== false) {
      if(CLOUD::$folder == 'uploads') {
        $tardis = array();
        for($i=0; $i < sizeof($files); $i++) {
          $date[$files[$i]] = filemtime($root."/".$files[$i]);
          $tardis[$files[$i]]['filename'] = CLOUD::fileWithoutOptions($files[$i]);
        }
        arsort($date);
        $files = array_keys($date);
        $eq = array();
        for($i=sizeof($files)-1; $i >= 0; $i--) {
          if($r = CLOUD::isVisible($files[$i])) {
            $file = $tardis[$files[$i]]['filename'];
            if(!isset($eq[$file])) {
              $eq[$file] = 1;
              $file .= " [1]";
            } elseif(isset($eq[$file])) {
              $eq[$file]++;
              $file .= " [".$eq[$file]."]";
            }
            $tardis[$files[$i]]['file'] = $file;
            if($r !== true) {
              $tardis[$files[$i]]['owner'] = $r;
            } else $tardis[$files[$i]]['owner'] = 0;
          } else $files[$i] = ".";
        }
      } else sort($files,SORT_STRING);
      $fo = ""; $fi = "";
      for($i=0; $i < sizeof($files); $i++) {
        if($files[$i] != '.' AND $files[$i] != '..' AND $files[$i] != '.htaccess' AND $files[$i] != 'setup') {
          if(@is_file($root.'/'.$files[$i])) {
            if(isset($tardis)) {
              if($eq[$tardis[$files[$i]]['filename']] > 1) {
                $file = $tardis[$files[$i]]['file'];
              } else $file = $tardis[$files[$i]]['filename'];
            } else $file = CLOUD::fileWithoutOptions($files[$i]);
            if(isset($tardis) AND ($tardis[$files[$i]]['owner'] != 0) AND UPLOAD) {
              if($tardis[$files[$i]]['owner'] == 1) {
                $fi .= "<div class='sel own'>".$file."</div><div class='sub'>"
                ."<div class='send menu' alt='".$f.$files[$i]."'>"._DOWNLOAD."</div>"
                ."<div class='send menu chmod'>"._PUBLIC."</div>"
                ."<div class='send menu rm'>"._REMOVE."</div></div>";
              } else {
                $fi .= "<div class='sel perm'>".$file."</div><div class='sub'>"
                ."<div class='send menu' alt='".$f.$files[$i]."'>"._DOWNLOAD."</div>"
                ."<div class='send menu chmod'>"._PRIVATE."</div>"
                ."<div class='send menu rm'>"._REMOVE."</div></div>";
              }
            } else {
              $fi .= "<div class='send file' alt='".$f.$files[$i]."'>".$file."</div>";
            }
          } else {
            if($files[$i] == 'uploads') {
              $fo .= "<div class='send uploads' alt='".$f.$files[$i]."'>".$files[$i]."</div>";
            } else $fo .= "<div class='send folder' alt='".$f.$files[$i]."'>".$files[$i]."</div>";
          }
        }
      }
      if($root == FILES) $fo .= "<div class='send setup' alt='setup'>setup</div>";
      if(!$fo AND !$fi) $fo = _EMPTY;
      if(CLOUD::$folder == 'uploads' AND UPLOAD) {
        $stats = number_format((CLOUD::$quota / (QUOTA * 1048576) * 100),2)."%";
      } else $stats = "&nbsp;";
      CLOUD::section($ba.$fo.$fi,100,$logout.(CLOUD::$folder != '' ? CLOUD::$folder : TITLE),$stats);
    } else CLOUD::section($ba._EMPTY,100,$logout.(CLOUD::$folder != '' ? CLOUD::$folder : TITLE));
    CLOUD::section("<input class='send' id='explorer' type='button' value='"
    ._RELOAD."' alt='".CLOUD::$folder."' />",25,"");
  break;

  case 'download':
    $file = CLOUD::getDownload($_GET['d']);
    if($file) {
      set_time_limit(0);
      header('Expires: 0');
      header('Cache-Control: must-revalidate, no-cache, post-check=0, pre-check=0');
      header('Pragma: no-cache');
      header('Content-Length: '.filesize($file));
      header('Content-Transfer-Encoding: binary');
      header('Connection: Keep-Alive');
      header('Content-Description: File Transfer');
      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      header('Content-Type: '.finfo_file($finfo,$file));
      header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
      header('Content-Disposition: attachment; filename="'.basename(CLOUD::fileWithoutOptions($file)).'"');
      ob_clean();
      flush();
      @readfile($file);
      exit();
    }
  break;

  case 'chmod':
    if(isset($_POST['file'])) {
      CLOUD::setChmod($_POST['file']);
    }
    CLOUD::content("1");
  break;

  case 'rm':
    if(isset($_POST['file'])) {
      CLOUD::rmFile($_POST['file'],CLOUD::getID());
    }
    CLOUD::content("1");
  break;

  case 'save':
    if(isset($_POST['access'])
    AND isset($_POST['id'])
    AND isset($_POST['name'])
    AND isset($_POST['pass'])) {
      $data = array();
      $data['access'] = $_POST['access'];
      $data['id'] = substr($_POST['id'],1);
      $data['name'] = $_POST['name'];
      $data['pass'] = $_POST['pass'];
      CLOUD::saveData($data);
    }
    CLOUD::content("1");
  break;

  case 'delete':
  case 'deletenow':
    if(isset($_POST['id'])) {
      if(CLOUD::deleteUSER($_POST['id']))
        CLOUD::content("1");
    } elseif(isset($_POST['user'])) {
      $id = substr($_POST['user'],1);
      $user = CLOUD::getUserName($id);
      if($user) {
        CLOUD::content("$");
        CLOUD::section(((CLOUD::getID() == 1) ? _DELETE_Q1." <span class='warn'>".$user."</span> "._DELETE_Q2 : "<span class='warn'>"._UNREGISTER_Q."</span>"),100,_QUESTION).CLOUD::section("<input class='send' id='yes' type='button' value='"._YES."' /><br class='clear_' /><input class='send' id='no' type='button' value='"._NO."' /><input id='Qid' type='hidden' value='".$id."' />",25,"");
      }
    }
  break;

}

if($cmd == 'start' AND !isset($_POST['uuid'])) {
  $c = "<!doctype html>\n<html><head><title>cloud</title><meta http-equiv='content-type' content='text/html; charset=utf-8'/><meta http-equiv='content-script-type' content='text/javascript'/><meta http-equiv='content-style-type' content='text/css'/><meta http-equiv='X-UA-Compatible' content='IE=edge,chrome=1'/><meta name='viewport' content='width=device-width,initial-scale=1'/><link rel='icon' href='favicon.ico' type='image/ico'/><link rel='shortcut icon' href='favicon.ico' type='image/x-icon'/><link rel='StyleSheet' href='css/style.css' type='text/css'/><script type='text/javascript' src='js/jquery.js'></script>".(UPLOAD ? "<script type='text/javascript' src='js/jquery.knob.js'></script><script type='text/javascript' src='js/jquery.ui.widget.js'></script><script type='text/javascript' src='js/jquery.iframe-transport.js'></script><script type='text/javascript' src='js/jquery.fileupload.js'></script>" : "")."<script type='text/javascript' src='js/javascript.js'></script></head><body lang='de'><div id='wrapper_con'><article id='con'><div id='view'>".CLOUD::getContent()."</div><div id='process'></div>".(UPLOAD ? "<section class='row100'><div class='wrapper'><form id='upload' method='post' action='index.php' enctype='multipart/form-data'><div id='drop'>"._DROPHERE." <a>"._BROWSE."</a><input type='file' name='upl' multiple /></div><ul></ul></form></div></section>" : "")."</article></div><iframe id='i' src='about:blank'></iframe></body></html>";
} else $c = CLOUD::getContent();
echo $c;

?>
