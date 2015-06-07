<?php

if(DB == 'sqlite') {
  class MyDB extends SQLite3 {
    function __construct() {
      try {
        $this ->open('db/data.sqlite',SQLITE3_OPEN_READWRITE);
      }
      catch(Exception $e) {
        if($this->lastErrorCode() == 14) {
          try {
            $this ->open('db/data.sqlite',SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
            $result = $this->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, cuser CHAR(20) NOT NULL, cpass CHAR(102) NOT NULL, locked INTEGER NOT NULL, uuid CHAR(32) NOT NULL, groups TEXT NOT NULL); CREATE UNIQUE INDEX 'cuser' on users (cuser COLLATE NOCASE ASC); CREATE TABLE files (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, uuid CHAR(32) NOT NULL, file CHAR(255) NOT NULL)");
          }
          catch(Exception $e) {}
        }
      }
    }
  }
}

class CLOUD {

  public static $id = 0;
  public static $quota = 0;
  public static $folder = "";
  private static $uuid = "z";
  private static $c = "";
  private static $db = false;

  private static function SHA512crypt($p){
    $base64_alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
    $salt='$6$'; for($i=0; $i<12; $i++) $salt.=$base64_alphabet[rand(0,63)];
    $salt.='$'; for($i=0; $i<25; $i++) $salt.=$base64_alphabet[rand(0,63)];
    return crypt($p,$salt.'$');
  }

  static function getContent() {
    return self::$c;
  }

  static function connect($uuid) {
    if(mb_strlen($uuid,'utf-8') == 32) {
      self::$uuid = $uuid;
      if(DB == 'sqlite') {
        self::$db = new MyDB();
      } else {
        self::$db = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
      }
    }
  }

