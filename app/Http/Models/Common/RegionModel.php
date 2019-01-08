<?php
namespace App\Http\Models\Common;

use App\Http\Models\Common\Model;
use Illuminate\Support\Facades\DB;

class RegionModel extends Model
{

    /*
     * tc_region 项目查询
     */
    public function region_select($condition ,$fields=[] ,$by=[]){
        $this->obj = DB::table("tc_region")->where("del_flag" , 0);
        foreach ($condition as $k => $v){
            if($v !== null && $v !== ''){
                switch ($k){
                    case "id":
                        $this->obj = $this->obj->where($k , $v);
                        break;
                }
            }
        }

        $this->getJoin($condition, $fields, $by);

        return $this->obj;
    }


}