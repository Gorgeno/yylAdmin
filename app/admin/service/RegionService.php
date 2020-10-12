<?php
/*
 * @Description  : 地区管理
 * @Author       : https://github.com/skyselang
 * @Date         : 2020-09-22
 * @LastEditTime : 2020-10-12
 */

namespace app\admin\service;

use think\facade\Db;
use app\common\cache\RegionCache;
use Overtrue\Pinyin\Pinyin;

class RegionService
{
    /**
     * 地区列表
     * 
     * @param array  $where 条件
     * @param array  $order 排序
     * @param string $field 字段
     *
     * @return array 
     */
    public static function list($where = [], $order = [], $field = '')
    {
        if (empty($field)) {
            $field = 'region_id,region_pid,region_path,region_name,region_pinyin,region_jianpin,region_initials,region_citycode,region_zipcode,region_sort';
        }

        if (empty($where)) {
            $where[] = ['region_pid', '=', 0];
        }

        if (empty($order)) {
            $order = ['region_sort' => 'desc', 'region_id' => 'asc'];
        }

        $list = Db::name('region')
            ->field($field)
            ->where($where)
            ->order($order)
            ->select()
            ->toArray();

        foreach ($list as $k => $v) {
            $v['children']    = [];
            $v['hasChildren'] = true;
            $list[$k] = $v;
        }

        $data['count'] = count($list);
        $data['list']  = $list;

        return $data;
    }

    /**
     * 地区信息
     * region_id：-1树形地区
     *
     * @param integer $region_id 地区id
     * 
     * @return array
     */
    public static function info($region_id)
    {
        $region = RegionCache::get($region_id);

        if (empty($region)) {
            if ($region_id == -1) {
                $region = self::list();
                $region = $region['list'];
            } else {
                $region = Db::name('region')
                    ->where('region_id', $region_id)
                    ->where('is_delete', 0)
                    ->find();

                if (empty($region)) {
                    error('地区不存在');
                }
            }

            RegionCache::set($region_id, $region);
        }

        return $region;
    }


    /**
     * 地区添加
     *
     * @param array $param 地区信息
     * 
     * @return array
     */
    public static function add($param)
    {
        $param['create_time'] = date('Y-m-d H:i:s');

        $pinyin = new Pinyin();
        $region_py = $pinyin->convert($param['region_name']);
        $region_pinyin = '';
        $region_jianpin = '';
        $region_initials = '';
        foreach ($region_py as $k => $v) {
            $region_py_i = '';
            $region_py_e = '';
            $region_py_i = strtoupper(substr($v, 0, 1));
            $region_py_e = substr($v, 1);
            $region_pinyin .= $region_py_i . $region_py_e;
            $region_jianpin .= $region_py_i;
            if ($k == 0) {
                $region_initials = $region_py_i;
            }
        }

        $param['region_pinyin'] = $param['region_pinyin'] ?: $region_pinyin;
        $param['region_jianpin'] = $param['region_jianpin'] ?: $region_jianpin;
        $param['region_initials'] = $param['region_initials'] ?: $region_initials;

        if ($param['region_pid']) {
            $region = self::info($param['region_pid']);

            $param['region_level'] = $region['region_level'] + 1;
            $region_id = Db::name('region')
                ->insertGetId($param);

            $region_path = $region['region_path'] . ',' . $region_id;
            $update['region_path'] = $region_path;
            $update['update_time'] = date('Y-m-d H:i:s');
            $update_re = Db::name('region')
                ->where('region_id', $region_id)
                ->update($update);
        } else {
            $region_id = Db::name('region')
                ->insertGetId($param);

            $region_path = $region_id;
            $update['region_path'] = $region_path;
            $update['update_time'] = date('Y-m-d H:i:s');
            $update_re = Db::name('region')
                ->where('region_id', $region_id)
                ->update($update);
        }

        if (empty($update_re)) {
            error();
        }

        $param['region_id']   = $region_id;
        $param['region_path'] = $region_path;

        RegionCache::del(-1);

        return $param;
    }

