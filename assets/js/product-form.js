(function ($) { "use strict";

    var ProductForm = function() {
        this.initLayout();
    }

    ProductForm.prototype.initLayout = function() {
        $('#Form-secondaryTabs .tab-pane.layout-cell')
            .addClass('padded-pane');
    }

    $(document).ready(function() {
        var form = new ProductForm();

        if ($.oc === undefined) {
            $.oc = {}
        }

        $.oc.shopProductForm = form;
    });

})(window.jQuery);
