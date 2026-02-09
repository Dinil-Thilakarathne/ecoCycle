<?php
/**
 * Simple registration view (UI only)
 * Mirrors the structure and style of the login selection page so it can be used
 * as a starting point for all user types. No backend integration in this file.
 */
$error = $error ?? (session()->getFlash('error') ?? null);
$success = $success ?? (session()->getFlash('success') ?? null);
$old = function ($k, $d = '') {
    return htmlspecialchars(old($k, $d));
};
$oldRaw = static function ($k, $d = '') {
    return old($k, $d);
};

$registrationConfig = config('registration', []);
$registrationDefaults = is_array($registrationConfig['defaults'] ?? null) ? $registrationConfig['defaults'] : [];
$roleConfig = is_array($registrationConfig['roles'] ?? null) ? $registrationConfig['roles'] : [];

if (empty($roleConfig)) {
    $roleConfig = [
        'customer' => [
            'label' => 'Customer',
            'option' => 'Customer — Track recycling requests',
            'summary' => 'Ideal for residents scheduling and monitoring recycling pickups.',
            'fields' => [],
        ],
        'collector' => [
            'label' => 'Collector',
            'option' => 'Collector — Manage pickups & routes',
            'summary' => 'Optimized for field teams coordinating route assignments and pickups.',
            'fields' => [],
        ],
        'company' => [
            'label' => 'Company',
            'option' => 'Company — Operations & analytics',
            'summary' => 'Built for company managers supervising recycling performance and KPIs.',
            'fields' => [],
        ],
        'admin' => [
            'label' => 'Admin',
            'option' => 'Admin — Platform configuration',
            'summary' => 'Reserved for administrators configuring roles, permissions, and platform settings.',
            'fields' => [],
        ],
    ];
}

$firstRoleKey = array_key_first($roleConfig) ?: 'customer';
$selectedRole = (string) old('role', $firstRoleKey);
if (!array_key_exists($selectedRole, $roleConfig)) {
    $selectedRole = $firstRoleKey;
}

$normalizeRules = static function ($rules): array {
    if (is_string($rules)) {
        $rules = array_map('trim', explode('|', $rules));
    }

    if (!is_array($rules)) {
        return [];
    }

    return array_values(array_filter(array_map(static function ($rule) {
        return is_string($rule) ? trim($rule) : '';
    }, $rules), static function ($rule) {
        return $rule !== '';
    }));
};

$renderFieldAttributes = static function (array $attributes): string {
    $compiled = '';
    foreach ($attributes as $attr => $value) {
        if ($value === null) {
            continue;
        }

        $attrName = htmlspecialchars((string) $attr, ENT_QUOTES, 'UTF-8');

        if ($value === true) {
            $compiled .= ' ' . $attrName;
            continue;
        }

        $compiled .= ' ' . $attrName . "=\"" . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . "\"";
    }

    return $compiled;
};