    /**
     * 地区修改
     *
     * @param array $param 地区信息
     * 
     * @return array
     */
    public static function edit($param)
    {
        $region_id = $param['region_id'];

        unset($param['region_id']);

        $pinyin = new Pinyin();
        $region_py = $pinyin->convert($param['region_name']);
        $region_pinyin = '';
        $region_jianpin = '';
        $region_initials = '';
        foreach ($region_py as $k => $v) {
            $region_py_i = '';
            $region_py_e = '';
            $region_py_i = strtoupper(substr($v, 0, 1));
            $region_py_e = substr($v, 1);
            $region_pinyin .= $region_py_i . $region_py_e;
            $region_jianpin .= $region_py_i;
            if ($k == 0) {
                $region_initials = $region_py_i;
            }
        }

        $param['region_pinyin'] = $param['region_pinyin'] ?: $region_pinyin;
        $param['region_jianpin'] = $param['region_jianpin'] ?: $region_jianpin;
        $param['region_initials'] = $param['region_initials'] ?: $region_initials;

        if ($param['region_pid']) {
            $region = self::info($param['region_pid']);

            $param['region_level'] = $region['region_level'] + 1;
            Db::name('region')
                ->where('region_id', $region_id)
                ->update($param);

            $region_path = $region['region_path'] . ',' . $region_id;
            $update['region_path'] = $region_path;
            $update['update_time'] = date('Y-m-d H:i:s');
            $update_re = Db::name('region')
                ->where('region_id', $region_id)
                ->update($update);
        } else {
            Db::name('region')
                ->where('region_id', $region_id)
                ->update($param);

            $region_path = $region_id;
            $update['region_path'] = $region_path;
            $update['update_time'] = date('Y-m-d H:i:s');
            $update_re = Db::name('region')
                ->where('region_id', $region_id)
                ->update($update);
        }

        if (empty($update_re)) {
            error();
        }

        $param['region_id']   = $region_id;
        $param['region_path'] = $region_path;

        RegionCache::del(-1);
        RegionCache::del($region_id);

        return $param;
    }

    /**
     * 地区删除
     *
     * @param integer $region_id 地区id
     * 
     * @return array
     */
    public static function dele($region_id)
    {
        $region = Db::name('region')
            ->field('region_id,region_pid')
            ->where('is_delete', 0)
            ->select();

        $region_ids   = self::getChildren($region, $region_id);
        $region_ids[] = (int) $region_id;

        $data['is_delete']   = 1;
        $data['delete_time'] = date('Y-m-d H:i:s');

        $update = Db::name('region')
            ->where('region_id', 'in', $region_ids)
            ->update($data);

        if (empty($update)) {
            error();
        }

        RegionCache::del(-1);

        return $region_ids;
    }

    /**
     * 地区所有子级获取
     *
     * @param array   $region    所有地区
     * @param integer $region_id 地区id
     * 
     * @return array
     */
    public static function getChildren($region, $region_id)
    {
        $children = [];

        foreach ($region as $k => $v) {
            if ($v['region_pid'] == $region_id) {
                $children[] = $v['region_id'];
                $children   = array_merge($children, self::getChildren($region, $v['region_id']));
            }
        }

        return $children;
    }

    /**
     * 地区树形获取
     *
     * @param array   $region 所有地区
     * @param integer $region_pid   地区父级id
     * 
     * @return array
     */
    public static function toTree($region, $region_pid)
    {
        $tree = [];

        foreach ($region as $k => $v) {
            if ($v['region_pid'] == $region_pid && $v['region_level'] <= 2) {
                $v['children'] = self::toTree($region, $v['region_id']);
                $tree[] = $v;
            }
        }

        return $tree;
    }

    /**
     * 地区模糊查询
     *
     * @param string $keyword 关键词
     * @param string $field   字段
     *
     * @return array
     */
    public static function likeQuery($keyword, $field = 'region_name')
    {
        $data = Db::name('region')
            ->where($field, 'like', '%' . $keyword . '%')
            ->select()
            ->toArray();

        return $data;
    }

    /**
     * 地区精确查询
     *
     * @param string $keyword 关键词
     * @param string $field   字段
     *
     * @return array
     */
    public static function etQuery($keyword, $field = 'region_name')
    {
        $data = Db::name('region')
            ->where($field, '=', $keyword)
            ->select()
            ->toArray();

        return $data;
    }
}
