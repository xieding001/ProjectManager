<?php
namespace App\Func;
/**
 * Class Common
 * @package App\Func
 */
class RequestFunc{

    /*
     * 删除条件中的page字段，用于获取总条数
     */
    public static function removePage($condition){
        if(!empty($condition["pageNo"])){
            unset($condition["pageNo"]);
        }
        if(!empty($condition["pageSize"])){
            unset($condition["pageSize"]);
        }
        return $condition;
    }

}



