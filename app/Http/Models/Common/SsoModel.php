<?php
namespace App\Http\Models\Common;

use App\Http\Models\Common\Model;
use Illuminate\Support\Facades\DB;

class SsoModel extends Model
{
    /*
     * 关联表查询
     */
    public function userRelate_select($condition, $fields = [], $by = []){


    }

    public function userRelate_m1_select(){

    }

    /*
     *
     */
    public function userRelate_m1_m2_select($condition, $fields = [], $by = []){
        $this->obj = DB::table("sso.ss_user_relative as a")
            ->leftJoin('trace.tc_users as b', function ($join1) {
                $join1->on('a.uid_zz', '=', 'b.uid');
            })->leftJoin('jyh.cxly_member as c', function ($join1) {
            $join1->on('a.uid_jyh', '=', 'c.uid');
        })->where("a.del_flag", 0);

        foreach ($condition as $k => $v) {
            if ($v !== '') {
                switch ($k) {
                    case "uid_zz":
                    case "uid_jyh":
                        $this->obj = $this->obj->where("a." . $k, $v);
                        break;
                }
            }
        }
        $this->getJoin($condition, $fields, $by);

        return $this->obj;
    }
}