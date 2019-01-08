<?php

namespace App\Http\Services\Trace;

use App\Func\CommonFunc;
use App\Http\Models\Common\Model;
use App\Http\Models\Common\ProjectModel;

/**
 * 项目工程service
 */
class ProjectService
{
    /*
     * 前期进度增加后，状态修改
     */
    public function process_pre_add($field, $info)
    {
        $json_string = file_get_contents(dirname(__FILE__) . '../../../Controllers/Trace/process_pre.json');
        $json = json_decode($json_string, true);
        $to = [];
        foreach ($json['lines'] as $k => $v) {
            if ($v['from'] == $field) {
                $to[] = $v['to'];
            }
        }
        if ($field == "map_record") {
            //说明是最后一个
            ProjectModel::update("tc_process_check", [
                "id" => $info->id
            ], [
                "environment" => 1,
                "plan" => 1,
                "fire" => 1,
            ]);
        }
        foreach ($to as $k1 => $v1) {
            $from = [];
            foreach ($json['lines'] as $k => $v) {
                if ($v['to'] == $v1) {
                    $from[] = $v['from'];
                }
            }
            foreach ($from as $k2 => $v2) {
                if ($info->$v2 != 2) {
                    break;
                }
                if ($k2 == (count($from) - 1) && $info->$v1 == 0) {
                    ProjectModel::update("tc_process_pre", [
                        "id" => $info->id
                    ], [
                        $v1 => 1
                    ]);
                }
            }
        }
    }

    /*
     * 验收进度增加后，状态修改
     */
    public function process_check_add($field, $info)
    {
        $json_string = file_get_contents(dirname(__FILE__) . '../../../Controllers/Trace/process_check.json');
        $json = json_decode($json_string, true);
        $to = [];
        foreach ($json['lines'] as $k => $v) {
            if ($v['from'] == $field) {
                $to[] = $v['to'];
            }
        }
        foreach ($to as $k1 => $v1) {
            $from = [];
            foreach ($json['lines'] as $k => $v) {
                if ($v['to'] == $v1) {
                    $from[] = $v['from'];
                }
            }
            foreach ($from as $k2 => $v2) {
                if ($info->$v2 != 2) {
                    break;
                }
                if ($k2 == (count($from) - 1) && $info->$v1 == 0) {
                    ProjectModel::update("tc_process_check", [
                        "id" => $info->id
                    ], [
                        $v1 => 1
                    ]);
                }
            }
        }
    }

    /*
     * 给condition根据用户权限增加rids条件
     */
    public function rids($request, $model, $condition)
    {
        //返回rids条件
        $rids = $model->userAuth_select(["uid" => $request->uid])->value("rid");
        if (!empty($rids)) {
            $condition['rids'] = json_decode($rids, true);
        }
        return $condition;
    }

    /*
     * 增加流程登记的时候，自动生成项目跟踪
     */
    public function process_trace_insert($data)
    {
        $json_string = file_get_contents(dirname(__FILE__) . '../../../Controllers/Trace/process.json');
        $json = json_decode($json_string, true);
        $check = "";
        foreach ($json as $k => $v) {
            if ($v['k'] == $data['type']) {
                $check = $v['v'];
                break;
            }
        }
        //$desc = "在" . $data['time_begin'] . "办理了" . $check . "审批流程。备注：".$data['remark'];
        $desc = "办理了" . $check . "审批流程。备注：".$data['remark'];
        if (!empty($data['time_end'])) {
            $desc .= ",于{$data['time_end']}办理完成。";
        } else {
            $desc .= "。";
        }
        ProjectModel::insertGetId("tc_process_trace", [
            "pid" => $data["pid"],
            "uid" => $data["uid"],
            "date" => date("Y-m-d", time()),
            "flow" => $data["type"],
            "img" => $data["img"],
            "desc" => $desc,
        ]);
    }

    /*
     * 更新流程登记的时候，自动更新项目跟踪
     */
    public function process_trace_update($data)
    {
        $json_string = file_get_contents(dirname(__FILE__) . '../../../Controllers/Trace/process.json');
        $json = json_decode($json_string, true);
        $check = "";
        foreach ($json as $k => $v) {
            if ($v['k'] == $data['type']) {
                $check = $v['v'];
                break;
            }
        }
        $desc = "在" . $data['time_begin'] . "办理了" . $check . "审批流程";
        if (!empty($data['time_end'])) {
            $desc .= ",于{$data['time_end']}办理完成。";
        } else {
            $desc .= "。";
        }
        $condition = [
            "pid" => $data['pid'],
            "flow" => $data["type"],
        ];
        ProjectModel::update("tc_process_trace", $condition, [
            "pid" => $data["pid"],
            "uid" => $data["uid"],
            "date" => date("Y-m-d", time()),
            "desc" => $desc,
            "status" => 0
        ]);
    }
}
