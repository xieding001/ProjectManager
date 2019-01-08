<?php

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


class TraceController extends Controller
{
    private $userModel;
    private $projectModel;
    private $regionModel;
    private $projectService;

    public function __construct(Request $request)
    {
        $this->userModel = new UserModel();
        $this->projectModel = new ProjectModel();
        $this->regionModel = new RegionModel();
        $this->projectService = new ProjectService();
    }

    /*
     * 填报列表
     */
    public function traceList(Request $request)
    {
        $condition = [
            "rid" => $request->input("rid"),
            "pageNo" => $request->input("pageNo"),
            "pageSize" => $request->input("pageSize"),
            "status" => 0
        ];
        $condition = $this->projectService->rids($request, $this->userModel, $condition);

        $by['orderBy'] = ["tc_project.pid" => "desc","create_time"=>"asc"];
        $fields = ["tc_process_trace.*", "tc_project.company", "tc_project.project_type","tc_project.name", "tc_project.area_type",
            "tc_project.rid", "tc_project.invest_all","tc_project.invest_pre"];
        $list = $this->projectModel->processTrace_project_select($condition, $fields, $by)->get();
        foreach ($list as $k => $v) {
            $v->region = $this->regionModel->region_select(["id" => $v->rid])->value("region_name");
            //容错
            $v->invest_all = $v->invest_all > 0 ? $v->invest_all : 1;
            //续建项目
            $v->invest_all_used = $v->project_type == 2? $v->invest_all_used + $v->invest_pre:$v->invest_all_used;
            $v->invest_all_process = round($v->invest_all_used / $v->invest_all * 100, 2) . "%";
        }
        $count = $this->projectModel->processTrace_project_select(RequestFunc::removePage($condition))->count();
        CommonFunc::mapi_export([
            "count" => $count,
            "list" => $list
        ]);
    }

    /*
     * 添加填报
     */
    public function traceAdd(Request $request)
    {
        $data = [
            "pid" => $request->input("pid"),
            "uid" => $request->uid,
            "type" => $request->input("type"),
            "date" => date("Y-m-d", time()),
            "desc" => $request->input("desc"),
            "invest_device_used" => $request->input("invest_device_used"),
            "invest_all_used" => $request->input("invest_all_used"),
            "img" => serialize($request->input("img")),
        ];
        CommonFunc::arrEmptyVerify($data, ["type", "desc", "invest_device_used", "invest_all_used"]);

        $d = date("d", time());
        if ($request->uid != 1 && ($d == "26" || $d == "27" || $d == "28" || $d == "29" || $d == "30" || $d == "31")) {
        // if ($d == "30" || $d == "31") {
            CommonFunc::mapi_error("20000", "每个月25号之后不允许填报。");
        }

        ProjectModel::insertGetId("tc_process_trace", $data);
        CommonFunc::mapi_export([
            "res" => 1
        ]);
    }
    //获取跟踪进度
    public function traceGet(Request $request)
    {
        $traceId = $request->input("trace_id");
        $condition = ['id' => $traceId];
        $fields = ["tc_process_trace.*"];
        $traceInfo = $this->projectModel->processTrace_project_select($condition, $fields)->first();
        $traceInfo->img = unserialize($traceInfo->img);
        if(empty($traceInfo->img)){
                $traceInfo->img = [];
        }
        CommonFunc::mapi_export([
            "res" => $traceInfo
        ]);

    }
    //编辑形象进度
    public function traceEdit(Request $request)
    {
        $data = [
            "id" => $request->input("trace_id"),
            "pid" => $request->input("pid"),
            "uid" => $request->uid,
            "type" => $request->input("type"),
            "date" => date("Y-m-d", time()),
            "desc" => $request->input("desc"),
            "invest_device_used" => $request->input("invest_device_used"),
            "invest_all_used" => $request->input("invest_all_used"),
            "img" => serialize($request->input("img")),
        ];
        //var_dump($data);
        CommonFunc::arrEmptyVerify($data, ["id","type", "desc", "invest_device_used", "invest_all_used"]);

        $d = date("d", time());
        if ($d == "26" || $d == "27" || $d == "28" || $d == "29" || $d == "30" || $d == "31") {
        //if ($d == "30" || $d == "31") {
            CommonFunc::mapi_error("20000", "每个月25号之后不允许填报。");
        }
        $condition = ['id'=>$request->input('trace_id')];
        $data['status'] = 0;
        ProjectModel::update("tc_process_trace", $condition, $data);
        CommonFunc::mapi_export([
            "res" => 1
        ]);
    }

