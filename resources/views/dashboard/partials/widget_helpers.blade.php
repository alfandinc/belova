<script>
    window.DashboardWidgetHelpers = (function ($) {
        function showAlert(options) {
            if (window.Swal && typeof window.Swal.fire === 'function') {
                window.Swal.fire(options);
            }
        }

        function showWarningAlert(title, text) {
            showAlert({
                icon: 'warning',
                title: title,
                text: text,
                confirmButtonText: 'OK'
            });
        }

        function showErrorAlert(title, text) {
            showAlert({
                icon: 'error',
                title: title,
                text: text,
                confirmButtonText: 'OK'
            });
        }

        function showSuccessAlert(title, text) {
            showAlert({
                icon: 'success',
                title: title,
                text: text,
                confirmButtonText: 'OK',
                timer: 1800,
                timerProgressBar: true
            });
        }

        function cleanupModalState() {
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('padding-right', '');
        }

        function closeModal(modalId, onClosed) {
            var modal = modalId ? $('#' + modalId) : $();
            var completed = false;

            function finalize() {
                if (completed) {
                    return;
                }

                completed = true;
                cleanupModalState();

                if (typeof onClosed === 'function') {
                    onClosed();
                }
            }

            if (!modal.length) {
                finalize();
                return;
            }

            modal.one('hidden.bs.modal', finalize);
            modal.modal('hide');

            window.setTimeout(finalize, 400);
        }

        function initializeDataTable(table, options) {
            if (!$.fn.DataTable || !table || !table.length) {
                return null;
            }

            if ($.fn.DataTable.isDataTable(table)) {
                table.DataTable().destroy();
            }

            return table.DataTable(options);
        }

        return {
            showSuccessAlert: showSuccessAlert,
            showWarningAlert: showWarningAlert,
            showErrorAlert: showErrorAlert,
            closeModal: closeModal,
            initializeDataTable: initializeDataTable
        };
    })(jQuery);
</script>