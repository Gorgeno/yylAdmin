<?php
/*
 * @Description  : token验证中间件
 * @Author       : https://github.com/skyselang
 * @Date         : 2020-05-05
 * @LastEditTime : 2020-09-27
 */

namespace app\admin\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Config;
use app\admin\service\AdminTokenService;

class AdminTokenVerify
{
    /**
     * 处理请求
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        $admin_menu_url = admin_menu_url();
        $api_white_list = Config::get('admin.api_white_list');

        if (!in_array($admin_menu_url, $api_white_list)) {
            $admin_token = admin_token();

            if (empty($admin_token)) {
                error('AdminToken must');
            }

            $admin_user_id = admin_user_id();

            if (empty($admin_user_id)) {
                error('AdminUserId must');
            }

            AdminTokenService::verify($admin_token, $admin_user_id);
        }

        return $next($request);
    }
}
