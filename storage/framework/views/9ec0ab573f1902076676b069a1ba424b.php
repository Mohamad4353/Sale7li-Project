<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'type' => 'request',   // request | quote | payment | verification | complaint
    'value',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'type' => 'request',   // request | quote | payment | verification | complaint
    'value',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    // Visual tone per status value. Labels live in lang/{locale}/status.php.
    $tones = [
        'request' => [
            'pending' => 'warning', 'awaiting_acceptance' => 'warning',
            'accepted' => 'info', 'scheduled' => 'info', 'on_the_way' => 'info', 'in_progress' => 'info',
            'completed' => 'success', 'cancelled' => 'danger', 'rejected' => 'danger',
        ],
        'quote' => ['pending' => 'warning', 'accepted' => 'success', 'rejected' => 'danger', 'expired' => 'neutral'],
        'payment' => [
            'pending' => 'warning', 'paid' => 'success', 'failed' => 'danger',
            'refunded' => 'neutral', 'partially_refunded' => 'neutral',
        ],
        'verification' => ['pending' => 'warning', 'verified' => 'success', 'rejected' => 'danger', 'suspended' => 'danger'],
        'complaint' => ['open' => 'warning', 'investigating' => 'info', 'resolved' => 'success', 'closed' => 'neutral'],
    ];
    $tone = $tones[$type][$value] ?? 'neutral';
    $label = __("status.$type.$value");
    if (str_starts_with($label, 'status.')) {
        $label = ucfirst(str_replace('_', ' ', (string) $value));
    }
?>
<?php if (isset($component)) { $__componentOriginal2ddbc40e602c342e508ac696e52f8719 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2ddbc40e602c342e508ac696e52f8719 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.badge','data' => ['tone' => $tone,'dot' => true,'attributes' => $attributes]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tone' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($tone),'dot' => true,'attributes' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($attributes)]); ?><?php echo e($label); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $attributes = $__attributesOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__attributesOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2ddbc40e602c342e508ac696e52f8719)): ?>
<?php $component = $__componentOriginal2ddbc40e602c342e508ac696e52f8719; ?>
<?php unset($__componentOriginal2ddbc40e602c342e508ac696e52f8719); ?>
<?php endif; ?>
<?php /**PATH C:\Users\mofak\Desktop\SALE7LI-v3\resources\views/components/status.blade.php ENDPATH**/ ?>