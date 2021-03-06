<?php
/*
 * @Description  : 会员日志
 * @Author       : https://github.com/skyselang
 * @Date         : 2020-12-01
 * @LastEditTime : 2020-12-11
 */

namespace app\admin\controller;

use think\facade\Request;
use app\admin\validate\LogValidate;
use app\admin\service\LogService;
use app\admin\service\ApiService;
use app\admin\service\MemberService;

class Log
{
    /**
     * 会员日志列表
     *
     * @method GET
     * 
     * @return json
     */
    public function logList()
    {
        $page            = Request::param('page/d', 1);
        $limit           = Request::param('limit/d', 10);
        $log_type        = Request::param('log_type/d', '');
        $sort_field      = Request::param('sort_field/s ', '');
        $sort_type       = Request::param('sort_type/s', '');
        $request_keyword = Request::param('request_keyword/s', '');
        $member_keyword  = Request::param('member_keyword/s', '');
        $api_keyword     = Request::param('api_keyword/s', '');
        $create_time     = Request::param('create_time/a', []);

        $where = [];
        if ($log_type) {
            $where[] = ['log_type', '=', $log_type];
        }
        if ($request_keyword) {
            $where[] = ['request_ip|request_region|request_isp', 'like', '%' . $request_keyword . '%'];
        }
        if ($member_keyword) {
            $member     = MemberService::etQuery($member_keyword);
            $member_ids = array_column($member, 'member_id');
            $where[]    = ['member_id', 'in', $member_ids];
        }
        if ($api_keyword) {
            $api     = ApiService::etQuery($api_keyword);
            $api_ids = array_column($api, 'api_id');
            $where[] = ['api_id', 'in', $api_ids];
        }
        if ($create_time) {
            $where[] = ['create_time', '>=', $create_time[0] . ' 00:00:00'];
            $where[] = ['create_time', '<=', $create_time[1] . ' 23:59:59'];
        }

        $order = [];
        if ($sort_field && $sort_type) {
            $order = [$sort_field => $sort_type];
        }

        $data = LogService::list($where, $page, $limit, $order);

        return success($data);
    }

    /**
     * 会员日志信息
     *
     * @method GET
     * 
     * @return json
     */
    public function logInfo()
    {
        $param['log_id'] = Request::param('log_id/d', '');

        validate(LogValidate::class)->scene('log_id')->check($param);

        $data = LogService::info($param['log_id']);

        if ($data['is_delete'] == 1) {
            exception('日志已删除');
        }

        return success($data);
    }

    /**
     * 会员日志删除
     *
     * @method POST
     * 
     * @return json
     */
    public function logDele()
    {
        $param['log_id'] = Request::param('log_id/d', '');

        validate(LogValidate::class)->scene('log_id')->check($param);

        $data = LogService::dele($param['log_id']);

        return success($data);
    }

    /**
     * 会员日志统计
     *
     * @method POST
     *
     * @return json
     */
    public function LogStatistic()
    {
        $type   = Request::param('type/s', '');
        $date   = Request::param('date/a', []);
        $region = Request::param('region/s', 'city');

        $data     = [];
        $date_arr = ['total', 'today', 'yesterday', 'thisweek', 'lastweek', 'thismonth', 'lastmonth'];

        if ($type == 'number') {
            $number = [];
            foreach ($date_arr as $k => $v) {
                $number[$v] = LogService::staNumber($v);
            }
            $data['number'] = $number;
        } elseif ($type == 'date') {
            $data['date'] = LogService::staDate($date);
        } elseif ($type == 'region') {
            $data['region'] = LogService::staRegion($date, $region);
        } else {
            $number = [];
            foreach ($date_arr as $k => $v) {
                $number[$v] = LogService::staNumber($v);
            }
            $data['number'] = $number;

            $data['date'] = LogService::staDate($date);

            $data['region'] = LogService::staRegion($date, $region);
        }

        return success($data);
    }
}
