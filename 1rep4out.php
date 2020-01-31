<?
  error_reporting( E_ERROR );
  date_default_timezone_set("Asia/Baghdad");

  $dateo=date('Y-m-d');
  #$dateo='2019-12-16';

  $ini=parse_ini_file("1rep4out.ini");
  foreach($ini as $k=>$v)
    {
     $ini[$k]=str_replace('{dateo}',$dateo,$v);
     if(preg_match('/^dir/i', $k)) { echo "mkdir $ini[$k]\n"; mkdir($ini[$k],0777,true); }
    }
  echo2log($ini['logfile'], "--- START ".$_SERVER['COMPUTERNAME']."->".$_SERVER['SCRIPT_NAME'].", is_prod=$ini[is_prod]");

  //
  // KWT
  //
  function repfile2kwt($fn1, $file4already)
    {
     global $ini;

     $f1="$ini[dir4astra]\\$fn1";

     echo2log($ini['logfile'], "\t*** FORSEND", $fn1);

     $s1=system("arj32 x -y $f1 $ini[dir4arj]\\",$r1);
     echo2log($ini['logfile'], "\t\t***arj32forsend [$f1]", "//$r1\n$s1");

     if($res==0)
       {
        $fz2=dir2arr($ini['dir4arj']);//временная папка для разархива
        foreach($fz2 as $f2_id=>$f2)
          {
           $s2=system("arj32 x -y $f2 $ini[dir4kwt]\\",$r2);
           echo2log($ini['logfile'], "\t\t***arj32kwt [$f2]", "//$r2\n$s2");
           unlink($f2);

           $fz3=dir2arr($ini['dir4kwt']);
           foreach($fz3 as $f3_id=>$f3)
             {
              $fn3=strtolower(basename($f3));
              if(preg_match('/^(KWTFCB|IZVTUB|SBF|SFF)/i', $fn3, $m))#substr($fn3,0,6)=='kwtfcb')
                {
                 echo2log($ini['logfile'], "\t\t***outsign [$fn3]", "$m[0]");
                 $xml=file_get_contents($f3);
                 $x1=strpos($xml,utf2win('<?xml version="1.0" encoding="windows-1251"?>'));
                 if($x1>0) $xml=substr($xml,$x1);
                 $x2=strpos($xml,utf2win('</Файл>'));
                 if($x2>0) $xml=substr($xml,0,$x2+7);
                 file_put_contents($f3, $xml);
                 if(preg_match('/^(SBF|SFF)/i', $fn3, $m)) rename($f3,"$ini[dir4rs311]\\".strtoupper($fn3));
                                                      else rename($f3,"$ini[dir4rs440]\\".strtoupper($fn3));
                }
              else
                {
                 echo2log($ini['logfile'],"\t\t***outsign [$fn3]", "NOT4MEE***");
                }
              unlink($f3);
             }
          }
       }

     file_put_contents($file4already, curtime($fn1), FILE_APPEND | LOCK_EX);
    }

  
  //
  // IES
  //
  function repfile2ies($fn1, $file4already)
    {
     global $dateo;
     global $ini;

     echo2log($ini['logfile'], "\t--- XML", $fn1);
     $xml4tk=simplexml_load_file($ini['dir4astra']."\\".$fn1);
     $fn_out=$xml4tk->{'ДанныеТК'}->{'ЭС'}['ИмяФайлаЭС'];
     if((strtoupper(basename($fn1))===strtoupper(basename('TK_'.$fn_out)))or($fn_out!=''))
       {
        // наш файл - работаем - декодируем и расставляем \n
        $o64=$xml4tk->{'ДанныеТК'}->{'ЭС'};
        foreach($o64 as $k=>$v) $i64=base64_decode($v);
        $i64=str_replace(">",">\x0D\x0A",$i64);
        $i64=str_replace("\"\x20","\"\x0D\x0A\t",$i64);
        echo2log($ini['logfile'],"\t\t*** IES2DECODE $fn_out ($fn1)", $i64);

        //сохраним результат
        mkdir("$ini[dir4ies]\\$dateo");
        file_put_contents("$ini[dir4ies]\\$dateo\\$fn_out",$i64);

        if(false)
          {
           //переложим результат
           $xml4rep=simplexml_load_string($i64);
           $dt4rep=substr($xml4rep->{'РеквОЭС'}['ОтчДата'],0,10);
           $tm4rep=substr($xml4rep->{'РеквОЭС'}['ДатаВремяРегистрации'],0,10);//ДатаВремяФормирования//ОтчДата//2017-10-30T10:56:29
           if($tm4rep=='') $tm4rep=$xml4rep->{'РеквОЭС'}['ОтчДата'];
           $form_id = $xml4rep->{'РеквОЭС'}['КодФормы'];
           $form_period = $xml4rep->{'РеквОЭС'}['Периодичность'];
           $form_dir=dir4form($tm4rep, $form_id, $form_period);

           $fn_out="$fn_out.$dt4rep.txt";
           /*
           mkdir($form_dir,0777,true);
           echo utf2win("сохраняем ИЭС")."$form_dir\\$fn_out\n$i64\n\n\n";

           //отправим почту
           $form_email=email4form($tm4rep, $form_id, $form_period);
           $form_subj=subj4form($tm4rep, $form_id, $form_period);
           $form_dir_utf=utf2lat(win2utf($form_dir));
           $email_cmd="sendemail.exe -f $ini[mailfrom] -t $form_email -s $ini[mailserv]:$ini[mailport] -u \"$form_subj\" -m \"$form_dir_utf\" -a \"$form_dir\\$fn_out\"";
           #if($form_email!='') $email_lo=`$email_cmd`;
           echo "$email_cmd\n$email_lo\n";
           //todo: если совпали "УникИдОЭС" - и исходный файл - сквитуем
           */
          }
        file_put_contents($file4already, curtime($fn1), FILE_APPEND | LOCK_EX);
        echo2log($ini['logfile'], "-\n-\n-\n");
       }
     else
       {
        echo2log($ini['logfile'], "!!! WARNINIG non report xml !!!", $fn1);
       }
    }


  //
  // НАЧАЛО РАБОТЫ
  //
  $file4already="$ini[dir4dateo]\\$dateo.lo";
  $text4already=file_get_contents($file4already);
  $filez=dir2arr($ini['dir4astra']);
  foreach($filez as $f1_id=>$f1)
    {
     $fn1=strtolower(basename($f1));
     echo2log($ini['logfile'], "[ FILE_ID: $f1_id ] $fn1");
     if(strpos($text4already,$fn1)===false)
       {
        //
        // IES
        //
        if((substr($fn1,strlen($fn1)-4)==='.xml')and(substr($fn1,0,3)=='tk_'))
          {
           #if($fn1=='tk_1579768336134_ko-3337_2020-01-23t11-31-05_1_f0409664_ies1.xml')
             {
              repfile2ies($fn1,$file4already);
             }
          }
        //
        // KWT
        //
        elseif(substr($fn1,0,7)==='forsend')
          {
           #if($fn1=='forsend_7.arj')
             {
              repfile2kwt($fn1,$file4already);
             }
          }
        //
        // GU - 311p
        //
        elseif(substr($fn1,0,3)==='gu_')
          {
           #if($fn1=='forsend_7.arj')
             {
              repfile2kwt($fn1,$file4already);
             }
          }
        else
          {
           echo2log($ini['logfile'], "ignore");
          }
       }
     else
       {
        echo2log($ini['logfile'], "already");
       }
    }

  echo2log($ini['logfile'], "ENND\n");

  //
  //
  //
  function echo2log($fn, $subj='', $text='')
    {
     $r=true;

     if($text=='') $t=curtime("$subj");
              else $t=curtime("$subj:\n$text");
     echo $t;
     file_put_contents($fn, $t, FILE_APPEND | LOCK_EX);

     return $r;
    }

  function curtime($s)
    {
     return(date('Y-m-d H:i:s :: ').$s."\n");
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

  function email4form($dt, $form_id, $form_period='')
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

?>