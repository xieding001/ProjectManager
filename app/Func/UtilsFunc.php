<?php
namespace App\Func;
/**
 * Class Common
 * @package App\Func
 */
class UtilsFunc{

    /*
     * 往$arr数组，键为$Key的字段前面追加$append字段
     */
    public static function strAppend($arr , $key , $append){
        $arr = UtilsFunc::object2array($arr);
        foreach ($arr as $k => &$v){
            if(is_array($v)){
                $v = UtilsFunc::strAppend($v , $key , $append);
            }else{
                if($k == $key){
                    if(strpos($v,$append)===false){
                        $v = $append.$v;
                    }
                }
            }
        }
        return $arr;
    }

    /*
     * 返回数组中，不为空的第一个键对应的值
     */
    public static function fieldsFind($arr , $keys){
        $arr = UtilsFunc::object2array($arr);
        $field = "";
        foreach ($arr as $k => $v){
            if(in_array($k , $keys)){
                if(!empty($v)){
                    $field = $v;
                    break;
                }
            }
        }
        return $field;
    }

    /*
     * 对象转数组
     */
    public static function object2array($obj) {
        if (is_object($obj)) {
            $arr = json_decode(json_encode($obj) , true);
        }
        else {
            $arr = $obj;
        }
        return $arr;
    }

    /*
     * 时间日期转换
     */
    public static function myFormat($arr , $keys) {
        $arr = UtilsFunc::object2array($arr);
        foreach ($arr as $k => &$v){
            if(in_array($k , $keys)){
                if(!empty($v)){
                    $time = strtotime($v);
                    $v = date("Y年m月d号" , $time);
                }
            }
        }
        return $arr;
    }

    /*
     * 生成.xls文件
     * $datas：二维数组...
     * $keys：二维数组里面的所有key的值...
     */
    public static function excelData($datas,$keys,$titlename="财务对账",$filename=".xls"){
        $normal = array(
            "id" =>"ID",
            "merchant_id" =>"商户Id",
            "shop_id" =>"商户Id",
            "date" =>"日期",
            "settlement_amount" =>"商户应得",
            "status" =>"结算状态",
            "shop_name" =>"商户名称",
            "goods_amount" =>"市场金额",
            "count" =>"交易笔数",
            "order_id" =>"订单ID",
            "order_no" =>"订单ID",
            "orderNum" =>"订单数量",
            "orderStatus" =>"订单状态",
            "user_id" =>"用户ID",
            "pay_time" =>"支付时间",
            "goods_type" =>"商品类型",
            "wx_amount" =>"微信金额",
            "aliay_amount" =>"支付宝金额",
            "wxwebPay" =>"微信公众号金额",
            "point_amount" =>"积分金额",
            "union_amount" =>"银联金额",
            "gift_amount" =>"积分礼券金额",
            "lan_amount" =>"兰州支付金额",
			"order_num" =>"总交易(条)",
			"total_amount" =>"总交易额(元)",
			"day" =>"日期",
			"trans_amount" =>"市场总额(元)",
			"updated" =>"时间",
            "amount" =>"交易金额",
            "brand_id" =>"品牌id",
        );
        $str = "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\"\r\nxmlns:x=\"urn:schemas-microsoft-com:office:excel\"\r\nxmlns=\"http://www.w3.org/TR/REC-html40\">\r\n<head>\r\n<meta http-equiv=Content-Type content=\"text/html; charset=utf-8\">\r\n</head>\r\n<body>";
        $str .="<table border=1>";
        $str .= "<tr>";
        foreach ($keys as $k =>$v){
            $str .= "<td>$normal[$v]</td>";
        }
        $str .= "</tr>";
        foreach ($datas  as $key=> $rt )
        {
            $str .= "<tr>";
            foreach ( $rt as $k => $v )
            {
                $str .= "<td>{$v}</td>";
            }
            $str .= "</tr>\n";
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
}



