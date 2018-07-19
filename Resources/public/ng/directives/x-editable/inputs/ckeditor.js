(function ($) {
    "use strict";

    var Ckeditor = function (options) {
        this.init('ckeditor', options, Ckeditor.defaults);
    };

    $.fn.editableutils.inherit(Ckeditor, $.fn.editabletypes.abstractinput);

    $.extend(Ckeditor.prototype, {
        render: function () {
            this.$input.attr('id', 'textarea_' + (new Date()).getTime());

            this.setClass();
            this.setAttr('placeholder');
            this.setAttr('rows');

            //ctrl + enter
            this.$input.keydown(function (e) {
                if (e.ctrlKey && e.which === 13) {
                    $(this).closest('form').submit();
                }
            });
        },

        input2value: function() {
            return CKEDITOR.instances[this.$input.attr('id')].getData();
        },

        activate: function () {
            CKEDITOR.replace(this.$input.attr('id'), this.options.config);
        },

        isEmpty: function($element) {
            if($.trim($element.html()) === '') {
                return true;
            } else if($.trim($element.text()) !== '') {
                return false;
            } else {
                return !$element.height() || !$element.width();
            }
        }
    });

    Ckeditor.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        /**
         * @property tpl
         * @default <textarea></textarea>
         *
         */
        tpl: '<textarea></textarea>',

        /**
         * @property inputclass
         * @default input-large
         **/
        inputclass: 'input-large',

        /**
         * Placeholder attribute of input. Shown when input is empty.
         *
         * @property placeholder
         * @type string
         * @default null
         **/
        placeholder: null,

        /**
         * Number of rows in textarea
         *
         * @property rows
         * @type integer
         * @default 7
         **/
        rows: 7,

        /**
         * Ckeditor config
         *
         * @property config
         * @type object
         * @default {}
         **/
        config: {}
    });

    $.fn.editabletypes.ckeditor = Ckeditor;

}(window.jQuery));
