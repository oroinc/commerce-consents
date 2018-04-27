define(function(require) {
    'use strict';

    var ConsentsGroupView;
    var BaseView = require('oroui/js/app/views/base/view');
    var $ = require('jquery');
    var _ = require('underscore');
    var Modal = require('oroui/js/modal');

    ConsentsGroupView = BaseView.extend({
        confirmModal: Modal,

        confirmModalTitle: _.__('oro.consent.frontend.confirm_modal.title'),

        confirmModalContent: _.__('oro.consent.frontend.confirm_modal.message'),

        confirmModalOkText: _.__('oro.consent.frontend.confirm_modal.ok'),

        confirmModalCancelText: _.__('oro.consent.frontend.confirm_modal.cancel'),

        confirmModalOkButtonClass: 'btn ok',

        confirmModalCancelButtonClass: 'btn cancel btn--info',

        /**
         * @inheritDoc
         */
        constructor: function ConsentsGroupView() {
            ConsentsGroupView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @constructor
         */
        initialize: function() {
            this.initModal();
            this.$form = this.$el.closest('form');
            this.$consents = this.$el.find('[data-role="consent-checkbox"]');
            this.delegateEvents();
            this._removeValidationRules();

            ConsentsGroupView.__super__.initialize.apply(this, arguments);
        },

        initModal: function() {
            this.confirmModal = new this.confirmModal({
                title: this.confirmModalTitle,
                content: this.confirmModalContent,
                okText: this.confirmModalOkText,
                cancelText: this.confirmModalCancelText,
                okButtonClass: this.confirmModalOkButtonClass,
                cancelButtonClass: this.confirmModalCancelButtonClass
            });

            this.listenTo(this.confirmModal, 'ok', _.bind(this._onModalConfirmed, this));
        },

        delegateEvents: function() {
            ConsentsGroupView.__super__.delegateEvents.apply(this, arguments);

            if (this.$form && this.$form.length) {
                this.$form.on('submit' + this.eventNamespace(), _.bind(this._onFormSubmit, this));
            }
        },

        undelegateEvents: function() {
            if (this.$form && this.$form.length) {
                this.$form.off(this.eventNamespace());
            }

            ConsentsGroupView.__super__.undelegateEvents.apply(this, arguments);
        },

        /**
         * Remove base validation rules for required consent
         */
        _removeValidationRules: function() {
            this.$consents.removeAttr('data-validation');
        },

        _onFormSubmit: function(event) {
            var needConfirm = this.$consents.filter(function() {
                return this.defaultChecked && $(this).is(':not(:checked)');
            });

            if (needConfirm.length) {
                event.preventDefault();
                this.confirmModal.open();
            }
        },

        _onModalConfirmed: function() {
            this.undelegateEvents();
            this.$form.submit();

            this.delegateEvents();
        },

        dispose: function() {
            this.undelegateEvents();

            delete this.$form;
            delete this.$consents;

            ConsentsGroupView.__super__.dispose.apply(this, arguments);
        }
    });

    return ConsentsGroupView;
});
