<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/10/11
 * Time: 22:42
 */

namespace App\Http\Models\Common;


use Illuminate\Support\Facades\DB;

class Model
{
    protected $obj;

    /*
     * 查询结果字段选择
     */
    public function fields($getFields){

        if(!empty($getFields)){
            $this->obj = $this->obj->select($getFields);
        }
    }

    /*
     * 分页
     */
    public function limit($condition){

        if(!empty($condition['pageNo'])){
            if(empty($condition['pageSize'])){
                $condition['pageSize'] = 10;
            }
            $this->obj = $this->obj->skip(($condition['pageNo']-1)*$condition['pageSize'])->take($condition['pageSize']);
        }
    }

    /*
     * orderBy && groupBy控制
     */
    public function by($by){

        if(!empty($by['orderBy'])){
            foreach ($by['orderBy'] as $k => $v){
                $this->obj = $this->obj->orderBy($k , $v);
            }
        }
        if(!empty($by['groupBy'])){
            foreach ($by['groupBy'] as $k => $v){
                $this->obj = $this->obj->groupBy($v);
            }
        }
    }

    /*
     * 插入单挑记录操作，所有对象共享-->静态方法
     */
    public static function insertGetId($table , $fields){

        $id = DB::table($table)->insertGetId($fields);

        return $id;
    }

    /*
     * 插入操作（可同时插入多条sql语句），所有对象共享-->静态方法
     * $fields可以是个二维数组
     * DB::table('users')->insert([
     *    ['email' => 'taylor@example.com', 'votes' => 0],
     *    ['email' => 'dayle@example.com', 'votes' => 0]
     * ]);
     */
    public static function insert($table , $fields){

        DB::table($table)->insert($fields);
    }

    /*
     * 所有更新操作，所有对象共享-->静态方法
     */
    public static function update($table , $condition , $fields){

        DB::table($table)->where($condition)->update($fields);

    }

    public function getJoin($condition,$fields,$by){
        $this->fields($fields);
        $this->limit($condition);
        $this->by($by);
        return $this->obj;
    }
}