  static function connectUSER($u,$p) {
    if(DB == 'sqlite') {
      $result = @self::$db->query("SELECT id,cpass FROM users
      WHERE (cuser='".addslashes($u)."' COLLATE NOCASE AND locked < '".LOCKED."') LIMIT 1");
      if($result) {
        $row = $result->fetchArray(SQLITE3_NUM);
        if(crypt($p,$row[1]) == $row[1]) {
          @self::$db->query("UPDATE users SET uuid='".addslashes(self::$uuid)."',locked='0' WHERE id='".$row[0]."' LIMIT 1");
          return true;
        } else @self::$db->query("UPDATE users SET locked = locked + 1 WHERE id='".$row[0]."' LIMIT 1");
      }
    } else {
      $result = @mysqli_query(self::$db,"SELECT id,cpass FROM ".DB_PREF."users
      WHERE (cuser='".addslashes($u)."' AND locked < '".LOCKED."') LIMIT 1");
      if($result AND (mysqli_num_rows($result) > 0)) {
        $row = mysqli_fetch_row($result);
        if(crypt($p,$row[1]) == $row[1]) {
          @mysqli_query(self::$db,"UPDATE ".DB_PREF."users SET uuid='".addslashes(self::$uuid)."',locked='0' WHERE id='".$row[0]."' LIMIT 1");
          return true;
        } else @mysqli_query(self::$db,"UPDATE ".DB_PREF."users SET locked = locked + 1 WHERE id='".$row[0]."' LIMIT 1");
      }
    }
    return false;
  }

  static function disconnectUSER() {
    if(DB == 'sqlite') {
      @self::$db->query("UPDATE users  SET uuid=''
      WHERE uuid='".addslashes(self::$uuid)."' LIMIT 1");
    } else {
      @mysqli_query(self::$db,"UPDATE ".DB_PREF."users SET uuid=''
      WHERE uuid='".addslashes(self::$uuid)."' LIMIT 1");
    }
  }

  static function access() {
    if(self::$uuid == 'z') return false;
    if(DB == 'sqlite') {
      $result = @self::$db->query("SELECT id FROM users
      WHERE (uuid='".addslashes(self::$uuid)."' AND locked='0') LIMIT 1");
      if($result) {
        $row = $result->fetchArray(SQLITE3_NUM);
        if($row[0]) {
          self::$id = $row[0];
          return true;
        }
      }
    } else {
      $result = @mysqli_query(self::$db,"SELECT id FROM ".DB_PREF."users
      WHERE (uuid='".addslashes(self::$uuid)."' AND locked='0') LIMIT 1");
      if($result AND (mysqli_num_rows($result) > 0)) {
        $row = mysqli_fetch_row($result);
        self::$id = $row[0];
        return true;
      }
    }
    return false;
  }

  static function getID() {
    return self::$id;
  }

  static function newUSER($u,$p) {
    if(DB == 'sqlite') {
      @self::$db->query("INSERT INTO users (cuser,cpass,locked,uuid,groups)
      VALUES ('".addslashes($u)."','".self::SHA512crypt($p)."','".LOCKED."','','')");
      if(@self::$db->lastInsertRowID() == 1) @self::$db->query("UPDATE users SET locked='-10' WHERE id='1' LIMIT 1");
    } else {
      @mysqli_query(self::$db,"INSERT INTO ".DB_PREF."users (cuser,cpass,locked)
      VALUES ('".addslashes($u)."','".self::SHA512crypt($p)."','".LOCKED."')");
      if(@mysqli_insert_id(self::$db) == 1) @mysqli_query(self::$db,"UPDATE ".DB_PREF."users SET locked='-10' WHERE id='1' LIMIT 1");
    }
  }

  static function deleteUSER($id) {
    $id = intval($id);
    if($id AND self::$id != 1) return false;
    elseif($id == 1) return false;
    elseif(!$id AND self::$id == 1) return false;
    elseif(!$id) $id = self::$id;
    if(DB == 'sqlite') {
      @self::$db->query("DELETE FROM users WHERE id = '".$id."' LIMIT 1");
    } else {
      @mysqli_query(self::$db,"DELETE FROM ".DB_PREF."users WHERE id = '".$id."' LIMIT 1");
    }
    $files = @scandir(FILES.'/uploads');
    for($i=0; $i < sizeof($files); $i++) {
      if($files[$i] != '.' AND $files[$i] != '..' AND $files[$i] != '.htaccess') {
        if(@is_file(FILES.'/uploads/'.$files[$i])) {
          self::rmFile('uploads/'.$files[$i],$id);
        }
      }
    }
    return true;
  }

  static function content($content) {
    self::$c = $content;
  }

  static function section($content="",$row="100",$title=TITLE,$stats="&nbsp;") {
    self::$c .= "<section class='row".$row."'>"
    .(($title != '') ? "<header class='wrapper'><h3>".$title
    ."<br /><span class='stats'>".$stats."</span></h3></header>" : "")
    ."<div class='wrapper'>".$content."</div></section>";
  }

  static function fileWithOptions($file) {
    $file_ = array();
    $pos = strripos($file,'.');
    if($pos !== false) {
      $file_[0] = substr($file,0,$pos)."[".self::$id.",0]".substr($file,$pos);
      $file_[1] = substr($file,0,$pos)."[".self::$id.",1]".substr($file,$pos);
    } else {
      $file_[0] = $file."[".self::$id.",0]";
      $file_[1] = $file."[".self::$id.",1]";
    }
    return $file_;
  }

  static function fileWithoutOptions($file) {
    $pos1 = strripos($file,']');
    if($pos1 !== false) {
      $pos2 = strripos($file,'[');
      if($pos2 !== false) {
        if($pos2 < $pos1) {
          $file = substr($file,0,$pos2).substr($file,$pos1+1);
        }
      }
    }
    return $file;
  }

  static function isVisible($file) {
    $pos1 = strripos($file,']');
    if($pos1 !== false) {
      $pos2 = strripos($file,'[');
      if($pos2 !== false) {
        if($pos2 < $pos1) {
          $option = explode(',',substr($file,$pos2+1,$pos1-$pos2-1));
          if(sizeof($option) == 2) {
            if($option[0] != self::$id
            AND $option[1] == '0')
            return false;
            if($option[0] == self::$id) {
              self::$quota += @filesize(FILES.'/'.self::$folder.'/'.$file);
              if($option[1]) {
                return 2;
              } else { return 1; }
            }
          }
        }
      }
    }
    return true;
  }

  static function userQuota() {
    $files = @scandir(FILES.'/'.self::$folder);
    for($i=0; $i < sizeof($files); $i++) {
      if($files[$i] != '.' AND $files[$i] != '..' AND $files[$i] != '.htaccess') {
        if(@is_file(FILES.'/'.self::$folder.'/'.$files[$i])) {
          self::isVisible($files[$i]);
        }
      }
    }
    if((self::$quota / 1048576) <= QUOTA) return true;
    return false;
  }

  static function setDownload($file) {
    $uuid = md5(uniqid(mt_rand(0,65535),true));
    if(DB == 'sqlite') {
      $result = @self::$db->query("INSERT INTO files (uuid,file)
      VALUES ('".$uuid."','".addslashes($file)."')");
      if($result) return $uuid;
    } else {
      @mysqli_query(self::$db,"INSERT INTO ".DB_PREF."files (uuid,file)
      VALUES ('".$uuid."','".addslashes($file)."')");
      if(mysqli_affected_rows(self::$db) >= 1) return $uuid;
    }
    return "";
  }

  static function getDownload($uuid) {
    if(DB == 'sqlite') {
      self::$db = new MyDB();
      $result = @self::$db->query("SELECT id,file FROM files
      WHERE (uuid='".addslashes($uuid)."') LIMIT 1");
      if($result) {
        $row = $result->fetchArray(SQLITE3_NUM);
        if($row[0]) {
          @self::$db->query("DELETE FROM files WHERE id = '".$row[0]."' LIMIT 1");
          return $row[1];
        }
      }
    } else {
      self::$db = @mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
      $result = @mysqli_query(self::$db,"SELECT id,file FROM ".DB_PREF."files
      WHERE (uuid='".addslashes($uuid)."') LIMIT 1");
      if($result AND (mysqli_num_rows($result) > 0)) {
        $row = mysqli_fetch_row($result);
        @mysqli_query(self::$db,"DELETE FROM ".DB_PREF."files WHERE id = '".$row[0]."' LIMIT 1");
        return $row[1];
      }
    }
    return false;
  }

  static function setChmod($file) {
    $file = str_replace("..","",$file);
    $file = FILES."/".$file;
    if(!@file_exists($file)) die("");
    if(@is_file($file)) {
      $pos1 = strripos($file,']');
      if($pos1 !== false) {
        $pos2 = strripos($file,'[');
        if($pos2 !== false) {
          if($pos2 < $pos1) {
            $option = explode(',',substr($file,$pos2+1,$pos1-$pos2-1));
            if(sizeof($option) == 2) {
              if($option[0] == self::$id) {
                $n = substr($file,0,$pos2)."[".$option[0].",".($option[1] ? "0" : "1").substr($file,$pos1);
                @rename($file,$n);
              }
            }
          }
        }
      }
    }
  }

  static function rmFile($file,$id) {
    $file = str_replace("..","",$file);
    $file = FILES."/".$file;
    if(!@file_exists($file)) die("");
    if(@is_file($file)) {
      $pos1 = strripos($file,']');
      if($pos1 !== false) {
        $pos2 = strripos($file,'[');
        if($pos2 !== false) {
          if($pos2 < $pos1) {
            $option = explode(',',substr($file,$pos2+1,$pos1-$pos2-1));
            if(sizeof($option) == 2) {
              if($option[0] == $id) {
                @unlink($file);
              }
            }
          }
        }
      }
    }
  }

  static function getUser() {
    $data = array();
    if(DB == 'sqlite') {
      $result = @self::$db->query("SELECT id,cuser,locked FROM users ORDER BY id DESC");
      if($result) {
        while($row = $result->fetchArray(SQLITE3_ASSOC)){
          if($row) $data[] = $row;
        }
      }
    } else {
      $result = @mysqli_query(self::$db,"SELECT id,cuser,locked FROM ".DB_PREF."users ORDER BY id DESC");
      if($result AND (mysqli_num_rows($result) > 0)) {
        while($row=mysqli_fetch_assoc($result)){
          if($row) $data[] = $row;
        }
      }
    }
    return $data;
  }

  static function getData() {
    $data = array();
    if(DB == 'sqlite') {
      $result = @self::$db->query("SELECT id,cuser,locked FROM users
      WHERE id='".self::$id."' LIMIT 1");
      if($result) {
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if($row) $data[] = $row;
      }
    } else {
      $result = @mysqli_query(self::$db,"SELECT id,cuser,locked FROM ".DB_PREF."users
      WHERE id='".self::$id."' LIMIT 1");
      if($result AND (mysqli_num_rows($result) > 0)) {
        $row = mysqli_fetch_assoc($result);
        if($row) $data[] = $row;
      }
    }
    return $data;
  }

  static function getUserName($id) {
    if(!$id) $id = self::$id;
    if($id == '1') return "";
    $name = "";
    if(DB == 'sqlite') {
      $result = @self::$db->query("SELECT cuser FROM users
      WHERE id='".addslashes($id)."' LIMIT 1");
      if($result) {
        $row = $result->fetchArray(SQLITE3_NUM);
        if($row) $name = $row[0];
      }
    } else {
      $result = @mysqli_query(self::$db,"SELECT cuser FROM ".DB_PREF."users
      WHERE id='".addslashes($id)."' LIMIT 1");
      if($result AND (mysqli_num_rows($result) > 0)) {
        $row = mysqli_fetch_row($result);
        if($row) $name = $row[0];
      }
    }
    return $name;
  }

  static function saveData($data) {
    $data['id'] = intval($data['id']);
    if($data['id'] AND self::$id != 1) return false;
    elseif($data['name'] == '') return false;
    elseif(!$data['id']) $data['id'] = self::$id;
    if(self::$id == 1 AND $data['id'] != 1) {
      if($data['access'] == 'false') $data['access'] = LOCKED;
      else $data['access'] = 0;
    } else $data['access'] = 0;
    $set = "cuser='".addslashes($data['name'])."',locked='".$data['access']."'".($data['pass'] ? ",cpass='".self::SHA512crypt($data['pass'])."'" : "");
    if(DB == 'sqlite') {
      @self::$db->query("UPDATE users SET ".$set." WHERE id='".$data['id']."' LIMIT 1");
    } else {
      @mysqli_query(self::$db,"UPDATE ".DB_PREF."users SET ".$set." WHERE id='".$data['id']."' LIMIT 1");
    }
  }

}

?>
