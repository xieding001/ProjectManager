<?php

namespace App\Http\Controllers\Trace;

use App\Func\CommonFunc;
use App\Func\RequestFunc;
use App\Http\Controllers\Controller;
use App\Http\Models\Common\ProjectModel;
use App\Http\Models\Common\RegionModel;
use App\Http\Models\Common\UserModel;
use App\Http\Services\Trace\ProjectService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ProjectController extends Controller
{
    private $userModel;
    private $projectModel;
    private $regionModel;
    private $projectService;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->projectModel = new ProjectModel();
        $this->regionModel = new RegionModel();
        $this->projectService = new ProjectService();
    }

    /*
     * 重点项目列表
     */
    public function projectList(Request $request)
    {
        $rid = $request->input("rid");

        $condition = [
            "rid" => $rid,
            "name" => $request->input("name"),
            "pageNo" => $request->input("pageNo"),
            "pageSize" => $request->input("pageSize"),
        ];
        $condition = $this->projectService->rids($request, $this->userModel, $condition);
        $by['orderBy'] = ["tc_project.project_type" => "desc","tc_region.sort" => "asc","tc_project.project_sort" => "asc"];
        $list = $this->projectModel->project_region_select($condition, [], $by)->get();
        foreach ($list as $k => $v) {
            $v->region = $v->region_name;
            $v->invest_all = $v->invest_all > 0 ? $v->invest_all : 1;
            //续建项目
            $v->invest_all_used = $v->project_type == 2? $v->invest_all_used + $v->invest_pre:$v->invest_all_used;
            $v->invest_all_process = round($v->invest_all_used / $v->invest_all * 100, 2) . "%";
            $v->is_trace = $this->projectModel->processTrace_select(["pid" => $v->pid,"curmonth" => 1])->count();
            $condition_trace = [
                "pid" => $v->pid,
                "status" => 1
            ];
            $by['orderBy'] = ["id" => "desc"];
            $v->trace = $this->projectModel->processTrace_select($condition_trace, [], $by)->value("desc");
        }
        $count = $this->projectModel->project_select(RequestFunc::removePage($condition))->count();
        CommonFunc::mapi_export([
            "count" => $count,
            "list" => $list
        ]);
    }

    /*
     * 重点项目详情
     */
    public function projectDetail(Request $request)
    {
        $pid = $request->input("pid");

        $condition = [
            "pid" => $pid,
        ];
        //1.详情
        $detail = $this->projectModel->project_select($condition)->first();
        $detail->region = $this->regionModel->region_select(["id" => $detail->rid])->value("region_name");
        $detail->invest_all_used = $detail->project_type == 2? $detail->invest_all_used + $detail->invest_pre:$detail->invest_all_used;
        $detail->invest_device_used = $detail->project_type == 2? $detail->invest_device_used + $detail->invest_device_pre:$detail->invest_device_used;
        //2.前期进度
        $condition = ["id" => $pid];
        $pre = $this->projectModel->processPre_select($condition)->first();
        $json_string = file_get_contents(dirname(__FILE__) . '/process_pre.json');
        $pre_json = json_decode($json_string, true);
        foreach ($pre_json['nodes'] as $k => &$v) {
            switch ($pre->$k) {
                case 0:
                    $v['color'] = "#DBDBDB";
                    $v['click'] = "";
                    $v['value'] = 0;
                    break;
                case 1:
                    $v['color'] = "#CDCD00";
                    $v['click'] = "redict_iframe('/trace/t/#project/report.html?id=" . $pre->id . "&type=" . $k . "&show=3')";
                    $v['value'] = 1;
                    break;
                case 2:
                    $v['color'] = "#436EEE";
                    $v['click'] = "redict_iframe('/trace/t/#project/report.html?id=" . $pre->id . "&type=" . $k . "&show=3')";
                    $v['value'] = 2;
                    break;
            }
            if ($v['color'] == "#436EEE") {
                $condition_trace = [
                    "pid" => $pid,
                    "flow" => $k,
                ];
                $condition_apply = [
                    "pid" => $pid,
                    "type" => $k,
                ];
                $status = $this->projectModel->processTrace_select($condition_trace)->value('status');
                // $time_end = $this->projectModel->processApply_select($condition_apply)->value("time_end");
                //var_dump($condition_trace);var_dump($status);
                if (($status != 1)) {
                    $v['color'] = "#ff6000";
                    $v['click'] = "redict_iframe('/trace/t/#project/report.html?id=" . $pre->id . "&type=" . $k . "&show=3')";
                }else{
                    $v['color'] = "#00CD00";
                    $v['click'] = "redict_iframe('/trace/t/#project/report.html?id=" . $pre->id . "&type=" . $k . "&show=3')";
                }
            }
        }

        //3.验收进度
        $condition = ["id" => $pid];
        $check = $this->projectModel->processCheck_select($condition)->first();
        $json_string = file_get_contents(dirname(__FILE__) . '/process_check.json');
        $check_json = json_decode($json_string, true);
        foreach ($check_json['nodes'] as $k => &$v) {
            switch ($check->$k) {
                case 0:
                    $v['color'] = "#DBDBDB";
                    $v['click'] = "";
                    break;
                case 1:
                    $v['color'] = "#CDCD00";
                    $v['click'] = "redict_iframe('/trace/t/#project/report2.html?id=" . $pre->id . "&type=" . $k . "&show=5')";
                    break;
                case 2:
                    $v['color'] = "#436EEE";
                    $v['click'] = "redict_iframe('/trace/t/#project/report2.html?id=" . $pre->id . "&type=" . $k . "&show=5')";
                    break;
            }
            if ($v['color'] == "#436EEE") {
                $condition_trace = [
                    "pid" => $pid,
                    "flow" => $k,
                ];
                $condition_apply = [
                    "pid" => $pid,
                    "type" => $k,
                ];
                $status = $this->projectModel->processTrace_select($condition_trace)->value("status");
                $time_end = $this->projectModel->processApply_select($condition_apply)->value("time_end");
                if (($status != 1) || empty($time_end)) {
                    $v['color'] = "#ff6000";
                    $v['click'] = "redict_iframe('/trace/t/#project/report2.html?id=" . $pre->id . "&type=" . $k . "&show=5')";
                }
            }
        }
        //4.形象进度
        $condition = ["pid" => $pid];
        $trace = $this->projectModel->processTrace_select($condition)->get();
        foreach ($trace as $k => $v1) {
            $v1->img = unserialize($v1->img);
            if(!$v1->img){
                $v1->img = null;
            }
            $v1->flow = $v1->flow=='0'?1:0;
        }

        CommonFunc::mapi_export([
            "detail" => $detail,
            "process_pre" => $pre_json,
            "process_check" => $check_json,
            "trace" => $trace,
            "canClick" => $pre->map_record
        ]);
    }

    /*
     * 重点项目增加
     */
    public function projectAdd(Request $request)
    {
        DB::beginTransaction();
        try {
            //一、项目进度
            $data = [
                "uid" => $request->uid,
                "rid" => $request->input("rid"),
                "name" => $request->input("name"),
                "desc" => $request->input("desc"),
                "company" => $request->input("company"),
                "company_user" => $request->input("company_user"),
                "company_contact" => $request->input("company_contact"),
                "important_type" => $request->input("important_type"),
                "invest_all" => $request->input("invest_all"),
                "invest_pre" => $request->input("invest_pre"),
                "invest_now" => $request->input("invest_now"),
                "invest_device_now" => $request->input("invest_device_now"),
                "invest_device" => $request->input("invest_device"),
                "invest_device_pre" => $request->input("invest_device_pre"),
                "invest_device_smart" => $request->input("invest_device_smart"),
                "invest_device_import" => $request->input("invest_device_import"),
                "project_type" => $request->input("project_type"),
                "product_new" => $request->input("product_new"),
                "product_important" => $request->input("product_important"),
                "area_type" => $request->input("area_type"),
                "area_all" => $request->input("area_all"),
                "area_plant" => $request->input("area_plant"),
                "area_plant_build" => $request->input("area_plant_build"),
                "area_work" => $request->input("area_work"),
                "date_begin" => $request->input("date_begin"),
                "date_invest" => $request->input("date_invest"),
                "guess_sale" => $request->input("guess_sale"),
                "guess_capacity" => $request->input("guess_capacity"),
                "tax" => $request->input("tax"),
                "profit" => $request->input("profit")
            ];
            CommonFunc::arrEmptyVerify($data, ['name', 'company']);

            foreach ($data as $k => $v) {
                if (empty($v)) {
                    $data[$k] = 0;
                }
            }
            $id = ProjectModel::insertGetId("tc_project", $data);
            //更新排序
            $condition = ['pid' => $id];
            $data = ['project_sort' => $id];
            ProjectModel::update("tc_project",$condition,$data);
            //二、前期进度
            ProjectModel::insertGetId("tc_process_pre", [
                "id" => $id,
                "land_index" => 1,
                "name" => 1,
            ]);
            //三、验收进度
            ProjectModel::insertGetId("tc_process_check", [
                "id" => $id
            ]);
            DB::commit();
            CommonFunc::mapi_export([
                "res" => 1
            ]);
        } catch (ModelNotFoundException  $e) {
            DB::rollBack();
            CommonFunc::mapi_error("20000", "添加失败");
        }
    }

    /*
     * 重点项目修改
     */
    public function projectEdit(Request $request)
    {
        DB::beginTransaction();
        try {
            $condition = [
                "pid" => $request->pid
            ];
            $data = [
                "uid" => $request->uid,
                "rid" => $request->input("rid"),
                "name" => $request->input("name"),
                "desc" => $request->input("desc"),
                "company" => $request->input("company"),
                "company_user" => $request->input("company_user"),
                "company_contact" => $request->input("company_contact"),
                "important_type" => $request->input("important_type"),
                "invest_all" => $request->input("invest_all"),
                "invest_pre" => $request->input("invest_pre"),
                "invest_now" => $request->input("invest_now"),
                "invest_device_now" => $request->input("invest_device_now"),
                "invest_device" => $request->input("invest_device"),
                "invest_device_pre" => $request->input("invest_device_pre"),
                "invest_device_smart" => $request->input("invest_device_smart"),
                "invest_device_import" => $request->input("invest_device_import"),
                "project_type" => $request->input("project_type"),
                "area_type" => $request->input("area_type"),
                "product_new" => $request->input("product_new"),
                "product_important" => $request->input("product_important"),
                "area_all" => $request->input("area_all"),
                "area_plant" => $request->input("area_plant"),
                "area_plant_build" => $request->input("area_plant_build"),
                "area_work" => $request->input("area_work"),
                "date_begin" => $request->input("date_begin"),
                "date_invest" => $request->input("date_invest"),
                "guess_sale" => $request->input("guess_sale"),
                "guess_capacity" => $request->input("guess_capacity"),
                "tax" => $request->input("tax"),
                "profit" => $request->input("profit")
            ];
            CommonFunc::arrEmptyVerify($data, ['name', 'company']);

           /*  foreach ($data as $k => $v) {
                if (empty($v)) {
					if(gettype($v) == 'string')
					{
						$data[$k] = '';
					}
					else{
						$data[$k] = 0;
					}
                }
            } */
            ProjectModel::update("tc_project", $condition, $data);
            DB::commit();
            CommonFunc::mapi_export([
                "res" => 1
            ]);
        } catch (Exception  $e) {
            DB::rollBack();
            CommonFunc::mapi_error("20000", "修改失败");
        }
    }

    /*
     * 项目删除
     */
    public function projectDel(Request $request)
    {
        $data = $request->all();
        CommonFunc::arrEmptyVerify($data, ['pid']);

        DB::beginTransaction();
        try {
            $condition = ["pid" => $data['pid']];
            $data = ["del_flag" => 1];
            ProjectModel::update("tc_project", $condition, $data);
            ProjectModel::update("tc_process_apply", $condition, $data);
            ProjectModel::update("tc_process_trace", $condition, $data);

            DB::commit();
            CommonFunc::mapi_export([
                "res" => 1
            ]);
        } catch (Exception  $e) {
            DB::rollBack();
            CommonFunc::mapi_error("20000", "修改失败");
        }

    }

    public function projectUp(Request $request)
    {
        $data = $request->all();
        CommonFunc::arrEmptyVerify($data, ['start']);
        $start = $data['start'];
        DB::beginTransaction();
        try {
            $condition = [
            	"name" => $data['name'],
            	'rid' => $data['rid'],
            ];
            $by['orderBy'] = ["tc_project.project_type" => "desc","tc_region.sort" => "asc","tc_project.project_sort" => "asc"];
            $condition = $this->projectService->rids($request, $this->userModel, $condition);
        	$list = $this->projectModel->project_region_select($condition, [], $by)->skip($start-1)->take(2)->get();
            $first = $list[0];
            $second = $list[1];
            if(($first->project_type != $second->project_type) || ($first->rid != $second->rid))
            {
                throw new Exception("不同镇区不同属性上移无效！");
            }
            $condition = ['pid' => $first->pid];
            $data = ['project_sort'=>$second->project_sort];
            var_dump($list);
            ProjectModel::update("tc_project", $condition, $data);
            $condition = ['pid' => $second->pid];
            $data = ['project_sort'=>$first->project_sort];

            ProjectModel::update("tc_project", $condition, $data);
            DB::commit();
            CommonFunc::mapi_export([
                "res" => 1
            ]);
        } catch (Exception  $e) {
            DB::rollBack();
            //var_dump($e);
            CommonFunc::mapi_error("20000", $e->getMessage());
        }

    }

    public function projectDown(Request $request)
    {
        $data = $request->all();
        CommonFunc::arrEmptyVerify($data, ['start']);
        $start = $data['start'];
        DB::beginTransaction();
        try {
            $condition = [
                "name" => $data['name'],
                'rid' => $data['rid'],
            ];
            $by['orderBy'] = ["tc_project.project_type" => "desc","tc_region.sort" => "asc","tc_project.project_sort" => "asc"];
            $condition = $this->projectService->rids($request, $this->userModel, $condition);
            $list = $this->projectModel->project_region_select($condition, [], $by)->skip($start)->take(2)->get();
            if(count($list) < 2){
                throw new Exception("无法下移");
            }
            $first = $list[0];
            $second = $list[1];
            if(($first->project_type != $second->project_type) || ($first->rid != $second->rid))
            {
                throw Exception("不同镇区不同属性上移无效！");
            }
            $condition = ['pid' => $first->pid];
            $data = ['project_sort'=>$second->project_sort];
            var_dump($list);
            ProjectModel::update("tc_project", $condition, $data);
            $condition = ['pid' => $second->pid];
            $data = ['project_sort'=>$first->project_sort];

            ProjectModel::update("tc_project", $condition, $data);
            DB::commit();
            CommonFunc::mapi_export([
                "res" => 1
            ]);
        } catch (Exception  $e) {
            DB::rollBack();
            //var_dump($e);
            CommonFunc::mapi_error("20000", $e->getMessage());
        }

    }

    /*
     * 镇区列表
     */
    public function projectRegion(Request $request)
    {
        $list = $this->regionModel->region_select([])->get();
        CommonFunc::mapi_export([
            "list" => $list
        ]);
    }

}
