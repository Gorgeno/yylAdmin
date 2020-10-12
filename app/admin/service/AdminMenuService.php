<?php
/*
 * @Description  : 菜单管理
 * @Author       : https://github.com/skyselang
 * @Date         : 2020-05-05
 * @LastEditTime : 2020-09-28
 */

namespace app\admin\service;

use think\facade\Db;
use app\common\cache\AdminMenuCache;

class AdminMenuService
{
    /**
     * 菜单列表
     *
     * @return array 
     */
    public static function list()
    {
        $tree = AdminMenuCache::get(-1);

        if (empty($tree)) {
            $field = 'admin_menu_id,menu_pid,menu_name,menu_url,menu_sort,is_prohibit,is_unauth,create_time,update_time';

            $admin_menu_pid = Db::name('admin_menu')
                ->field($field)
                ->where('menu_pid', '=', 0)
                ->where('is_delete', 0)
                ->order(['menu_sort' => 'desc', 'admin_menu_id' => 'asc'])
                ->select()
                ->toArray();

            $admin_menu_child = Db::name('admin_menu')
                ->field($field)
                ->where('menu_pid', '>', 0)
                ->where('is_delete', 0)
                ->order(['menu_sort' => 'desc', 'admin_menu_id' => 'asc',])
                ->select()
                ->toArray();

            $admin_menu = array_merge($admin_menu_pid, $admin_menu_child);

            $tree = self::toTree($admin_menu, 0);

            AdminMenuCache::set(-1, $tree);
        }

        $data['count'] = count($tree);
        $data['list']  = $tree;

        return $data;
    }

    /**
     * 菜单信息
     * admin_menu_id：-1树形菜单，0所有菜单链接
     *
     * @param integer $admin_menu_id 菜单id
     * @param boolean $is_menu_url   是否菜单url
     * 
     * @return array
     */
    public static function info($admin_menu_id, $is_menu_url = false)
    {
        $admin_menu = AdminMenuCache::get($admin_menu_id);

        if (empty($admin_menu)) {
            if ($is_menu_url) {
                $admin_menu = Db::name('admin_menu')
                    ->where('menu_url', '=', $admin_menu_id)
                    ->find();

                if (empty($admin_menu)) {
                    error('菜单不存在');
                }
            } else {
                if ($admin_menu_id == 0) {
                    $where[] = ['is_delete', '=', 0];
                    $where[] = ['is_prohibit', '=', 0];
                    $where[] = ['menu_url', '<>', ''];

                    $where_un[] = ['is_delete', '=', 0];
                    $where_un[] = ['is_prohibit', '=', 0];
                    $where_un[] = ['menu_url', '<>', ''];
                    $where_un[] = ['is_unauth', '=', 1];

                    $admin_menu = Db::name('admin_menu')
                        ->field('menu_url')
                        ->order('menu_url', 'asc')
                        ->whereOr([$where, $where_un])
                        ->column('menu_url');
                } elseif ($admin_menu_id == -1) {
                    $admin_menu = self::list();
                    $admin_menu = $admin_menu['list'];
                } else {
                    $admin_menu = Db::name('admin_menu')
                        ->where('admin_menu_id', $admin_menu_id)
                        ->where('is_delete', 0)
                        ->find();

                    if (empty($admin_menu)) {
                        error('菜单不存在');
                    }
                }
            }

            AdminMenuCache::set($admin_menu_id, $admin_menu);
        }

        return $admin_menu;
    }

    /**
     * 菜单添加
     *
     * @param array $param 菜单信息
     * 
     * @return array
     */
    public static function add($param)
    {
        $param['create_time'] = date('Y-m-d H:i:s');

        $admin_menu_id = Db::name('admin_menu')
            ->insertGetId($param);
            
        if (empty($admin_menu_id)) {
            error();
        }

        $param['admin_menu_id'] = $admin_menu_id;

        AdminMenuCache::del(-1);
        AdminMenuCache::del(0);

        return $param;
    }

    /**
     * 菜单修改
     *
     * @param array $param 菜单信息
     * 
     * @return array
     */
    public static function edit($param)
    {
        $admin_menu_id = $param['admin_menu_id'];

        $admin_menu_info = self::info($admin_menu_id);
        $admin_menu_url  = $admin_menu_info['menu_url'];

        unset($param['admin_menu_id']);

        $param['update_time'] = date('Y-m-d H:i:s');
        
        $update = Db::name('admin_menu')
            ->where('admin_menu_id', $admin_menu_id)
            ->update($param);

        if (empty($update)) {
            error();
        }

        $param['admin_menu_id'] = $admin_menu_id;

        AdminMenuCache::del(-1);
        AdminMenuCache::del(0);
        AdminMenuCache::del($admin_menu_url);

        return $param;
    }

