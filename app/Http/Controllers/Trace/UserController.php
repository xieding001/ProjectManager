<?php

namespace App\Http\Controllers\Trace;

use App\Func\CommonFunc;
use app\Func\ExcelFunc;
use App\Func\RequestFunc;
use App\Http\Controllers\Controller;
use App\Http\Models\Common\ProjectModel;
use App\Http\Models\Common\RegionModel;
use App\Http\Models\Common\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class UserController extends Controller
{
    private $userModel;
    private $projectModel;
    private $regionModel;

    public function __construct(Request $request)
    {
        $this->userModel = new UserModel();
        $this->projectModel = new ProjectModel();
        $this->regionModel = new RegionModel();
    }

    /*
     * 用户列表
     */
    public function userList(Request $request)
    {
        $condition = [
            "pageNo" => $request->input("pageNo"),
            "pageSize" => $request->input("pageSize"),
            "username" => $request->input("username")
        ];
        $fields = ["tc_users.*", "tc_user_auth.rid", "tc_user_auth.menu"];
        $list = $this->userModel->users_userAuth_select($condition, $fields)->get();

        $count = $this->userModel->users_userAuth_select(RequestFunc::removePage($condition))->count();
        CommonFunc::mapi_export([
            "count" => $count,
            "list" => $list
        ]);
    }

    /*
     * 用户详情
     */
    public function userDetail(Request $request)
    {
        $condition = [
            "uid" => $request->input("uid")
        ];
        $detail = $this->userModel->users_userAuth_select($condition)->first();
        if ($detail->rid) {
            $detail->rid = json_decode($detail->rid, true);
        } else {
            $detail->rid = [];
        }
        if ($detail->menu) {
            $detail->menu = json_decode($detail->menu, true);
        } else {
            $detail->menu = [];
        }
        CommonFunc::mapi_export([
            "detail" => $detail
        ]);
    }

    /*
     * 用户添加
     */
    public function userAdd(Request $request)
    {
        $data = [
            "fid" => $request->uid,
            "username" => $request->input("username"),
            "mobile" => $request->input("mobile"),
            "email" => $request->input("email"),
            "status" => $request->input("status"),
            "role" => "user",
            "password" => "593c9b4a9390551d53e5cacf28ebd638",
            "salt" => "111111"
        ];
        CommonFunc::arrEmptyVerify($data, ["username"]);
        $res = $this->userModel->users_select(["username" => $data['username']])->value("uid");
        if ($res) CommonFunc::mapi_error("20000", "用户名已存在");

        $uid = ProjectModel::insertGetId("tc_users", $data);
        $data = [
            "uid" => $uid,
            "rid" => $request->input("rid"),
            "menu" => $request->input("menu"),
        ];
        ProjectModel::insertGetId("tc_user_auth", $data);
        CommonFunc::mapi_export([
            "res" => 1
        ]);
    }


    /*
     * 用户编辑
     */
    public function userEdit(Request $request)
    {
        $condition = [
            "uid" => $request->input("uid"),
        ];
        $data = [
            "rid" => $request->input("rid"),
            "menu" => $request->input("menu"),
        ];
        ProjectModel::update("tc_user_auth", $condition, $data);
        $data = [
            "mobile" => $request->input("mobile"),
            "email" => $request->input("email"),
            "status" => $request->input("status"),
        ];
        ProjectModel::update("tc_users", $condition, $data);
        CommonFunc::mapi_export([
            "res" => 1
        ]);
    }

    /*
     * 重置密码
     */
    public function userReset(Request $request)
    {
        $condition = [
            "uid" => $request->input("uid"),
        ];
        $data = [
            "password" => "593c9b4a9390551d53e5cacf28ebd638",
            "salt" => "111111"
        ];
        ProjectModel::update("tc_users", $condition, $data);
        CommonFunc::mapi_export([
            "res" => 1
        ]);
    }

    /*
     * 用户修改密码
     */
    public function userPassword(Request $request)
    {
        $pwd1 = $request->input("pwd1");
        $pwd2 = $request->input("pwd2");

        $condition = ["uid" => $request->uid];
        $row = $this->userModel->users_select($condition)->first();

        $md5 = md5($pwd1 . $row->salt);
        if ($md5 != $row->password) {
            CommonFunc::mapi_error("20000", "原密码错误");
        } else {
            UserModel::update("tc_users", $condition, [
                "password" => md5($pwd2 . $row->salt)
            ]);
        }

        CommonFunc::mapi_export([
            "res" => 1
        ]);
    }


}
