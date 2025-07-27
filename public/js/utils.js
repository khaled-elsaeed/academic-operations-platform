// ===========================
// UTILITY FUNCTIONS
// ===========================
const Utils = {

    /**
     * Show success notification (normal alert or toast)
     * @param {string} message - Success message to display
     * @param {boolean|null} toast - Show as toast (true), normal alert (false), or auto (null)
     * @param {string|null} position - Toast position (e.g., 'top-end'), or null for default
     */
    showSuccess(message, toast = null, position = null) {
      if (toast === true) {
        Swal.fire({
          toast: true,
          position: position || 'top-end',
          icon: 'success',
          title: message,
          showConfirmButton: false,
          timer: 1800,
          timerProgressBar: true
        });
      } else {
        Swal.fire({
          icon: 'success',
          title: 'Success',
          text: message,
          showConfirmButton: true
        });
      }
    },
  
    /**
     * Show error alert
     * @param {string} message - Error message to display
     */
    showError(message) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: message
      });
    },
  
    /**
     * Show error alert with HTML content
     * @param {string} title - Error title
     * @param {string} htmlContent - HTML content for the error
     */
    showErrorHtml(title, htmlContent) {
      Swal.fire({
        icon: 'error',
        title: title,
        html: htmlContent
      });
    },
  

    /**
     * Set loading state for a button
     * @param {jQuery} $btn - Button element
     * @param {boolean} isLoading - Whether button is in loading state
     * @param {Object} options - Configuration options
     */
    setLoadingState($btn, isLoading, options = {}) {
      const defaults = {
        loadingText: 'Loading...',
        loadingIcon: 'bx bx-loader-alt bx-spin me-1',
        normalText: '',
        normalIcon: ''
      };
      const config = { ...defaults, ...options };

      if (isLoading) {
        $btn.prop('disabled', true)
            .html(`<i class="${config.loadingIcon}"></i>${config.loadingText}`);
      } else {
        $btn.prop('disabled', false)
            .html(`<i class="${config.normalIcon}"></i>${config.normalText}`);
      }
    },
  
    /**
     * Replace route parameter with actual ID
     * @param {string} route - Route with placeholder
     * @param {string|number} id - ID to replace placeholder with
     * @returns {string} - Route with ID replaced
     */
    replaceRouteId(route, id) {
      return route.replace(':id', id);
    },
  
    /**
     * Debounce function to limit function calls
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     * @returns {Function} - Debounced function
     */
    debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    },
  
    /**
     * Format validation errors for display
     * @param {Object} errors - Validation errors object
     * @returns {string} - Formatted HTML string
     */
    formatValidationErrors(errors) {
      let html = '<ul class="mb-0">';
      for (let key in errors) {
        if (Array.isArray(errors[key])) {
          errors[key].forEach(err => {
            html += `<li>${err}</li>`;
          });
        } else {
          html += `<li>${errors[key]}</li>`;
        }
      }
      html += '</ul>';
      return html;
    },
  
    /**
     * Handle AJAX error responses
     * @param {Object} xhr - XMLHttpRequest object
     * @param {string} defaultMessage - Default error message
     */
    handleAjaxError(xhr, defaultMessage = 'An error occurred. Please try again.') {
      let msg = xhr.responseJSON?.message || defaultMessage;
      let errors = xhr.responseJSON?.errors || {};
      
      let html = `<strong>${msg}</strong>`;
      if (Object.keys(errors).length > 0) {
        html += this.formatValidationErrors(errors);
      }
      
      this.showErrorHtml('Error', html);
    },
  
    /**
     * Show confirmation dialog
     * @param {Object} options - Configuration options
     * @returns {Promise} - Promise that resolves with user's choice
     */
    showConfirmDialog(options = {}) {
      const defaults = {
        title: 'Are you sure?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!',
        cancelButtonText: 'Cancel'
      };
      
      const config = { ...defaults, ...options };
      return Swal.fire(config);
    },
  
    /**
     * Validate form field
     * @param {jQuery} $field - Field to validate
     * @param {string} message - Validation message
     * @param {boolean} isValid - Whether field is valid
     */
    validateField($field, message, isValid) {
      const $feedback = $field.siblings('.invalid-feedback');
      
      if (isValid) {
        $field.removeClass('is-invalid').addClass('is-valid');
        $feedback.text('');
      } else {
        $field.removeClass('is-valid').addClass('is-invalid');
        if ($feedback.length === 0) {
          $field.after(`<div class="invalid-feedback">${message}</div>`);
        } else {
          $feedback.text(message);
        }
      }
    },
  
    /**
     * Clear validation states from form
     * @param {jQuery} $form - Form element
     */
    clearValidation($form) {
      $form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
      $form.find('.invalid-feedback').text('');
    },
  
    /**
     * Check if value is empty
     * @param {*} value - Value to check
     * @returns {boolean} - Whether value is empty
     */
    isEmpty(value) {
      if (value === null || value === undefined) return true;
      if (typeof value === 'string') return value.trim() === '';
      if (Array.isArray(value)) return value.length === 0;
      if (typeof value === 'object') return Object.keys(value).length === 0;
      return false;
    },
  
    /**
     * Redirect after delay
     * @param {string} url - URL to redirect to
     * @param {number} delay - Delay in milliseconds
     */
    redirectAfterDelay(url, delay = 2000) {
      setTimeout(() => {
        window.location.href = url;
      }, delay);
    },
  
    /**
     * Get URL parameter value
     * @param {string} name - Parameter name
     * @returns {string|null} - Parameter value or null
     */
    getUrlParameter(name) {
      const urlParams = new URLSearchParams(window.location.search);
      return urlParams.get(name);
    },

    /**
     * Populate a <select> element with options.
     * @param {jQuery} $select - jQuery object for the select element
     * @param {Array} items - Array of items to populate. Each item can be an object or a string/number.
     * @param {Object} options - Optional config:
     *   - valueField: property name for option value (default: 'id')
     *   - textField: property name for option text (default: 'name')
     *   - placeholder: placeholder text for the first option (default: 'Select')
     *   - selected: value to be selected by default
     *   - includePlaceholder: whether to include placeholder (default: true)
     * @param {boolean} isSelect2 - true if select2 is used, false for normal select (default: false)
     */
    populateSelect($select, items, options = {}, isSelect2 = false) {
      // Always treat $select as a jQuery object
      // (Assume all callers pass a jQuery object)
      const {
        valueField = 'id',
        textField = 'name',
        placeholder = 'Select',
        selected = null,
        includePlaceholder = true
      } = options;

      let html = '';
      if (includePlaceholder) {
        html += `<option value="">${placeholder}</option>`;
      }

      items.forEach(item => {
        let value, text;
        if (typeof item === 'object') {
          value = item[valueField];
          text = item[textField];
        } else {
          value = item;
          text = item;
        }
        html += `<option value="${value}"${selected != null && value == selected ? ' selected' : ''}>${text}</option>`;
      });

      $select.html(html);

      // If select2 is requested, trigger the appropriate event
      if (isSelect2) {
        if ($select.hasClass('select2-hidden-accessible')) {
          $select.trigger('change.select2');
        } else {
          $select.trigger('change');
        }
      } else {
        $select.trigger('change');
      }
    },

    /**
     * Initialize select2 on a select element with optional config.
     * @param {jQuery} $select - jQuery object for the select element
     * @param {Object} options - select2 options (optional)
     */
    initSelect2($select, options = {}) {
      if ($select.length > 0) {
        
        // If select2 is already applied, destroy it first
        if ($select.hasClass('select2-hidden-accessible')) {
          $select.select2('destroy');
        }

        $select.select2({
          theme: 'bootstrap-5',
          width: '100%',
          placeholder:  options.placeholder,
          allowClear: true,
          dropdownParent: options.dropdownParent,
          ...options
        });
      }
    },

    /**
     * Show the page loader overlay.
     */
    showPageLoader() {
      const loader = document.getElementById('pageLoader');
      if (loader) {
        loader.classList.remove('fade-out');
        // Hide scrollbars when loader is shown
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';
      }
    },

    /**
     * Hide the page loader overlay.
     */
    hidePageLoader() {
      const loader = document.getElementById('pageLoader');
      if (loader) {
        loader.classList.add('fade-out');
        // Restore scrollbars when loader is hidden
        document.documentElement.style.overflow = '';
        document.body.style.overflow = '';
      }
    }

};
