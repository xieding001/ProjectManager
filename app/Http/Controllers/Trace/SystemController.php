<?php

namespace App\Http\Controllers\Trace;

use App\Func\CommonFunc;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class SystemController extends Controller
{
	/*
     * 上传图片
     */
	public function imgUpload(Request $request){
		header('content-type:text/html charset:utf-8');
		$img = array();
		$type = $request->input('type');

		$dir_base = $_SERVER['DOCUMENT_ROOT']."/upload/trace/"; //文件上传根目录

		$index = 0;        //$_FILES 以文件name为数组下标，不适用foreach($_FILES as $index=>$file)
		foreach($_FILES as $k => $file){
			$filename = $file['name'];
			$extpos = strrpos($filename,'.');	//返回字符串filename中'.'号最后一次出现的数字位置
			$ext = substr($filename,$extpos+1);
			$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
			$str = "";
			for ($i = 0; $i < 16; $i++) {
				$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
			}
			$prefix = substr($str,0,1);
			$gb_filename = date("Y-m-d").'/'.$prefix.'.origin/'.$str.'.'.$ext;
			$path = $dir_base.date("Y-m-d").'/'.$prefix.'.origin';
			if(!file_exists($path)){
				mkdir($path,0777,true);
			}
			/* 文件不存在才上传 */
			if(!file_exists($dir_base.$gb_filename)){
				$isMoved = false;  //默认上传失败
				$MAXIMUM_FILESIZE = 80 * 1024 * 1024;     //文件大小限制    1M = 1 * 1024 * 1024 B;
				$rEFileTypes = "/^\.(jpg|jpeg|gif|png){1}$/i";
				if ($file['size'] <= $MAXIMUM_FILESIZE &&
						preg_match($rEFileTypes, strrchr($gb_filename, '.'))) {
					$isMoved = @move_uploaded_file ( $file['tmp_name'], $dir_base.$gb_filename);       //上传文件
				}
			}else{
				$isMoved = true;    //已存在文件设置为上传成功
			}
			if($isMoved){
				$img[] = '/upload/trace/'.$gb_filename;
			}else {
				$img[] = '/upload/trace/'.$gb_filename;
			}
			$index++;
		}
		CommonFunc::mapi_export($img);
	}







}