/* global jQuery, yii, Swiper, kanbanBaseUrl: false */
window.sa = {};
window.sa.kanban = (function ($, Swiper, baseUrl) {
    var activeBucket;
    var slider;
    // var searchTimeout;

    var pub = {
        isActive: true,

        init: function () {
            var $tabs = $('#plan-tabs');
            $('#taskModal').on('show.bs.modal', function (evt) {
                var link = $(evt.relatedTarget);
                var href = link.prop('href');

                if (!href) {
                    return;
                }

                var modal = $(this);
                modal.find('.modal-content').load(href);
            });


            if ($tabs.length) {
                $tabs.find('a[data-toggle="tab"]').on('shown.bs.tab', function () {
                    var $bottomScrollBar = $('.kanban-bottom-scrollbar');
                    if ($bottomScrollBar.is(':visible')) {
                        initScrollBars();
                        $tabs.find('a[data-toggle="tab"]').off('shown.bs.tab');
                    }
                });
            } else {
                initScrollBars();
            }
            initTask();
            initSortable();
            initChecklist();
            initLinks();
        },
        getSwiper: function () {
            return slider;
        },
        /**
         * Add assignee
         * @param {string} id
         */
        addAssignee: function (id) {
            var $this = $(this);
            var $assignees = $this.closest('.kanban-task-assignees').find('.dropdown-toggle');
            var name = $this.data('name') || '',
                image = $this.data('image') || '';
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
        /**
         * Remove assignee
         * @param {string} id
         */
        removeAssignee: function (id) {
            var $this = $(this);
            var $assignees = $this.closest('.kanban-task-assignees').find('.dropdown-toggle');
            var $assignee = $assignees.find('.kanban-user[data-id="' + id + '"');

            $assignee.remove();
            $this.removeClass('is-assigned').css('display', 'none');
            $this.closest('.dropdown-menu').find('.add-assignee[data-id="' + id + '"]')
                .removeClass('is-assigned').css('display', '');
        },
        /**
         * Copy passed text to browser clipboard
         *
         * @param {string} text the text to copy to clipboard
         */
        copyTextToClipboard: function (text) {
            if (!window.navigator.clipboard) {
                var textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';  //avoid scrolling to bottom
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();

                try {
                    document.execCommand('copy');
                } catch (err) {
                    // console.error('Fallback: Oops, unable to copy', err);
                }

            }

            function listener(e)
            {
                e.clipboardData.setData('text/plain', text);
                e.preventDefault();
            }

            document.addEventListener('copy', listener);
            document.execCommand('copy');
            document.removeEventListener('copy', listener);
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
                        '<svg class="svg-inline--fa fa-trash-alt fa-w-14" aria-hidden="true" data-prefix="fas" data-icon="trash-alt" data-fa-i2svg="" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path fill="currentColor" d="M32 464a48 48 0 0 0 48 48h288a48 48 0 0 0 48-48V128H32zm272-256a16 16 0 0 1 32 0v224a16 16 0 0 1-32 0zm-96 0a16 16 0 0 1 32 0v224a16 16 0 0 1-32 0zm-96 0a16 16 0 0 1 32 0v224a16 16 0 0 1-32 0zM432 32H312l-9.4-18.7A24 24 0 0 0 281.1 0H166.8a23.72 23.72 0 0 0-21.4 13.3L136 32H16A16 16 0 0 0 0 48v32a16 16 0 0 0 16 16h416a16 16 0 0 0 16-16V48a16 16 0 0 0-16-16z"></path></svg>' +
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
                $datePicker = $addElement.find('.krajee-datepicker'),
                idParts = $datePicker.prop('id').split('-');
            $this.closest('.add-checklist-element').removeClass('add-checklist-element');
            $this.attr('placeholder', $this.val());

            idParts[idParts.length - 1] = parseInt(idParts[idParts.length - 1]) + 1;
            $inputGroup.find('.remove-checklist-element').removeClass('disabled').prop('disabled', false);
            $addElement.find('input[type="text"]').val('');
            $datePicker.prop('id', idParts.join('-')).kvDatepicker(eval($datePicker.data('krajeeKvdatepicker')))

            $checklist.append($addElement);
        } else {
            if ($this.val() === '') {
                if (!$this.hasClass('krajee-datepicker')) {
                    $this.val($this.attr('placeholder'));
                }
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
                slideClass: 'kanban-bucket',
                navigation: {
                    nextEl: '.kanban-button-next',
                    prevEl: '.kanban-button-prev',
                    disabledClass: 'text-muted'
                }
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
            var el = evt.target.tagName.toLowerCase();
            if (el === 'div' || el === 'h6' || el === 'img' || $(el).closest('.kanban-task-description').length) {
                var $modal = $('#taskModal');

                $modal.modal('show');
                $modal.find('.modal-content').load($(this).find('.kanban-task-update-link').prop('href'));
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
            distance: 5,
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
                                timeout: 0,
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
        var regex = /^https?:\/\/(([A-Z0-9][A-Z0-9_-]*)(\.[A-Z0-9][A-Z0-9_-]*)*)(?::\d{1,5})?(?:$|[?\/#])/i;
        $(document).on('keydown.sa.kanban', '.linklist input[type="text"]', function (evt) {
            var $this = $(this);
            var code = evt.keyCode || evt.which;
            if ($this.val() === '') {
                return;
            }
            if (parseInt(code) === 9 || parseInt(code) === 13) {
                evt.preventDefault();
                if ($this.val().match(regex)) {
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
            if ($this.val().match(regex)) {
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
            var code = evt.which || evt.keyCode;
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
})(window.jQuery, window.Swiper, window.kanbanBaseUrl);

window.jQuery(function () {
    window.yii.initModule(window.sa.kanban);
});
