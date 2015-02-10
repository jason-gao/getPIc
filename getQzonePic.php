<?php
/**
 * author jasong
 * date 2014/12/20
 * 采集图片
 * http://blog.csdn.net/gj369326973/article/details/6153342
 * todo... 1图片后缀 2 文件名非法字符过滤  /\* 3 auth basic password 4文件名有空格时 分割问题
 *         5 加入重试机制
 */
define('__IMG__', __DIR__.'/img/'); //图片目录
define('__LOG__', __DIR__.'/log/'); //日志目录
define('__LOG_NAME__', __LOG__.date('Y-m-d-His').'.log'); //日志 每次运行生成的文件名 文件名不能含:
define('__PIC_URL_NAME__', 'picUrl.txt'); //图片url文件名

set_time_limit(0); //没有时间限制

//set header
function setHeader(){
	header("Content-type: text/html; charset=utf-8");
}
//todo...
function askPassword($text="Enter the password") { 
    header('WWW-Authenticate: Basic realm="'. utf8_decode($text) .'"'); 
    header('HTTP/1.0 401 Unauthorized'); 
    return 1;
} 

//dump
function dump($arr, $print_r = true, $exit = false){
	
	if($print_r){
		print_r($arr);
	}else{
		var_dump($arr);
	}
	
	if($exit) exit();	
}

//write log
function writeLog($content){
	$fp = fopen(__LOG_NAME__, "a"); //不存在尝试创建之
	flock($fp, LOCK_EX) ; //独占锁
	fwrite($fp, $content."\n");
	flock($fp, LOCK_UN); //释放  无论共享或独占
	fclose($fp);
}

function get(){
	//set header
	setHeader();
	
	if(isset($_GET['act'])){
		switch(trim($_GET['act'])){
			case 'clearImgDir': //清空图片目录
				array_map('unlink', glob(__IMG__.'*'));
				die('clear success <a href="'.$_SERVER['PHP_SELF'].'">重新下载</a>');
				break;
			default:
				die('act error');
				break;
		}
	}
	
	//need password
	//askPassword();
	
	$picFileName = __PIC_URL_NAME__;
	//check file exist
	if(!file_exists(__DIR__.'/'.$picFileName)){
		die('file not exists');
	}
	
	//read file
	$fp = fopen(__DIR__.'/'.$picFileName, "rb");
	if(!$fp) die('open file failed');
	
	while(!feof($fp)){
		$row = fgets($fp, 2000);
		if(!$row) continue;
		if(false === strpos($row, ';')){
			if(false !== strrpos($row, ' ')){
				if(count(explode(' ', $row)) > 2){ //多个空格处理
					$pos = strrpos($row, ' '); //最后一个空格的位置
					$url = substr($row, 0, $pos);
					$name = substr($row, $pos);
				}else{
					list($url, $name) = explode(' ', $row);
				}
				$url = trim($url);
				$name = trim($name);
				
				//dump(array('url' => $url, 'name' => $name), true, true);
				
				//Create a stream
				$opts = array(
				  'http'=>array(
					'method'=>"GET",
					'header'=>"Accept-language: zh-CN,zh;q=0.8\r\n" .
							  "Cookie: foo=bar\r\n",
					'timeout' =>10, //超时控制
				  )
				);

				$context = stream_context_create($opts);
				$data = file_get_contents(substr($url, 0, strpos($url, '?')).urlencode(substr($url, strpos($url, '?'))), false, $context); //url里含有特殊字符处理
				if(!$data){
					writeLog($url.' '.$name.'打不开');
					continue;
				}
				$r = file_put_contents(__IMG__.iconv('UTF-8', 'GBK', $name).'.jpg', $data); //中文文件名需要转换编码否则写入失败
				if(!$r){
					writeLog($url.' '.$name.'写入失败');
					continue;
				}
			}
		}
	}
	echo 'success <a href="?act=clearImgDir">清空图片目录</a>';
	fclose($fp);
}

//run get
get();