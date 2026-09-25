<div class="modal fade" id="taskDetailModal" tabindex="-1" role="dialog" aria-labelledby="taskDetailTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content task-detail-modal">
            <div class="modal-header task-detail-header">
                <div class="task-detail-header-main">
                    <div class="task-detail-eyebrow">Chi tiết công việc</div>
                    <h5 class="modal-title mb-0" id="taskDetailTitle">—</h5>
                    <div class="task-detail-badges" id="taskDetailBadges"></div>
                </div>
                <div class="task-detail-header-actions">
                    <div id="taskDetailEditBtnWrap"></div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
            <div class="modal-body task-detail-body" id="taskDetailBody">
                <div class="task-detail-loading">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span>Đang tải...</span>
                </div>
            </div>
            <div class="modal-footer task-detail-footer d-none" id="taskDetailFooter"></div>
        </div>
    </div>
</div>
