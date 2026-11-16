<?php ($ar = app()->getLocale() === 'ar'); ?>
<!doctype html>
<html lang="<?php echo e(app()->getLocale()); ?>" dir="<?php echo e($ar ? 'rtl' : 'ltr'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo $__env->yieldContent('code'); ?> · SALE7LI</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px;
            font-family: "DM Sans", "Noto Kufi Arabic", ui-sans-serif, system-ui, sans-serif;
            background: #f7f7f4; color: #0d1115; }
        .box { max-width: 460px; text-align: center; }
        .code { font-size: 64px; font-weight: 800; letter-spacing: -.04em; }
        .title { margin-top: 8px; font-size: 20px; font-weight: 700; }
        .msg { margin-top: 12px; color: #64748b; line-height: 1.6; font-size: 14px; }
        .home { display: inline-block; margin-top: 24px; padding: 12px 20px; border-radius: 12px;
            background: #0d1115; color: #fff; text-decoration: none; font-weight: 700; font-size: 14px; }
        .brand { margin-bottom: 20px; font-weight: 800; font-size: 18px; }
        .brand span { color: #f4c430; }
    </style>
</head>
<body>
    <div class="box">
        <div class="brand">SALE<span>7</span>LI</div>
        <div class="code"><?php echo $__env->yieldContent('code'); ?></div>
        <div class="title"><?php echo $__env->yieldContent('title'); ?></div>
        <p class="msg"><?php echo $__env->yieldContent('message'); ?></p>
        <a class="home" href="<?php echo e(url('/')); ?>"><?php echo e($ar ? 'العودة إلى الصفحة الرئيسية' : 'Back to home'); ?></a>
    </div>
</body>
</html>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/errors/minimal.blade.php ENDPATH**/ ?>