<?php
// 应用中间件定义文件
return [
    // 跨域请求
    \app\admin\middleware\AllowCrossDomain::class,
    // token验证
    \app\admin\middleware\AdminTokenVerify::class,
    // 权限验证
    \app\admin\middleware\AdminRuleVerify::class,
    // 请求频率限制
    \app\admin\middleware\AdminThrottle::class,
    // 日志
    \app\admin\middleware\AdminLog::class,
];
