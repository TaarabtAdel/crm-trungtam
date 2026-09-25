(function () {
    var cfg = window.TaskBoard || {};
    var csrf = cfg.csrf || '';
    var lastDetailPayload = null;
    var detailEditMode = false;

    var ACTION_LABELS = {
        created: 'đã tạo công việc',
        updated: 'đã cập nhật',
        status_changed: 'đã đổi trạng thái',
        commented: 'đã bình luận',
        mentioned: 'đã nhắc đến người',
        attachment_added: 'đã đính kèm tệp',
        watchers_updated: 'đã cập nhật người liên quan',
        published: 'đã công bố',
        deleted: 'đã xóa'
    };

    function headersJson() {
        return {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest'
        };
    }

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatTextWithLinks(text, emptyLabel) {
        var raw = String(text == null ? '' : text).trim();
        if (!raw) {
            return '<span class="text-muted">' + esc(emptyLabel || '—') + '</span>';
        }
        var linked = esc(raw).replace(
            /(https?:\/\/[^\s<]+|www\.[^\s<]+|\/admin\/[^\s<]+)/gi,
            function (url) {
                var href = url;
                var external = false;
                if (/^www\./i.test(href)) {
                    href = 'https://' + href;
                    external = true;
                } else if (/^https?:\/\//i.test(href)) {
                    external = true;
                }
                var extra = external ? ' target="_blank" rel="noopener noreferrer"' : '';
                return '<a href="' + href + '"' + extra + '>' + url + '</a>';
            }
        );
        return linked.replace(/\n/g, '<br>');
    }

    function renderDetailEditButton(canEdit) {
        var wrap = document.getElementById('taskDetailEditBtnWrap');
        if (!wrap) {
            return;
        }
        if (!canEdit) {
            wrap.innerHTML = '';
            return;
        }
        if (detailEditMode) {
            wrap.innerHTML =
                '<button type="button" class="btn btn-sm btn-light task-detail-edit-btn" id="btnCancelDetailEdit" title="Thoát chế độ sửa">' +
                '<i class="bi bi-x-lg"></i></button>';
        } else {
            wrap.innerHTML =
                '<button type="button" class="btn btn-sm btn-light task-detail-edit-btn" id="btnDetailEdit" title="Sửa thông tin công việc">' +
                '<i class="bi bi-pencil"></i></button>';
        }
    }

    function pad(n) {
        return n < 10 ? '0' + n : '' + n;
    }

    function parseDate(iso) {
        if (!iso) return null;
        var d = new Date(iso);
        return isNaN(d.getTime()) ? null : d;
    }

    function formatDateVi(iso, withTime) {
        var d = parseDate(iso);
        if (!d) return '—';
        var s = pad(d.getDate()) + '/' + pad(d.getMonth() + 1) + '/' + d.getFullYear();
        if (withTime !== false) {
            s += ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
        }
        return s;
    }

    function toLocalInput(iso) {
        var d = parseDate(iso);
        if (!d) return '';
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
            'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    function formatBytes(n) {
        n = Number(n) || 0;
        if (n <= 0) return '';
        if (n < 1024) return n + ' B';
        if (n < 1048576) return (n / 1024).toFixed(1) + ' KB';
        return (n / 1048576).toFixed(1) + ' MB';
    }

    function initials(name) {
        var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) return '?';
        if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    function destroySelect2In(root) {
        if (!window.jQuery) return;
        window.jQuery(root).find('select.select2-hidden-accessible').each(function () {
            window.jQuery(this).select2('destroy');
        });
    }

    function initDetailSelect2() {
        if (typeof window.crmSelect2Local !== 'function') return;
        var $modal = window.jQuery('#taskDetailModal');
        window.crmSelect2Local($modal.find('.js-assignee-select'), {
            placeholder: 'Chọn người thực hiện...',
            allowClear: false
        });
        window.crmSelect2Local($modal.find('.js-watchers'), {
            placeholder: 'Chọn người liên quan...',
            allowClear: true
        });
    }

    function initCreateSelect2() {
        if (typeof window.crmSelect2Local !== 'function') return;
        var $modal = window.jQuery('#taskCreateModal');
        window.crmSelect2Local($modal.find('.js-create-assignee'), {
            placeholder: 'Chọn người thực hiện...',
            allowClear: false
        });
        window.crmSelect2Local($modal.find('.js-create-watchers'), {
            placeholder: 'Chọn người liên quan...',
            allowClear: true
        });
    }

    function delBtn(cls) {
        return '<button type="button" class="task-icon-btn task-icon-btn-danger ' + cls + '" title="Xóa">' +
            '<i class="bi bi-trash"></i></button>';
    }

    function personChip(user) {
        if (!user) return '';
        return '<span class="task-person-chip">' +
            '<span class="task-avatar">' + esc(initials(user.name)) + '</span>' +
            esc(user.name) + '</span>';
    }

    function openTask(id) {
        var body = document.getElementById('taskDetailBody');
        var footer = document.getElementById('taskDetailFooter');
        if (!body) return;

        destroySelect2In(body);
        body.innerHTML =
            '<div class="task-detail-loading">' +
            '<div class="spinner-border spinner-border-sm text-primary" role="status"></div>' +
            '<span>Đang tải...</span></div>';
        if (footer) {
            footer.classList.add('d-none');
            footer.innerHTML = '';
        }
        var badges = document.getElementById('taskDetailBadges');
        if (badges) badges.innerHTML = '';
        var titleEl = document.getElementById('taskDetailTitle');
        if (titleEl) titleEl.textContent = 'Đang tải...';
        detailEditMode = false;
        renderDetailEditButton(false);

        window.jQuery('#taskDetailModal').modal('show');

        fetch(cfg.urls.show + '/' + id, { headers: { 'Accept': 'application/json' } })
            .then(function (r) {
                if (!r.ok) throw new Error('load failed');
                return r.json();
            })
            .then(function (data) {
                renderDetail(data);
            })
            .catch(function () {
                body.innerHTML = '<div class="alert alert-danger mb-0">Không tải được công việc.</div>';
            });
    }

    function renderDetail(data, editMode) {
        if (editMode === undefined) {
            editMode = detailEditMode;
        } else {
            detailEditMode = !!editMode;
        }
        lastDetailPayload = data;

        var task = data.task;
        var canEdit = !!data.can_edit;
        var canWork = !!data.can_work;
        var canDelete = !!data.can_delete;
        var canPublish = !!data.can_publish;
        var canComment = !!data.can_comment;
        var canDuplicate = !!data.can_duplicate;
        var canAssign = !!data.can_assign;
        var canSave = canEdit || canWork;
        var statuses = data.statuses || {};
        var priorities = data.priorities || {};
        var users = data.users || [];
        var titleEl = document.getElementById('taskDetailTitle');
        var body = document.getElementById('taskDetailBody');
        var footer = document.getElementById('taskDetailFooter');
        var badges = document.getElementById('taskDetailBadges');

        if (titleEl) titleEl.textContent = task.title || 'Công việc';
        renderDetailEditButton(canEdit);

        var dueLabel = task.due_date ? formatDateVi(task.due_date) : null;
        var metaEditing = detailEditMode && canEdit;
        var isOverdue = !!(task.due_date && task.status !== 'done' && parseDate(task.due_date) && parseDate(task.due_date) < new Date());

        if (badges) {
            badges.innerHTML =
                (task.is_published
                    ? '<span class="task-badge task-badge-published"><i class="bi bi-broadcast"></i> Đã công bố</span>'
                    : '<span class="task-badge task-badge-draft"><i class="bi bi-pencil-square"></i> Bản nháp</span>') +
                '<span class="task-badge task-badge-status-' + esc(task.status) + '">' +
                '<i class="bi bi-circle-fill" style="font-size:.45rem"></i> ' + esc(statuses[task.status] || task.status) +
                '</span>' +
                '<span class="task-badge task-priority task-priority-' + esc(task.priority) + '">' +
                esc(priorities[task.priority] || task.priority) +
                '</span>' +
                (dueLabel
                    ? '<span class="task-badge' + (isOverdue ? ' is-overdue' : '') + '">' +
                      '<i class="bi bi-calendar-event"></i> ' + (isOverdue ? 'Quá hạn · ' : 'Hạn ') + esc(dueLabel) +
                      '</span>'
                    : '') +
                (task.assignee
                    ? '<span class="task-badge"><i class="bi bi-person"></i> ' + esc(task.assignee.name) + '</span>'
                    : '');
        }

        var statusOpts = Object.keys(statuses).map(function (k) {
            return '<option value="' + k + '"' + (task.status === k ? ' selected' : '') + '>' + esc(statuses[k]) + '</option>';
        }).join('');
        var priorityOpts = Object.keys(priorities).map(function (k) {
            return '<option value="' + k + '"' + (task.priority === k ? ' selected' : '') + '>' + esc(priorities[k]) + '</option>';
        }).join('');

        var assigneeId = task.assignee_id || (task.assignee && task.assignee.id) || '';
        var assigneeOpts = users.map(function (u) {
            return '<option value="' + u.id + '"' + (String(assigneeId) === String(u.id) ? ' selected' : '') + '>' + esc(u.name) + '</option>';
        }).join('');
        var watcherIds = (task.watchers || []).map(function (w) { return w.id; });
        var watcherOpts = users.map(function (u) {
            return '<option value="' + u.id + '"' + (watcherIds.indexOf(u.id) >= 0 ? ' selected' : '') + '>' + esc(u.name) + '</option>';
        }).join('');

        var checklistItems = task.checklist_items || [];
        var checklist = checklistItems.map(function (item) {
            return '<div class="task-check-item' + (item.is_done ? ' is-done' : '') + '" data-id="' + item.id + '">' +
                '<input type="checkbox" class="mt-1 js-check-toggle"' + (item.is_done ? ' checked' : '') + (canWork ? '' : ' disabled') + '>' +
                '<span class="task-item-text">' + esc(item.title) + '</span>' +
                (canWork ? delBtn('js-check-del') : '') +
                '</div>';
        }).join('') || '<div class="task-empty">Chưa có checklist.</div>';

        var subtaskItems = task.subtasks || [];
        var subtasks = subtaskItems.map(function (item) {
            return '<div class="task-sub-item' + (item.is_done ? ' is-done' : '') + '" data-id="' + item.id + '">' +
                '<input type="checkbox" class="mt-1 js-sub-toggle"' + (item.is_done ? ' checked' : '') + (canWork ? '' : ' disabled') + '>' +
                '<div class="task-item-text">' + esc(item.title) +
                (item.assignee ? '<span class="task-item-meta">' + esc(item.assignee.name) + '</span>' : '') +
                '</div>' +
                (canWork ? delBtn('js-sub-del') : '') +
                '</div>';
        }).join('') || '<div class="task-empty">Chưa có công việc con.</div>';

        var comments = (task.comments || []).map(function (c) {
            var name = c.user ? c.user.name : '—';
            return '<div class="task-comment">' +
                '<span class="task-avatar task-avatar-lg">' + esc(initials(name)) + '</span>' +
                '<div class="task-comment-body">' +
                '<div class="task-comment-head">' +
                '<span class="task-comment-name">' + esc(name) + '</span>' +
                '<span class="task-comment-time">' + esc(formatDateVi(c.created_at)) + '</span>' +
                '</div>' +
                '<div class="task-comment-text">' + esc(c.body) + '</div>' +
                '</div></div>';
        }).join('') || '<div class="task-empty">Chưa có bình luận.</div>';

        var attachments = (task.attachments || []).map(function (a) {
            var url = '/storage/' + a.path;
            var size = formatBytes(a.size);
            return '<div class="task-attach-item" data-id="' + a.id + '">' +
                '<span class="task-attach-icon"><i class="bi bi-paperclip"></i></span>' +
                '<div class="flex-grow-1 min-w-0">' +
                '<a class="task-attach-name" href="' + esc(url) + '" target="_blank" rel="noopener">' + esc(a.original_name) + '</a>' +
                (size ? '<div class="task-attach-size">' + esc(size) + '</div>' : '') +
                '</div>' +
                (canWork ? delBtn('js-att-del') : '') +
                '</div>';
        }).join('') || '<div class="task-empty">Chưa có tệp đính kèm.</div>';

        var activity = (task.activity_logs || []).slice(0, 20).map(function (log) {
            var who = log.user ? log.user.name : 'Hệ thống';
            var action = ACTION_LABELS[log.action] || log.action;
            return '<div class="task-activity">' +
                '<span class="task-activity-dot"></span>' +
                '<div><div class="task-activity-text"><strong>' + esc(who) + '</strong> ' + esc(action) + '</div>' +
                '<div class="task-activity-time">' + esc(formatDateVi(log.created_at)) + '</div></div>' +
                '</div>';
        }).join('') || '<div class="task-empty">Chưa có hoạt động.</div>';

        var assigneeBlock = metaEditing && canAssign
            ? '<select class="form-control js-field js-assignee-select" data-field="assignee_id" data-placeholder="Chọn người thực hiện...">' +
              assigneeOpts + '</select>'
            : (task.assignee ? personChip(task.assignee) : '<div class="task-field-static">—</div>');

        var dueBlock = metaEditing
            ? '<input type="datetime-local" class="form-control form-control-sm js-field" data-field="due_date" value="' + esc(toLocalInput(task.due_date)) + '">'
            : '<div class="task-field-static">' + esc(dueLabel || '—') + '</div>';

        var watchersBlock = metaEditing
            ? '<select class="form-control js-watchers" multiple data-placeholder="Chọn người liên quan...">' +
              watcherOpts + '</select>'
            : ((task.watchers || []).map(personChip).join('') || '<div class="task-field-static">—</div>');

        var titleBlock = metaEditing
            ? '<input type="text" class="form-control task-title-input js-field" data-field="title" value="' + esc(task.title) + '">'
            : '<div class="task-view-title">' + formatTextWithLinks(task.title, 'Chưa có tên') + '</div>';

        var descBlock = metaEditing
            ? '<textarea class="form-control js-field" data-field="description" rows="6" placeholder="Thêm mô tả...">' +
              esc(task.description || '') + '</textarea>'
            : '<div class="task-rich-text">' + formatTextWithLinks(task.description, 'Không có mô tả.') + '</div>';

        var mainPanel =
            '<div class="task-panel">' +
            '<div class="task-main-fields">' +
            '<div><label class="task-field-label">Người thực hiện</label>' + assigneeBlock + '</div>' +
            '<div><label class="task-field-label">Hạn hoàn thành</label>' + dueBlock + '</div>' +
            '<div class="task-main-span-2"><label class="task-field-label">Tên công việc</label>' + titleBlock + '</div>' +
            '<div class="task-main-span-2"><label class="task-field-label">Mô tả</label>' + descBlock + '</div>' +
            '</div></div>';

        destroySelect2In(body);

        body.innerHTML =
            '<div class="task-detail-grid" data-task-id="' + task.id + '">' +
            '<div>' +
            mainPanel +
            '<div class="task-panel">' +
            '<div class="task-panel-head"><h6 class="task-panel-title"><i class="bi bi-check2-square"></i> Checklist</h6>' +
            '<span class="task-panel-count">' + checklistItems.filter(function (i) { return i.is_done; }).length + '/' + checklistItems.length + '</span></div>' +
            '<div id="detailChecklist">' + checklist + '</div>' +
            (canWork
                ? '<div class="task-add-row">' +
                  '<input type="text" class="form-control form-control-sm" id="newCheckTitle" placeholder="Thêm mục checklist...">' +
                  '<button class="btn btn-sm btn-outline-secondary" type="button" id="btnAddCheck"><i class="bi bi-plus-lg"></i></button>' +
                  '</div>'
                : '') +
            '</div>' +
            '<div class="task-panel">' +
            '<div class="task-panel-head"><h6 class="task-panel-title"><i class="bi bi-diagram-3"></i> Công việc con</h6>' +
            '<span class="task-panel-count">' + subtaskItems.filter(function (i) { return i.is_done; }).length + '/' + subtaskItems.length + '</span></div>' +
            '<div id="detailSubtasks">' + subtasks + '</div>' +
            (canWork
                ? '<div class="task-add-row">' +
                  '<input type="text" class="form-control form-control-sm" id="newSubTitle" placeholder="Thêm công việc con...">' +
                  '<button class="btn btn-sm btn-outline-secondary" type="button" id="btnAddSub"><i class="bi bi-plus-lg"></i></button>' +
                  '</div>'
                : '') +
            '</div>' +
            '<div class="task-panel">' +
            '<div class="task-panel-head"><h6 class="task-panel-title"><i class="bi bi-chat-left-text"></i> Bình luận</h6></div>' +
            '<div id="detailComments">' + comments + '</div>' +
            (canComment
                ? '<div class="task-comment-compose">' +
                  '<textarea class="form-control form-control-sm" id="newComment" rows="2" placeholder="Viết bình luận... Dùng @id:123 để nhắc người"></textarea>' +
                  '<div class="text-right mt-2">' +
                  '<button type="button" class="btn btn-sm btn-primary" id="btnAddComment"><i class="bi bi-send mr-1"></i>Gửi</button>' +
                  '</div></div>'
                : '') +
            '</div>' +
            '</div>' +
            '<div>' +
            '<div class="task-panel">' +
            '<div class="task-panel-head"><h6 class="task-panel-title"><i class="bi bi-sliders"></i> Thông tin</h6></div>' +
            '<div class="task-meta-grid">' +
            '<div><label class="task-field-label">Trạng thái</label>' +
            '<select class="form-control form-control-sm js-field" data-field="status"' + (canWork ? '' : ' disabled') + '>' + statusOpts + '</select></div>' +
            '<div><label class="task-field-label">Ưu tiên</label>' +
            (metaEditing
                ? '<select class="form-control form-control-sm js-field" data-field="priority">' + priorityOpts + '</select>'
                : '<div class="task-field-static">' + esc(priorities[task.priority] || task.priority || '—') + '</div>') +
            '</div>' +
            '<div class="task-meta-full"><label class="task-field-label">Người liên quan</label>' + watchersBlock + '</div>' +
            (task.creator
                ? '<div class="task-meta-full"><label class="task-field-label">Người tạo</label>' +
                  personChip(task.creator) +
                  '<div class="task-item-meta mt-1">Tạo lúc ' + esc(formatDateVi(task.created_at)) + '</div></div>'
                : '') +
            '</div></div>' +
            '<div class="task-panel">' +
            '<div class="task-panel-head"><h6 class="task-panel-title"><i class="bi bi-paperclip"></i> Tệp đính kèm</h6>' +
            '<span class="task-panel-count">' + (task.attachments || []).length + '</span></div>' +
            '<div id="detailAttachments" class="task-attach-list">' + attachments + '</div>' +
            (canWork
                ? '<div class="task-upload"><i class="bi bi-cloud-arrow-up"></i> Chọn tệp để tải lên (tối đa 10MB)' +
                  '<input type="file" id="taskFileInput"></div>'
                : '') +
            '</div>' +
            '<div class="task-panel">' +
            '<div class="task-panel-head"><h6 class="task-panel-title"><i class="bi bi-clock-history"></i> Hoạt động</h6></div>' +
            activity +
            '</div>' +
            '</div></div>';

        if (footer) {
            var left = '';
            var right = '';
            if (!task.is_published) {
                left +=
                    '<span class="task-draft-hint text-muted small">' +
                    '<i class="bi bi-info-circle"></i> Bản nháp — chưa gửi thông báo.</span>';
            }
            if (canPublish) {
                right +=
                    '<button type="button" class="btn btn-success" id="btnPublishTask">' +
                    '<i class="bi bi-broadcast mr-1"></i>Công bố</button>';
            }
            if (canDuplicate) {
                right +=
                    '<button type="button" class="btn btn-outline-secondary" id="btnDuplicateTask">' +
                    '<i class="bi bi-files mr-1"></i>Sao chép</button>';
            }
            if (canDelete) {
                right +=
                    '<button type="button" class="btn btn-outline-danger" id="btnDeleteTask">' +
                    '<i class="bi bi-trash mr-1"></i>Xóa</button>';
            }
            right += '<button type="button" class="btn btn-light" data-dismiss="modal">Đóng</button>';
            if (metaEditing || canSave) {
                right +=
                    '<button type="button" class="btn btn-primary" id="btnSaveTask">' +
                    '<i class="bi bi-check2 mr-1"></i>Lưu thay đổi</button>';
            }
            footer.classList.remove('d-none');
            footer.innerHTML =
                '<div class="task-detail-footer-left">' + left + '</div>' +
                '<div class="task-detail-footer-right">' + right + '</div>';
        }

        initDetailSelect2();
        bindDetailEvents(task.id, { canEdit: canEdit, canWork: canWork, canComment: canComment, metaEditing: metaEditing });
    }

    function bindDetailEvents(taskId, abilities) {
        var canWork = !!(abilities && abilities.canWork);
        var canComment = !!(abilities && abilities.canComment);
        var root = document.getElementById('taskDetailBody');
        var footer = document.getElementById('taskDetailFooter');
        if (!root) return;

        var editBtn = document.getElementById('btnDetailEdit');
        if (editBtn) {
            editBtn.addEventListener('click', function () {
                if (lastDetailPayload) {
                    renderDetail(lastDetailPayload, true);
                }
            });
        }
        var cancelEditBtn = document.getElementById('btnCancelDetailEdit');
        if (cancelEditBtn) {
            cancelEditBtn.addEventListener('click', function () {
                if (lastDetailPayload) {
                    renderDetail(lastDetailPayload, false);
                }
            });
        }

        function saveTask() {
            var payload = {};
            root.querySelectorAll('.js-field').forEach(function (el) {
                if (el.disabled) return;
                if (el.classList.contains('select2-hidden-accessible') && window.jQuery) {
                    payload[el.getAttribute('data-field')] = window.jQuery(el).val();
                } else {
                    payload[el.getAttribute('data-field')] = el.value;
                }
            });
            var watcherSelect = root.querySelector('.js-watchers');
            if (watcherSelect) {
                if (window.jQuery && window.jQuery(watcherSelect).hasClass('select2-hidden-accessible')) {
                    payload.watcher_ids = (window.jQuery(watcherSelect).val() || []).map(function (v) {
                        return parseInt(v, 10);
                    });
                } else {
                    payload.watcher_ids = Array.prototype.map.call(watcherSelect.selectedOptions, function (o) {
                        return parseInt(o.value, 10);
                    });
                }
            }
            var btn = footer && footer.querySelector('#btnSaveTask');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span>Đang lưu...';
            }
            fetch(cfg.urls.show + '/' + taskId, {
                method: 'PUT',
                headers: headersJson(),
                body: JSON.stringify(payload)
            }).then(function (r) {
                if (!r.ok) throw new Error('save failed');
                return r.json();
            }).then(function () {
                detailEditMode = false;
                openTask(taskId);
            }).catch(function () {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check2 mr-1"></i>Lưu thay đổi';
                }
                alert('Không lưu được. Vui lòng thử lại.');
            });
        }

        if (footer) {
            var saveBtn = footer.querySelector('#btnSaveTask');
            if (saveBtn) saveBtn.addEventListener('click', saveTask);

            var publishBtn = footer.querySelector('#btnPublishTask');
            if (publishBtn) {
                publishBtn.addEventListener('click', function () {
                    if (!confirm('Công bố công việc này? Người thực hiện và người liên quan sẽ nhận thông báo.')) return;
                    publishBtn.disabled = true;
                    publishBtn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span>Đang công bố...';
                    fetch(cfg.urls.show + '/' + taskId + '/publish', {
                        method: 'POST',
                        headers: headersJson(),
                        body: '{}'
                    }).then(function (r) {
                        if (!r.ok) throw new Error('publish failed');
                        return r.json();
                    }).then(function () {
                        location.href = cfg.urls.show + '?task=' + taskId;
                    }).catch(function () {
                        publishBtn.disabled = false;
                        publishBtn.innerHTML = '<i class="bi bi-broadcast mr-1"></i>Công bố';
                        alert('Không công bố được. Vui lòng thử lại.');
                    });
                });
            }

            var dupBtn = footer.querySelector('#btnDuplicateTask');
            if (dupBtn) {
                dupBtn.addEventListener('click', function () {
                    if (!confirm('Sao chép công việc này thành bản nháp mới?')) return;
                    dupBtn.disabled = true;
                    fetch(cfg.urls.show + '/' + taskId + '/duplicate', {
                        method: 'POST',
                        headers: headersJson(),
                        body: '{}'
                    }).then(function (r) {
                        if (!r.ok) throw new Error('dup failed');
                        return r.json();
                    }).then(function (data) {
                        var openId = data.open || (data.task && data.task.id);
                        location.href = cfg.urls.show + '?task=' + openId;
                    }).catch(function () {
                        dupBtn.disabled = false;
                        alert('Không sao chép được. Vui lòng thử lại.');
                    });
                });
            }

            var delBtnEl = footer.querySelector('#btnDeleteTask');
            if (delBtnEl) {
                delBtnEl.addEventListener('click', function () {
                    if (!confirm('Xóa công việc này? Thao tác không thể hoàn tác.')) return;
                    fetch(cfg.urls.show + '/' + taskId, {
                        method: 'DELETE',
                        headers: headersJson()
                    }).then(function () { location.href = cfg.urls.show; });
                });
            }
        }

        if (canWork) {
        root.querySelectorAll('.js-check-toggle').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var item = cb.closest('.task-check-item');
                fetch(cfg.urls.show + '/' + taskId + '/checklist/' + item.getAttribute('data-id'), {
                    method: 'PUT',
                    headers: headersJson(),
                    body: JSON.stringify({ is_done: cb.checked })
                });
                item.classList.toggle('is-done', cb.checked);
            });
        });

        root.querySelectorAll('.js-check-del').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var item = btn.closest('.task-check-item');
                fetch(cfg.urls.show + '/' + taskId + '/checklist/' + item.getAttribute('data-id'), {
                    method: 'DELETE',
                    headers: headersJson()
                }).then(function () { item.remove(); });
            });
        });

        var addCheck = root.querySelector('#btnAddCheck');
        if (addCheck) {
            addCheck.addEventListener('click', function () {
                var input = root.querySelector('#newCheckTitle');
                if (!input || !input.value.trim()) return;
                fetch(cfg.urls.show + '/' + taskId + '/checklist', {
                    method: 'POST',
                    headers: headersJson(),
                    body: JSON.stringify({ title: input.value.trim() })
                }).then(function () { openTask(taskId); });
            });
            var checkInput = root.querySelector('#newCheckTitle');
            if (checkInput) {
                checkInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addCheck.click();
                    }
                });
            }
        }

        root.querySelectorAll('.js-sub-toggle').forEach(function (cb) {
            cb.addEventListener('change', function () {
                var item = cb.closest('.task-sub-item');
                fetch(cfg.urls.show + '/' + taskId + '/subtasks/' + item.getAttribute('data-id'), {
                    method: 'PUT',
                    headers: headersJson(),
                    body: JSON.stringify({ is_done: cb.checked })
                });
                item.classList.toggle('is-done', cb.checked);
            });
        });

        root.querySelectorAll('.js-sub-del').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var item = btn.closest('.task-sub-item');
                fetch(cfg.urls.show + '/' + taskId + '/subtasks/' + item.getAttribute('data-id'), {
                    method: 'DELETE',
                    headers: headersJson()
                }).then(function () { item.remove(); });
            });
        });

        var addSub = root.querySelector('#btnAddSub');
        if (addSub) {
            addSub.addEventListener('click', function () {
                var input = root.querySelector('#newSubTitle');
                if (!input || !input.value.trim()) return;
                fetch(cfg.urls.show + '/' + taskId + '/subtasks', {
                    method: 'POST',
                    headers: headersJson(),
                    body: JSON.stringify({ title: input.value.trim() })
                }).then(function () { openTask(taskId); });
            });
            var subInput = root.querySelector('#newSubTitle');
            if (subInput) {
                subInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addSub.click();
                    }
                });
            }
        }

        var fileInput = root.querySelector('#taskFileInput');
        if (fileInput) {
            fileInput.addEventListener('change', function () {
                if (!fileInput.files || !fileInput.files[0]) return;
                var fd = new FormData();
                fd.append('file', fileInput.files[0]);
                fetch(cfg.urls.show + '/' + taskId + '/attachments', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: fd
                }).then(function () { openTask(taskId); });
            });
        }

        root.querySelectorAll('.js-att-del').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var item = btn.closest('.task-attach-item');
                var id = item ? item.getAttribute('data-id') : btn.getAttribute('data-id');
                if (!id) return;
                fetch(cfg.urls.show + '/' + taskId + '/attachments/' + id, {
                    method: 'DELETE',
                    headers: headersJson()
                }).then(function () { openTask(taskId); });
            });
        });
        } // canWork

        if (canComment) {
        var addComment = root.querySelector('#btnAddComment');
        if (addComment) {
            addComment.addEventListener('click', function () {
                var input = root.querySelector('#newComment');
                if (!input || !input.value.trim()) return;
                addComment.disabled = true;
                fetch(cfg.urls.show + '/' + taskId + '/comments', {
                    method: 'POST',
                    headers: headersJson(),
                    body: JSON.stringify({ body: input.value.trim() })
                }).then(function () { openTask(taskId); })
                    .catch(function () { addComment.disabled = false; });
            });
        }
        }
    }

    document.querySelectorAll('.task-card, .js-open-task, .task-row').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (e.target.closest('a,button') && !el.classList.contains('task-card') && !el.classList.contains('task-row')) return;
            var id = el.getAttribute('data-id') || (el.querySelector('.js-open-task') && el.querySelector('.js-open-task').getAttribute('data-id'));
            if (el.classList.contains('js-open-task')) id = el.getAttribute('data-id');
            if (id) openTask(id);
        });
    });

    var addRow = document.getElementById('addChecklistRow');
    if (addRow) {
        addRow.addEventListener('click', function () {
            var wrap = document.getElementById('createChecklistWrap');
            if (!wrap) return;
            var row = document.createElement('div');
            row.className = 'task-create-check-row';
            row.innerHTML = '<input type="text" name="checklist[]" class="form-control form-control-sm" placeholder="Mục việc cần làm...">';
            wrap.appendChild(row);
        });
    }

    if (window.jQuery) {
        window.jQuery('#taskCreateModal').on('shown.bs.modal', function () {
            initCreateSelect2();
        });
        window.jQuery('#taskDetailModal').on('hidden.bs.modal', function () {
            destroySelect2In(document.getElementById('taskDetailBody'));
        });
    }

    if (cfg.canManage && window.Sortable) {
        document.querySelectorAll('.task-column-body').forEach(function (col) {
            Sortable.create(col, {
                group: 'tasks',
                animation: 150,
                draggable: '.task-card[data-can-work="1"]',
                filter: '.task-card[data-can-work="0"]',
                ghostClass: 'sortable-ghost',
                onAdd: function (evt) {
                    var card = evt.item;
                    if (card.getAttribute('data-can-work') !== '1') return;
                    var status = col.getAttribute('data-status');
                    var ids = Array.prototype.map.call(col.querySelectorAll('.task-card'), function (c) {
                        return parseInt(c.getAttribute('data-id'), 10);
                    });
                    fetch(cfg.urls.status + '/' + card.getAttribute('data-id') + '/status', {
                        method: 'PUT',
                        headers: headersJson(),
                        body: JSON.stringify({
                            status: status,
                            position: evt.newIndex + 1,
                            ordered_ids: ids
                        })
                    });
                },
                onUpdate: function (evt) {
                    var card = evt.item;
                    if (card.getAttribute('data-can-work') !== '1') return;
                    var status = col.getAttribute('data-status');
                    var ids = Array.prototype.map.call(col.querySelectorAll('.task-card'), function (c) {
                        return parseInt(c.getAttribute('data-id'), 10);
                    });
                    fetch(cfg.urls.status + '/' + card.getAttribute('data-id') + '/status', {
                        method: 'PUT',
                        headers: headersJson(),
                        body: JSON.stringify({
                            status: status,
                            position: evt.newIndex + 1,
                            ordered_ids: ids
                        })
                    });
                }
            });
        });
    }

    if (cfg.openId) {
        openTask(cfg.openId);
    }
})();
