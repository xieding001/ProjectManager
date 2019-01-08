<?php
namespace App\Http\Models\Common;

use App\Http\Models\Common\Model;
use Illuminate\Support\Facades\DB;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/11
 * Time: 22:06
 */
class UserModel extends Model
{
    /*
     * tc_users 表查询
     */
    public function users_select($condition, $fields = [], $by = [])
    {
        $this->obj = DB::table("tc_users")->where("del_flag", 0);

        foreach ($condition as $k => $v) {
            if ($v !== '') {
                switch ($k) {
                    case "mobile":
                    case "username":
                    case "email":
                    case "password":
                    case "role":
                    case "uid":
                        $this->obj = $this->obj->where($k, $v);
                        break;
                }
            }
        }
        $this->getJoin($condition, $fields, $by);

        return $this->obj;
    }

    /*
     * tc_user_auth 表查询
     */
    public function userAuth_select($condition, $fields = [], $by = [])
    {
        $this->obj = DB::table("tc_user_auth");

        foreach ($condition as $k => $v) {
            if ($v !== '') {
                switch ($k) {
                    case "uid":
                        $this->obj = $this->obj->where($k, $v);
                        break;
                }
            }
        }
        $this->getJoin($condition, $fields, $by);

        return $this->obj;
    }

    /*
     * tc_users 表查询
     */
    public function users_userAuth_select($condition, $fields = [], $by = [])
    {
        $this->obj = DB::table("tc_users")
            ->leftJoin('tc_user_auth', function ($join1) {
                $join1->on('tc_users.uid', '=', 'tc_user_auth.uid');
            })->where("tc_users.del_flag", 0);;

        foreach ($condition as $k => $v) {
            if ($v !== '') {
                switch ($k) {
                    case "username":
                        $this->obj = $this->obj->where("tc_users." . $k, "like", "%" . $v . "%");
                        break;
                    case "role":
                    case "uid":
                        $this->obj = $this->obj->where("tc_users." . $k, $v);
                        break;
                }
            }
        }
        $this->getJoin($condition, $fields, $by);

        return $this->obj;
    }

}