<?php
/**
 * Created by PhpStorm.
 * User: xieding001
 * Date: 2019/1/9
 * Time: 15:26
 */

namespace App\Http\Controllers\Trace;

use App\Func\CommonFunc;
use app\Func\ExcelFunc;
use App\Func\RequestFunc;
use App\Http\Controllers\Controller;
use App\Http\Models\Common\ProjectModel;
use App\Http\Models\Common\RegionModel;
use App\Http\Models\Common\UserModel;
use App\Http\Services\Trace\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class FacilityController extends Controller
{
    private $userModel;
    private $projectModel;
    private $projectService;

    public function __construct(Request $request)
    {
        $this->userModel = new UserModel();
        $this->projectModel = new ProjectModel();
        $this->projectService = new ProjectService();
    }

    //企业登录后能看到本企业的项目列表
    public function projectList(Request $request)
    {
        $condition = [
            "uid" => $request->input("uid"),
            "username" => "",
            "pageNo" => $request->input("pageNo"),
            "pageSize" => $request->input("pageSize"),
        ];
        $detail = $this->userModel->users_select($condition)->first();
        $condition["username"] = $detail->username;
//        $condition = $this->projectService->rids($request, $this->userModel, $condition);
        $by['orderBy'] = ["tc_project.project_type" => "desc","tc_project.project_sort" => "asc"];
        $list = $this->projectModel->project_enterprise_select($condition, [], $by)->get();
        foreach ($list as $k => $v) {
           // $v->region = $v->region_name;
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
        $count = $this->projectModel->project_enterprise_select(RequestFunc::removePage($condition))->count();
        CommonFunc::mapi_export([
            "count" => $count,
            "list" => $list
        ]);
    }

    //某个项目的设备投入上报列表
    public function facilityList(Request $request)
    {

        $condition = [
            "pageNo" => $request->input("pageNo"),
            "pageSize" => $request->input("pageSize"),
            "pid" => $request->input("pid"),
            "uid" => $request->input("uid"),
        ];
        $list = $this->getFacilityList($condition["pageNo"],$condition["pageSize"],$condition["pid"],$condition["uid"]);

        $latestInfo = $list->first();
        $this->facilityCheck($latestInfo);
        $count = $this->projectModel->project_processFacility_select(RequestFunc::removePage($condition))->count();
        CommonFunc::mapi_export([
            "count" => $count,
            "list" => $list
        ]);
    }

        //新增设备投入
        public function facilityAdd(Request $request)
    {
        $data = [
            "pid" => $request->input("pid"),
            "uid" => $request->uid,
            "date" => $request->input("date"),
            "invested" => $request->input("invested"),
            "img" => serialize($request->input("img")),
        ];
        //var_dump($data);
        if($data["img"] == "N;")
            CommonFunc::emptyVerify(null,"请上传图片");
        CommonFunc::arrEmptyVerify($data, ["date", "invested"]);

        $list = $this->getFacilityList(1,10, $data["pid"],$data["uid"]);
        $latestInfo = $list->first();
        $tag = $this->facilityCheck($latestInfo);

        //==0表示需要更新到最新记录上 否则新增记录
        if($tag == 0){
            $img = array_merge($request->input("img"),unserialize($latestInfo->img));
            $condition1 = [
                "id" => $latestInfo->id
            ];
            $data1 = [
                "invested" => $latestInfo->invested + $data["invested"],
                "img" => serialize($img)
            ];
            ProjectModel::update("tc_process_facility", $condition1, $data1);
        }
        else
            ProjectModel::insert("tc_process_facility", $data);

        CommonFunc::mapi_export([
            "res" => 1
        ]);
    }

    private function getFacilityList($pageNo, $pageSize, $uid, $pid){
        $condition = [
            "pageNo" => $pageNo,
            "pageSize" => $pageSize,
            "pid" => $uid,
            "uid" => $pid,
        ];
        $fields = ["tc_process_facility.*", "tc_project.name", "tc_project.company"];
        $by['orderBy'] = ["tc_process_facility.create_time" => "desc"];
        $list = $this->projectModel->project_processFacility_select($condition, $fields, $by)->get();
        return $list;
    }

    public function facilityAddAuth(Request $request){
        $condition = [
            "uid" => $request->input("uid"),
            "pid" => $request->input("pid")
        ];
        $detail = $this->userModel->users_select($condition)->first();
        $condition["username"] = $detail->username;
        $fields = ["tc_project.name", "tc_project.company"];
        $list = $this->projectModel->project_enterprise_select($condition, $fields);
        if($list)
            CommonFunc::mapi_export([
                "res" => $list->first()
            ]);
        else
            CommonFunc::mapi_export([
                "res" => null
            ]);
    }

    public function facilityGet(Request $request)
    {
        $traceId = $request->input("trace_id");
        $condition = ['id' => $traceId];
        $fields = ["tc_process_facility.*","tc_project.name", "tc_project.company"];
        $by['orderBy'] = ["tc_process_facility.create_time" => "desc"];
        $traceInfo = $this->projectModel->project_processFacility_select($condition, $fields, $by)->first();
        $traceInfo->img = unserialize($traceInfo->img);
        if(empty($traceInfo->img)){
            $traceInfo->img = [];
        }
        CommonFunc::mapi_export([
            "res" => $traceInfo
        ]);

    }

    public function facilityEdit(Request $request)
    {
        $data = [
            "id" => $request->input("trace_id"),
            "pid" => $request->input("pid"),
            "uid" => $request->uid,
            "invested" => $request->input("invested"),
            "date" => $request->input("date"),
            "img" => serialize($request->input("img")),
        ];
        //var_dump($data);
        if($data["img"] == "N;")
            CommonFunc::emptyVerify(null,"请上传图片");
        CommonFunc::arrEmptyVerify($data, ["id","date", "invested"]);

        $condition = ['id'=>$request->input('trace_id')];

        ProjectModel::update("tc_process_facility", $condition, $data);
        CommonFunc::mapi_export([
            "res" => 1
        ]);
    }

    private function facilityCheck($latestInfo){
   //     $traceInfo = $this->projectModel->project_processFacility_select($condition, $fields, $by)->first();
        if($latestInfo == null) {
            return -1;
        }
        $data = [
            "new_flag" => 0
        ];
        $condition1 = [
            "pid" => $latestInfo->pid
        ];
        $year = (int)date("Y");
        $month = (int)date("m");
        $day = (int)date("d");

        $year_db = (int)substr($latestInfo->date,0,4);
        $month_db = (int)substr($latestInfo->date,7,2);

        //最新记录的月份 和 当前月份最多只相差一个月 且当前是在10号之前的则不更新
        //否则把最新记录的new_flag设为0，永远最多只有最新一条记录可以被编辑
        if(($month == $month_db && $year_db == $year && $day > 10) || ($day <= 10 && (($year_db == $year && $month - $month_db == 1) || ($year - $year_db == 1 && $month_db == 12 && $month == 1))))
            return 0;
        else {
            if($latestInfo->new_flag == 1)
                ProjectModel::update("tc_process_facility", $condition1, $data);
            return 1;
        }

    }

}