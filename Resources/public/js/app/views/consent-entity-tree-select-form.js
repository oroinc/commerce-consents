define(function(require) {
    'use strict';

    var ConsentEntityTreeSelectFormView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var ApiAccessor = require('oroui/js/tools/api-accessor');
    var LoadingMaskView = require('oroui/js/app/views/loading-mask-view');
    var EntityTreeSelectFormTypeComponent = require('oroform/js/app/components/entity-tree-select-form-type-component');

    /**
     * Extension for jsTree view from @entity-tree-select-form-type-component
     * Add new way for update and re-render tree from response data
     */
    ConsentEntityTreeSelectFormView = EntityTreeSelectFormTypeComponent.extend({
        optionNames: EntityTreeSelectFormTypeComponent.prototype.optionNames.concat([
            'updateApiAccessor', 'chooseWebCatalogMessage',
            'loadingMask', 'webCatalogId', 'webCatalogName'
        ]),

        /**
         * @property {Object}
         */
        updateApiAccessor: {
            http_method: 'GET',
            route: 'oro_api_consent_webcatalog_tree_get',
            routeQueryParameterNames: ['webCatalog']
        },

        /**
         * @property {String}
         */
        chooseWebCatalogMessage: __('oro.consent.jstree.please_choose_web_catlog'),

        /**
         * @property {View}
         */
        loadingMask: null,

        /**
         * @property {Number}
         */
        webCatalogId: null,

        /**
         * @property {String}
         */
        webCatalogName: null,

        /**
         * @constructor
         */
        constructor: function ConsentEntityTreeSelectFormView() {
            ConsentEntityTreeSelectFormView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @initialize
         *
         * @param {Object} options
         */
        initialize: function(options) {
            this.apiAccessor = new ApiAccessor(
                this.updateApiAccessor
            );
            ConsentEntityTreeSelectFormView.__super__.initialize.apply(this, arguments);

            this.loadingMask = new LoadingMaskView({
                container: this.$el
            });

            this.$fieldSelector.on('update:field', _.bind(this.updateTree, this));

            //TODO: Should refactor in BB-13929
            if (options.data === 'oro_consent_empty_web_catalog') {
                this.disableSearchField(true);
            }
        },

        /**
         * Update data from the server when field get update event
         *
         * @param {jQuery.Event} event
         * @returns {*}
         */
        updateTree: function(event) {
            this.webCatalogId = event.updatedData.id;
            this.webCatalogName = event.updatedData.name;

            if (_.isEmpty(this.webCatalogId)) {
                this.onDeselect();
                this.showSearchResultMessage(this.chooseWebCatalogMessage);
                this.disableSearchField(true);
                return;
            }

            this.loadingMask.show();
            this.apiAccessor.send({
                webCatalog: this.webCatalogId
            }).then(_.bind(this._updateTreeFromData, this));
        },

        /**
         * Update core config for the tree
         *
         * @param {Object} config
         */
        updateTreeCoreConfig: function(config) {
            this.jsTreeConfig.core = _.extend(this.jsTreeConfig.core, config);
        },

        /**
         * Disable/enable search field in the tree
         *
         * @param {Boolean} state
         */
        disableSearchField: function(state) {
            $(this.$searchField).prop('disabled', state);
        },

        /**
         * Re-render tree with new data
         *
         * @param {*} data
         * @private
         */
        _updateTreeFromData: function(data) {
            this.updateTreeCoreConfig({
                data: _.isString(data) ? JSON.parse(data) : data
            });

            if (!_.isEmpty(data)) {
                this.disableSearchField(false);
                this.render();
            } else {
                this.showSearchResultMessage(
                    __('oro.consent.jstree.web_catlog_is_empty', {webCatalog: this.webCatalogName})
                );
                this.onDeselect();
            }

            this.loadingMask.hide();
        }
    });

    return ConsentEntityTreeSelectFormView;
});
