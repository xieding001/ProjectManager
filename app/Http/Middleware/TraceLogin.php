<?php

namespace App\Http\Middleware;

use App\Func\CommonFunc;
use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Class Login
 * @package App\Http\Middleware
 *古越龙山后台登陆中间件
 */
class TraceLogin
{

    protected $auth;

    /*
     * 单点登录
     */
    public function handle($request, Closure $next)
    {

        session_start();
        if (!empty($_SESSION['admin_zz'])) {
            $userInfo = $_SESSION['admin_zz'];
            if (!empty($userInfo['user_auth'])) {
                $request->uid = $userInfo['user_auth']['uid'];
                $request->role = $userInfo['user_auth']['role'];
                setcookie("login_union", 1, time() + 3600 * 2, "/");
                return $next($request);
            } else {
                setcookie("login_union", 0, time() - 3600 * 24, "/");
                CommonFunc::mapi_error("20001", "用户未登陆");
            }
        } else {
            setcookie("login_union", 0, time() - 3600 * 24, "/");
            CommonFunc::mapi_error("20001", "用户未登陆");
        }
    }

    /*
     * 原本的过滤器
     */
    public function handle1($request, Closure $next)
    {
        //[1].是否存在cookie
        if (empty($_COOKIE['login_union'])) {
            CommonFunc::mapi_error("20001", "用户未登陆");
        }

        //[2].是否存在登陆缓存
        $info = Cache::get($_COOKIE['login_union']);
        if (empty($info)) {
            setcookie("login_union", 0, time() - 3600 * 24, "/");
            CommonFunc::mapi_error("20001", "用户未登陆");
        }

        //[3].刷新登陆缓存
        Cache::put($_COOKIE['login_union'], $info, 60 * 24 * 5);

        $request->uid = $info['uid'];
        $request->mobile = $info['username'];
        $request->role = $info['role'];

        return $next($request);
    }


}
