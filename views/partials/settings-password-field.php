<?php

declare(strict_types=1);

/** @var array{id: string, name: string, label: string, autocomplete: string, hint?: string} $field */
/** @var array<string, string> $fieldErrors */
$fieldError = $fieldErrors[$field['name']] ?? null;
$descriptions = [];
if (isset($field['hint'])) {
    $descriptions[] = $field['id'] . '-hint';
}
if ($fieldError !== null) {
    $descriptions[] = $field['id'] . '-error';
}
?>
<div class="settings-field">
    <div class="settings-field-heading">
        <label for="<?= e($field['id']) ?>"><?= e($field['label']) ?></label>
        <?php if ($field['autocomplete'] === 'current-password'): ?>
            <a class="settings-recovery" href="<?= e(url('/forgot-password')) ?>">Forgot password?</a>
        <?php endif; ?>
    </div>
    <div class="settings-password">
        <input class="form-control" type="password" id="<?= e($field['id']) ?>" name="<?= e($field['name']) ?>" autocomplete="<?= e($field['autocomplete']) ?>" required <?= $field['autocomplete'] === 'new-password' ? 'minlength="8" maxlength="72"' : '' ?> <?= $fieldError !== null ? 'aria-invalid="true"' : '' ?> <?= $descriptions !== [] ? 'aria-describedby="' . e(implode(' ', $descriptions)) . '"' : '' ?>>
        <button class="settings-toggle" type="button" data-toggle-password="<?= e($field['id']) ?>" aria-controls="<?= e($field['id']) ?>" aria-label="Show password" aria-pressed="false"><span class="settings-show">Show</span><span class="settings-hide">Hide</span></button>
    </div>
    <?php if (isset($field['hint'])): ?><p class="settings-help" id="<?= e($field['id']) ?>-hint"><?= e($field['hint']) ?></p><?php endif; ?>
    <?php if ($fieldError !== null): ?><p class="settings-error" id="<?= e($field['id']) ?>-error"><?= e($fieldError) ?></p><?php endif; ?>
</div>
