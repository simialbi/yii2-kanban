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
        },
        addAssignee: function (id) {
            var $this = $(this);
            var $assignees = $this.closest('.kanban-task-assignees').find('.dropdown-toggle');
            var name = $this.data('name'),
                image = $this.data('image');
            var img;
            if (image) {
                img = '<img src="' + image + '" class="rounded-circle mr-1" alt="' + name + '" title="' + name + '">';
            } else {
                img = '<span class="kanban-visualisation mr-1" title="' + name + '">' +
                    name.substr(0, 1).toUpperCase() +
                    '</span>';
            }
            var $assignee = $(
                '<span class="kanban-user" data-id="' + id + '">' +
                '<input type="hidden" name="assignees[]" value="' + id + '">' +
                img +
                '</span>'
            );
            $assignees.append($assignee);

            $this.removeClass('d-flex').addClass('d-none');
            $this.closest('.dropdown-menu').find('.remove-assignee[data-id="' + id + '"]')
                .removeClass('d-none').addClass('d-flex');
        },
        removeAssignee: function (id) {
            var $this = $(this);
            var $assignees = $this.closest('.kanban-task-assignees').find('.dropdown-toggle');
            var $assignee = $assignees.find('.kanban-user[data-id="' + id + '"');

            $assignee.remove();
            $this.removeClass('d-flex').addClass('d-none');
            $this.closest('.dropdown-menu').find('.add-assignee[data-id="' + id + '"]')
                .removeClass('d-none').addClass('d-flex');
        }
    };

    function initTask()
    {
        $('[data-toggle="tooltip"]').tooltip();
        $('.kanban-task').on('click.sa.kanban', function (evt) {
            var element = evt.target.tagName.toLowerCase();
            if (element === 'div' || element === 'h6') {
                $(this).find('.kanban-task-update-link').trigger('click');
            }
        });
    }

    function initSortable()
    {
        $('.kanban-tasks').sortable({
            items: '> .kanban-sortable',
            connectWith: '.kanban-tasks',
            update: function (event, ui) {
                var $element = ui.item;
                var $oldParent = ui.sender ? ui.sender.closest('.kanban-bucket') : $element.closest('.kanban-bucket');
                var $newParent = $element.closest('.kanban-bucket');
                var $before = $element.prev('.kanban-sortable');
                var action = 'move-after';
                var pk = null;
                var promise;

                if (ui.sender === null && ui.position.left !== ui.originalPosition.left) {
                    return;
                }

                if (!$before.length) {
                    action = 'move-as-first';
                } else {
                    pk = $before.data('id');
                }

                if ($oldParent.get(0) !== $newParent.get(0)) {
                    var changeAction = $oldParent.data('action'),
                        keyName = $oldParent.data('keyName'),
                        sort = $oldParent.data('sort');
                    var data = {
                        modelClass: 'simialbi\\yii2\\kanban\\models\\Task',
                        modelPk: $element.data('id')
                    };
                    data[keyName] = $newParent.data('id');
                    promise = $.post(baseUrl + '/sort/' + changeAction, data);
                    if (!sort) {
                        // console.log($element);
                        // return;
                        promise.done(function () {
                            var event = jQuery.Event('click');
                            var container = '#' + $element.prop('id');

                            event.currentTarget = document.createElement('a');
                            event.currentTarget.href = baseUrl + '/task/view?id=' + $element.data('id');
                            jQuery.pjax.click(event, container, {
                                replace: false,
                                push: false,
                                skipOuterContainers: true
                            });
                        });
                        return;
                    }
                } else {
                    var dfd = $.Deferred();
                    promise = dfd.promise();
                    dfd.resolve();
                }

                promise.done(function () {
                    $.post(baseUrl + '/sort/' + action, {
                        modelClass: 'simialbi\\yii2\\kanban\\models\\Task',
                        modelPk: $element.data('id'),
                        pk: pk
                    }, function (data) {
                        console.log(data);
                    });
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
