<?php

namespace App\Http\Controllers\Trace;

use App\Func\CommonFunc;
use App\Http\Controllers\Controller;
use App\Http\Models\Common\SsoModel;
use App\Http\Models\Common\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class LoginController extends Controller
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function login(Request $request)
    {
        $username = $request->input("username");
        $pwd = $request->input("pwd");
        //一、验证登录
        $condition = ["username" => $username];
        $row = $this->userModel->users_select($condition)->first();
        if (!empty($row->uid)) {
            if (empty($row->status)) {
                CommonFunc::mapi_error("20000", "用户被禁用。");
            }
            $md5 = md5($pwd . $row->salt);
            if ($md5 != $row->password) {
                CommonFunc::mapi_error("20000", "密码错误");
            }
        } else {
            CommonFunc::mapi_error("20000", "用户名错误");
        }
        //二、获取sso用户信息，存在关联情况则设置工业集约化session
        session_start();
        $condition = ["uid_zz" => $row->uid];
        $fields = ["c.*"];
        $ssoModel = new SsoModel();
        $sso = $ssoModel->userRelate_m1_m2_select($condition, $fields)->first();
        if (!empty($sso->uid)) {
            $_SESSION['admin']['user_auth'] = [
                "uid" => $sso->uid,
                "username" => $sso->username,
                "last_login_time" => $sso->last_login_time,
            ];
            $_SESSION['admin']['user_auth_sign'] = $this->data_auth_sign($_SESSION['admin']['user_auth']);
        }
        //三、设置重点工业追踪的session
        $_SESSION['admin_zz'] = [
            "user_auth" => [
                "uid" => $row->uid,
                "role" => $row->role,
            ]
        ];
        //四、设置sessionId的cookie
        $host = explode(".",$_SERVER['HTTP_HOST']);
        if(count($host)>2){
            $host = $host[1].".".$host[2];
        }else{
            $host = $host[0].".".$host[1];
        }
        setcookie("PHPSESSID", $_COOKIE['PHPSESSID'], time()+3600*2, "/", $host);
        setcookie("login_union",1,time()+3600*2,"/");

        UserModel::insert("tc_user_log", [
            "uid" => $row->uid,
            "ip" => CommonFunc::get_real_ip()
        ]);

        CommonFunc::mapi_export([
            "uid" => $row->uid,
            "username" => $row->username,
            "role" => $row->role,
        ]);
    }

    /*
     * 退出登录
     */
    public function unLogin(){
        session_start();
        unset($_SESSION['admin_zz']);
        unset($_SESSION['admin']);
        CommonFunc::mapi_export([
            "res" => 1
        ]);
    }

    /*
     * 用户登陆信息
     */
    public function loginDetail(Request $request)
    {
        $condition = [
            "uid" => $request->uid
        ];
        $fields = ["uid", "mobile", "username", "role"];
        $res = $this->userModel->users_select($condition, $fields)->first();
        if (empty($res)) {
            CommonFunc::mapi_error("20000", "非法操作");
        }

        $menu = $this->loginMenu($request);

        CommonFunc::mapi_export([
            "detail" => $res,
            "menu" => $menu
        ]);
    }

    /*
     * 登录菜单详情
     */
    public function loginMenu($request)
    {
        $condition = [
            "uid" => $request->uid
        ];
        $detail = $this->userModel->users_userAuth_select($condition)->first();
        $detail->menu = json_decode($detail->menu, true);

        $json_string = file_get_contents(dirname(__FILE__) . '/menu.json');
        $menu = json_decode($json_string, true);

        if ($detail->role == "admin") {
            $menu2 = $menu;
        } else {
            $menu2 = [];
            foreach ($menu as $k => $v) {
                foreach ($detail->menu as $k1 => $v1) {
                    if (strpos($v['url'], $v1) !== false) {
                        $menu2[] = $v;
                        break;
                    }
                }
            }
        }
        return $menu2;
    }

    function data_auth_sign($data)
    {
        //数据类型检测
        if (!is_array($data)) {
            $data = (array)$data;
        }
        ksort($data); //排序
        $code = http_build_query($data); //url编码并生成query字符串
        $sign = sha1($code); //生成签名
        return $sign;
    }


}
