<?
  error_reporting( E_ERROR );
  date_default_timezone_set("Asia/Baghdad");
  require_once('lib4php.php');

  $dateo=date('Y-m-d');
  #$dateo='2020-03-03';

  $ini=parse_ini_file("1rep4out.ini");
  foreach($ini as $k=>$v)
    {
     $ini[$k]=str_replace('{dateo}',$dateo,$v);
     if(preg_match('/^dir/i', $k)) { echo "mkdir $ini[$k]\n"; mkdir($ini[$k],0777,true); }
    }
  echo2log($ini['logfile'], "--- START ".$_SERVER['COMPUTERNAME']."//".$_SERVER['SCRIPT_NAME']."//".PHP_OS);

  //
  // KWT
  //
  function repfile2forsend($fn1)//forsend.arj
    {
     global $ini;
     $r="$fn1:\n";

     $f1="$ini[dir4astra]\\$fn1";

     echo2log($ini['logfile'], "\t*** FORSEND", $fn1);

     $s1=system("arj32 x -y $f1 $ini[dir4arj]\\",$r1);//временная папка для разархива

     if($r1==0)
       {
        echo2log($ini['logfile'], "\t\t***arj32forsend [$f1]", "//$r1\n$s1");
        $fz2=dir2arr($ini['dir4arj']);
        foreach($fz2 as $f2_id=>$f2)
          {
           $r.="+".basename($f2)."\n";
           $s2=system("arj32 x -y $f2 $ini[dir4kwt]\\",$r2);

           if($r2==0)
             {
              echo2log($ini['logfile'], "\t\t***arj32kwt [$f2]", "//$r2\n$s2");

              $fz3=dir2arr($ini['dir4kwt']);
              foreach($fz3 as $f3_id=>$f3)
                {
                 $fn3=strtolower(basename($f3));
                 $r.="--".basename($f3)."\n";
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
                    $r.="\t\tWARNING: outsign [$fn3] - NOT4MEE***\n";
                   }
                 unlink($f3);
                }
              unlink($f2);
             }
           else//r2==0
             {
              $r.="\t\tWARNING: dearh2 [$fn2] - NOT4MEE***\n";
             }
          }
       }
     else
       {
        $r.="\t\tWARNING: dearh1 [$fn1] - NOT4MEE***\n";
       }
     clearDir($ini['dir4arj']);
     clearDir($ini['dir4kwt']);
     return $r;
    }

  //
  // IES
  //
  function repfile2ies($fn1)//ies12
    {
     $r="$fn1:\n";
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
        $r.=$fn_out;

        if(true)
          {
           //отправим на почту
           $x64=simplexml_load_string($i64);
           $dt4rep=substr($x64->{'РеквОЭС'}['ОтчДата'],0,10);
           $dt4reg=substr($x64->{'РеквОЭС'}['ДатаВремяРегистрации'],0);
           $form_id     = $x64->{'РеквОЭС'}['КодФормы'];
           $form_period = $x64->{'РеквОЭС'}['Периодичность'];

           sendEmail($ini['mailto'], "$form_id $dt4rep $dt4reg ".utf2win($form_period), "<b>$fn_out</b><pre>$i64</pre>");
           //todo: если совпали "УникИдОЭС" - и исходный файл - сквитуем
          }
        echo2log($ini['logfile'], '-');
       }
     else
       {
        echo2log($ini['logfile'], "!!! WARNINIG non report xml !!!", $fn1);
        $r.="WARNING: $fn1 - non report xml!!!\n";
       }
     return $r;
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
     $mm='';
     echo2log($ini['logfile'], "[ FILE_ID: $f1_id ] $fn1");
     if(strpos($text4already,$fn1)===false)
       {
        // IES
        if((substr($fn1,strlen($fn1)-4)==='.xml')and(substr($fn1,0,3)=='tk_'))
          {
           #if($fn1=='tk_1579768336134_ko-3337_2020-01-23t11-31-05_1_f0409664_ies1.xml')
             {
              $mm=repfile2ies($fn1);
             }
          }
        // KWT
        elseif(substr($fn1,0,7)==='forsend')
          {
           #if($fn1=='forsend_7.arj')
             {
              $mm=repfile2forsend($fn1);
             }
          }
        // GU - 311p
        elseif(substr($fn1,0,3)==='gu_')
          {
           #if($fn1=='forsend_7.arj')
             {
              $mm=repfile2forsend($fn1);
             }
          }
        else
          {
           echo2log($ini['logfile'], "ignore");
           $mm='under construction 1!!!';//добавить содержимое архива
          }
        file_put_contents($file4already, curtime($fn1), FILE_APPEND | LOCK_EX);
        //отправим письмо о содержимом архива
        sendEmail($ini['mailto'], utf2win("получен файл: $fn1"), "<b>$fn1</b><pre>$mm</pre>");
       }
     else
       {
        echo2log($ini['logfile'], "already");
       }
    }

  echo2log($ini['logfile'], "--- ENND\n");
?>
