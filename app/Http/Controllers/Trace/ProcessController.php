<?php

namespace App\Http\Controllers\Trace;

use App\Func\CommonFunc;
use App\Func\RequestFunc;
use App\Http\Controllers\Controller;
use App\Http\Models\Common\ProjectModel;
use App\Http\Models\Common\RegionModel;
use App\Http\Models\Common\UserModel;
use App\Http\Services\Trace\ProjectService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ProcessController extends Controller
{
    private $userModel;
    private $projectModel;
    private $regionModel;
    private $uid;
    private $projectService;


    public function __construct(Request $request)
    {
        $this->userModel = new UserModel();
        $this->projectModel = new ProjectModel();
        $this->regionModel = new RegionModel();
        $this->projectService = new ProjectService();

    }

    /*
     * 流程详情
     */
    public function applyDetail(Request $request)
    {
        $pid = $request->input("pid");
        $type = $request->input("type");

        $condition = [
            "pid" => $pid,
            "type" => $type
        ];
        $detail = $this->projectModel->processApply_select($condition)->first();
        if(!empty($detail->img)){
            $detail->img = unserialize($detail->img);
        }

        CommonFunc::mapi_export([
            "detail" => $detail
        ]);
    }

    /*
     * 流程提交
     */
    public function applyDo(Request $request)
    {
        $data = [
            "pid" => $request->input("pid"),
            "type" => $request->input("type"),
            "title" => $request->input("title"),
            "department" => $request->input("department"),
            "material" => $request->input("material"),
            "remark" => $request->input("remark"),
            "time_begin" => $request->input("time_begin"),
            "time_end" => $request->input("time_end"),
            "uid" => $request->uid,
            "img" => serialize($request->input("img")),
        ];
        // CommonFunc::arrEmptyVerify($data, ["pid", "type", "title", "department", "time_begin"]);
        CommonFunc::arrEmptyVerify($data, ["pid", "type"]);
        //1.查找 ==> 插入 or 更新
        $condition = [
            "pid" => $request->input("pid"),
            "type" => $request->input("type")
        ];
        DB::beginTransaction();
        try{

            $detail = $this->projectModel->processApply_select($condition)->first();
            if (empty($detail->id)) {
                ProjectModel::insertGetId("tc_process_apply", $data);
                //生成形象进度
                $this->projectService->process_trace_insert($data);
            } else {
                ProjectModel::update("tc_process_apply", $condition, $data);
                //更新形象进度
                $this->projectService->process_trace_update($data);
            }

            //2.更改当前流程节点信息
            $condition_pre = ["id" => $data['pid']];
            ProjectModel::update("tc_process_pre", $condition_pre, [
                $data['type'] => 2
            ]);

            //3.更改子节点流程信息
            $detail = $this->projectModel->processPre_select($condition_pre)->first();
            $service = new ProjectService();
            $service->process_pre_add($data['type'], $detail);
            DB::commit();
            CommonFunc::mapi_export([
                "res" => 1
            ]);
        }catch (\Exception $e){
            DB::rollBack();
            CommonFunc::mapi_error("20000", "操作失败");
        }
    }

    /*
    * 流程提交
    */
    public function applyDo2(Request $request)
    {
        $data = [
            "pid" => $request->input("pid"),
            "type" => $request->input("type"),
            "title" => $request->input("title"),
            "department" => $request->input("department"),
            "material" => $request->input("material"),
            "remark" => $request->input("remark"),
            "time_begin" => $request->input("time_begin"),
            "time_end" => $request->input("time_end"),
            "uid" => $request->uid
        ];
        CommonFunc::emptyVerify($data, "参数不全");

        //1.查找 ==> 插入 or 更新
        $condition = [
            "pid" => $request->input("pid"),
            "type" => $request->input("type")
        ];
        $detail = $this->projectModel->processApply_select($condition)->first();
        DB::beginTransaction();
        try{
            if (empty($detail->id)) {
                ProjectModel::insertGetId("tc_process_apply", $data);
                //生成形象进度
                $this->projectService->process_trace_insert($data);
            } else {
                ProjectModel::update("tc_process_apply", $condition, $data);
                //更新形象进度
                $this->projectService->process_trace_update($data);
            }

            //2.更改当前流程节点信息
            $condition_pre = ["id" => $data['pid']];
            ProjectModel::update("tc_process_check", $condition_pre, [
                $data['type'] => 2
            ]);

            //3.更改子节点流程信息
            $detail = $this->projectModel->processCheck_select($condition_pre)->first();
            $service = new ProjectService();
            $service->process_check_add($data['type'], $detail);
            DB::commit();
            CommonFunc::mapi_export([
                "res" => 1
            ]);
        }catch (\Exception $e){
            DB::rollBack();
            CommonFunc::mapi_error("20000", "操作失败");
        }
    }
}
