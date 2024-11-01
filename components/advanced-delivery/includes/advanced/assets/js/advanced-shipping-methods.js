/* global shippingZoneMethodsLocalizeScript, ajaxurl */
(function ($, data, wp, ajaxurl) {
    $(function () {
        var $table = $('.wdad-advanced-shipping-methods'),
            $tbody = $('.wdad-advanced-shipping-methods-rows'),
            $save_button = $('.woocommerce-save-button'),
            $row_template = wp.template('wdad-advanced-shipping-method-row'),
            $blank_template = wp.template('wdad-advanced-shipping-method-row-blank');

        // Backbone model
        var ShippingMethod = Backbone.Model.extend({
            methods: {},
            changes: {},
            save: function () {
                $.post(
                    ajaxurl + (ajaxurl.indexOf('?') > 0 ? '&' : '?') + 'action=wdad_shipping_methods_save_changes',
                    {
                        wdad_shipping_method_nonce: data.wdad_shipping_method_nonce,
                        methods: conditions_field.val(),
                        condition: this.methods,
                    },
                    this.onSaveResponse,
                    'json'
                );
            },
            onSaveResponse: function (response, textStatus) {
                if ('success' === textStatus) {
                    if (response.success) {
                        shippingMethod.set('methods', response.data.methods);
                        shippingMethod.changes = {};
                        shippingMethod.trigger('saved:methods');
                    } else {
                        window.alert(data.strings.save_failed);
                    }
                }
            }
        });

        // Backbone view
        var ShippingMethodView = Backbone.View.extend({
            rowTemplate: $row_template,
            initialize: function () {
                //this.listenTo(this.model, 'change:methods', this.setUnloadConfirmation);
                //this.listenTo(this.model, 'saved:methods', this.clearUnloadConfirmation);
                this.listenTo(this.model, 'saved:methods', this.render);
                //$(window).on('beforeunload', {view: this}, this.unloadConfirmation);
                $save_button.on('click', {view: this}, this.onSubmit);

                $(document.body).on('click', '.wdad-advanced-shipping-add-method', {view: this}, this.onAddShippingMethod);
                $(document.body).on('click', '.wdad-advanced-shipping-edit-method', {view: this}, this.onEditShippingMethod);
                $(document.body).on('click', '.wdad-advanced-shipping-delete-method', {view: this}, this.onDeleteRow);
                $(document.body).on('wc_backbone_modal_response', this.onEditShippingMethodSubmitted);
                $(document.body).on('wc_backbone_modal_response', this.onAddShippingMethodSubmitted);
            },
            block: function () {
                $(this.el).block({
                    message: null,
                    overlayCSS: {
                        background: '#fff',
                        opacity: 0.6
                    }
                });
            },
            unblock: function () {
                $(this.el).unblock();
            },
            render: function () {
                var methods = this.model.get('methods'),
                    view = this;

                // Blank out the contents.
                this.$el.empty();
                this.unblock();

                if (_.size(methods)) {
                    // Populate $tbody with the current methods
                    $.each(methods, function (id, rowData) {
                        rowData.ID = id;
                        view.$el.append(view.rowTemplate(rowData));
                    });

                    // Make the rows function
                    this.$el.find('.wdad-advanced-shipping-delete-method').on('click', {view: this}, this.onDeleteRow);
                } else {
                    view.$el.append($blank_template);
                }

                this.initTooltips();
            },
            initTooltips: function () {
                $('#tiptip_holder').removeAttr('style');
                $('#tiptip_arrow').removeAttr('style');
                $('.tips').tipTip({'attribute': 'data-tip', 'fadeIn': 50, 'fadeOut': 50, 'delay': 50});
            },
            onSubmit: function (event) {
                event.data.view.block();
                //event.data.view.model.save();
                //event.preventDefault();
            },
            onDeleteRow: function (event) {
                event.preventDefault();

                var view = event.data.view,
                    model = view.model,
                    methods = model.get('methods'),
                    changes = {},
                    id = $(this).closest('tr').data('id');

                methods.splice(id, 1);
                changes.methods = changes.methods || {methods: {}};
                changes.methods[id] = _.extend(changes.methods[id] || {}, {deleted: 'deleted'});
                model.set('methods', methods);

                conditions_field.val(JSON.stringify(methods));
                view.render();
            },
            showErrors: function (errors) {
                var error_html = '<div id="woocommerce_errors" class="error notice is-dismissible">';

                $(errors).each(function (index, value) {
                    error_html = error_html + '<p>' + value + '</p>';
                });
                error_html = error_html + '</div>';

                $table.before(error_html);
            },
            onAddShippingMethod: function (event) {
                event.preventDefault();

                $(this).WCBackboneModal({
                    template: 'wdad-modal-add-shipping-method',
                    variable: {}
                });
            },
            onEditShippingMethod: function (event) {
                event.preventDefault();
                var id = $(this).closest('tr').attr('data-id');
                $(this).WCBackboneModal({
                    template: 'wdad-modal-edit-shipping-method-' + id,
                    variable: {}
                });
            },
            onAddShippingMethodSubmitted: function (event, target, posted_data) {
                if ('wdad-modal-add-shipping-method' === target) {
                    shippingMethodView.block();

                    // Add method via ajax call
                    $.post(ajaxurl, {
                        action: 'wdad_shipping_methods_save_changes',
                        wdad_shipping_method_nonce: data.wdad_shipping_method_nonce,
                        methods: conditions_field.val(),
                        condition: JSON.stringify(posted_data)
                    }, function (response, textStatus) {
                        if ('success' === textStatus && response.success) {
                            conditions_field.val(JSON.stringify(response.data.methods));
                            // Trigger save if there are changes, or just re-render
                            if (_.size(shippingMethodView.model.changes)) {
                                shippingMethodView.model.save();
                            } else {
                                shippingMethodView.model.set('methods', response.data.methods);
                                shippingMethodView.model.trigger('change:methods');
                                shippingMethodView.model.changes = {};
                                shippingMethodView.model.trigger('saved:methods');
                            }
                        }
                        shippingMethodView.unblock();
                    }, 'json');
                }
            },
            onEditShippingMethodSubmitted: function (event, target, posted_data) {
                if (target.indexOf('wdad-modal-edit-shipping-method') === 0) {
                    shippingMethodView.block();

                    var id = Number(target.slice('wdad-modal-edit-shipping-method-'.length));
                    // edit method via ajax call
                    $.post(ajaxurl, {
                        action: 'wdad_shipping_methods_save_changes',
                        wdad_shipping_method_nonce: data.wdad_shipping_method_nonce,
                        methods: conditions_field.val(),
                        condition: JSON.stringify(posted_data),
                        id: id
                    }, function (response, textStatus) {
                        if ('success' === textStatus && response.success) {
                            conditions_field.val(JSON.stringify(response.data.methods));
                            // Trigger save if there are changes, or just re-render
                            if (_.size(shippingMethodView.model.changes)) {
                                shippingMethodView.model.save();
                            } else {
                                shippingMethodView.model.set('methods', response.data.methods);
                                shippingMethodView.model.trigger('change:methods');
                                shippingMethodView.model.changes = {};
                                shippingMethodView.model.trigger('saved:methods');
                            }
                        }
                        shippingMethodView.unblock();
                    }, 'json');
                }
            },
        });

        var conditions_field = $('input#' + data.conditions_id);

        var shippingMethod = new ShippingMethod({
            methods: JSON.parse(conditions_field.val())
        });

        var shippingMethodView = new ShippingMethodView({
            model: shippingMethod,
            el: $tbody
        });
    });

    $(function () {
        var wdad = data;

        // Update condition values
        $(document.body).on('change', '.wdad-condition', function () {

            var loading_wrap = '<span style="width: auto; border: 1px solid transparent; display: inline-block;">&nbsp;</span>';
            var data = {
                action: wdad.action_prefix + 'update_condition_value',
                id: $(this).attr('data-id'),
                group: $(this).attr('data-group'),
                condition: $(this).val(),
                nonce: wdad.nonce
            };
            var condition_group = $(this).parents('.wdad-conditions').find('.wdad-condition-group-' + data.group);
            var replace = '.wdad-value-wrap-' + data.id;

            condition_group.find(replace + ' > *').attr("style", "visibility:hidden;");
            // Loading icon
            condition_group.find(replace).append(loading_wrap).block({
                message: null,
                overlayCSS: {background: '', opacity: 0.6},
            });

            // Replace value field
            $.post(ajaxurl, data, function (response) {
                condition_group.find(replace).replaceWith(response);
                $(document.body).trigger('wc-enhanced-select-init');
            });

            // Update condition description
            var description = {
                action: wdad.action_prefix + 'update_condition_description',
                condition: data.condition,
                nonce: wdad.nonce
            };

            $.post(ajaxurl, description, function (description_response) {
                condition_group.find(replace + ' ~ .wdad-description').replaceWith(description_response);
                // Tooltip
                $('.tips, .help_tip, .woocommerce-help-tip').tipTip({
                    'attribute': 'data-tip',
                    'fadeIn': 50,
                    'fadeOut': 50,
                    'delay': 200
                });
            })

        });

        // Add condition
        $(document.body).on('click', '.wdad-condition-and-add', function () {

            var $this = $(this);
            var data = {
                action: wdad.action_prefix + 'add_condition',
                group: $(this).attr('data-group'),
                id: $(this).attr('data-id'),
                nonce: wdad.nonce
            };
            var condition_group = $this.parents('.wdad-conditions').find('.wdad-condition-group-' + data.group);

            var loading_icon = '<div class="wdad-condition-wrap loading"></div>';
            condition_group.append(loading_icon).children(':last').block({
                message: null,
                overlayCSS: {background: '', opacity: 0.6}
            });

            $.post(ajaxurl, data, function (response) {
                $this.hide();
                condition_group.find(' .wdad-condition-wrap.loading').first().replaceWith(function () {
                    return $(response).hide().fadeIn('normal');
                });
            });

        });

        // Delete condition
        $(document.body).on('click', '.wdad-condition-delete', function () {

            if ($(this).closest('.wdad-condition-group').children('.wdad-condition-wrap').length === 1) {
                $(this).closest('.wdad-condition-group').fadeOut('normal', function () {
                    $(this).next('.or-text').remove();
                    $(this).remove();
                });
            } else {
                $(this).closest('.wdad-condition-wrap').slideUp('fast', function () {
                    $(this).remove();
                });
            }

        });

        // Add condition group
        $(document.body).on('click', '.wdad-condition-or-add', function () {

            var new_group_id = parseInt($('.wdad-condition-group').last().attr('data-group')) + 1;
            var condition_group_loading = '<div class="wdad-condition-group wdad-condition-group-' + new_group_id + ' loading" data-group="' + new_group_id + '"></div>';
            var conditions = $(this).parent().prev('.wdad-conditions');
            var data = {
                action: wdad.action_prefix + 'add_condition_group',
                group: new_group_id,
                nonce: wdad.nonce
            };

            // Display loading icon
            conditions.append(condition_group_loading).children(':last').block({
                message: null,
                overlayCSS: {background: '', opacity: 0.6}
            });

            // Insert condition group
            $.post(ajaxurl, data, function (response) {
                conditions.find('.wdad-condition-group.loading').first().replaceWith(function () {
                    return $(response).hide().fadeIn('normal');
                });
            });

        });

        // Sortable
        $('.wdad-conditions-post-table tbody').sortable({
            items: 'tr',
            handle: '.sort',
            cursor: 'move',
            axis: 'y',
            scrollSensitivity: 40,
            forcePlaceholderSize: true,
            helper: 'clone',
            opacity: 0.65,
            placeholder: 'wc-metabox-sortable-placeholder',
            start: function (event, ui) {
                ui.item.css('background-color', '#f6f6f6');
            },
            stop: function (event, ui) {
                ui.item.removeAttr('style');
            },
            update: function (event, ui) {

                $table = $(this).closest('table');
                $table.block({message: null, overlayCSS: {background: '#fff', opacity: 0.6}});
                // Update fee order
                var data = {
                    action: wdad.action_prefix + 'save_post_order',
                    form: $(this).closest('form').serialize(),
                    nonce: wdad.nonce
                };

                $.post(ajaxurl, data, function (response) {
                    $('.wdad-conditions-post-table tbody tr:even').addClass('alternate');
                    $('.wdad-conditions-post-table tbody tr:odd').removeClass('alternate');
                    $table.unblock();
                });
            }
        });


        // Toggle list table rows on small screens
        $('#advanced_shipping_shipping_methods').on('click', '.toggle-row', function () {
            $(this).closest('tr').toggleClass('is-expanded');
        });

    });
})(jQuery, shippingZoneMethodsLocalizeScript, wp, ajaxurl);
