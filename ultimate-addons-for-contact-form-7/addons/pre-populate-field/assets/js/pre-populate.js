;(function ($) {
    'use strict';

    $(document).ready(function () {

        // ==========================================
        // 1. Pre-populate Fields from URL Parameters
        // ==========================================
        var urlParams = new URLSearchParams(window.location.search);
        var hasParams = urlParams.toString().length > 0;

        if (hasParams) {
            var form_id = urlParams.get('form');
            var $targetForm = null;

            if (form_id) {
                // Scope to the specific form if form ID is provided
                var $matched = $('form:has(input[name="_wpcf7"][value="' + form_id + '"])');
                if ($matched.length) {
                    $targetForm = $matched;
                }
            }

            if (!$targetForm || !$targetForm.length) {
                $targetForm = $('form.wpcf7-form');
            }
            if (!$targetForm.length) {
                $targetForm = $('form');
            }

            var groupedParams = {};

            for (var pair of urlParams.entries()) {
                var key = pair[0];
                var value = pair[1];

                // Skip 'form' parameter from populating into inputs
                if (key === 'form') {
                    continue;
                }

                if (key.endsWith('[]')) {
                    var plainKey = key.slice(0, -2);
                    if (!groupedParams[plainKey]) {
                        groupedParams[plainKey] = [];
                    }
                    groupedParams[plainKey].push(value);
                } else {
                    groupedParams[key] = value;
                }
            }

            for (var key in groupedParams) {
                var val = groupedParams[key];

                if (Array.isArray(val)) {
                    val.forEach(function (v) {
                        var decoded = decodeURIComponent(v);
                        $targetForm.find("[name='" + key + "[]'][value='" + decoded + "']")
                            .prop('checked', true)
                            .trigger('change');
                    });
                } else {
                    var decodedVal = decodeURIComponent(val);
                    var $input = $targetForm.find("[name='" + key + "']");
                    var inputType = $input.attr('type');

                    if (inputType === 'radio' || inputType === 'checkbox') {
                        $targetForm.find("[name='" + key + "'][value='" + decodedVal + "']")
                            .prop('checked', true)
                            .trigger('change');
                    } else if ($input.is('select')) {
                        $input.val(decodedVal).trigger('change');
                    } else {
                        $input.val(decodedVal).trigger('keyup').trigger('change').trigger('input');
                    }

                    // UACF7 Repeater support
                    var $repeaterInput = $targetForm.find("[uacf-original-name='" + key + "']");
                    if ($repeaterInput.length) {
                        $repeaterInput
                            .val(decodedVal)
                            .attr('uacf-field-type', 'pre-populate')
                            .trigger('keyup')
                            .trigger('change')
                            .trigger('input');
                    }
                }
            }
        }

        // ==========================================
        // 2. Handle Redirection on Form Submission
        // ==========================================
        function buildRedirectUrl(config, $form) {
            if (!config || !config.pre_populate_enable || !config.data_redirect_url) {
                return false;
            }

            var baseUrl = config.data_redirect_url;
            var fields = config.pre_populate_passing_field || [];
            var params = [];

            if (config.pre_populate_form) {
                params.push('form=' + encodeURIComponent(config.pre_populate_form));
            }

            fields.forEach(function (field_name) {
                var $inputs = $form.find("[name='" + field_name + "'], [name='" + field_name + "[]']");
                if (!$inputs.length) {
                    return;
                }

                var firstType = $inputs.first().attr('type');

                if (firstType === 'checkbox') {
                    var checkedValues = [];
                    $inputs.filter(':checked').each(function () {
                        checkedValues.push($(this).val());
                    });
                    if (checkedValues.length === 1) {
                        params.push(encodeURIComponent(field_name) + '=' + encodeURIComponent(checkedValues[0]));
                    } else if (checkedValues.length > 1) {
                        checkedValues.forEach(function (cv) {
                            params.push(encodeURIComponent(field_name + '[]') + '=' + encodeURIComponent(cv));
                        });
                    }
                } else if (firstType === 'radio') {
                    var radioVal = $inputs.filter(':checked').val();
                    if (radioVal) {
                        params.push(encodeURIComponent(field_name) + '=' + encodeURIComponent(radioVal));
                    }
                } else if ($inputs.is('select') && $inputs.prop('multiple')) {
                    var selectedVals = $inputs.val();
                    if (Array.isArray(selectedVals)) {
                        selectedVals.forEach(function (sv) {
                            params.push(encodeURIComponent(field_name + '[]') + '=' + encodeURIComponent(sv));
                        });
                    }
                } else {
                    var val = $inputs.val();
                    if (val) {
                        params.push(encodeURIComponent(field_name) + '=' + encodeURIComponent(val));
                    }
                }
            });

            var separator = baseUrl.indexOf('?') !== -1 ? '&' : '?';
            return params.length ? (baseUrl + separator + params.join('&')) : baseUrl;
        }

        // Listen for CF7 mail sent event globally on document
        document.addEventListener('wpcf7mailsent', function (event) {
            if (!event || !event.detail) {
                return;
            }

            var formId = String(event.detail.contactFormId || '');
            var $form = $(event.target).find('form.wpcf7-form');
            if (!$form.length) {
                $form = $(event.target);
            }
            if (!$form.length && formId) {
                $form = $('form:has(input[name="_wpcf7"][value="' + formId + '"])');
            }

            // 1. Try preloaded config first (instant redirect without network latency)
            var formsConfig = (typeof uacf7_pre_populate_url !== 'undefined' && uacf7_pre_populate_url.forms) ? uacf7_pre_populate_url.forms : null;
            if (formsConfig && formsConfig[formId]) {
                var config = formsConfig[formId];
                var redirectUrl = buildRedirectUrl(config, $form);
                if (redirectUrl) {
                    location.href = redirectUrl;
                    return;
                }
            }

            // 2. Fallback: AJAX request if config was not preloaded
            if (typeof uacf7_pre_populate_url !== 'undefined' && uacf7_pre_populate_url.ajaxurl && formId) {
                jQuery.ajax({
                    url: uacf7_pre_populate_url.ajaxurl,
                    type: 'post',
                    dataType: 'json',
                    data: {
                        action: 'uacf7_ajax_pre_populate_redirect',
                        form_id: formId,
                        nonce: uacf7_pre_populate_url.nonce,
                        ajax_nonce: uacf7_pre_populate_url.nonce,
                    },
                    success: function (data) {
                        if (data && data.pre_populate_enable) {
                            var redirectUrl = buildRedirectUrl(data, $form);
                            if (redirectUrl) {
                                location.href = redirectUrl;
                            }
                        }
                    }
                });
            }
        }, false);

    });

})(jQuery);