    /*
     * 填报审核
     */
    public function traceCheck(Request $request)
    {
        $id = $request->input("id");
        $status = $request->input("status");

        //一、更改审核状态
        $condition = [
            "id" => $id
        ];
        ProjectModel::update("tc_process_trace", $condition, [
            "status" => $status,
            "cid" => $request->uid
        ]);
        //二、将project表的已使用投资和已使用设备投资数据更改为最新的
        $detail = $this->projectModel->processTrace_select($condition)->first();
        // $condition_new = [
        //     "pid" => $detail->pid,
        //     "status" => 1
        // ];
        // $by['orderBy'] = ["id" => "desc"];
        // $new = $this->projectModel->processTrace_select($condition_new, [], $by)->first();
        // ProjectModel::update("tc_process_trace", ["pid" => $detail->pid], [
        //     "new_flag" => 0
        // ]);

        if ($status == 1) {
            ProjectModel::update("tc_process_trace", ["pid" => $detail->pid], [
                    "new_flag" => 0
                ]);
            if($detail->flow == '0'){
                ProjectModel::update("tc_project", [
                    "pid" => $detail->pid
                ], [
                    "invest_all_used" => $detail->invest_all_used,
                    "invest_device_used" => $detail->invest_device_used,
                    "process" => $detail->type,
                ]);  
                //三、tc_process_trace所有的new_flag置为0，再将最新的置为1 
                ProjectModel::update("tc_process_trace", ["id" => $id], [
                    "new_flag" => 1
                ]);
            }
        } 
            

        CommonFunc::mapi_export([
            "res" => 1
        ]);
    }

    /*
     * 审核详情
     */
    public function traceCheckDetail(Request $request)
    {
        $condition = [
            "id" => $request->input("id")
        ];
        $trace = $this->projectModel->processTrace_select($condition)->first();
        $trace->img = unserialize($trace->img);

        //1.详情
        $detail = $this->projectModel->project_select(["pid" => $trace->pid])->first();
        $detail->region = $this->regionModel->region_select(["id" => $detail->rid])->value("region_name");

        CommonFunc::mapi_export([
            "detail" => $detail,
            "trace" => $trace,
        ]);
    }

    /*
     * 报告列表
     */
    public function reportList(Request $request)
    {
        $search = $request->input("search");
        $search = json_decode($search, true);
        if ($search['region']) {
            $search['region'] = explode(",", str_replace("01", "1", $search['region']));
        } else {
            unset($search['region']);
        }
        if ($search['project_types']) {
            $search['project_types'] = explode(",", str_replace("01", "1", $search['project_types']));
        } else {
            unset($search['project_types']);
        }

        if ($search['product_important']) {
            $search['product_important'] = explode(",", str_replace("01", "1", $search['product_important']));
        } else {
            unset($search['product_important']);
        }

        if ($search['month']) {
            $curMonth = $search['year'].'-'.$search['month'].'-01';
            $nextMonth = date('Y-m-d',strtotime("$curMonth +1 month"));
            $search['month'] = $nextMonth;
        }else{
            $day = date('d',time());
            $curMonth = date('m',time());
            if($day < 15){
                $curMonth = date('m',strtotime("-1 month"));
            }
            $curMonth = $search['year'].'-'.$curMonth.'-01';
            $nextMonth = date('Y-m-d',strtotime("$curMonth +1 month"));
            $search['month'] = $nextMonth;
        }

        if (!empty($search['product_type'])) {
            $search['product_type'] = explode(",", str_replace("01", "1", $search['product_type']));
        } elseif($search['product_type'] == '0'){
            $search['product_type'] = [0];
        }
        else {
            unset($search['product_type']);
        }
        $search['pageNo'] = $request->input("pageNo");
        $search['pageSize'] = $request->input("pageSize");
        $search = $this->projectService->rids($request, $this->userModel, $search);
        $fields = ["tc_project.*",DB::raw("ifnull(trace.invest_all_used,0) as invest_all_used_month"),DB::raw("ifnull(trace.invest_device_used,0) as invest_device_used_month"),DB::raw("ifnull(trace.type,0) as process")];
        $by['orderBy'] = ["tc_region.sort" => "asc",
 "tc_project.project_type" => "desc","tc_project.pid" => "asc"];
        //DB::connection()->enableQueryLog();
        $list = $this->projectModel->project_processTrace_select($search, $fields, $by)->get();
        //var_dump(DB::getQueryLog());die;
        $count = $this->projectModel->project_processTrace_select(RequestFunc::removePage($search))->count();
        foreach ($list as $k => $v) {
            $v->region = $this->regionModel->region_select(["id" => $v->rid])->value("region_name");
            $v->invest_all = $v->invest_all > 0 ? $v->invest_all : 1;
            $v->invest_all_process = round($v->invest_all_used / $v->invest_all * 100, 2) . "%";
        }

        CommonFunc::mapi_export([
            "list" => $list,
            "count" => $count
        ]);
    }

