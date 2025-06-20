// This file is part of Moodle - http://moodle.org/

define(['jquery', 'jqueryui'], function($) {
    'use strict';

    var FormBuilder = {
        fieldCounter: 0,
        selectedField: null,

        init: function() {
            this.initDragDrop();
            this.initFieldPalette();
            this.initFormCanvas();
            this.bindEvents();
            this.loadExistingForm();
        },

        initDragDrop: function() {
            // Make field types draggable
            $('.field-type').draggable({
                helper: 'clone',
                connectToSortable: '#form-fields',
                start: function(event, ui) {
                    ui.helper.addClass('dragging');
                }
            });

            // Make form canvas sortable
            $('#form-fields').sortable({
                placeholder: 'field-placeholder',
                items: '.form-field',
                receive: function(event, ui) {
                    var fieldType = ui.item.data('type');
                    FormBuilder.createField(fieldType, ui.item);
                },
                update: function(event, ui) {
                    FormBuilder.updateFormData();
                }
            });
        },

        initFieldPalette: function() {
            $('.field-type').on('click', function() {
                var fieldType = $(this).data('type');
                FormBuilder.addFieldToCanvas(fieldType);
            });
        },

        initFormCanvas: function() {
            // Hide drop zone when fields are present
            this.toggleDropZone();
        },

        bindEvents: function() {
            var self = this;
            
            // Field selection
            $(document).on('click', '.form-field', function(e) {
                e.stopPropagation();
                self.selectField($(this));
            });

            // Field deletion
            $(document).on('click', '.delete-field', function(e) {
                e.stopPropagation();
                self.deleteField($(this).closest('.form-field'));
            });

            // Property updates
            $(document).on('change', '#properties-content input, #properties-content textarea, #properties-content select', function() {
                self.updateFieldProperties();
            });

            // Form submission
            $('form').on('submit', function() {
                self.updateFormData();
            });
        },

        addFieldToCanvas: function(fieldType) {
            var field = this.createFieldElement(fieldType);
            $('#form-fields').append(field);
            this.toggleDropZone();
            this.selectField(field);
            this.updateFormData();
        },

        createField: function(fieldType, element) {
            var field = this.createFieldElement(fieldType);
            element.replaceWith(field);
            this.toggleDropZone();
            this.selectField(field);
            this.updateFormData();
        },

        createFieldElement: function(fieldType) {
            this.fieldCounter++;
            var fieldId = 'field_' + this.fieldCounter;
            var fieldHtml = this.getFieldHtml(fieldType, fieldId);
            
            var field = $('<div class="form-field" data-type="' + fieldType + '" data-id="' + fieldId + '">' +
                '<div class="field-controls">' +
                '<span class="drag-handle">⋮⋮</span>' +
                '<button type="button" class="delete-field btn btn-sm btn-danger">×</button>' +
                '</div>' +
                fieldHtml +
                '</div>');

            return field;
        },

        getFieldHtml: function(fieldType, fieldId) {
            switch (fieldType) {
                case 'text':
                    return '<label>Text Input</label><input type="text" class="form-control" placeholder="Enter text" disabled>';
                case 'textarea':
                    return '<label>Multi-line Text</label><textarea class="form-control" rows="3" placeholder="Enter text" disabled></textarea>';
                case 'select':
                    return '<label>Dropdown</label><select class="form-control" disabled><option>Option 1</option><option>Option 2</option></select>';
                case 'checkbox':
                    return '<div class="form-check"><input type="checkbox" class="form-check-input" disabled><label class="form-check-label">Checkbox</label></div>';
                case 'radio':
                    return '<div><label>Radio Button</label><div class="form-check"><input type="radio" name="' + fieldId + '" class="form-check-input" disabled><label class="form-check-label">Option 1</label></div></div>';
                case 'file':
                    return '<label>File Upload</label><input type="file" class="form-control" disabled>';
                case 'email':
                    return '<label>Email</label><input type="email" class="form-control" placeholder="Enter email" disabled>';
                case 'number':
                    return '<label>Number</label><input type="number" class="form-control" placeholder="Enter number" disabled>';
                case 'date':
                    return '<label>Date</label><input type="date" class="form-control" disabled>';
                case 'heading':
                    return '<h3 contenteditable="true">Heading Text</h3>';
                case 'paragraph':
                    return '<p contenteditable="true">Paragraph text goes here.</p>';
                default:
                    return '<label>Unknown Field</label><input type="text" class="form-control" disabled>';
            }
        },

        selectField: function(field) {
            $('.form-field').removeClass('selected');
            field.addClass('selected');
            this.selectedField = field;
            this.showFieldProperties(field);
        },

        deleteField: function(field) {
            if (confirm('Are you sure you want to delete this field?')) {
                field.remove();
                this.toggleDropZone();
                this.clearProperties();
                this.updateFormData();
            }
        },

        showFieldProperties: function(field) {
            var fieldType = field.data('type');
            var fieldId = field.data('id');
            var propertiesHtml = this.getPropertiesHtml(fieldType, field);
            $('#properties-content').html(propertiesHtml);
        },

        getPropertiesHtml: function(fieldType, field) {
            var label = field.find('label').first().text() || 'Field Label';
            var required = field.hasClass('required') ? 'checked' : '';
            
            var html = '<div class="form-group">' +
                '<label>Field Label</label>' +
                '<input type="text" id="field-label" class="form-control" value="' + label + '">' +
                '</div>';

            if (!['heading', 'paragraph'].includes(fieldType)) {
                html += '<div class="form-group">' +
                    '<label><input type="checkbox" id="field-required" ' + required + '> Required</label>' +
                    '</div>';
                
                html += '<div class="form-group">' +
                    '<label>Placeholder Text</label>' +
                    '<input type="text" id="field-placeholder" class="form-control">' +
                    '</div>';
                
                html += '<div class="form-group">' +
                    '<label>Help Text</label>' +
                    '<textarea id="field-helptext" class="form-control" rows="2"></textarea>' +
                    '</div>';
            }

            if (['select', 'radio', 'checkbox'].includes(fieldType)) {
                html += '<div class="form-group">' +
                    '<label>Options (one per line)</label>' +
                    '<textarea id="field-options" class="form-control" rows="3">Option 1\nOption 2\nOption 3</textarea>' +
                    '</div>';
            }

            return html;
        },

        updateFieldProperties: function() {
            if (!this.selectedField) return;

            var label = $('#field-label').val();
            var required = $('#field-required').is(':checked');
            var placeholder = $('#field-placeholder').val();
            var helptext = $('#field-helptext').val();
            var options = $('#field-options').val();

            // Update field label
            this.selectedField.find('label').first().text(label);
            
            // Update required status
            this.selectedField.toggleClass('required', required);
            
            // Store properties as data attributes
            this.selectedField.attr('data-label', label);
            this.selectedField.attr('data-required', required);
            this.selectedField.attr('data-placeholder', placeholder);
            this.selectedField.attr('data-helptext', helptext);
            if (options) {
                this.selectedField.attr('data-options', options);
            }

            this.updateFormData();
        },

        clearProperties: function() {
            $('#properties-content').html('Select a field to edit its properties');
            this.selectedField = null;
        },

        toggleDropZone: function() {
            var hasFields = $('#form-fields .form-field').length > 0;
            $('#form-fields .drop-zone').toggle(!hasFields);
        },

        updateFormData: function() {
            var formData = {
                fields: []
            };

            $('#form-fields .form-field').each(function() {
                var field = $(this);
                var options = field.attr('data-options');
                var fieldData = {
                    id: field.data('id'),
                    type: field.data('type'),
                    label: field.attr('data-label') || field.find('label').first().text(),
                    required: field.attr('data-required') === 'true',
                    placeholder: field.attr('data-placeholder') || '',
                    helptext: field.attr('data-helptext') || ''
                };

                if (options) {
                    fieldData.options = options.split('\n').filter(function(option) {
                        return option.trim() !== '';
                    });
                }

                formData.fields.push(fieldData);
            });

            $('input[name="formdata"]').val(JSON.stringify(formData));
        },

        loadExistingForm: function() {
            var formData = $('input[name="formdata"]').val();
            if (formData) {
                try {
                    var data = JSON.parse(formData);
                    if (data.fields) {
                        var self = this;
                        data.fields.forEach(function(fieldData) {
                            var field = self.createFieldElement(fieldData.type);
                            field.attr('data-id', fieldData.id);
                            field.attr('data-label', fieldData.label);
                            field.attr('data-required', fieldData.required);
                            field.attr('data-placeholder', fieldData.placeholder);
                            field.attr('data-helptext', fieldData.helptext);
                            
                            if (fieldData.options) {
                                field.attr('data-options', fieldData.options.join('\n'));
                            }

                            // Update field display
                            field.find('label').first().text(fieldData.label);
                            field.toggleClass('required', fieldData.required);
                            
                            $('#form-fields').append(field);
                        });
                        self.toggleDropZone();
                    }
                } catch (e) {
                    console.error('Error loading form data:', e);
                }
            }
        }
    };

    return {
        init: function() {
            FormBuilder.init();
        }
    };
});
