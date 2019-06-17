/* global jQuery, yii: false */
window.sa = {};
window.sa.kanban = (function ($) {
    var pub = {
        isActive: true,

        init: function (module) {
            $('#taskModal').on('show.bs.modal', function (evt) {
                var link = $(evt.relatedTarget);
                var href = link.prop('href');

                var modal = $(this);
                modal.find('.modal-content').load(href);
            });
        }
    };

    return pub;
})(window.jQuery);

window.yii.initModule(window.sa.kanban);
