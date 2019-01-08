<?php
namespace App\Func;
/**
 * Class Common
 * @package App\Func
 */
class CommonFunc
{
    /**
     * @param $url
     * @return mixed
     * get方式curl
     */
    public function get_curl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $output = curl_exec($ch);

        curl_close($ch);
        return $output;
    }

    /**
     * @param $data
     * 数据输出
     */
    public static function mapi_export($data)
    {
//        header('Content-type: application/json');
        $data_export = array();
        $data_export['error_code'] = 0;
        $data_export['error_msg'] = "";
        $data_export['data'] = $data;
        echo json_encode($data_export);
    }

    public static function mapi_export_jsonp($request, $data)
    {
        header('Content-type: text/json');
        $cb = $request->input("cb");
        $data_export = array();
        $data_export['error_code'] = 0;
        $data_export['error_msg'] = "";
        $data_export['data'] = $data;
        echo $cb . "(" . json_encode($data_export) . ")";
    }

    public static function mapi_error($errorCode, $errorMsg)
    {
        $data['error_code'] = $errorCode;
        $data['error_msg'] = $errorMsg;
        echo json_encode($data);
        exit();
    }

    public static function mapi_error_jsonp($request, $errorCode, $errorMsg)
    {
        $cb = $request->input("cb");
        $_ERROR = array(
            '10001' => '系统维护',
            '10002' => '不支持的客户端版本',
            '10003' => '不存在该接口',
            '20001' => '手机号错误',
            '20002' => '非法的密码',
            '20003' => '非法的昵称',
            '20004' => '用户验证失败',
            '20005' => '该用户不存在',
            '20006' => '注册失败',
        );
        $data = array(
            'error_code' => $errorCode
        );
        if (empty($errorMsg)) {
            $data['error_msg'] = $_ERROR[$errorCode];
        } else {
            $data['error_msg'] = $errorMsg;
        }
        echo $cb . "(" . json_encode($data) . ")";
        exit();
    }


    public static function randomNum($length)
    {
        $authNum = '';
        $char = "0,1,2,3,4,5,6,7,8,9,a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,i,s,t,u,v,w,x,y,z";
        $list = explode(",", $char);
        for ($i = 0; $i < $length; $i++) {
            $randNum = rand(0, 35); // 10+26;
            $authNum .= $list[$randNum];
        }
        return $authNum;
    }

    public static function get_real_ip()
    {
        $ip = false;
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) {
                array_unshift($ips, $ip);
                $ip = FALSE;
            }
            for ($i = 0; $i < count($ips); $i++) {
                if (!eregi("^(10|172\.16|192\.168)\.", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }

    /*
     * 验证是否有效
     */
    public static function emptyVerify($obj, $msg)
    {
        if (is_array($obj)) {
            foreach ($obj as $k => $v) {
                if (empty($v)) {
                    CommonFunc::mapi_error("20000", $msg);
                }
            }
        } else {
            if (empty($obj)) {
                CommonFunc::mapi_error("20000", $msg);
            }
        }

    }

    /*
     * 批量替换数组指定数据 0=>否
     */
    public static function arrReplace($arr, $replace)
    {
        foreach ($arr as $k => &$v) {
            foreach ($replace as $k1 => $v1) {
                if ($k1 == $k) {
                    foreach ($v1 as $k2 => $v2) {
                        if ($v == $k2) {
                            $v = $v2;
                        }
                    }
                }
            }
        }
        return $arr;
    }

    /*
     * 验证数组字段是否为空
     */
    public static function arrEmptyVerify($data, $arr)
    {
        foreach ($arr as $k => $v) {
            if (!isset($data[$v])) {
                CommonFunc::mapi_error("20000", "参数不足。");
            }
            if ($data[$v] === '' || $data[$v] === null){
                CommonFunc::mapi_error("20000", "参数不足。");
            }
        }
    }

    /*
     * 验证数组字段是否为空
     */
    public static function arrIssetVerify($data, $arr)
    {
        foreach ($arr as $k => $v) {
            if (!isset($data[$v])) {
                CommonFunc::mapi_error("20000", "参数不足。");
            }
        }
    }


}



