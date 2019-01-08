<?php
namespace app\Func;

/**
 * Class Common
 * @package App\Func
 */
class ExcelFunc{
    /*
     * 生成.xls文件
     * $datas：二维数组...
     * $keys：二维数组里面的所有key的值...
     */
    public static function excelData($data , $titlename = "年度模板", $filename = ".xls"){
        $str = "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\"\r\nxmlns:x=\"urn:schemas-microsoft-com:office:excel\"\r\nxmlns=\"http://www.w3.org/TR/REC-html40\">\r\n<head>\r\n<meta http-equiv=Content-Type content=\"text/html; charset=utf-8\">\r\n</head>\r\n<body>";
        $str .="<table border=1>";
        foreach ($data  as $k => $v ){
            $str .= "<tr>";
            foreach($v as $k1 => $v1){
                $color = "black";
                $height = "";
                $width = "";
                if(!empty($v1['color'])){
                    $color = $v1['color'];
                }
                if(!empty($v1['height'])){
                    $height = $v1['height'];
                }
                if(!empty($v1['width'])){
                    $width = $v1['width'];
                }
                $str .= "<td align=\"center\" valign=\"middle\" style=\"color: {$color};height: {$height}px;width: {$width}px;table-layout:fixed;word-wrap:break-word;\">{$v1['name']}</td>";
            }
            $str .= "</tr>";
        }

        $str .= "</table></body></html>";
        header( "Content-Type: application/vnd.ms-excel; name='excel'" );
        header( "Content-type: application/octet-stream" );
        header( "Content-Disposition: attachment; filename=".$titlename.$filename );
        header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
        header( "Pragma: no-cache" );
        header( "Expires: 0" );
        exit( $str );
    }

    public static function phpExcelOutput($expTitle,$expCellName,$expTableData , $style = []){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称 将字符串从utf-8编码转为gb2312编码
        $cellNum = count($expCellName);//获取文件的列数
        $dataNum = count($expTableData);//获取数据的条数
        $objPHPExcel = new \PHPExcel();//生成PHPExcel类实例
        //A-AZ列
        $cellName = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
            'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','AO','AP','AQ'];
        // 设置excel文档的属性
        $objPHPExcel->getProperties()->setCreator("php")//设置文档属性作者
        ->setLastModifiedBy("php")//设置最后修改人
        ->setTitle("Microsoft Office Excel Document")//设置文档属性标题
        ->setSubject("php")//设置文档属性文档主题
        ->setDescription("php")//设置文档属性备注
        ->setKeywords("php")//设置文档属性关键字
        ->setCategory("php");//设置文档属性类别

        //设置表的名称
        $objPHPExcel->getActiveSheet()->setTitle($expTitle);

        if(!empty($style)){
            if(!empty($style['width'])){
                foreach($style['width'] as $k => $v){
                    $objPHPExcel->getActiveSheet()->getColumnDimension($k)->setWidth($v);
                }
            }
            if(!empty($style['height'])){
                foreach($style['height'] as $k => $v){
                    $objPHPExcel->getActiveSheet()->getRowDimension($k)->setRowHeight($v);
                }
            }
            //固定表头
            if(!empty($style['freezePane'])){
                $objPHPExcel->getActiveSheet()->freezePane($style['freezePane']);
            }
        }

        //自动换行、左右垂直居中
        $objPHPExcel->getDefaultStyle()->getAlignment()->setWrapText(true);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

        for($i=0;$i<$cellNum;$i++){
            //遍历设置单元格的值 设置列名
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'1', $expCellName[$i]['name']);
            if(!empty($expCellName[$i]['color'])){
                $objPHPExcel->getActiveSheet()->getStyle($cellName[$i].'1')->getFont()->getColor()->setARGB($expCellName[$i]['color']);
            }
        }
        //让总循环次数小于数据条数
        for($i=0;$i<$dataNum;$i++){
            //让每列的数据数小于列数
            for($j=0;$j<$cellNum;$j++){
                //设置单元格的值
                $objPHPExcel->getActiveSheet()->setCellValue($cellName[$j].($i+2), $expTableData[$i][$expCellName[$j]["key"]]);
            }
        }

        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$expTitle.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
}



