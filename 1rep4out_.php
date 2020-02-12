<?
  function echo2log($fn, $subj='', $text='')
    {
     $r=true;

     if($text=='-')    $t='\n\n\n';
     elseif($text=='') $t=curtime("$subj");
                  else $t=curtime("$subj:\n$text");
     echo $t;
     file_put_contents($fn, $t, FILE_APPEND | LOCK_EX);

     return $r;
    }

  function curtime($s)
    {
     if($s!='') return(date('Y-m-d H:i:s :: ').$s."\n");
           else return(date('Y-m-d H:i:s')."\n");
    }


  function clearDir($dir='')
    {
     if(dir!='')
       {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file)
          {
           (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
          }
       }
     #return rmdir($dir);
    }

  function dir2arr($dir)
    { 
     $r=array(); 

     $cdir=scandir($dir); 
     foreach($cdir as $key=>$value) 
       { 
        if(!in_array($value,array(".",".."))) 
          { 
           if(is_dir($dir . DIRECTORY_SEPARATOR . $value)) 
             { 
             } 
           else 
             { 
              $r[]=strtoupper($dir.DIRECTORY_SEPARATOR.$value);
             } 
          } 
       } 
     
     return $r;
    } 

  
  function utf2win($str)
    {
     if($str!='') return @iconv("UTF-8", "CP1251", $str);
     else return '';
    }
  function win2utf($str)
    {
     if($str!='') return @iconv("CP1251", "UTF-8", $str);
     else return '';
    }
  
  function dos2win($str)
    {
     if($str!='') return @iconv("866", "CP1251", $str);
     else return '';
    }
  function win2dos($str)
    {
     if($str!='') return @iconv("CP1251", "866", $str);
     else return '';
    }

  function utf2lat($s)
    {
     $s=strtr($s, "абвгдеёзийклмнопрстуфхыэ",
                  "abvgdeeziyklmnoprstufhie");
     $s=strtr($s, "АБВГДЕЁЗИЙКЛМНОПРСТУФХЫЭ",
                  "ABVGDEEZIYKLMNOPRSTUFHIE");
     $s=strtr($s,array(
                       "ж"=>"zh",  "ц"=>"ts", "ч"=>"ch", "ш"=>"sh",
                       "щ"=>"shch","ь"=>"",  "ъ"=>"", "ю"=>"yu", "я"=>"ya",
                       "Ж"=>"ZH",  "Ц"=>"TS", "Ч"=>"CH", "Ш"=>"SH",
                       "Щ"=>"SHCH","Ь"=>"", "Ъ"=>"",  "Ю"=>"YU", "Я"=>"YA"
                      ));
     return $s;
    }

  function subj4form($dt, $form_id, $form_period='')
    {
     $r='';
     $r=preg_replace('/^0409(\d\d\d)/i', "F$1", $form_id);

     if($r!='')
       {
        if($form_period=='нерегулярная') $r.='D';
        if($form_period=='декадная')
          {
           if($form_id=='0409664') $r='D664';
          }
        $r="IES you have mail - $r//$dt";
       }

     return($r);
    }


  function dir4form($dt, $form_id, $form_period='')
    {
     global $dir4arhform;
     $r='';
     $r=preg_replace('/^0409(\d\d\d)/i', "F$1", $form_id);

     if($r==$form_id) $r='';//не поняли что за отчетность
     if($r!='')
       {
        if($form_period=='нерегулярная') $r.='D';
        if($form_period=='декадная')
          {
           if($form_id=='0409664') $r='D664';
          }

        $dt=str_replace('-','\\',$dt);
        $r=utf2win("$dir4arhform\\$dt\\ies");//архив
       }

     return($r);
    }

  function email4form($form_id)
    {
     global $ini;
     $r=$ini['mailto'];
     if(preg_match('/^0409(\d\d\d)/i',$form_id,$m))
       {
        #echo "\$m[1]=$m[1]\n";
        $e=$ini["f$m[1]"];
        if($e!='') $r.=",$e";
        #if($m[1]=='135') $r.=',f135@bank.ru';
       }
     return($r);
    }

  function sendEmail($to, $subj, $text='')
    {
     global $ini;
     $objEmail = new COM("CDO.Message") or die("Cannot init CDO.Message");

     $objEmail->Configuration->Fields->Item("http://schemas.microsoft.com/cdo/configuration/sendusing")->Value = 2;
     $objEmail->Configuration->Fields->Item("http://schemas.microsoft.com/cdo/configuration/smtpserver")->Value     = $ini['mailserver'];//
     $objEmail->Configuration->Fields->Item("http://schemas.microsoft.com/cdo/configuration/smtpserverport")->Value = $ini['mailport'];
     $objEmail->Configuration->Fields->Update();

     $objEmail->From    = $ini['mailfrom'];
     $objEmail->To      = $to;
     $objEmail->Subject = $subj;
 
     $objEmail->BodyPart->Charset = $ini['mailcharset'];
     $objEmail->HTMLBody  = $text;
 
     return $objEmail->Send();
    }
?>
