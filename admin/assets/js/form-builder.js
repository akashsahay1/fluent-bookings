/**
 * Form Builder JavaScript
 */

(function($) {
    'use strict';

    var FluentBookingFormBuilder = {
        fields: [],
        fieldCounter: 0,

        init: function() {
            this.loadExistingForm();
            this.initDragAndDrop();
            this.initTabs();
            this.initSettings();
            this.initSaveForm();
            this.initFieldEditor();
        },

        loadExistingForm: function() {
            var formDataEl = jQuery('#fb-form-data');
            if (formDataEl.length && formDataEl.val()) {
                var formData = JSON.parse(formDataEl.val());

                // Load form fields
                if (formData.form_fields && formData.form_fields.length > 0) {
                    this.fields = formData.form_fields;
                    this.renderFields();
                }

                // Load form settings
                if (formData.form_settings) {
                    this.loadFormSettings(formData.form_settings);
                }

                // Load style settings
                if (formData.style_settings) {
                    this.loadStyleSettings(formData.style_settings);
                }

                // Load notification settings
                if (formData.notification_settings) {
                    this.loadNotificationSettings(formData.notification_settings);
                }
            } else {
                // Add default fields for new form
                this.addDefaultFields();
            }
        },

        addDefaultFields: function() {
            var defaultFields = [
                { type: 'text', label: 'Full Name', placeholder: 'Enter your full name', required: true, width: 100, show_label: true },
                { type: 'email', label: 'Email Address', placeholder: 'Enter your email', required: true, width: 50, show_label: true },
                { type: 'tel', label: 'Phone Number', placeholder: 'Enter your phone', required: false, width: 50, show_label: true },
                { type: 'date', label: 'Select Date', placeholder: 'Choose appointment date', required: true, width: 50, show_label: true },
                { type: 'time', label: 'Select Time', placeholder: 'Choose appointment time', required: true, width: 50, show_label: true },
                { type: 'textarea', label: 'Additional Notes', placeholder: 'Any special requests', required: false, width: 100, show_label: true }
            ];

            var self = this;
            jQuery.each(defaultFields, function(index, field) {
                field.id = 'field_' + self.getFieldShorthand(field.type);
                field.order = index + 1;
                self.fields.push(field);
            });

            this.renderFields();
        },

        getFieldShorthand: function(type) {
            var shorthands = {
                'text': 'name',
                'email': 'email',
                'tel': 'phone',
                'date': 'date',
                'time': 'time',
                'textarea': 'notes'
            };
            return shorthands[type] || type;
        },

        loadFormSettings: function(settings) {
            jQuery('#fb-setting-duration').val(settings.duration || 30);
            jQuery('#fb-setting-buffer').val(settings.buffer_time || 0);
            jQuery('#fb-setting-min-notice').val(settings.min_booking_notice || 24);
            jQuery('#fb-setting-max-advance').val(settings.max_booking_advance || 30);
            jQuery('#fb-setting-confirmation').val(settings.confirmation_message || '');
            jQuery('#fb-setting-success-action').val(settings.success_action || 'message');
            jQuery('#fb-setting-redirect-url').val(settings.redirect_url || '');

            if (settings.success_action === 'redirect') {
                jQuery('.fb-redirect-url-group').show();
            }
        },

        loadStyleSettings: function(styles) {
            jQuery('#fb-style-form-bg').val(styles.form_background || '#ffffff');
            jQuery('#fb-style-form-border-color').val(styles.form_border_color || '#e0e0e0');
            jQuery('#fb-style-form-border-width').val(styles.form_border_width || 1);
            jQuery('#fb-style-form-border-radius').val(styles.form_border_radius || 5);
            jQuery('#fb-style-form-padding').val(styles.form_padding || 20);

            jQuery('#fb-style-label-color').val(styles.label_color || '#333333');
            jQuery('#fb-style-label-font-size').val(styles.label_font_size || 14);
            jQuery('#fb-style-label-font-weight').val(styles.label_font_weight || '600');
            jQuery('#fb-style-label-font-family').val(styles.label_font_family || 'inherit');

            jQuery('#fb-style-field-bg').val(styles.field_background || '#ffffff');
            jQuery('#fb-style-field-border-color').val(styles.field_border_color || '#cccccc');
            jQuery('#fb-style-field-border-width').val(styles.field_border_width || 1);
            jQuery('#fb-style-field-border-radius').val(styles.field_border_radius || 3);
            jQuery('#fb-style-field-padding').val(styles.field_padding || 10);
            jQuery('#fb-style-field-color').val(styles.field_color || '#333333');
            jQuery('#fb-style-field-font-size').val(styles.field_font_size || 14);
            jQuery('#fb-style-field-font-family').val(styles.field_font_family || 'inherit');

            jQuery('#fb-style-button-bg').val(styles.button_background || '#0073aa');
            jQuery('#fb-style-button-color').val(styles.button_color || '#ffffff');
            jQuery('#fb-style-button-border-radius').val(styles.button_border_radius || 3);
            jQuery('#fb-style-button-padding').val(styles.button_padding || 12);
            jQuery('#fb-style-button-font-size').val(styles.button_font_size || 16);
            jQuery('#fb-style-button-font-weight').val(styles.button_font_weight || '600');
        },

        loadNotificationSettings: function(notifications) {
            if (notifications.customer_notification) {
                jQuery('#fb-notif-customer-enabled').prop('checked', notifications.customer_notification.enabled);
                jQuery('#fb-notif-customer-subject').val(notifications.customer_notification.subject || '');
                jQuery('#fb-notif-customer-message').val(notifications.customer_notification.message || '');
            }

            if (notifications.admin_notification) {
                jQuery('#fb-notif-admin-enabled').prop('checked', notifications.admin_notification.enabled);
                jQuery('#fb-notif-admin-subject').val(notifications.admin_notification.subject || '');
                jQuery('#fb-notif-admin-message').val(notifications.admin_notification.message || '');
            }
        },

        initDragAndDrop: function() {
            var self = this;

            // Make field types draggable
            jQuery('.fb-field-type').draggable({
                helper: 'clone',
                connectToSortable: '#fb-fields-container',
                revert: 'invalid',
                cursor: 'move'
            });

            // Make fields container sortable
            jQuery('#fb-fields-container').sortable({
                placeholder: 'fb-field-placeholder',
                handle: '.fb-field-handle',
                update: function(event, ui) {
                    self.updateFieldOrder();
                },
                receive: function(event, ui) {
                    var fieldType = ui.item.data('field-type');
                    ui.item.remove();
                    self.addField(fieldType);
                }
            });
        },

        addField: function(type) {
            this.fieldCounter++;

            var field = {
                id: 'field_' + this.fieldCounter,
                type: type,
                label: this.getDefaultLabel(type),
                placeholder: '',
                required: false,
                width: 100,
                order: this.fields.length + 1,
                show_label: true,
                options: []
            };

            this.fields.push(field);
            this.renderFields();
        },

        getDefaultLabel: function(type) {
            var labels = {
                'text': 'Text Field',
                'email': 'Email',
                'tel': 'Phone',
                'textarea': 'Textarea',
                'select': 'Dropdown',
                'radio': 'Radio Buttons',
                'checkbox': 'Checkboxes',
                'date': 'Date',
                'time': 'Time',
                'number': 'Number'
            };
            return labels[type] || 'Field';
        },

        renderFields: function() {
            var container = jQuery('#fb-fields-container');
            container.empty();

            var self = this;
            jQuery.each(this.fields, function(index, field) {
                container.append(self.renderFieldHTML(field, index));
            });
        },

        renderFieldHTML: function(field, index) {
            var html = '<div class="fb-field-item" data-index="' + index + '">';
            html += '<div class="fb-field-header">';
            html += '<span class="fb-field-handle dashicons dashicons-menu"></span>';
            html += '<span class="fb-field-type-icon dashicons ' + this.getFieldIcon(field.type) + '"></span>';
            html += '<span class="fb-field-title">' + field.label + '</span>';
            html += '<div class="fb-field-actions">';
            html += '<button type="button" class="fb-edit-field" data-index="' + index + '"><span class="dashicons dashicons-edit"></span></button>';
            html += '<button type="button" class="fb-delete-field" data-index="' + index + '"><span class="dashicons dashicons-trash"></span></button>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
            return html;
        },

        getFieldIcon: function(type) {
            var icons = {
                'text': 'dashicons-edit',
                'email': 'dashicons-email',
                'tel': 'dashicons-phone',
                'textarea': 'dashicons-text',
                'select': 'dashicons-arrow-down-alt2',
                'radio': 'dashicons-marker',
                'checkbox': 'dashicons-yes',
                'date': 'dashicons-calendar-alt',
                'time': 'dashicons-clock',
                'number': 'dashicons-calculator'
            };
            return icons[type] || 'dashicons-edit';
        },

        updateFieldOrder: function() {
            var self = this;
            var newFields = [];

            jQuery('#fb-fields-container .fb-field-item').each(function(index) {
                var oldIndex = jQuery(this).data('index');
                var field = self.fields[oldIndex];
                field.order = index + 1;
                newFields.push(field);
                jQuery(this).attr('data-index', index);
            });

            this.fields = newFields;
        },

        initFieldEditor: function() {
            var self = this;

            // Edit field
            jQuery(document).on('click', '.fb-edit-field', function() {
                var index = jQuery(this).data('index');
                self.openFieldEditor(index);
            });

            // Delete field
            jQuery(document).on('click', '.fb-delete-field', function() {
                var index = jQuery(this).data('index');
                if (confirm('Are you sure you want to delete this field?')) {
                    self.fields.splice(index, 1);
                    self.renderFields();
                }
            });
        },

        openFieldEditor: function(index) {
            // Create a modal for field editing
            var field = this.fields[index];

            var modalHTML = '<div id="fb-field-editor-modal" class="fb-modal">';
            modalHTML += '<div class="fb-modal-content">';
            modalHTML += '<span class="fb-modal-close">&times;</span>';
            modalHTML += '<h2>Edit Field</h2>';
            modalHTML += '<div class="fb-form-group">';
            modalHTML += '<label>Field Label</label>';
            modalHTML += '<input type="text" id="fb-edit-label" value="' + field.label + '">';
            modalHTML += '</div>';
            modalHTML += '<div class="fb-form-group">';
            modalHTML += '<label>Placeholder</label>';
            modalHTML += '<input type="text" id="fb-edit-placeholder" value="' + (field.placeholder || '') + '">';
            modalHTML += '</div>';
            modalHTML += '<div class="fb-form-group">';
            modalHTML += '<label>Field Width (%)</label>';
            modalHTML += '<input type="number" id="fb-edit-width" value="' + field.width + '" min="25" max="100" step="25">';
            modalHTML += '</div>';
            modalHTML += '<div class="fb-form-group">';
            modalHTML += '<label><input type="checkbox" id="fb-edit-required"' + (field.required ? ' checked' : '') + '> Required Field</label>';
            modalHTML += '</div>';
            modalHTML += '<div class="fb-form-group">';
            modalHTML += '<label><input type="checkbox" id="fb-edit-show-label"' + (field.show_label ? ' checked' : '') + '> Show Label</label>';
            modalHTML += '</div>';
            modalHTML += '<button type="button" class="button button-primary" id="fb-save-field-edit" data-index="' + index + '">Save Changes</button>';
            modalHTML += '</div>';
            modalHTML += '</div>';

            jQuery('body').append(modalHTML);

            // Close modal
            jQuery('.fb-modal-close, #fb-field-editor-modal').on('click', function(e) {
                if (e.target === this) {
                    jQuery('#fb-field-editor-modal').remove();
                }
            });

            // Save field edits
            var self = this;
            jQuery('#fb-save-field-edit').on('click', function() {
                var idx = jQuery(this).data('index');
                self.fields[idx].label = jQuery('#fb-edit-label').val();
                self.fields[idx].placeholder = jQuery('#fb-edit-placeholder').val();
                self.fields[idx].width = parseInt(jQuery('#fb-edit-width').val());
                self.fields[idx].required = jQuery('#fb-edit-required').is(':checked');
                self.fields[idx].show_label = jQuery('#fb-edit-show-label').is(':checked');

                self.renderFields();
                jQuery('#fb-field-editor-modal').remove();
            });
        },

        initTabs: function() {
            jQuery('.fb-tab-button').on('click', function() {
                var tab = jQuery(this).data('tab');

                jQuery('.fb-tab-button').removeClass('active');
                jQuery(this).addClass('active');

                jQuery('.fb-tab-content').removeClass('active');
                jQuery('[data-tab-content="' + tab + '"]').addClass('active');
            });
        },

        initSettings: function() {
            // Success action change
            jQuery('#fb-setting-success-action').on('change', function() {
                if (jQuery(this).val() === 'redirect') {
                    jQuery('.fb-redirect-url-group').show();
                } else {
                    jQuery('.fb-redirect-url-group').hide();
                }
            });
        },

        initSaveForm: function() {
            var self = this;

            jQuery('#fb-save-form').on('click', function() {
                self.saveForm();
            });
        },

        saveForm: function() {
            var formId = jQuery('#fb-form-id').val();
            var title = jQuery('#fb-form-title').val();
            var description = jQuery('#fb-form-description').val();

            if (!title) {
                alert('Please enter a form title');
                return;
            }

            // Collect form settings
            var formSettings = {
                duration: parseInt(jQuery('#fb-setting-duration').val()),
                buffer_time: parseInt(jQuery('#fb-setting-buffer').val()),
                min_booking_notice: parseInt(jQuery('#fb-setting-min-notice').val()),
                max_booking_advance: parseInt(jQuery('#fb-setting-max-advance').val()),
                confirmation_message: jQuery('#fb-setting-confirmation').val(),
                success_action: jQuery('#fb-setting-success-action').val(),
                redirect_url: jQuery('#fb-setting-redirect-url').val()
            };

            // Collect style settings
            var styleSettings = {
                form_background: jQuery('#fb-style-form-bg').val(),
                form_border_color: jQuery('#fb-style-form-border-color').val(),
                form_border_width: jQuery('#fb-style-form-border-width').val(),
                form_border_radius: jQuery('#fb-style-form-border-radius').val(),
                form_padding: jQuery('#fb-style-form-padding').val(),

                label_color: jQuery('#fb-style-label-color').val(),
                label_font_size: jQuery('#fb-style-label-font-size').val(),
                label_font_weight: jQuery('#fb-style-label-font-weight').val(),
                label_font_family: jQuery('#fb-style-label-font-family').val(),

                field_background: jQuery('#fb-style-field-bg').val(),
                field_border_color: jQuery('#fb-style-field-border-color').val(),
                field_border_width: jQuery('#fb-style-field-border-width').val(),
                field_border_radius: jQuery('#fb-style-field-border-radius').val(),
                field_padding: jQuery('#fb-style-field-padding').val(),
                field_color: jQuery('#fb-style-field-color').val(),
                field_font_size: jQuery('#fb-style-field-font-size').val(),
                field_font_family: jQuery('#fb-style-field-font-family').val(),

                button_background: jQuery('#fb-style-button-bg').val(),
                button_color: jQuery('#fb-style-button-color').val(),
                button_border_radius: jQuery('#fb-style-button-border-radius').val(),
                button_padding: jQuery('#fb-style-button-padding').val(),
                button_font_size: jQuery('#fb-style-button-font-size').val(),
                button_font_weight: jQuery('#fb-style-button-font-weight').val()
            };

            // Collect notification settings
            var notificationSettings = {
                customer_notification: {
                    enabled: jQuery('#fb-notif-customer-enabled').is(':checked'),
                    subject: jQuery('#fb-notif-customer-subject').val(),
                    message: jQuery('#fb-notif-customer-message').val()
                },
                admin_notification: {
                    enabled: jQuery('#fb-notif-admin-enabled').is(':checked'),
                    subject: jQuery('#fb-notif-admin-subject').val(),
                    message: jQuery('#fb-notif-admin-message').val()
                }
            };

            // Save form
            jQuery.ajax({
                url: fluentBookingAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fb_save_form',
                    form_id: formId,
                    title: title,
                    description: description,
                    form_fields: this.fields,
                    form_settings: formSettings,
                    style_settings: styleSettings,
                    notification_settings: notificationSettings,
                    nonce: fluentBookingAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(fluentBookingAdmin.strings.save_success);
                        window.location.href = 'admin.php?page=fluent-booking-forms';
                    } else {
                        alert(response.data.message || fluentBookingAdmin.strings.save_error);
                    }
                }
            });
        }
    };

    // Initialize on document ready
    jQuery(document).ready(function() {
        if (jQuery('#fb-form-builder').length) {
            FluentBookingFormBuilder.init();
        }
    });

})(jQuery);