    /*
     * 报告详情
     */
    public function reportDetail(Request $request)
    {
        $condition = [
            "pid" => $request->input("id")
        ];
        $fields = ["tc_project.*", "tc_process_trace.date", "tc_process_trace.status", "tc_process_trace.desc", "tc_process_trace.img", "tc_process_trace.cid"];
        $detail = $this->projectModel->project_processTrace_select($condition, $fields)->first();

        $detail->region = $this->regionModel->region_select(["id" => $detail->rid])->value("region_name");
        $detail->invest_all = $detail->invest_all > 0 ? $detail->invest_all : 1;
        $detail->invest_all_process = round($detail->invest_all_used / $detail->invest_all * 100, 2) . "%";

        CommonFunc::mapi_export([
            "detail" => $detail,
        ]);
    }

    /*
     * 报表导出
     */
    public function reportExport(Request $request)
    {
        //一、根据筛选条件查询数据
        $search = $request->input("search");
        $search = json_decode($search, true);
        if ($search['region']) {
            $search['region'] = explode(",", str_replace("01", "1", $search['region']));
        } else {
            unset($search['region']);
        }
        if ($search['project_types']) {
            $search['project_types'] = explode(",", str_replace("01", "1", $search['project_types']));
        } else {
            unset($search['project_types']);
        }

        if ($search['product_important']) {
            $search['product_important'] = explode(",", str_replace("01", "1", $search['product_important']));
        } else {
            unset($search['product_important']);
        }

        if ($search['month']) {
            $curMonth = $search['year'].'-'.$search['month'].'-01';
            $nextMonth = date('Y-m-d',strtotime("$curMonth +1 month"));
            $search['month'] = $nextMonth;
        }else{
            $day = date('d',time());
            $curMonth = date('m',time());
            if($day < 15){
                $curMonth = date('m',strtotime("-1 month"));
            }
            $curMonth = $search['year'].'-'.$curMonth.'-01';
            $nextMonth = date('Y-m-d',strtotime("$curMonth +1 month"));
            $search['month'] = $nextMonth;
        }

        if (!empty($search['product_type'])) {
            $search['product_type'] = explode(",", str_replace("01", "1", $search['product_type']));
        } elseif($search['product_type'] == '0'){
            $search['product_type'] = [0];
        }
        else {
            unset($search['product_type']);
        }
        $search = $this->projectService->rids($request, $this->userModel, $search);
        $fields = ["tc_project.*",DB::raw("ifnull(trace.invest_all_used,0) as invest_all_used_month"),DB::raw("ifnull(trace.invest_device_used,0) as invest_device_used_month"),"trace.desc2 as desc2",DB::raw("ifnull(trace.type,0) as process")];
        $by['orderBy'] = ["tc_region.sort" => "asc","tc_project.project_type" => "desc","tc_project.project_sort" => "asc"];
        $list = $this->projectModel->project_processTrace_select($search, $fields, $by)->get();
        //查询最新的进度
        // foreach($list as $k => &$v)
        // {
        //     $queryCondition=['pid' => $v->pid];
        //     if(!empty($search['month'])){
        //         $queryCondition['month'] = $search['month'];
        //     }
        //     $orderBy['orderBy'] = ['tc_process_trace.id' => "desc"];
        //     $trace = $this->projectModel->processTrace_select($queryCondition,[],$orderBy)->first();
        //     $v->desc2 = $trace?$trace->desc:'';
        //     $v->process = $trace?$trace->type:'未开工';
        // }

        //二、生成表格
        $Reader = new \PHPExcel_Reader_Excel2007();
        $PHPExcel = $Reader->load($_SERVER['DOCUMENT_ROOT'] . "/trace/source/report_out.xlsx");

        $list = json_decode($list, true);

        $fields = [DB::raw('count(1) as project_num'),DB::raw('sum(tc_project.invest_all) as invest_all'),DB::raw("sum(tc_project.invest_device) as invest_device"),DB::raw("sum(tc_project.invest_pre) as invest_pre"),DB::raw("sum(tc_project.invest_now) as invest_now"),DB::raw("sum(tc_project.invest_device_now) as invest_device_now"),DB::raw("ifnull(sum(trace.invest_device_used),0) as invest_device_used"),DB::raw("ifnull(sum(trace.invest_all_used),0) as invest_all_used")];
        $groupBy['groupBy'] = ["tc_project.rid"];
        $allSum = $this->projectModel->project_processTrace_select($search, $fields)->first();
        array_push($fields, 'tc_project.rid as rid');
        $sumList = $this->projectModel->project_processTrace_select($search, $fields, $groupBy)->get();
        $sumList = json_decode($sumList,true);
        foreach($sumList as $k => $v){
            $sumList[$v['rid']] = $v;
        }
        //var_dump($sumList);die;
        
        $rowNum = 3;
        //生成全市统计
        if(empty($search['region'])){
            $rowNum ++;
            $name = '全市'.$allSum->project_num.'个项目';
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("D" . $rowNum, $name);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("G" . $rowNum, $allSum->invest_all);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("H" . $rowNum, $allSum->invest_device);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("I" . $rowNum, $allSum->invest_pre);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("J" . $rowNum, $allSum->invest_now);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("K" . $rowNum, $allSum->invest_device_now);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("L" . $rowNum, $allSum->invest_all_used);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("M" . $rowNum, $allSum->invest_device_used);
            $allSum->invest_now = $allSum->invest_now > 0 ? $allSum->invest_now : 1;
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("N" . $rowNum, round($allSum->invest_all_used/$allSum->invest_now * 100,2));
            $PHPExcel->setActiveSheetIndex(0)->getStyle('A'.$rowNum.':P'.$rowNum)->getFont()->setBold(true);
            $PHPExcel->getActiveSheet()->getRowDimension($rowNum)->setRowHeight(18);
            $PHPExcel->getActiveSheet()->freezePane('A'.($rowNum+1));
        }
        $region = 0;
        $region_name='';
        foreach ($list as $k => &$v) {
            $rid = $v['rid'];
            $v['region'] = $this->regionModel->region_select(["id" => $v['rid']])->value("region_name");
            if($region != $rid){
                //增加上一个镇区的统计
                if(!empty($region)){
                    $rowNum ++;
                    $regionSum = $sumList[$region];
                    $regionSum['name'] = $region_name.$regionSum['project_num'].'个项目';
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("D" . $rowNum, $regionSum['name']);
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("G" . $rowNum, $regionSum['invest_all']);
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("H" . $rowNum, $regionSum['invest_device']);
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("I" . $rowNum, $regionSum['invest_pre']);
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("J" . $rowNum, $regionSum['invest_now']);
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("K" . $rowNum, $regionSum['invest_device_now']);
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("L" . $rowNum, $regionSum['invest_all_used']);
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("M" . $rowNum, $regionSum['invest_device_used']);
                    $regionSum['invest_now'] = $regionSum['invest_now'] > 0 ? $regionSum['invest_now'] : 1;
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("N" . $rowNum, round($regionSum['invest_all_used']/$regionSum['invest_now'] * 100,2));
                    $PHPExcel->setActiveSheetIndex(0)->getStyle('A'.$rowNum.':P'.$rowNum)->getFont()->setBold(true);
                    $PHPExcel->getActiveSheet()->getRowDimension($rowNum)->setRowHeight(18);
                }
                $region = $v['rid'];
                $region_name = $v['region'];
            }
            $v['invest_all'] = $v['invest_all'] > 0 ? $v['invest_all'] : 1;
            $v['invest_all_process'] = round($v['invest_all_used'] / $v['invest_all'] * 100, 2) . "%";
            $v = CommonFunc::arrReplace($v, [
                "project_type" => ["1" => "新建", "2" => "续建", "3" => "新增"],
            ]);
            $v = CommonFunc::arrReplace($v, [
                "process" => ["0" => "未开工", "1" => "已开工", "2" => "在建","3" => "部分投产","4" => "竣工投产"],
            ]);
            //建设年限
            $v['date_begin'] = empty($v['date_begin'])?date('Y'):$v['date_begin'];
            $v['date_invest'] = empty($v['date_invest'])?date('Y'):$v['date_invest'];
            if(substr($v['date_invest'],0,4)  == substr($v['date_begin'],0,4)){
                $buildYear = substr($v['date_invest'],0,4);
            }else{
                $buildYear = substr($v['date_begin'],0,4).'-'.substr($v['date_invest'],0,4);
            }
            $rowNum ++;
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("A" . $rowNum, $k + 1);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("B" . $rowNum, $v['company']);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("C" . $rowNum, $v['company_user'].$v['company_contact']);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("D" . $rowNum, $v['name'] . "," . trim($v['desc']).PHP_EOL);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("E" . $rowNum, $buildYear);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("F" . $rowNum, $v['project_type']);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("G" . $rowNum, $v['invest_all']);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("H" . $rowNum, $v['invest_device']);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("I" . $rowNum, $v['invest_pre']);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("J" . $rowNum, $v['invest_now']);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("K" . $rowNum, $v['invest_device_now']);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("L" . $rowNum, $v['invest_all_used_month']);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("M" . $rowNum, $v['invest_device_used_month']);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("N" . $rowNum, $v['desc2'].PHP_EOL);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("O" . $rowNum, $v['process']);
            $PHPExcel->setActiveSheetIndex(0)->setCellValue("P" . $rowNum, $v['region']);
            $PHPExcel->setActiveSheetIndex(0)->getStyle('A'.$rowNum.':P'.$rowNum)->getAlignment()->setWrapText(true);

        }
        if(!empty($region)){
                    $rowNum ++;
                    $regionSum = $sumList[$region];
                    $regionSum['name'] = $region_name.$regionSum['project_num'].'个项目';
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("D" . $rowNum, $regionSum['name']);
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("G" . $rowNum, $regionSum['invest_all']);
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("H" . $rowNum, $regionSum['invest_device']);
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("I" . $rowNum, $regionSum['invest_pre']);
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("J" . $rowNum, $regionSum['invest_now']);
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("K" . $rowNum, $regionSum['invest_device_now']);
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("L" . $rowNum, $regionSum['invest_all_used']);
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("M" . $rowNum, $regionSum['invest_device_used']);
                    $regionSum['invest_now'] = $regionSum['invest_now'] > 0 ? $regionSum['invest_now'] : 1;
                    $PHPExcel->setActiveSheetIndex(0)->setCellValue("N" . $rowNum, round($regionSum['invest_all_used']/$regionSum['invest_now'] * 100,2));
                    $PHPExcel->setActiveSheetIndex(0)->getStyle('A'.$rowNum.':P'.$rowNum)->getFont()->setBold(true);
                    $PHPExcel->getActiveSheet()->getRowDimension($rowNum)->setRowHeight(18);
        }
        $month=(int)date('m');
        
        $PHPExcel->setActiveSheetIndex()->setCellValue("L3", "1-".$month.'月份完成投资');
        //三、根据导出设置，删除指定栏目
        $fields = $request->input("fields");
        $columns2 = [];
        if (strpos($fields, "id") === false) {
            $columns2 = $this->column2($columns2, "A");
        }
        if (strpos($fields, "company") === false) {
            $columns2 = $this->column2($columns2, "B");
        }
        if (strpos($fields, "contact") === false) {
            $columns2 = $this->column2($columns2, "C");
        }
        if (strpos($fields, "name") === false) {
            $columns2 = $this->column2($columns2, "D");
        }
        if (strpos($fields, "years_all") === false) {
            $columns2 = $this->column2($columns2, "E");
        }
        if (strpos($fields, "project_type") === false) {
            $columns2 = $this->column2($columns2, "F");
        }
        if (strpos($fields, "invest_all") === false) {
            $columns2 = $this->column2($columns2, "G");
        }
        if (strpos($fields, "invest_device") === false) {
            $columns2 = $this->column2($columns2, "H");
        }
        if (strpos($fields, "year_2017") === false) {
            $columns2 = $this->column2($columns2, "I");
        }
        if (strpos($fields, "year_2018") === false) {
            $columns2 = $this->column2($columns2, "J");
        }
        if (strpos($fields, "device_2018") === false) {
            $columns2 = $this->column2($columns2, "K");
        }
        if (strpos($fields, "all_used") === false) {
            $columns2 = $this->column2($columns2, "L");
        }
        if (strpos($fields, "device_used") === false) {
            $columns2 = $this->column2($columns2, "M");
        }
        if (strpos($fields, "trace_desc") === false) {
            $columns2 = $this->column2($columns2, "N");
        }
        if (strpos($fields, "trace_type") === false) {
            $columns2 = $this->column2($columns2, "O");
        }
        if (strpos($fields, "region") === false) {
            $columns2 = $this->column2($columns2, "P");
        }

        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),

            ),
        );
        $PHPExcel->getActiveSheet()->setTitle('按镇区分');
        $PHPExcel->getActiveSheet()->getStyle( 'A4:P'.$rowNum)->applyFromArray($styleThinBlackBorderOutline);
        $PHPExcel->getActiveSheet()->setCellValue("A1", "溧阳市{$search['year']}年度重点工业项目汇总表");
        $PHPExcel->getActiveSheet()->setCellValue("N2", "填表时间：" . date("Y年m月d日", time()));
        foreach ($columns2 as $k => $v) {
            $PHPExcel->getActiveSheet()->removeColumn($v);
        }
        //四大产业分
        $PHPExcel->setActiveSheetIndex(1);
        $search['product_important']=[1,2,3,4];
        //按车、电、动、农排序
        $fields = ["tc_project.*",DB::raw("ifnull(trace.invest_all_used,0) as invest_all_used_month"),DB::raw("ifnull(trace.invest_device_used,0) as invest_device_used_month"),"trace.desc2 as desc2",DB::raw("ifnull(trace.type,0) as process")];
        $list = $this->projectModel->project_processTrace_select($search, $fields, [])->orderByRaw("field(tc_project.product_important,3,2,1,4) asc,tc_region.sort asc,tc_project.project_type desc,tc_project.project_sort asc")->get();
        //查询最新的进度
        // foreach($list as $k => &$v)
        // {
        //     $queryCondition=['pid' => $v->pid];
        //     if(!empty($search['month'])){
        //         $queryCondition['month'] = $search['month'];
        //     }
        //     $orderBy['orderBy'] = ['tc_process_trace.id' => "desc"];
        //     $trace = $this->projectModel->processTrace_select($queryCondition,[],$orderBy)->first();
        //     $v->desc2 = $trace?$trace->desc:'';
        //     $v->process = $trace?$trace->type:'未开工';
        // }
        $list = json_decode($list, true);

        $fields = [DB::raw('count(1) as project_num'),DB::raw('sum(tc_project.invest_all) as invest_all'),DB::raw("sum(tc_project.invest_device) as invest_device"),DB::raw("sum(tc_project.invest_pre) as invest_pre"),DB::raw("sum(tc_project.invest_now) as invest_now"),DB::raw("sum(tc_project.invest_device_now) as invest_device_now"),DB::raw("ifnull(sum(trace.invest_device_used),0) as invest_device_used"),DB::raw("ifnull(sum(trace.invest_all_used),0) as invest_all_used")];
        $groupBy['groupBy'] = ["tc_project.product_important"];
        $allSum = $this->projectModel->project_processTrace_select($search, $fields)->first();
        array_push($fields, 'tc_project.product_important as product_important');
        $sumList = $this->projectModel->project_processTrace_select($search, $fields, $groupBy)->get();
        $sumList = json_decode($sumList,true);
        foreach($sumList as $k => $v){
            $sumList[$v['product_important']] = $v;
        }
        //填充表格
        $rowNum = 3;
        //生成全市统计
        if(empty($search['region'])){
            $rowNum ++;
            $name = '全市'.$allSum->project_num.'个项目';
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("D" . $rowNum, $name);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("G" . $rowNum, $allSum->invest_all);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("H" . $rowNum, $allSum->invest_device);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("I" . $rowNum, $allSum->invest_pre);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("J" . $rowNum, $allSum->invest_now);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("K" . $rowNum, $allSum->invest_device_now);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("L" . $rowNum, $allSum->invest_all_used);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("M" . $rowNum, $allSum->invest_device_used);
            $allSum->invest_now = $allSum->invest_now > 0 ? $allSum->invest_now : 1;
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("N" . $rowNum, round($allSum->invest_all_used/$allSum->invest_now * 100,2));
            $PHPExcel->setActiveSheetIndex(1)->getStyle('A'.$rowNum.':P'.$rowNum)->getFont()->setBold(true);
            $PHPExcel->getActiveSheet()->getRowDimension($rowNum)->setRowHeight(18);
            $PHPExcel->getActiveSheet()->freezePane('A'.($rowNum+1));
        }
        $product_important = 0;
        $product_important_name = '';
        foreach ($list as $k => &$v) {
            $important = $v['product_important'];
            $product_name = CommonFunc::arrReplace(['product_important'=>$important], [
                "product_important" => ["1" => "动", "2" => "电","3" => "车","4" => "农"],
            ]);
            $v = CommonFunc::arrReplace($v, [
                "product_important" => ["1" => "动力电池", "2" => "智能电网","3" => "汽车及零部件","4" => "农牧与饲料机械"],
            ]);

            $v['region'] = $this->regionModel->region_select(["id" => $v['rid']])->value("region_name");
            if($important != $product_important){
                //增加上一个镇区的统计
                if(!empty($product_important)){
                    $rowNum ++;
                    $importantSum = $sumList[$product_important];
                    $importantSum['name'] = $product_important_name.$importantSum['project_num'].'个项目';
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("D" . $rowNum, $importantSum['name']);
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("G" . $rowNum, $importantSum['invest_all']);
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("H" . $rowNum, $importantSum['invest_device']);
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("I" . $rowNum, $importantSum['invest_pre']);
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("J" . $rowNum, $importantSum['invest_now']);
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("K" . $rowNum, $importantSum['invest_device_now']);
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("L" . $rowNum, $importantSum['invest_all_used']);
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("M" . $rowNum, $importantSum['invest_device_used']);
                    $regionSum['invest_now'] = $importantSum['invest_now'] > 0 ? $importantSum['invest_now'] : 1;
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("N" . $rowNum, round($importantSum['invest_all_used']/$importantSum['invest_now'] * 100,2));
                    $PHPExcel->setActiveSheetIndex(1)->getStyle('A'.$rowNum.':P'.$rowNum)->getFont()->setBold(true);
                    $PHPExcel->getActiveSheet()->getRowDimension($rowNum)->setRowHeight(18);
                }
                $product_important = $important;
                $product_important_name = $v['product_important'];
            }
            $v['invest_all'] = $v['invest_all'] > 0 ? $v['invest_all'] : 1;
            $v['invest_all_process'] = round($v['invest_all_used'] / $v['invest_all'] * 100, 2) . "%";
            $v = CommonFunc::arrReplace($v, [
                "project_type" => ["1" => "新建", "2" => "续建", "3" => "新增"],
            ]);
            $v = CommonFunc::arrReplace($v, [
                "process" => ["0" => "未开工", "1" => "已开工", "2" => "在建","3" => "部分投产","4" => "竣工投产"],
            ]);
            //建设年限
            $v['date_begin'] = empty($v['date_begin'])?date('Y'):$v['date_begin'];
            $v['date_invest'] = empty($v['date_invest'])?date('Y'):$v['date_invest'];
            if(substr($v['date_invest'],0,4)  == substr($v['date_begin'],0,4)){
                $buildYear = substr($v['date_invest'],0,4);
            }else{
                $buildYear = substr($v['date_begin'],0,4).'-'.substr($v['date_invest'],0,4);
            }
            $rowNum ++;
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("A" . $rowNum, $k + 1);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("B" . $rowNum, $v['company']);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("C" . $rowNum, $v['company_user'].$v['company_contact']);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("D" . $rowNum, $v['name'] . "," . trim($v['desc']).PHP_EOL);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("E" . $rowNum, $buildYear);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("F" . $rowNum, $v['project_type']);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("G" . $rowNum, $v['invest_all']);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("H" . $rowNum, $v['invest_device']);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("I" . $rowNum, $v['invest_pre']);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("J" . $rowNum, $v['invest_now']);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("K" . $rowNum, $v['invest_device_now']);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("L" . $rowNum, $v['invest_all_used_month']);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("M" . $rowNum, $v['invest_device_used_month']);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("N" . $rowNum, $v['desc2'].PHP_EOL);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("O" . $rowNum, $v['process']);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("P" . $rowNum, $v['region']);
            $PHPExcel->setActiveSheetIndex(1)->setCellValue("Q" . $rowNum, $product_name['product_important']);
            $PHPExcel->setActiveSheetIndex(1)->getStyle('Q'.$rowNum)->getFont()->setBold(true);
            $PHPExcel->setActiveSheetIndex(1)->getStyle('A'.$rowNum.':P'.$rowNum)->getAlignment()->setWrapText(true);

        }
        if(!empty($region)){
                    $rowNum ++;
                    $importantSum = $sumList[$product_important];
                    $regionSum['name'] = $region_name.$regionSum['project_num'].'个项目';
                    $importantSum['name'] = $product_important_name.$importantSum['project_num'].'个项目';
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("D" . $rowNum, $importantSum['name']);
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("G" . $rowNum, $importantSum['invest_all']);
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("H" . $rowNum, $importantSum['invest_device']);
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("I" . $rowNum, $importantSum['invest_pre']);
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("J" . $rowNum, $importantSum['invest_now']);
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("K" . $rowNum, $importantSum['invest_device_now']);
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("L" . $rowNum, $importantSum['invest_all_used']);
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("M" . $rowNum, $importantSum['invest_device_used']);
                    $regionSum['invest_now'] = $importantSum['invest_now'] > 0 ? $importantSum['invest_now'] : 1;
                    $PHPExcel->setActiveSheetIndex(1)->setCellValue("N" . $rowNum, round($importantSum['invest_all_used']/$importantSum['invest_now'] * 100,2));
                    $PHPExcel->setActiveSheetIndex(1)->getStyle('A'.$rowNum.':P'.$rowNum)->getFont()->setBold(true);
                    $PHPExcel->getActiveSheet()->getRowDimension($rowNum)->setRowHeight(18);
        }
        //$month=(int)substr($curMonth,6,2);
        

        $PHPExcel->getActiveSheet()->getStyle( 'A4:Q'.$rowNum)->applyFromArray($styleThinBlackBorderOutline);
        $PHPExcel->getActiveSheet()->setTitle('四大产业');
        $PHPExcel->getActiveSheet()->setCellValue("A1", "溧阳市{$search['year']}年度重点工业项目汇总表");
        $PHPExcel->getActiveSheet()->setCellValue("N2", "填表时间：" . date("Y年m月d日", time()));
        $PHPExcel->setActiveSheetIndex(1)->setCellValue("L3", "1-".$month.'月份完成投资');
        foreach ($columns2 as $k => $v) {
            $PHPExcel->getActiveSheet()->removeColumn($v);
        }
        header('pragma:public');
        $xlsTitle = 100;
        $expTitle = "溧阳市{$search['year']}年度重点工业项目汇总表-" . date("Y年m月d日", time());
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename={$expTitle}1.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($PHPExcel, 'Excel2007');
        ob_end_clean();

        $objWriter->save('php://output');

    }

    function column2($columns2, $c)
    {
        $length = count($columns2);
        $columns = ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M"];
        foreach ($columns as $k => $v) {
            if ($v == $c) {
                $columns2[] = $columns[$k - $length];
            }
        }
        return $columns2;
    }

}
