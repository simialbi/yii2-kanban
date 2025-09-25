/* global jQuery, yii, Swiper, kanbanBaseUrl: false */
window.sa = {};
window.sa.kanban = (function ($, Swiper, baseUrl) {
    var activeBucket;
    var slider;
    // var searchTimeout;

    var pub = {
        isActive: true,

        init: function () {
            pub.initSwiper();
            initSortable();
            initChecklist();
            initLinks();
        },
        /**
         * Initialize task
         * @param {string|HTMLElement|jQuery} el
         */
        initTask: function (el) {
            $('[data-bs-toggle="tooltip"]').tooltip({
                trigger: 'hover',
                boundary: 'body'
            });
            $(el).off('click.sa.kanban').on('click.sa.kanban', function (evt) {
                if (!evt.target || !evt.target.tagName) {
                    return;
                }
                var el = evt.target.tagName.toLowerCase(),
                    $el = $(evt.target);
                if (el === 'div' || el === 'h6' || el === 'img' || $el.closest('.kanban-task-description').length) {
                    $(this).find('.kanban-task-update-link').trigger('click');
                    // $modal.find('.modal-content').load($(this).find('.kanban-task-update-link').prop('href'));
                }
            });
            $(el).find('[data-ajax="true"]').on('click.sa.kanban', function (evt) {
                var $this = $(this);
                evt.preventDefault();
                if ($this.data('confirm')) {
                    evt.stopPropagation();
                    if (!confirm($this.data('confirm'))) {
                        return;
                    }
                }
                $.ajax({
                    url: $this.attr('href')
                }).done(function () {
                    $this.closest('.kanban-bucket').get(0).reload();
                });
            });
        },
        /**
         * Update sortable
         */
        updateSortable: function () {
            var $tasks = $('.kanban-tasks');
            $('.kanban-plan-sortable').sortable('refresh');

            $tasks.each(function () {
                var $this = $(this);
                if ($this.data('uiSortable')) {
                    $this.sortable('destroy');
                }
            });

            $tasks.sortable({
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
                                var element = $element.get(0);
                                if (null === element.src) {
                                    element.src = baseUrl + '/task/view?id=' + $element.data('id');
                                } else {
                                    element.reload();
                                }
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
                            // console.log(data);
                        });
                    });
                }
            });
        },
        /**
         * Get Swiper instance
         * @return {Swiper}
         */
        getSwiper: function () {
            return slider;
        },
        /**
         * Add assignee
         * @param {int} id
         */
        addAssignee: function (id) {
            var $this = $(this);
            var $dropdown = $this.closest('.kanban-task-assignees').find('.dropdown-toggle');
            var $input = $this.closest('.dropdown-menu').find('.search-field input');
            var $notAssigned = $this.closest('.dropdown-menu').find('.add-assignee:not(.is-assigned):visible');

            var $toAdd = $this;
            if (id === 0) {
                $toAdd = $notAssigned;
            }

            $.each($toAdd, function () {
                var id = $(this).data('id');
                var name = $(this).data('name') || '',
                    image = $(this).data('image') || '';
                var img;
                if (image) {
                    img = '<img src="' + image + '" class="rounded-circle me-1 mb-1" alt="' + name + '" title="' + name + '">';
                } else {
                    img = '<span class="kanban-visualisation me-1 mb-1" title="' + name + '">' +
                        name.substring(0, 1).toUpperCase() +
                        '</span>';
                }
                var $assignee = $(
                    '<span class="kanban-user" data-id="' + id + '">' +
                    '<input type="hidden" name="assignees[]" value="' + id + '">' +
                    img +
                    '</span>'
                );
                $dropdown.append($assignee);

                $(this).addClass('is-assigned')
                    .css('display', 'none');
                $(this).closest('.dropdown-menu')
                    .find('.remove-assignee[data-id="' + id + '"]')
                    .addClass('is-assigned')
                    .css('display', '');
            });

            var $event = $.Event('keyup');
            $event.keyCode = 8; // Backspace
            $input.val("").trigger($event).trigger('focus');
        },
        /**
         * Remove assignee
         * @param {int} id
         */
        removeAssignee: function (id) {
            var $dropdown = $(this).closest('.kanban-task-assignees').find('.dropdown-toggle');
            var $input = $(this).closest('.dropdown-menu').find('.search-field input');
            var $assignee = $dropdown.find('.kanban-user[data-id="' + id + '"');
            var $assigned = $dropdown.find('.kanban-user');

            var $toRemove = $assignee;
            if (id === 0) {
                $toRemove = $assigned;
            }

            $.each($toRemove, function () {
                var id = $(this).data('id');
                $(this).closest('.kanban-task-assignees')
                    .find('.remove-assignee[data-id="' + id + '"]')
                    .removeClass('is-assigned')
                    .css('display', 'none');
                $(this).closest('.kanban-task-assignees')
                    .find('.add-assignee[data-id="' + id + '"]')
                    .removeClass('is-assigned')
                    .css('display', '');
                $(this).remove();
            });

            var $event = $.Event('keyup');
            $event.keyCode = 8; // Backspace
            $input.val("").trigger($event).trigger('focus');
        },
        /**
         * Set responsible person
         * @param {int} id
         */
        chooseResponsible: function (id) {
            var $this = $(this);
            var $name = $this.data('name');
            $('#task-responsible_id-dummy').val($name);
            $('#task-responsible_id').val(id);
        },
        /**
         * remove responsible person
         */
        removeResponsible: function() {
            var id = $('#task-responsible_id').val();
            if (id) {
                $('#task-responsible_id-dummy').val('');
            }
            $('#task-responsible_id').val(null);
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
        },
        /**
         * Init Swiper if needed
         */
        initSwiper: function () {
            var $bottomScrollBar = $('.kanban-bottom-scrollbar');
            if ($bottomScrollBar.css('overflow-x') == 'hidden') {
                slider = new Swiper('.kanban-bottom-scrollbar', {
                    wrapperClass: 'sw-wrapper',
                    slideClass: 'kanban-bucket',
                    navigation: {
                        nextEl: '.kanban-button-next',
                        prevEl: '.kanban-button-prev',
                        disabledClass: 'text-muted'
                    }
                });
            }
        },
        /**
         * Load checklist template elements into task
         * @param id checklist template id
         */
        loadChecklistTemplate: function(id)
        {
            $.get(baseUrl + '/checklist-template/list?id=' + id, function (data) {
                $.each(data, function(key, item) {

                    let checklist = $('.checklist .input-group').last(),
                        nameInput = checklist.find('input[type="text"]').first(),
                        dateInput = checklist.find('.flatpickr-input');

                    nameInput.val(item.name);

                    let refDate = $("#task-start_date").val();
                    if (refDate !== '' && item.dateOffset !== null) {
                        let split = refDate.split('.');
                        let date = new Date(split[2], split[1] - 1, split[0]);
                        date.setDate(date.getDate() + item.dateOffset);
                        let lang = dateInput[0]._flatpickr.config.locale;
                        dateInput[0]._flatpickr.setDate(date.toLocaleDateString(new Intl.Locale(lang), {
                            'day': '2-digit',
                            'month': '2-digit',
                            'year': 'numeric'
                        }));
                    }

                    nameInput.trigger('change');
                })
            });
        },

        initDoneLazyLoading: function(url, scrollSelector, containerSelector)
        {
            let scrollDiv = $(scrollSelector);
            let threshold = 50;
            let loading = false;
            let limit = 50;

            scrollDiv[0].addEventListener('show.bs.collapse', event => {
                fetchMore(url, containerSelector).catch((reason) => {
                    console.error('fetching failed with status ' + reason);
                });
                scrollDiv.on('scroll', onScroll);
            });
            scrollDiv[0].addEventListener('hide.bs.collapse',  event =>  {
                scrollDiv.off('scroll', onScroll);
            });

            function onScroll(event)
            {
                let $this = $(this);
                if ($this.scrollTop + $this.innerHeight() > $this[0].scrollHeight - threshold) {
                    fetchMore(url, containerSelector).catch((reason) => {
                        console.error('fetching failed with status ' + reason);
                    });
                }
            }

            async function fetchMore(url, containerSelector)
            {
                if (loading) {
                    return;
                }
                loading = true;

                let container = $(containerSelector);
                let start = container[0].hasAttribute('data-start') ? container[0].getAttribute('data-start') : 0;

                let response = await fetch(url + '&start=' + start + '&limit=' + limit);
                if (response.ok) {
                    let body  = await response.text();

                    container.append(body);
                    container[0].setAttribute('data-start', parseInt(start) + limit);

                    loading = false;
                } else {
                    return Promise.reject(response.status + ' ' + response.statusText);
                }
            }
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

            $inputGroup.append($buttonDelete);

            $addElement.find('input[type="text"]').val('').removeClass(['is-valid', 'is-invalid']);

            $addElement.insertAfter($linklist.find('.input-group').last());
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
                $datePicker = $addElement.find('.flatpickr-input'),
                idParts = $datePicker.prop('id').split('-'),
                $config = $('.checklist .flatpickr-input')[0]._flatpickr.config;
            $this.closest('.add-checklist-element').removeClass('add-checklist-element');
            $this.attr('placeholder', $this.val());

            $addElement.find('input:not(.flatpickr-calendar input)').each(function () {
                var $input = $(this),
                    name = $input.prop('name'),
                    parts = name.match(/^checklist\[new\]\[(\d+)\]\[([a-z_]+)\]/),
                    cnt = parseInt(parts[1]) + 1,
                    fieldName = parts[2];

                $input.prop('name', 'checklist[new][' + cnt + '][' + fieldName + ']');
            });

            idParts[idParts.length - 1] = parseInt(idParts[idParts.length - 1]) + 1;
            $inputGroup.find('.remove-checklist-element').removeClass('disabled').prop('disabled', false);
            $addElement.find('input[type="text"]').val('');
            $datePicker.prop('id', idParts.join('-'));

            $checklist.append($addElement);
            $datePicker.removeClass('flatpickr-input');

            $config.defaultDate = null;
            flatpickr($datePicker[0], $config);
        } else {
            if ($this.val() === '') {
                if (!$this.hasClass('flatpickr-input')) {
                    $this.val($this.attr('placeholder'));
                }
            } else {
                $this.attr('placeholder', $this.val());
            }
        }
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
                    // console.log(data);
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
            if (code === 13) {
                evt.preventDefault();
            }
            if (parseInt(code) === 9 || parseInt(code) === 13) {
                addChecklistElement.apply(this);
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
