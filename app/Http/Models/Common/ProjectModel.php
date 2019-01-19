<?php
namespace App\Http\Models\Common;

use App\Http\Models\Common\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProjectModel extends Model
{

    /*
     * tc_project 项目查询
     */
    public function project_select($condition, $fields = [], $by = [])
    {
        $this->obj = DB::table("tc_project")->where("del_flag", 0);
        foreach ($condition as $k => $v) {
            if ($v !== null && $v !== '') {
                switch ($k) {
                    case "pid":
                    case "uid":
                    case "rid":

                        $this->obj = $this->obj->where($k, $v);
                        break;
                    case "name":
                    case "company":
                        $this->obj = $this->obj->where($k, "like", "%{$v}%");
                        break;
                    case "rids":
                        $this->obj = $this->obj->whereIn("rid", $v);
                        break;
                }
            }
        }

        $this->getJoin($condition, $fields, $by);

        return $this->obj;
    }

    /*
     * tc_project tc_region 项目查询
     */
    public function project_region_select($condition, $fields = [], $by = [])
    {
        $this->obj = DB::table("tc_project")
            ->leftJoin('tc_region', function ($join1) {
                $join1->on('tc_project.rid', '=', 'tc_region.id');
            })->where("tc_project.del_flag", 0);
        foreach ($condition as $k => $v) {
            if ($v !== null && $v !== '') {
                switch ($k) {
                    case "pid":
                    case "uid":
                    case "rid":
                        $this->obj = $this->obj->where("tc_project." . $k, $v);
                        break;
                    case "name":
                    case "company":
                        $this->obj = $this->obj->where("tc_project." . $k, "like", "%{$v}%");
                        break;
                    case "rids":
                        $this->obj = $this->obj->whereIn("tc_project.rid", $v);
                        break;
                }
            }
        }

        $this->getJoin($condition, $fields, $by);

        return $this->obj;
    }

    /*
     * 前期进度
     */
    public function processPre_select($condition, $fields = [], $by = [])
    {
        $this->obj = DB::table("tc_process_pre")->where("del_flag", 0);
        foreach ($condition as $k => $v) {
            if ($v !== null && $v !== '') {
                switch ($k) {
                    case "id":
                        $this->obj = $this->obj->where($k, $v);
                        break;
                }
            }
        }

        $this->getJoin($condition, $fields, $by);

        return $this->obj;
    }

    /*
     * 验收进度
     */
    public function processCheck_select($condition, $fields = [], $by = [])
    {
        $this->obj = DB::table("tc_process_check")->where("del_flag", 0);
        foreach ($condition as $k => $v) {
            if ($v !== null && $v !== '') {
                switch ($k) {
                    case "id":
                        $this->obj = $this->obj->where($k, $v);
                        break;
                }
            }
        }

        $this->getJoin($condition, $fields, $by);

        return $this->obj;
    }


    /*
     * 进度申报
     */
    public function processApply_select($condition, $fields = [], $by = [])
    {
        $this->obj = DB::table("tc_process_apply")->where("del_flag", 0);
        foreach ($condition as $k => $v) {
            if ($v !== null && $v !== '') {
                switch ($k) {
                    case "id":
                    case "pid":
                    case "type":
                        $this->obj = $this->obj->where($k, $v);
                        break;
                }
            }
        }

        $this->getJoin($condition, $fields, $by);

        return $this->obj;
    }

    /*
     * 形象进度查询
     */
    public function processTrace_select($condition, $fields = [], $by = [])
    {
        $this->obj = DB::table("tc_process_trace")->where("del_flag", 0);
        foreach ($condition as $k => $v) {
            if ($v !== null && $v !== '') {
                switch ($k) {
                    case "id":
                    case "uid":
                    case "pid":
                    case "flow":
                    case "new_flag":
                    case "status":
                        $this->obj = $this->obj->where($k, $v);
                        break;
                    case "not_flow":
                        $this->obj = $this->obj->where("flow != '0'");
                    case "month":
                        $this->obj = $this->obj->where("tc_process_trace.create_time", "<=",$v);
                        break;
                    case "curmonth":
                        //当前日期小于20号
                        if(date('d') < 20){
                            $this->obj = $this->obj->where("tc_process_trace.create_time", ">",Carbon::parse(date('Y-m-20',strtotime(date('Y',time()).'-'.(date('m',time())-1).'-20'))));
                        }else{
                            $this->obj = $this->obj->where("tc_process_trace.create_time", ">",Carbon::parse(date('Y-m').'-20'));
                        }
                        break;
                        
                }
            }
        }

        $this->getJoin($condition, $fields, $by);

        return $this->obj;
    }

    /*
     * 形象进度 右连接 项目查询
     */
    public function processTrace_project_select($condition, $fields = [], $by = [])
    {
        $this->obj = DB::table("tc_process_trace")
            ->leftJoin('tc_project', function ($join1) {
                $join1->on('tc_process_trace.pid', '=', 'tc_project.pid')
                    ->where('tc_project.del_flag', 0);
            })->where("tc_process_trace.del_flag", 0);
        foreach ($condition as $k => $v) {
            if ($v !== null && $v !== '') {
                switch ($k) {
                    case "id":
                    case "uid":
                    case "pid":
                    case "status":
                        $this->obj = $this->obj->where("tc_process_trace." . $k, $v);
                        break;
                    case "rid":
                        $this->obj = $this->obj->where("tc_project." . $k, $v);
                        break;
                    case "rids":
                        $this->obj = $this->obj->whereIn("tc_project.rid", $v);
                        break;
                }
            }
        }

        $this->getJoin($condition, $fields, $by);

        return $this->obj;
    }

    public function project_processTrace_select($condition, $fields = [], $by = [])
    {
        //默认时间
        if(empty($condition['month'])){
            $condition['month'] = '2099-12-31';
        }
        $month = $condition['month'];
        $subQuey = DB::raw("(SELECT tc_process_trace.pid,ifnull(tc_process_trace.invest_all_used,0) as invest_all_used,ifnull(tc_process_trace.invest_device_used,0) as invest_device_used,tc_process_trace.desc as desc2,tc_process_trace.type as type FROM tc_process_trace WHERE tc_process_trace.id IN ( SELECT max(id) FROM tc_process_trace A WHERE A.flow = '0' AND A. STATUS = 1 AND A.del_flag = 0 AND A.create_time < '$month' GROUP BY pid )) trace");
        $this->obj = DB::table("tc_project")
            ->leftJoin($subQuey, function ($join1) {
                $join1->on('trace.pid', '=', 'tc_project.pid');
            })->leftJoin('tc_region', function ($join1) {
                $join1->on('tc_project.rid', '=', 'tc_region.id');
            })->where("tc_project.del_flag", 0);
        foreach ($condition as $k => $v) {
            if ($v !== null && $v !== '') {
                switch ($k) {
                    case "company":
                        $this->obj = $this->obj->where("tc_project.$k", "like", "%{$v}%");
                        break;
                    case "pid":
                        $this->obj = $this->obj->where("tc_project.$k", $v);
                        break;
                    case "region":
                        $this->obj = $this->obj->whereIn("tc_project.rid", $v);
                        break;
                    case "product_important":
                        $this->obj = $this->obj->whereIn("tc_project.product_important", $v);
                        break;
                    case "project_types":
                        $this->obj = $this->obj->whereIn("tc_project.project_type", $v);
                        break;
                    case "product_type":
                        $this->obj = $this->obj->whereIn("trace.type", $v);
                        break;
                    case "invest_1":
                        $this->obj = $this->obj->where("tc_project.invest_all", ">=", $v);
                        break;
                    case "invest_2":
                        $this->obj = $this->obj->where("tc_project.invest_all", "<=", $v);
                        break;
                    case "area_1":
                        $this->obj = $this->obj->where("tc_project.area_all", ">=", $v);
                        break;
                    case "area_2":
                        $this->obj = $this->obj->where("tc_project.area_all", "<=", $v);
                        break;
                    case "process_1":
                        $this->obj = $this->obj->where("trace.invest_all_used", ">=", $v);
                        break;
                    case "process_2":
                        $this->obj = $this->obj->where("trace.invest_all_used", "<=", $v);
                        break;
                    case "year":
                        $this->obj = $this->obj->whereRaw("left(tc_project.create_time, 4) = $v");
                        break;
                    case "rids":
                        $this->obj = $this->obj->whereIn("tc_project.rid", $v);
                        break;
                }
            }
        }

        $this->getJoin($condition, $fields, $by);

        return $this->obj;
    }

    //根据项目id 用户id 找到对应的投入填报列表
    //根据id找到详情
    public function project_processFacility_select($condition, $fields = [], $by = []){
        $this->obj = DB::table("tc_process_facility")
            ->leftJoin('tc_project', function ($join1) {
                $join1->on('tc_process_facility.pid', '=', 'tc_project.pid');
            })->where("tc_process_facility.del_flag", 0);

        foreach ($condition as $k => $v) {
            if ($v !== null && $v !== '') {
                switch ($k) {
                    case "uid": $this->obj = $this->obj->where("tc_process_facility." . $k, $v);
                        break;
                    case "id": $this->obj = $this->obj->where("tc_process_facility." . $k, $v);
                        break;
                    case "pid": $this->obj = $this->obj->where("tc_process_facility.pid", $v);
                        break;
                }
            }
        }
        $this->getJoin($condition, $fields, $by);
        return $this->obj;
    }

    //根据用户名和企业联系电话匹配可见项目列表
    public function project_enterprise_select($condition, $fields = [], $by = []){
        $this->obj = DB::table("tc_project")->where("tc_project.del_flag", 0);

        foreach ($condition as $k => $v) {
            if ($v !== '') {
                switch ($k) {
                    case "username":
                        $this->obj = $this->obj->where("tc_project.company_contact", $v);
                        break;
                    case "pid":
                        $this->obj = $this->obj->where("tc_project." . $k, $v);
                        break;
                }
            }
        }

        $this->getJoin($condition, $fields, $by);
        return $this->obj;
    }

}