    /**
     * 菜单删除
     *
     * @param integer $admin_menu_id 菜单id
     * 
     * @return array
     */
    public static function dele($admin_menu_id)
    {
        $admin_menu = Db::name('admin_menu')
            ->field('admin_menu_id,menu_pid')
            ->where('is_delete', 0)
            ->select();

        $admin_menu_ids   = self::getChildren($admin_menu, $admin_menu_id);
        $admin_menu_ids[] = (int) $admin_menu_id;

        $update['is_delete']   = 1;
        $update['delete_time'] = date('Y-m-d H:i:s');
        
        $delete = Db::name('admin_menu')
            ->where('admin_menu_id', 'in', $admin_menu_ids)
            ->update($update);

        if (empty($delete)) {
            error();
        }

        $admin_menu_info = self::info($admin_menu_id);
        $admin_menu_url  = $admin_menu_info['menu_url'];

        AdminMenuCache::del(-1);
        AdminMenuCache::del(0);
        AdminMenuCache::del($admin_menu_url);

        return $admin_menu_ids;
    }

    /**
     * 菜单是否禁用
     *
     * @param array $param 菜单信息
     * 
     * @return array
     */
    public static function prohibit($param)
    {
        $admin_menu_id = $param['admin_menu_id'];

        $data['is_prohibit'] = $param['is_prohibit'];
        $data['update_time'] = date('Y-m-d H:i:s');
        
        $update = Db::name('admin_menu')
            ->where('admin_menu_id', $admin_menu_id)
            ->update($data);

        if (empty($update)) {
            error();
        }

        AdminMenuCache::del(-1);
        AdminMenuCache::del(0);

        return $param;
    }

    /**
     * 菜单是否无需授权
     *
     * @param array $param 菜单信息
     * 
     * @return array
     */
    public static function unauth($param)
    {
        $admin_menu_id = $param['admin_menu_id'];

        $data['is_unauth']   = $param['is_unauth'];
        $data['update_time'] = date('Y-m-d H:i:s');
        
        $update = Db::name('admin_menu')
            ->where('admin_menu_id', $admin_menu_id)
            ->update($data);

        if (empty($update)) {
            error();
        }

        AdminMenuCache::del(-1);
        AdminMenuCache::del(0);

        return $param;
    }

    /**
     * 菜单所有子级获取
     *
     * @param array   $admin_menu    所有菜单
     * @param integer $admin_menu_id 菜单id
     * 
     * @return array
     */
    public static function getChildren($admin_menu, $admin_menu_id)
    {
        $children = [];

        foreach ($admin_menu as $k => $v) {
            if ($v['menu_pid'] == $admin_menu_id) {
                $children[] = $v['admin_menu_id'];
                $children   = array_merge($children, self::getChildren($admin_menu, $v['admin_menu_id']));
            }
        }

        return $children;
    }

    /**
     * 菜单树形获取
     *
     * @param array   $admin_menu 所有菜单
     * @param integer $menu_pid   菜单父级id
     * 
     * @return array
     */
    public static function toTree($admin_menu, $menu_pid)
    {
        $tree = [];

        foreach ($admin_menu as $k => $v) {
            if ($v['menu_pid'] == $menu_pid) {
                $v['children'] = self::toTree($admin_menu, $v['admin_menu_id']);
                $tree[] = $v;
            }
        }

        return $tree;
    }

    /**
     * 菜单模糊查询
     *
     * @param string $keyword 关键词
     * @param string $field   字段
     *
     * @return array
     */
    public static function likeQuery($keyword, $field = 'menu_url|menu_name')
    {
        $data = Db::name('admin_menu')
            ->where($field, 'like', '%' . $keyword . '%')
            ->select()
            ->toArray();

        return $data;
    }

    /**
     * 菜单精确查询
     *
     * @param string $keyword 关键词
     * @param string $field   字段
     *
     * @return array
     */
    public static function etQuery($keyword, $field = 'menu_url|menu_name')
    {
        $data = Db::name('admin_menu')
            ->where($field, '=', $keyword)
            ->select()
            ->toArray();

        return $data;
    }
}
