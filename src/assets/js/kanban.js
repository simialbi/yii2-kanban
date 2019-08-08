/* global jQuery, yii, Swiper, kanbanBaseUrl: false */
window.sa = {};
window.sa.kanban = (function ($, Swiper, baseUrl) {
    var activeBucket;
    var slider;

    var pub = {
        isActive: true,

        init: function (module) {
            $('#taskModal').on('show.bs.modal', function (evt) {
                var link = $(evt.relatedTarget);
                var href = link.prop('href');

                var modal = $(this);
                modal.find('.modal-content').load(href);
            });

            initScrollBars();
            initTask();
            initSortable();
            initChecklist();
            initLinks();
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

            $this.addClass('is-assigned').css('display', 'none');
            $this.closest('.dropdown-menu').find('.remove-assignee[data-id="' + id + '"]')
                .addClass('is-assigned').css('display', '');
        },
        removeAssignee: function (id) {
            var $this = $(this);
            var $assignees = $this.closest('.kanban-task-assignees').find('.dropdown-toggle');
            var $assignee = $assignees.find('.kanban-user[data-id="' + id + '"');

            $assignee.remove();
            $this.removeClass('is-assigned').css('display', 'none');
            $this.closest('.dropdown-menu').find('.add-assignee[data-id="' + id + '"]')
                .removeClass('is-assigned').css('display', '');
        }
    };

    function addLinkElement()
    {
        var $this = $(this);
        var $linklist = $(this).closest('.linklist');

        if ($this.closest('.add-linklist-element').length) {
            var $addElement = $this.closest('.add-linklist-element').clone(),
                $inputGroup = $this.closest('.input-group'),
                $buttonDelete = $(
                    '<button class="btn btn-outline-danger remove-linklist-element">' +
                    '<i class="fas fa-trash-alt"></i>' +
                    '</button>'
                );
            $this.closest('.add-linklist-element').removeClass('add-linklist-element');
            $this.attr('placeholder', $this.val());
            $this.removeAttr('id');

            $inputGroup.append($('<div class="input-group-append" />').append($buttonDelete));

            $addElement.find('input[type="text"]').val('').removeClass(['is-valid', 'is-invalid']);

            $linklist.append($addElement);
        } else {
            if ($this.val() === '') {
                $this.val($this.attr('placeholder'));
            } else {
                $this.attr('placeholder', $this.val());
            }
        }
    }

    function addChecklistElement()
    {
        var $this = $(this);
        var $checklist = $(this).closest('.checklist');

        if ($this.closest('.add-checklist-element').length) {
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
    }

    function initScrollBars()
    {
        var $topScrollBar = $('.kanban-top-scrollbar'),
            $bottomScrollBar = $('.kanban-bottom-scrollbar');

        if ($topScrollBar.is(':visible')) {
            $topScrollBar.find('> div').css('width', $bottomScrollBar.find('> div').prop('scrollWidth'));

            $topScrollBar.on('scroll', function () {
                $bottomScrollBar.scrollLeft($topScrollBar.scrollLeft());
            });
            $bottomScrollBar.on('scroll', function () {
                $topScrollBar.scrollLeft($bottomScrollBar.scrollLeft());
            });
        } else {
            slider = new Swiper('.kanban-bottom-scrollbar', {
                wrapperClass: 'kanban-plan-sortable',
                slideClass: 'swiper-slide'
            });
        }
    }

    function initTask()
    {
        $('[data-toggle="tooltip"]').tooltip();
        $('.kanban-task').on('click.sa.kanban', function (evt) {
            if (!evt.target || !evt.target.tagName) {
                return;
            }
            var element = evt.target.tagName.toLowerCase();
            if (element === 'div' || element === 'h6' || element === 'img') {
                $(this).find('.kanban-task-update-link').trigger('click');
            }
        });
    }

    function initSortable()
    {
        $('.kanban-plan-sortable').sortable({
            items: '> .kanban-bucket',
            handle: '.kanban-bucket-sort-handle',
            stop: function (event, ui) {
                var $element = ui.item;
                var $before = $element.prev('.kanban-bucket');
                var action = 'move-after';
                var pk = null;

                if (!$before.length) {
                    action = 'move-as-first';
                } else {
                    pk = $before.data('id');
                }

                $.post(baseUrl + '/sort/' + action, {
                    modelClass: 'simialbi\\yii2\\kanban\\models\\Bucket',
                    modelPk: $element.data('id'),
                    pk: pk
                }, function (data) {
                    console.log(data);
                });
            }
        });

        $('.kanban-tasks').sortable({
            items: '> .kanban-sortable',
            connectWith: '.kanban-tasks',
            start: function (event, ui) {
                var $element = ui.item;
                activeBucket = $element.closest('.kanban-bucket');
            },
            stop: function (event, ui) {
                var $element = ui.item;
                var $oldParent = activeBucket;
                var $newParent = $element.closest('.kanban-bucket');
                var $before = $element.prev('.kanban-sortable');
                var action = 'move-after';
                var pk = null;
                var promise;

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

    function initLinks()
    {
        $(document).on('keydown.sa.kanban', '.linklist input[type="text"]', function (evt) {
            var $this = $(this);
            var code = evt.keyCode || evt.which;
            if ($this.val() === '') {
                return;
            }
            if (parseInt(code) === 9 || parseInt(code) === 13) {
                evt.preventDefault();
                if ($this.val().match(/^https?:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)*)(?::\d{1,5})?(?:$|[?\/#])/i)) {
                    $this.removeClass('is-invalid').addClass('is-valid');
                    addLinkElement.apply(this);
                    $('.add-linklist-element input[type="text"]').focus();
                } else {
                    $this.addClass('is-invalid').addClass('is-valid');
                }
            }
        });
        $(document).on('change.sa.kanban', '.linklist input[type="text"]', function () {
            var $this = $(this);
            if ($this.val().match(/^https?:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)*)(?::\d{1,5})?(?:$|[?\/#])/i)) {
                $this.removeClass('is-invalid').addClass('is-valid');
                addLinkElement.apply(this);
            } else {
                $this.addClass('is-invalid').addClass('is-valid');
            }
        });
        $(document).on('click.sa.kanban', '.linklist .remove-linklist-element', function () {
            $(this).closest('.input-group').remove();
        });
    }

    function initChecklist()
    {
        $(document).on('keydown.sa.kanban', '.checklist input[type="text"]', function (evt) {
            var $this = $(this);
            var code = evt.keyCode || evt.which;
            if ($this.val() === '') {
                return;
            }
            if (parseInt(code) === 9 || parseInt(code) === 13) {
                evt.preventDefault();
                addChecklistElement.apply(this);
                $('.add-checklist-element input[type="text"]').focus();
            }
        });
        $(document).on('change.sa.kanban', '.checklist input[type="text"]', function () {
            addChecklistElement.apply(this);
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
})(jQuery, Swiper, kanbanBaseUrl);

window.jQuery(function () {
    window.yii.initModule(window.sa.kanban);
});
