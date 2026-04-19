/**
 * ZafaSys Alert Helper
 * Centralized alert system using SweetAlert2
 * Provides consistent styling and behavior across the application
 */

const AlertHelper = {
    // Default configuration
    config: {
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false,
        position: 'top-end',
        toast: true,
        customClass: {
            popup: 'colored-toast'
        }
    },

    /**
     * Apply text/html message into SweetAlert config.
     * Uses html when message contains HTML tags.
     * @param {Object} config
     * @param {string} message
     */
    setMessage(config, message) {
        if (!message) {
            return;
        }

        const normalizedMessage = String(message);
        const hasHtmlTag = /<\/?[a-z][\s\S]*>/i.test(normalizedMessage);

        if (hasHtmlTag) {
            config.html = normalizedMessage;
            return;
        }

        config.text = normalizedMessage;
    },

    /**
     * Extract readable message from AJAX/fetch style error objects.
     * @param {Object} error - jqXHR or error object
     * @param {string} fallbackMessage - fallback message if no detail found
     * @returns {string}
     */
    parseAjaxError(error, fallbackMessage = 'Terjadi kesalahan pada server') {
        if (!error) {
            return fallbackMessage;
        }

        const responseJSON = error.responseJSON || error.response?.data;
        if (responseJSON) {
            if (typeof responseJSON.message === 'string' && responseJSON.message.trim() !== '') {
                return responseJSON.message;
            }

            if (responseJSON.errors && typeof responseJSON.errors === 'object') {
                const firstError = Object.values(responseJSON.errors)
                    .flat()
                    .find((msg) => typeof msg === 'string' && msg.trim() !== '');

                if (firstError) {
                    return firstError;
                }
            }
        }

        if (typeof error.responseText === 'string' && error.responseText.trim() !== '') {
            try {
                const parsedResponse = JSON.parse(error.responseText);
                if (typeof parsedResponse.message === 'string' && parsedResponse.message.trim() !== '') {
                    return parsedResponse.message;
                }
            } catch (parseError) {
                // Ignore non-JSON responseText.
            }
        }

        if (typeof error.message === 'string' && error.message.trim() !== '') {
            return error.message;
        }

        if (error.status) {
            return `Request gagal (HTTP ${error.status})`;
        }

        return fallbackMessage;
    },

    /**
     * Shortcut to show standardized AJAX error alert.
     * @param {string} title
     * @param {Object} error
     * @param {string} fallbackMessage
     * @param {boolean} autoClose
     */
    ajaxError(title = 'Error!', error = null, fallbackMessage = 'Terjadi kesalahan pada server', autoClose = false) {
        const message = this.parseAjaxError(error, fallbackMessage);
        return this.error(title, message, autoClose);
    },

    /**
     * Success Alert
     * @param {string} title - Alert title
     * @param {string} text - Alert message (optional)
     * @param {boolean} autoClose - Auto close after timer (default: true)
     */
    success(title, text = '', autoClose = true) {
        const config = {
            icon: 'success',
            title: title,
            ...this.config
        };

        this.setMessage(config, text);

        if (!autoClose) {
            config.timer = undefined;
            config.showConfirmButton = true;
            config.toast = false;
            config.position = 'center';
        }

        return Swal.fire(config);
    },

    /**
     * Error Alert
     * @param {string} title - Alert title
     * @param {string} text - Alert message (optional)
     * @param {boolean} autoClose - Auto close after timer (default: false)
     */
    error(title, text = '', autoClose = false) {
        const config = {
            icon: 'error',
            title: title,
            ...this.config
        };

        this.setMessage(config, text);

        if (!autoClose) {
            config.timer = undefined;
            config.showConfirmButton = true;
            config.toast = false;
            config.position = 'center';
        }

        return Swal.fire(config);
    },

    /**
     * Warning Alert
     * @param {string} title - Alert title
     * @param {string} text - Alert message (optional)
     * @param {boolean} autoClose - Auto close after timer (default: true)
     */
    warning(title, text = '', autoClose = true) {
        const config = {
            icon: 'warning',
            title: title,
            ...this.config
        };

        this.setMessage(config, text);

        if (!autoClose) {
            config.timer = undefined;
            config.showConfirmButton = true;
            config.toast = false;
            config.position = 'center';
        }

        return Swal.fire(config);
    },

    /**
     * Info Alert
     * @param {string} title - Alert title
     * @param {string} text - Alert message (optional)
     * @param {boolean} autoClose - Auto close after timer (default: true)
     */
    info(title, text = '', autoClose = true) {
        const config = {
            icon: 'info',
            title: title,
            ...this.config
        };

        this.setMessage(config, text);

        if (!autoClose) {
            config.timer = undefined;
            config.showConfirmButton = true;
            config.toast = false;
            config.position = 'center';
        }

        return Swal.fire(config);
    },

    /**
     * Confirmation Dialog
     * @param {string} title - Dialog title
     * @param {string} text - Dialog message
     * @param {string} confirmButtonText - Confirm button text (default: 'Ya')
     * @param {string} cancelButtonText - Cancel button text (default: 'Batal')
     */
    confirm(title, text, confirmButtonText = 'Ya', cancelButtonText = 'Batal') {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            confirmButtonText: confirmButtonText,
            cancelButtonText: cancelButtonText,
            reverseButtons: true
        });
    },

    /**
     * Delete Confirmation Dialog
     * @param {string} title - Dialog title (default: 'Hapus Data?')
     * @param {string} text - Dialog message (default: 'Data yang dihapus tidak dapat dikembalikan')
     */
    confirmDelete(title = 'Hapus Data?', text = 'Data yang dihapus tidak dapat dikembalikan') {
        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        });
    },

    /**
     * Loading Dialog
     * @param {string} title - Loading title (default: 'Memproses...')
     * @param {string} text - Loading message (default: 'Mohon tunggu sebentar')
     */
    loading(title = 'Memproses...', text = 'Mohon tunggu sebentar') {
        return Swal.fire({
            title: title,
            text: text,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    },

    /**
     * Progress Dialog
     * @param {string} title - Progress title
     * @param {number} current - Current progress
     * @param {number} total - Total items
     */
    progress(title, current, total) {
        const percent = Math.round((current / total) * 100);
        return Swal.fire({
            title: title,
            html: `
                <div>Memproses <b>${current}</b> dari <b>${total}</b></div>
                <div class="progress mt-3">
                    <div class="progress-bar" role="progressbar" style="width: ${percent}%">
                        ${percent}%
                    </div>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    },

    /**
     * Update Progress Dialog
     * @param {number} current - Current progress
     * @param {number} total - Total items
     */
    updateProgress(current, total) {
        const percent = Math.round((current / total) * 100);
        Swal.update({
            html: `
                <div>Memproses <b>${current}</b> dari <b>${total}</b></div>
                <div class="progress mt-3">
                    <div class="progress-bar" role="progressbar" style="width: ${percent}%">
                        ${percent}%
                    </div>
                </div>
            `
        });
    },

    /**
     * Close any open alert
     */
    close() {
        Swal.close();
    },

    /**
     * Display Laravel session flash messages
     * Call this on page load to show any session messages
     */
    showSessionMessages() {
        // Check for success message
        const successMsg = document.querySelector('meta[name="flash-success"]');
        if (successMsg) {
            this.success(successMsg.content);
        }

        // Check for error message
        const errorMsg = document.querySelector('meta[name="flash-error"]');
        if (errorMsg) {
            this.error(errorMsg.content);
        }

        // Check for warning message
        const warningMsg = document.querySelector('meta[name="flash-warning"]');
        if (warningMsg) {
            this.warning(warningMsg.content);
        }

        // Check for info message
        const infoMsg = document.querySelector('meta[name="flash-info"]');
        if (infoMsg) {
            this.info(infoMsg.content);
        }
    }
};

// Auto-show session messages on page load
document.addEventListener('DOMContentLoaded', function() {
    AlertHelper.showSessionMessages();
});

// Make it globally available
window.AlertHelper = AlertHelper;
