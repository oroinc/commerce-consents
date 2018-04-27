define(function(require) {
    'use strict';

    var ConsentDragNDropSorting;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var DragNDropSorting = require('oroui/js/drag-n-drop-sorting');

    //TODO: Should refactor in BB-13929
    ConsentDragNDropSorting = DragNDropSorting.extend({
        $control: null,

        $consentSelect: null,

        $useDefaultCheckbox: null,

        $addConsent: null,

        sortableListSelector: '.sortable-wrapper',

        /**
         * @inheritDoc
         */
        constructor: function ConsentDragNDropSorting() {
            ConsentDragNDropSorting.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.$control = this.$el.closest('.control-group');
            this.$consentSelect = this.$el.find('input[type="hidden"]');
            this.$useDefaultCheckbox = this.$control.find('[data-role="changeUseDefault"]');
            this.$addConsent = this.$control.find('.add-list-item');
            this.$sortableList = this.$el.find(this.sortableListSelector);
            ConsentDragNDropSorting.__super__.initialize.apply(this, arguments);

            this._bindEvents();
        },

        initSortable: function() {
            ConsentDragNDropSorting.__super__.initSortable.call(this);

            this._updateFormItem();
            this.initAddButton();
        },

        initAddButton: function() {
            var isChecked = this.$useDefaultCheckbox.is(':checked');
            this.$addConsent
                .prop('disabled', isChecked)
                .attr('disabled', isChecked)
                .trigger(isChecked ? 'disable' : 'enable')
                .inputWidget('refresh');
        },

        /**
         *  Listener for removing validation errors
         */
        onClearValidation: function() {
            this.$consentSelect.on('change', function(e) {
                $(this).closest('td').find('.validation-failed').remove();
                $(this).closest('.error').removeClass('error');
            });
        },

        /**
         * Create listener
         *
         * @private
         */
        _bindEvents: function() {
            this.onClearValidation();
            this.$sortableList.on('content:changed', _.bind(this._updateFormItem, this));
            this.$useDefaultCheckbox.on('change', _.bind(this._updateFormItem, this));
            mediator.on('page:afterChange', function() {
                this.$useDefaultCheckbox.trigger('change');
            }, this);
        },

        /**
         * Update component view after change Use Default checkbox
         *
         * @private
         */
        _updateFormItem: function() {
            var isChecked = this.$useDefaultCheckbox.is(':checked');
            this.$sortableList.sortable(isChecked ? 'disable' : 'enable');
            this.$el.toggleClass('disabled', isChecked);
        }
    });

    return ConsentDragNDropSorting;
});
