/* global jQuery, yii, kanbanBaseUrl: false */
window.sa = {};
window.sa.kanban = (function ($, baseUrl) {
    var pub = {
        isActive: true,

        init: function (module) {
            $('#taskModal').on('show.bs.modal', function (evt) {
                var link = $(evt.relatedTarget);
                var href = link.prop('href');

                var modal = $(this);
                modal.find('.modal-content').load(href);
            });

            initTask();
            initSortable();
            initChecklist();
        }
    };

    function initTask()
    {
        $('[data-toggle="tooltip"]').tooltip()
    }

    function initSortable()
    {
        $('.kanban-tasks').sortable({
            items: '> .kanban-sortable',
            connectWith: '.kanban-tasks',
            update: function (event, ui) {
                var $element = ui.item;
                var $newParent = $element.closest('.kanban-bucket');
                var $before = $element.prev('.kanban-sortable');
                var action = 'move-after';
                var pk = null;

                if (!$before.length) {
                    action = 'move-as-first';
                } else {
                    pk = $before.data('id');
                }

                $.post(baseUrl + '/sort/' + action, {
                    modelClass: 'simialbi\\yii2\\kanban\\models\\Task',
                    modelPk: $element.data('id'),
                    pk: pk
                }, function (data) {
                    console.log(data);
                });
            }
        });
    }

    function initChecklist()
    {
        $(document).on('change.sa.kanban', '.checklist input[type="text"]', function (evt) {
            var $this = $(this);
            var $checklist = $(this).closest('.checklist');

            if ($this.closest('.add-checklist-element').length) {
                if (evt.type === 'keyup') {
                    if (evt.which === 13) {
                        evt.preventDefault();
                    } else {
                        return;
                    }
                }

                var $addElement = $this.closest('.add-checklist-element').clone(),
                    $inputGroup = $this.closest('.input-group'),
                    $buttonDelete = $(
                        '<button class="btn btn-outline-danger remove-checklist-element">' +
                        '<i class="fas fa-trash-alt"></i>' +
                        '</button>'
                    );
                $this.closest('.add-checklist-element').removeClass('add-checklist-element');
                $this.attr('placeholder', $this.val());

                $inputGroup.append($('<div class="input-group-append" />').append($buttonDelete));

                $addElement.find('input[type="text"]').val('');

                $checklist.append($addElement);
            } else {
                if ($this.val() === '') {
                    $this.val($this.attr('placeholder'));
                } else {
                    $this.attr('placeholder', $this.val());
                }
            }
        });
        $(document).on('click.sa.kanban', '.checklist .remove-checklist-element', function () {
            $(this).closest('.input-group').remove();
        });
        $(document).on('click.sa.kanban', '.checklist input[type="checkbox"]', function () {
            var $this = $(this);
            if ($this.closest('.add-checklist-element').length) {
                $this.prop('checked', false);
                return;
            }

            if ($this.is(':checked')) {
                $this.closest('.input-group').find('input[type="text"]').css('text-decoration', 'line-through');
            } else {
                $this.closest('.input-group').find('input[type="text"]').css('text-decoration', 'none');
            }
        })
    }

    return pub;
})(window.jQuery, kanbanBaseUrl);

window.jQuery(function () {
    window.yii.initModule(window.sa.kanban);
});
