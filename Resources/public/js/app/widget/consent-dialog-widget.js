define(function(require) {
    'use strict';

    var ConsentDialogWidget;
    var $ = require('jquery');
    var _ = require('underscore');
    var DialogWidget = require('oro/dialog-widget');
    var actionsTemplate = require('tpl!oroconsent/templates/consent-dialog-widget-actions.html');

    ConsentDialogWidget = DialogWidget.extend({
        optionNames: DialogWidget.prototype.optionNames.concat([
            'actionsTemplate', 'simpleActionTemplate', 'contentElement'
        ]),

        /**
         * @property {String}
         */
        actionsTemplate: actionsTemplate,

        /**
         * @property {String}
         */
        contentElement: 'section.page-content',

        /**
         * @property {Boolean}
         */
        simpleActionTemplate: false,

        /**
         * @inheritDoc
         */
        constructor: function ConsentDialogWidget() {
            return ConsentDialogWidget.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            _.extend(options, {
                stateEnabled: false,
                incrementalPosition: false,
                dialogOptions: {
                    modal: true,
                    resizable: true,
                    autoResize: true,
                    width: 800,
                    dialogClass: 'consent-dialog-widget',
                    close: _.bind(this._onClose, this)
                }
            });

            ConsentDialogWidget.__super__.initialize.apply(this, arguments);
        },

        prepareContentRequestOptions: function(data, method, url) {
            var options = ConsentDialogWidget.__super__.prepareContentRequestOptions.apply(this, arguments);
            options.data = '';
            return options;
        },

        _onContentLoad: function(content) {
            content = $(content).find(this.contentElement).addClass('widget-content');

            content.append(this.actionsTemplate({
                simpleActionTemplate: this.simpleActionTemplate
            }));

            content = content.parent().html();
            return ConsentDialogWidget.__super__._onContentLoad.call(this, content);
        },

        _onAdoptedFormSubmitClick: function() {
            this.trigger('acceptSelected');
            this.dispose();
        },

        _onAdoptedFormResetClick: function() {
            this.trigger('cancelSelected');
            this.dispose();
        },

        _onClose: function() {
            this.trigger('dialogClose');
        }
    });

    return ConsentDialogWidget;
});