$renderDynamicField = static function (string $roleKey, array $field, callable $oldRawFn, callable $normalizeRulesFn, callable $renderAttributesFn): string {
    $fieldName = $field['name'] ?? null;
    if (!$fieldName || !is_string($fieldName)) {
        return '';
    }

    $label = is_string($field['label'] ?? null) ? $field['label'] : ucfirst($fieldName);
    $type = strtolower((string) ($field['type'] ?? 'text'));
    $placeholder = is_string($field['placeholder'] ?? null) ? $field['placeholder'] : '';
    $help = is_string($field['help'] ?? null) ? $field['help'] : '';
    $attributes = is_array($field['attributes'] ?? null) ? $field['attributes'] : [];
    $normalizedRules = $normalizeRulesFn($field['rules'] ?? []);
    $isRequired = in_array('required', $normalizedRules, true);
    $inputId = 'field_' . preg_replace('/[^a-z0-9_]+/i', '_', $roleKey . '_' . $fieldName);
    $oldValueRaw = $oldRawFn($fieldName, $field['default'] ?? '');
    $oldValueEscaped = htmlspecialchars((string) $oldValueRaw, ENT_QUOTES, 'UTF-8');

    $attributeString = $renderAttributesFn($attributes);
    $requiredAttribute = $isRequired ? ' required' : '';

    ob_start();
    ?>
    <div class="form-input" data-role-field="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>">
        <label class="form-input__label" for="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?><?= $isRequired ? ' *' : '' ?>
        </label>
        <div class="form-input__control">
            <?php if ($type === 'textarea'): ?>
                <textarea class="form-input__field" id="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') ?>"<?= $attributeString ?><?= $requiredAttribute ?>><?= $oldValueEscaped ?></textarea>
            <?php elseif ($type === 'select'): ?>
                <select class="form-input__field" id="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>"<?= $attributeString ?><?= $requiredAttribute ?>>
                    <?php if ($placeholder !== ''): ?>
                        <option value="" disabled<?= $oldValueRaw === '' ? ' selected' : '' ?>><?= htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endif; ?>
                    <?php foreach (($field['options'] ?? []) as $optionValue => $optionLabel): ?>
                        <?php $optionValueString = is_int($optionValue) ? (string) $optionLabel : (string) $optionValue; ?>
                        <option value="<?= htmlspecialchars($optionValueString, ENT_QUOTES, 'UTF-8') ?>"<?= ((string) $oldValueRaw === $optionValueString) ? ' selected' : '' ?>><?= htmlspecialchars((string) $optionLabel, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else: ?>
                <input class="form-input__field" type="<?= htmlspecialchars($type, ENT_QUOTES, 'UTF-8') ?>" id="<?= htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8') ?>" name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') ?>" value="<?= $oldValueEscaped ?>"<?= $attributeString ?><?= $requiredAttribute ?>>
            <?php endif; ?>
        </div>
        <?php if ($help !== ''): ?>
            <p class="form-input__help" aria-hidden="true"><?= htmlspecialchars($help, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>
    <?php
    return trim((string) ob_get_clean());
};

$roleFieldsMarkup = [];
foreach ($roleConfig as $roleKey => $config) {
    $fields = is_array($config['fields'] ?? null) ? $config['fields'] : [];
    $markup = [];
    foreach ($fields as $field) {
        $markup[] = $renderDynamicField($roleKey, $field, $oldRaw, $normalizeRules, $renderFieldAttributes);
    }
    $roleFieldsMarkup[$roleKey] = implode("\n", array_filter($markup));
}

$defaultNameLabel = is_string($registrationDefaults['name_label'] ?? null) ? $registrationDefaults['name_label'] : 'Full name';
$defaultNamePlaceholder = is_string($registrationDefaults['name_placeholder'] ?? null) ? $registrationDefaults['name_placeholder'] : 'Your full name';

$selectedOverrides = is_array($roleConfig[$selectedRole]['overrides'] ?? null) ? $roleConfig[$selectedRole]['overrides'] : [];
$selectedNameLabel = is_string($selectedOverrides['name_label'] ?? null) ? $selectedOverrides['name_label'] : $defaultNameLabel;
$selectedNamePlaceholder = is_string($selectedOverrides['name_placeholder'] ?? null) ? $selectedOverrides['name_placeholder'] : $defaultNamePlaceholder;

$roleConfigJson = htmlspecialchars(json_encode(array_map(static function ($cfg) {
    return [
        'label' => $cfg['label'] ?? '',
        'summary' => $cfg['summary'] ?? '',
        'overrides' => $cfg['overrides'] ?? [],
    ];
}, $roleConfig), JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');

$defaultAvatar = htmlspecialchars(asset('assets/logo-icon.png'));
$roleSummaryText = htmlspecialchars((string) ($roleConfig[$selectedRole]['summary'] ?? ''));
$headContent = '<link rel="stylesheet" href="/css/page/login.css">';

?>

<section class="main-section auth-login-page register-page">
    <div class="login-content">
        <div class="content-top">
            <h1>Create an account</h1>
            <p>Register to access the platform. Choose your role and complete the form below.</p>
        </div>

        <form class="content-body" method="POST" action="/register" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="_token" value="<?= htmlspecialchars(csrf_token()) ?>">

            <?php if ($success): ?>
                <p role="status" aria-live="polite" style="color:var(--success);"><?= htmlspecialchars($success) ?></p>
            <?php endif; ?>

            <div
                class="role-tabs"
                data-role-tabs
                data-config="<?= $roleConfigJson ?>"
                data-default-name-label="<?= htmlspecialchars($defaultNameLabel, ENT_QUOTES, 'UTF-8') ?>"
                data-default-name-placeholder="<?= htmlspecialchars($defaultNamePlaceholder, ENT_QUOTES, 'UTF-8') ?>"
            ></div>
            <p class="role-tabs__summary" data-role-summary><?= $roleSummaryText ?></p>
            <input type="hidden" id="role-select" name="role" value="<?= htmlspecialchars($selectedRole) ?>">

            <profile-image-input label="Profile photo" name="profile_photo"
                help="Optional. JPG, PNG, GIF, or WEBP up to 2 MB." default-src="<?= $defaultAvatar ?>">
            </profile-image-input>
            <noscript>
                <div class="form-select">
                    <label for="profile_photo_fallback">Profile photo</label><br>
                    <input type="file" id="profile_photo_fallback" name="profile_photo" accept="image/*">
                </div>
            </noscript>

            <div class="form-grid">
                <form-input unwrap label="<?= htmlspecialchars($selectedNameLabel, ENT_QUOTES, 'UTF-8') ?>" name="name" placeholder="<?= htmlspecialchars($selectedNamePlaceholder, ENT_QUOTES, 'UTF-8') ?>" value="<?= $old('name') ?>"
                    required></form-input>

                <form-input unwrap label="Email" name="email" type="email" placeholder="email@example.com"
                    value="<?= $old('email') ?>" required></form-input>

                <form-input unwrap label="Password" name="password" type="password" placeholder="Choose a password"
                    required></form-input>

                <form-input unwrap label="Confirm password" name="password_confirm" type="password" placeholder="Repeat password"
                    required></form-input>

                <div class="role-fields" data-role-fields-wrapper>
                <?php foreach ($roleConfig as $roleKey => $config): ?>
                    <?php $isActiveRole = $roleKey === $selectedRole; ?>
                    <div class="role-fields__group<?= $isActiveRole ? ' is-active' : '' ?>" data-role-fields-group data-role="<?= htmlspecialchars($roleKey, ENT_QUOTES, 'UTF-8') ?>"<?= $isActiveRole ? '' : ' style="display:none;"' ?>>
                        <?php if (!empty($roleFieldsMarkup[$roleKey])): ?>
                            <?= $roleFieldsMarkup[$roleKey] ?>
                        <?php else: ?>
                            <p class="role-fields__empty">No additional information required for this role.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>

            <p id="registerError" role="status" aria-live="polite" style="color:var(--danger);"
                class="<?= $error ? 'visible' : '' ?>">
                <?= $error ? htmlspecialchars($error) : '&nbsp;' ?>
            </p>

            <noscript>
                <div style="display:none;">
                    <input type="text" name="name" value="<?= $old('name') ?>" />
                    <input type="email" name="email" value="<?= $old('email') ?>" />
                    <input type="password" name="password" />
                    <input type="password" name="password_confirm" />
                </div>
            </noscript>

            <div style="display:flex; gap:.5rem; margin-top:var(--space-2); width: 100%; flex-direction:column;">
                <button id="registerSubmit" type="submit" class="btn btn-gradient login-card__action">Create
                    account</button>
                <div class="forget-password">
                    <a href="/login">Already have an account? Sign in</a>
                </div>
            </div>
        </form>

        <div class="content-footer">
            <a href="/" class="btn btn-outline signup-btn">Back to home</a>
        </div>

        <script src="/js/toast.js"></script>
        <script>
            (function () {
                // Keep client-side validations. If validation passes, redirect to /login.
                var form = document.querySelector('form.content-body');
                var registerError = document.getElementById('registerError');
                var roleTabs = document.querySelector('[data-role-tabs]');
                var roleSummary = document.querySelector('[data-role-summary]');
                var roleSelect = document.getElementById('role-select');
                var fieldsWrapper = document.querySelector('[data-role-fields-wrapper]');
                var nameField = form ? form.querySelector('form-input[name="name"]') : null;

                function showError(msg) {
                    if (!registerError) { alert(msg); return; }
                    registerError.textContent = msg;
                    registerError.style.display = 'block';
                }

                if (roleTabs && roleSelect) {
                    var configData = roleTabs.dataset.config || '{}';
                    var config;
                    try {
                        config = JSON.parse(configData);
                    } catch (err) {
                        config = {};
                    }

                    if (!Object.keys(config).length) {
                        config = {
                            customer: { label: 'Customer', summary: 'Ideal for residents scheduling and monitoring recycling pickups.' },
                            collector: { label: 'Collector', summary: 'Optimized for field teams coordinating route assignments and pickups.' },
                            company: { label: 'Company', summary: 'Built for company managers supervising recycling performance and KPIs.' },
                            admin: { label: 'Admin', summary: 'Reserved for administrators configuring roles, permissions, and platform settings.' }
                        };
                    }

                    var buttons = [];
                    var defaultNameLabel = roleTabs.dataset.defaultNameLabel || 'Full name';
                    var defaultNamePlaceholder = roleTabs.dataset.defaultNamePlaceholder || 'Your full name';

                    function setActiveRole(role) {
                        if (!config[role]) return;
                        buttons.forEach(function (btn) {
                            var isActive = btn.dataset.role === role;
                            btn.classList.toggle('is-active', isActive);
                            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
                            btn.tabIndex = isActive ? 0 : -1;
                        });
                        roleSelect.value = role;
                        if (roleSummary) {
                            var summaryText = config[role].summary || '';
                            roleSummary.textContent = summaryText;
                            roleSummary.style.display = summaryText ? '' : 'none';
                        }

                        if (fieldsWrapper) {
                            var groups = fieldsWrapper.querySelectorAll('[data-role-fields-group]');
                            groups.forEach(function (group) {
                                var isGroupActive = group.dataset.role === role;
                                group.style.display = isGroupActive ? '' : 'none';
                                group.classList.toggle('is-active', isGroupActive);

                                // Ensure only inputs for the active role submit values.
                                var inputs = group.querySelectorAll('input, textarea, select');
                                inputs.forEach(function (el) {
                                    el.disabled = !isGroupActive;
                                });
                            });
                        }

                        if (nameField) {
                            var overrides = config[role] && config[role].overrides ? config[role].overrides : {};
                            nameField.setAttribute('label', overrides.name_label || defaultNameLabel);
                            nameField.setAttribute('placeholder', overrides.name_placeholder || defaultNamePlaceholder);
                        }
                    }

                    Object.keys(config).forEach(function (roleKey, index) {
                        var button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'role-tabs__button';
                        button.dataset.role = roleKey;
                        button.textContent = config[roleKey].label || roleKey;
                        button.setAttribute('role', 'tab');
                        button.addEventListener('click', function () {
                            setActiveRole(roleKey);
                            button.focus();
                        });
                        roleTabs.appendChild(button);
                        buttons.push(button);
                    });

                    if (buttons.length) {
                        roleTabs.setAttribute('role', 'tablist');
                        var initialRole = roleSelect.value && config[roleSelect.value] ? roleSelect.value : buttons[0].dataset.role;
                        setActiveRole(initialRole);
                        roleSelect.style.display = 'none';
                        roleSelect.setAttribute('aria-hidden', 'true');
                        roleSelect.tabIndex = -1;
                    }
                }

                if (!form) return;

                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    var btn = document.getElementById('registerSubmit');
                    if (btn) btn.disabled = true;
                    if (registerError) registerError.style.display = 'none';

                    // Basic client-side validation
                    var name = form.querySelector('[name="name"]').value.trim();
                    var email = form.querySelector('[name="email"]').value.trim();
                    var pwd = form.querySelector('[name="password"]').value;
                    var pwd2 = form.querySelector('[name="password_confirm"]').value;

                    if (!name || !email || !pwd || !pwd2) {
                        showError('Please fill out all required fields.');
                        if (btn) btn.disabled = false;
                        return;
                    }
                    if (pwd.length < 6) {
                        showError('Password should be at least 6 characters.');
                        if (btn) btn.disabled = false;
                        return;
                    }
                    if (pwd !== pwd2) {
                        showError('Passwords do not match.');
                        if (btn) btn.disabled = false;
                        return;
                    }

                    // All validations passed — submit via AJAX
                    if (btn) btn.textContent = 'Creating account...';
                    
                    var formData = new FormData(form);

                    fetch(form.action || '/register', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).then(function (resp) {
                        return resp.json().catch(function () {
                            return { success: false, message: 'Invalid server response' };
                        });
                    }).then(function (data) {
                        if (data && data.success) {
                            // Show success toast
                            try {
                                if (typeof __createToast === 'function') {
                                    __createToast(data.message || 'Account created successfully!', 'success', 2000);
                                }
                            } catch (e) {
                                // ignore
                            }
                            
                            // Redirect to login page
                            setTimeout(function () {
                                window.location.href = data.redirect || '/login';
                            }, 700);
                            return;
                        }

                        // Show error message
                        var msg = (data && data.message) ? data.message : 'Failed to create account';
                        showError(msg);
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = 'Create account';
                        }
                    }).catch(function (err) {
                        showError('Network error. Please try again.');
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = 'Create account';
                        }
                    });
                });

                // If server-side success was flashed (redirect from POST), show toast
                <?php if ($success): ?>
                    try {
                        if (typeof __createToast === 'function') __createToast(<?= json_encode($success) ?>, 'success', 4000);
                        else document.addEventListener('DOMContentLoaded', function () { if (typeof __createToast === 'function') __createToast(<?= json_encode($success) ?>, 'success', 4000); });
                    } catch (e) { /* ignore */ }
                <?php endif; ?>
            })();
        </script>
    </div>
    <div class="page_image">
        <img src="/assets/signup_page.png" alt="signup page Image" />
    </div>
</section>