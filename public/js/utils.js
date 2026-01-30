const Utils = {
  /**
   * Get RTL status from document
   */
  isRtl() {
    return document.documentElement.getAttribute('lang') === 'ar';
  },

  /**
   * Check if value is empty
   */
  isEmpty(value) {
    if (value === null || value === undefined) return true;
    if (typeof value === 'string') return value.trim() === '';
    if (Array.isArray(value)) return value.length === 0;
    if (typeof value === 'object') return Object.keys(value).length === 0;
    return false;
  },

  /**
   * Escape HTML to prevent XSS attacks
   * @param {string} text - Text to escape
   * @returns {string} Escaped text safe for HTML insertion
   */
  escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
  },

  /**
   * Replace route parameters with actual values
   * Can accept either:
   * - replaceRouteId(route, id) - replaces ':id' with id
   * - replaceRouteId(route, replacements) - replaces multiple parameters where replacements is an object like {id: value, detailId: value}
   */
  replaceRouteId(route, replacements) {
    if (typeof replacements === 'object' && replacements !== null) {
      // Handle object of replacements
      let result = route;
      for (const [key, value] of Object.entries(replacements)) {
        result = result.replace(`:${key}`, value);
      }
      return result;
    } else {
      // Backward compatibility: replace ':id' with the provided value
      return route.replace(':id', replacements);
    }
  },

  /**
   * Debounce function to limit function calls
   */
  debounce(func, wait) {
    let timeout;
    return (...args) => {
      clearTimeout(timeout);
      timeout = setTimeout(() => func(...args), wait);
    };
  },

  /**
   * Get URL parameter value
   */
  getUrlParameter(name) {
    return new URLSearchParams(window.location.search).get(name);
  },

  /**
   * Redirect after delay
   */
  redirectAfterDelay(url, delay = 2000) {
    setTimeout(() => window.location.href = url, delay);
  },

  /**
   * Format date for HTML date input (yyyy-MM-dd)
   */
  formatDateTime(dateString) {
    if (!dateString) return '';

    try {
      // Handle ISO datetime strings like "2025-02-08T00:00:00.000000Z"
      const date = new Date(dateString);
      if (isNaN(date.getTime())) return '';

      // Format as yyyy-MM-dd
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');

      return `${year}-${month}-${day}`;
    } catch (error) {
      console.warn('Invalid date format:', dateString);
      return '';
    }
  },

  /**
   * Format time range from start and end times
   */
  formatTimeRange(startTime, endTime) {
    if (!startTime || !endTime) return '';

    try {
      const formatTime = (timeStr) => {
        const [hours, minutes] = timeStr.split(':').map(Number);
        const period = hours >= 12 ? 'PM' : 'AM';
        const displayHours = hours % 12 || 12;
        return `${displayHours}:${String(minutes).padStart(2, '0')} ${period}`;
      };

      return `${formatTime(startTime)} - ${formatTime(endTime)}`;
    } catch (error) {
      console.warn('Invalid time format:', startTime, endTime);
      return '';
    }
  },

  /**
   * Parse time string to minutes since midnight
   */
  parseTime(timeStr) {
    if (!timeStr) return null;

    try {
      const [hours, minutes, seconds] = timeStr.split(':').map(Number);
      return hours * 60 + minutes;
    } catch (error) {
      console.warn('Invalid time format:', timeStr);
      return null;
    }
  },

  // ===========================
  // NOTIFICATIONS & ALERTS
  // ===========================

  /**
   * Show success notification
   */
  showSuccess(message, toast = false, position = null) {
    const rtl = this.isRtl();

    if (toast) {
      Swal.fire({
        toast: true,
        position: position ?? (rtl ? 'top-start' : 'top-end'),
        icon: 'success',
        title: message,
        showConfirmButton: false,
        timer: 1800,
        timerProgressBar: true
      });
    } else {
      Swal.fire({
        icon: 'success',
        title: rtl ? 'نجاح' : 'Success',
        text: message,
        showConfirmButton: true
      });
    }
  },

  /**
   * Show error alert
   */
  showError(message, toast = false, position = null) {
    const rtl = this.isRtl();

    if (toast) {
      Swal.fire({
        toast: true,
        position: position ?? (rtl ? 'top-start' : 'top-end'),
        icon: 'error',
        title: message,
        showConfirmButton: false,
        timer: 2500,
        timerProgressBar: true,
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: rtl ? 'خطأ' : 'Error',
        text: message,
      });
    }
  },

  /**
   * Show error alert with HTML content
   */
  showErrorHtml(title, htmlContent) {
    Swal.fire({
      icon: 'error',
      title,
      html: htmlContent
    });
  },

  /**
   * Show confirmation dialog
   */
  async showConfirmDialog(options = {}) {
    const rtl = this.isRtl();
    const defaults = {
      title: rtl ? 'هل أنت متأكد؟' : 'Are you sure?',
      text: rtl ? 'لا يمكن التراجع عن هذا الإجراء.' : 'This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: rtl ? 'نعم، تابع!' : 'Yes, proceed!',
      cancelButtonText: rtl ? 'إلغاء' : 'Cancel'
    };

    return Swal.fire({ ...defaults, ...options });
  },

  // ===========================
  // ERROR HANDLING
  // ===========================

  /**
   * Format validation errors for display
   */
  formatValidationErrors(errors) {
    const errorList = Object.entries(errors).flatMap(([key, value]) =>
      Array.isArray(value) ? value : [value]
    ).map(err => `<li>${err}</li>`).join('');

    return `<ul class="mb-0">${errorList}</ul>`;
  },

  /**
   * Universal error handler for all types of errors
   */
  handleError(error, defaultMessage = null, toast = false) {
    const rtl = this.isRtl();

    if (!defaultMessage) {
      defaultMessage = rtl ? 'حدث خطأ. يرجى المحاولة مرة أخرى.' : 'An error occurred. Please try again.';
    }

    let errorMessage = defaultMessage;
    let errorTitle = rtl ? 'خطأ' : 'Error';
    let validationErrors = null;

    // Extract error message and validation errors from various error formats
    if (error?.responseJSON) {
      errorMessage = error.responseJSON.message || defaultMessage;
      validationErrors = error.responseJSON.errors;
    } else if (error?.responseText) {
      try {
        const parsed = JSON.parse(error.responseText);
        errorMessage = parsed.message || defaultMessage;
        validationErrors = parsed.errors;
      } catch (e) {
        errorMessage = error.statusText || defaultMessage;
      }
    } else if (error?.response?.data) {
      errorMessage = error.response.data.message || defaultMessage;
      validationErrors = error.response.data.errors;
    } else if (error?.data?.message) {
      errorMessage = error.data.message;
      validationErrors = error.data.errors;
    } else if (error?.message && typeof error.message === 'string') {
      errorMessage = error.message;
    } else if (typeof error === 'string') {
      errorMessage = error;
    }

    // Display error
    if (validationErrors && Object.keys(validationErrors).length > 0) {
      const validationHtml = this.formatValidationErrors(validationErrors);
      const html = `<strong>${errorMessage}</strong>${validationHtml}`;
      this.showErrorHtml(errorTitle, html);
    } else {
      this.showError(errorMessage, toast);
    }

    console.error('Error handled:', error);
  },

  // ===========================
  // HTTP UTILITIES
  // ===========================

  /**
   * Check if API response is successful
   * @param {object} response - API response object
   * @returns {boolean} - True if response indicates success
   */
  isResponseSuccess(response) {
    if (!response) return false;
    return response.success === true || (response.success !== false && (response.data !== undefined || response.status === 'success'));
  },

  /**
   * Extract data from API response
   * @param {object} response - API response object
   * @param {*} defaultValue - Default value if no data found
   * @returns {*} - The data from response or default value
   */
  getResponseData(response, defaultValue = null) {
    if (!response) return defaultValue;
    return response.data !== undefined ? response.data : response;
  },

  /**
   * Make an HTTP GET request
   * @param {string} url - The URL to request
   * @param {object} options - Optional configuration (params, headers, etc.)
   * @returns {Promise} - jQuery AJAX promise
   */
  async get(url, options = {}) {
    return await $.ajax({
      url: url,
      method: 'GET',
      data: options.params || {},
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'Accept': 'application/json',
        ...options.headers
      },
      ...options
    });
  },

  /**
   * Make an HTTP POST request
   * @param {string} url - The URL to request
   * @param {object|FormData} data - Data to send
   * @param {object} options - Optional configuration
   * @returns {Promise} - jQuery AJAX promise
   */
  async post(url, data = {}, options = {}) {
    const isFormData = data instanceof FormData;

    return await $.ajax({
      url: url,
      method: 'POST',
      data: data,
      processData: !isFormData,
      contentType: isFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'Accept': 'application/json',
        ...options.headers
      },
      ...options
    });
  },

  /**
   * Make an HTTP PUT request
   * @param {string} url - The URL to request
   * @param {object|FormData} data - Data to send
   * @param {object} options - Optional configuration
   * @returns {Promise} - jQuery AJAX promise
   */
  async put(url, data = {}, options = {}) {
    const isFormData = data instanceof FormData;

    if (isFormData) {
      data.append('_method', 'PUT');
    } else {
      data = { ...data, _method: 'PUT' };
    }

    return await $.ajax({
      url: url,
      method: 'POST',
      data: data,
      processData: !isFormData,
      contentType: isFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'Accept': 'application/json',
        ...options.headers
      },
      ...options
    });
  },

  /**
   * Make an HTTP DELETE request
   * @param {string} url - The URL to request
   * @param {object} data - Optional data to send
   * @param {object} options - Optional configuration
   * @returns {Promise} - jQuery AJAX promise
   */
  async delete(url, data = {}, options = {}) {
    return await $.ajax({
      url: url,
      method: 'DELETE',
      data: { ...data, _method: 'DELETE' },
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'Accept': 'application/json',
        ...options.headers
      },
      ...options
    });
  },

  /**
   * Make an HTTP PATCH request
   * @param {string} url - The URL to request
   * @param {object|FormData} data - Data to send
   * @param {object} options - Optional configuration
   * @returns {Promise} - jQuery AJAX promise
   */
  async patch(url, data = {}, options = {}) {
    const isFormData = data instanceof FormData;

    if (isFormData) {
      data.append('_method', 'PATCH');
    } else {
      data = { ...data, _method: 'PATCH' };
    }

    return await $.ajax({
      url: url,
      method: 'POST',
      data: data,
      processData: !isFormData,
      contentType: isFormData ? false : 'application/x-www-form-urlencoded; charset=UTF-8',
      headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        'Accept': 'application/json',
        ...options.headers
      },
      ...options
    });
  },

  // ===========================
  // FORM UTILITIES
  // ===========================

  /**
   * Validate form field
   */
  validateField(field, message, isValid) {
    const $field = $(field);
    let $feedback = $field.siblings('.invalid-feedback');

    if (isValid) {
      $field.removeClass('is-invalid').addClass('is-valid');
      $feedback.text('');
    } else {
      $field.removeClass('is-valid').addClass('is-invalid');
      if ($feedback.length === 0) {
        $feedback = $(`<div class="invalid-feedback">${message}</div>`);
        $field.after($feedback);
      } else {
        $feedback.text(message);
      }
    }
  },

  /**
   * Clear validation states from form
   */
  clearValidation(form) {
    const $form = $(form);
    $form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
    $form.find('.invalid-feedback').text('');
  },

  /**
   * Reset form fields and clear validation states
   */
  resetForm(formId) {
    const $form = $(`#${formId}`);
    if ($form.length === 0) return;

    $form[0].reset();

    // Reset select2 fields
    $form.find('select').each((_, select) => {
      const $select = $(select);
      if ($select.hasClass('select2-hidden-accessible')) {
        $select.val('').trigger('change.select2');
      }
    });
  },

  // ===========================
  // ELEMENT MANIPULATION
  // ===========================

  /**
   * Set the text content, value, or HTML of an element
   */
  setElementText(selector, text, isHtml = false) {
    const $el = $(selector);
    if ($el.length === 0) return;

    if ($el.is('input, select, textarea')) {
      $el.val(text);
      if ($el.is('select') && $el.hasClass('select2-hidden-accessible')) {
        $el.trigger('change.select2');
      } else if ($el.is('select')) {
        $el.trigger('change');
      }
    } else {
      if (isHtml) {
        $el.html(text);
      } else {
        $el.text(text);
      }
    }
  },

  /**
   * Show element
   */
  show(selector) {
    $(selector).removeClass('d-none');
  },

  /**
   * Hide element
   */
  hide(selector) {
    $(selector).addClass('d-none');
  },

  /**
   * Toggle element visibility
   */
  toggle(selector, show) {
    if (show === undefined) {
      $(selector).toggleClass('d-none');
    } else {
      $(selector).toggleClass('d-none', !show);
    }
  },

  /**
   * Disable or enable an element
   */
  disable(el, disabled = true) {
    $(el).prop('disabled', !!disabled);
  },

  /**
   * Get data attributes from element
   */
  getElementData(element, attributes = null) {
    const $element = $(element);
    if ($element.length === 0) return null;

    // Shorthand for single 'id' attribute
    if (Array.isArray(attributes) && attributes.length === 1 && attributes[0] === 'id') {
      return $element.data('id');
    }

    const data = {};
    if (Array.isArray(attributes)) {
      attributes.forEach(attr => {
        data[attr] = $element.data(attr);
      });
    } else {
      Object.assign(data, $element.data());
    }
    return data;
  },

  /**
   * Show/hide loader elements
   */
  toggleLoader(loaderId, show = true) {
    $(`#${loaderId}`).toggleClass('d-none', !show);
  },

  showLoader(loaderId) {
    this.toggleLoader(loaderId, true);
  },

  hideLoader(loaderId) {
    this.toggleLoader(loaderId, false);
  },

  /**
   * Get loading HTML for displaying in elements
   */
  getLoadingHtml() {
    const rtl = this.isRtl();
    const loadingText = rtl ? 'جاري التحميل...' : 'Loading...';
    return `<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-1 text-primary"></i><div class="mt-2 text-muted">${loadingText}</div></div>`;
  },

  /**
   * Show loading state in an element
   */
  showLoading(selector, message = null) {
    const rtl = this.isRtl();
    const loadingText = message || (rtl ? 'جاري التحميل...' : 'Loading...');
    const html = `<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-1 text-primary"></i><div class="mt-2 text-muted">${this.escapeHtml(loadingText)}</div></div>`;
    $(selector).html(html);
  },

  /**
   * Show empty state in an element
   */
  showEmptyState(selector, icon, message) {
    const html = `
      <div class="text-center py-4">
        <i class="${icon} fs-1 text-muted"></i>
        <div class="mt-2 text-muted">${this.escapeHtml(message)}</div>
      </div>
    `;
    $(selector).html(html);
  },

  /**
   * Toggle no-data message visibility
   */
  toggleNoData(noDataId, show = true) {
    $(`#${noDataId}`).toggleClass('d-none', !show);
  },

  showNoData(noDataId) {
    this.toggleNoData(noDataId, true);
  },

  hideNoData(noDataId) {
    this.toggleNoData(noDataId, false);
  },

  // ===========================
  // BUTTON UTILITIES
  // ===========================

  /**
   * Disable or enable a button
   * @param {string|jQuery} button - Button selector or jQuery object
   * @param {boolean} disabled - Whether to disable (true) or enable (false)
   */
  disableButton(button, disabled = true) {
    const $btn = $(button);
    $btn.prop('disabled', !!disabled);
    if (disabled) {
      $btn.addClass('disabled');
    } else {
      $btn.removeClass('disabled');
    }
  },

  /**
   * Enable a button
   * @param {string|jQuery} button - Button selector or jQuery object
   */
  enableButton(button) {
    this.disableButton(button, false);
  },

  /**
   * Set loading state for a button
   */
  setLoadingState(btn, isLoading, options = {}) {
    const rtl = this.isRtl();
    const $btn = $(btn);

    const defaults = {
      loadingText: rtl ? 'جاري التحميل...' : 'Loading...',
      loadingIcon: 'bx bx-loader-alt bx-spin me-1',
      normalText: '',
      normalIcon: '',
      rounded: true
    };

    const config = { ...defaults, ...options };

    // Override text and icon for rounded buttons
    if (config.rounded) {
      if (isLoading && !options.hasOwnProperty('loadingText')) {
        config.loadingText = '';
      }
      if (!isLoading && !options.hasOwnProperty('normalText')) {
        config.normalText = '';
      }
      config.loadingIcon = config.loadingIcon.replace(/\s+(me-1|ms-1|mr-1|ml-1)/, '');
    }

    // Preserve original HTML so we can restore it when stopping the
    // loading state. This prevents the button text/icon from disappearing
    // after a loading cycle when callers don't pass `normalText`.
    if (!$btn.data('original-html')) {
      $btn.data('original-html', $btn.html());
    }

    if (isLoading) {
      this.disable(btn, true);
      const iconHtml = config.loadingIcon ? `<i class="${config.loadingIcon}"></i>` : '';
      const textHtml = config.loadingText || '';
      const spacer = (iconHtml && textHtml) ? ' ' : '';
      $btn.html(`${iconHtml}${spacer}${textHtml}`);
    } else {
      this.disable(btn, false);

      if (options.hasOwnProperty('normalText') || options.hasOwnProperty('normalIcon')) {
        const iconHtml = config.normalIcon ? `<i class="${config.normalIcon}"></i>` : '';
        const textHtml = config.normalText || '';
        const spacer = (iconHtml && textHtml) ? ' ' : '';
        $btn.html(`${iconHtml}${spacer}${textHtml}`);
      } else {
        const original = $btn.data('original-html') || '';
        $btn.html(original);
      }
    }
  },

  // ===========================
  // SELECT & DROPDOWN UTILITIES
  // ===========================

  /**
   * Populate select element with options
   */
  populateSelect(select, items, options = {}, isSelect2 = false) {
    const $select = $(select);
    const {
      valueField = 'id',
      textField = 'name',
      dataAttributes = {},
      placeholder = 'Select',
      selected = null,
      includePlaceholder = true,
      triggerChange = true
    } = options;

    const optionsHtml = items.map(item => {
      const value = typeof item === 'object' ? item[valueField] : item;
      const text = typeof item === 'object' ? item[textField] : item;

      // Build data-* attributes
      const dataAttrs = typeof item === 'object'
        ? Object.entries(dataAttributes)
          .filter(([_, fieldName]) => item[fieldName] != null)
          .map(([attrName, fieldName]) => {
            const kebabAttr = attrName.replace(/([A-Z])/g, '-$1').toLowerCase();
            return `data-${kebabAttr}="${item[fieldName]}"`;
          })
          .join(' ')
        : '';

      const isSelected = selected != null && value == selected ? ' selected' : '';
      return `<option value="${value}"${dataAttrs ? ' ' + dataAttrs : ''}${isSelected}>${text}</option>`;
    }).join('');

    const html = includePlaceholder
      ? `<option value="">${placeholder}</option>${optionsHtml}`
      : optionsHtml;

    $select.html(html);

    if (triggerChange) {
      $select.trigger(isSelect2 && $select.hasClass('select2-hidden-accessible')
        ? 'change.select2'
        : 'change');
    }
  },

  /**
   * Initialize select2 on a select element
   */
  initSelect2(select, options = {}) {
    const $select = $(select);
    if ($select.length === 0) return;

    // Destroy existing select2 if present
    if ($select.hasClass('select2-hidden-accessible')) {
      $select.select2('destroy');
    }

    $select.select2({
      theme: 'bootstrap-5',
      width: '100%',
      allowClear: true,
      ...options
    });
  },

  /**
   * Clear select2 elements
   * @param {string|Array} selectors - Single selector or array of selectors
   */
  clearSelect2(selectors) {
    const selectorArray = Array.isArray(selectors) ? selectors : [selectors];
    selectorArray.forEach((selector) => {
      const $element = $(selector);
      if ($element.length && $element.hasClass('select2-hidden-accessible')) {
        $element.val(null).trigger('change.select2');
      }
    });
  },

  // ===========================
  // DATATABLE UTILITIES
  // ===========================

  /**
   * Check if element is a DataTable
   */
  isDataTable(tableSelector) {
    try {
      const $table = $(tableSelector);
      return $table.length > 0 && $.fn.DataTable.isDataTable($table);
    } catch (error) {
      return false;
    }
  },

  /**
   * Reload DataTable if it exists
   */
  reloadDataTable(tableSelector, callback = null, resetPaging = false) {
    try {
      const selector = tableSelector.startsWith('#') ? tableSelector : '#' + tableSelector;
      const $table = $(selector);

      if ($table.length === 0) {
        console.warn('DataTable reload: Table element not found for selector:', selector);
        return false;
      }

      if (!$.fn.DataTable.isDataTable($table)) {
        console.warn('DataTable reload: Element is not a DataTable for selector:', selector);
        return false;
      }

      $table.DataTable().ajax.reload(callback, !resetPaging);
      return true;
    } catch (error) {
      console.error('DataTable reload error:', error);
      return false;
    }
  },

  /**
   * Destroy DataTable if it exists
   */
  destroyDataTable(tableSelector, remove = false) {
    try {
      const $table = $(tableSelector);

      if ($table.length > 0 && $.fn.DataTable.isDataTable($table)) {
        $table.DataTable().destroy(remove);
        return true;
      }
      return false;
    } catch (error) {
      console.error('DataTable destroy error:', error);
      return false;
    }
  },

  // ===========================
  // MANAGERS & FACTORIES
  // ===========================

  /**
   * Create statistics manager
   */
  createStatsManager(config) {
    const rtl = this.isRtl();
    const defaults = {
      apiMethod: null,
      statsKeys: [],
      subStatsConfig: {},
      urlParams: {},
      onError: rtl ? 'فشل تحميل الإحصائيات' : 'Failed to load statistics',
      dataPath: 'data',
      successCheck: (response) => response.success !== false
    };

    const settings = { ...defaults, ...config };

    if (!settings.apiMethod || !Array.isArray(settings.statsKeys) || settings.statsKeys.length === 0) {
      console.error('StatsManager: apiMethod and statsKeys are required');
      return null;
    }

    return {
      init() {
        this.load();
      },

      async load() {
        this.toggleAllLoadingStates(true);
        try {
          const params = typeof settings.urlParams === 'function'
            ? settings.urlParams()
            : settings.urlParams;
          const response = await settings.apiMethod(params);
          this.handleSuccess(response);
        } catch (error) {
          this.handleError(error);
        } finally {
          this.toggleAllLoadingStates(false);
        }
      },

      handleSuccess(response) {
        if (settings.successCheck(response)) {
          const stats = this.getStatsData(response);
          this.updateAllStats(stats);
        } else {
          this.setAllStatsToNA();
        }
      },

      getStatsData(response) {
        return settings.dataPath.split('.').reduce((data, part) => data?.[part], response) ?? {};
      },

      updateAllStats(stats) {
        settings.statsKeys.forEach(key => {
          const statData = stats[key];

          if (statData) {
            const { value, lastUpdateTime } = this.extractStatData(statData);
            this.updateStatElement(key, value, lastUpdateTime);

            if (settings.subStatsConfig[key]) {
              this.updateSubStats(key, statData);
            }
          } else {
            this.updateStatElement(key, 'N/A', 'N/A');

            if (settings.subStatsConfig[key]) {
              this.setSubStatsToNA(key);
            }
          }
        });
      },

      extractStatData(statData) {
        if (typeof statData === 'object') {
          return {
            value: statData.count ?? statData.value ?? statData.title ?? statData,
            lastUpdateTime: statData.lastUpdateTime ?? statData.updated_at ?? '--'
          };
        }
        return { value: statData, lastUpdateTime: '--' };
      },

      updateSubStats(statKey, statData) {
        const subStatsKeys = settings.subStatsConfig[statKey];
        if (!Array.isArray(subStatsKeys)) return;

        subStatsKeys.forEach(subStatKey => {
          const subValue = statData?.[subStatKey] ??
            statData?.subStats?.[subStatKey] ??
            statData?.breakdown?.[subStatKey] ??
            statData?.details?.[subStatKey] ??
            'N/A';

          this.updateSubStatElement(statKey, subStatKey, subValue);
        });
      },

      setSubStatsToNA(statKey) {
        const subStatsKeys = settings.subStatsConfig[statKey];
        if (!Array.isArray(subStatsKeys)) return;

        subStatsKeys.forEach(subStatKey => {
          this.updateSubStatElement(statKey, subStatKey, 'N/A');
        });
      },

      updateSubStatElement(statKey, subStatKey, value) {
        Utils.setElementText(`#${statKey}-${subStatKey}-value`, value ?? 'N/A');
      },

      handleError(error) {
        this.setAllStatsToNA();
        Utils.handleError(error, settings.onError);
      },

      updateStatElement(elementId, value, lastUpdateTime) {
        Utils.setElementText(`#${elementId}-value`, value ?? '0');
        Utils.setElementText(`#${elementId}-last-updated`, lastUpdateTime ?? '--');
      },

      setAllStatsToNA() {
        settings.statsKeys.forEach(elementId => {
          Utils.setElementText(`#${elementId}-value`, 'N/A');
          Utils.setElementText(`#${elementId}-last-updated`, 'N/A');

          if (settings.subStatsConfig[elementId]) {
            this.setSubStatsToNA(elementId);
          }
        });
      },

      toggleLoadingState(elementId, isLoading) {
        const selectors = {
          value: `#${elementId}-value`,
          loader: `#${elementId}-loader`,
          updated: `#${elementId}-last-updated`,
          updatedLoader: `#${elementId}-last-updated-loader`
        };

        Object.entries(selectors).forEach(([key, selector]) => {
          const $el = $(selector);
          $el.toggleClass('d-none', (key === 'loader' || key === 'updatedLoader') ? !isLoading : isLoading);
        });

        if (settings.subStatsConfig[elementId]) {
          this.toggleSubStatsLoadingState(elementId, isLoading);
        }
      },

      toggleSubStatsLoadingState(statKey, isLoading) {
        const subStatsKeys = settings.subStatsConfig[statKey];
        if (!Array.isArray(subStatsKeys)) return;

        subStatsKeys.forEach(subStatKey => {
          $(`#${statKey}-${subStatKey}-value`).toggleClass('d-none', isLoading);
          $(`#${statKey}-${subStatKey}-loader`).toggleClass('d-none', !isLoading);
        });
      },

      toggleAllLoadingStates(isLoading) {
        settings.statsKeys.forEach(elementId => {
          this.toggleLoadingState(elementId, isLoading);
        });
      },

      refresh() {
        this.load();
      },

      getConfig() {
        return { ...settings };
      },

      updateSubStatsConfig(newSubStatsConfig) {
        Object.assign(settings.subStatsConfig, newSubStatsConfig);
      }
    };
  },

  /**
   * Create chart loading state manager
   */
  createChartLoadingManager(chartIds = []) {
    return {
      charts: chartIds,

      showAll() {
        this.charts.forEach(chartId => {
          Utils.showLoader(`${chartId}-loader`);
          Utils.hideNoData(`${chartId}-no-data`);
        });
      },

      hideAll() {
        this.charts.forEach(chartId => Utils.hideLoader(`${chartId}-loader`));
      },

      showNoDataFor(chartId) {
        Utils.hideLoader(`${chartId}-loader`);
        Utils.showNoData(`${chartId}-no-data`);
      },

      hideNoDataFor(chartId) {
        Utils.hideNoData(`${chartId}-no-data`);
      }
    };
  },

  /**
   * Create modal manager
   */
  createModalManager(modalId) {
    const id = modalId.startsWith('#') ? modalId : `#${modalId}`;
    const $modal = $(id);

    return {
      show() {
        $modal.modal('show');
      },

      hide() {
        $modal.modal('hide');
      },

      setTitle(title) {
        $modal.find('.modal-title').text(title);
      },

      setSubmitButtonText(buttonId, text) {
        const btnId = buttonId.startsWith('#') ? buttonId : `#${buttonId}`;
        $(btnId).text(text);
      },

      resetForm(formId) {
        const fId = formId.startsWith('#') ? formId : `#${formId}`;
        $(fId)[0].reset();
      },

      setupAddMode(config) {
        this.resetForm(config.formId);
        this.setTitle(config.title);
        this.setSubmitButtonText(config.submitButtonId, config.submitButtonText);

        if (config.hiddenFieldsToClear) {
          config.hiddenFieldsToClear.forEach(fieldId => {
            const fId = fieldId.startsWith('#') ? fieldId : `#${fieldId}`;
            $(fId).val('');
          });
        }
      },

      setupEditMode(config) {
        this.setTitle(config.title);
        this.setSubmitButtonText(config.submitButtonId, config.submitButtonText);
      }
    };
  },

  /**
   * Create form manager
   */
  createFormManager(formId) {
    const id = formId.startsWith('#') ? formId : `#${formId}`;
    const $form = $(id);

    return {
      getData() {
        return Object.fromEntries(new FormData($form[0]).entries());
      },

      getFormData() {
        return new FormData($form[0]);
      },

      getSerialized() {
        return $form.serialize();
      },

      reset() {
        $form[0].reset();
      },

      populate(data) {
        Object.entries(data).forEach(([key, value]) => {
          const $field = $form.find(`[name="${key}"], #${key}`);

          if ($field.length) {
            let fieldValue = value;
            let callback = null;

            // Check if value is an object with value and callback
            if (typeof value === 'object' && value !== null && 'value' in value) {
              fieldValue = value.value;
              callback = value.callback;
            }

            if ($field.is(':checkbox')) {
              $field.prop('checked', !!fieldValue);
            } else if ($field.is(':radio')) {
              $field.filter(`[value="${fieldValue}"]`).prop('checked', true);
            } else if ($field.hasClass('select2-hidden-accessible')) {
              $field.val(fieldValue).trigger('change.select2');
            } else {
              $field.val(fieldValue);
            }

            // Execute callback if provided
            if (typeof callback === 'function') {
              callback(fieldValue, $field);
            }
          }
        });
      },

      getFieldValue(fieldName) {
        const $field = $form.find(`[name="${fieldName}"], #${fieldName}`);

        if ($field.is(':checkbox')) {
          return $field.is(':checked');
        } else if ($field.is(':radio')) {
          return $form.find(`[name="${fieldName}"]:checked`).val();
        }
        return $field.val();
      },

      setFieldValue(fieldName, value) {
        const $field = $form.find(`[name="${fieldName}"], #${fieldName}`);

        if ($field.is(':checkbox')) {
          $field.prop('checked', !!value);
        } else if ($field.is(':radio')) {
          $field.filter(`[value="${value}"]`).prop('checked', true);
        } else if ($field.hasClass('select2-hidden-accessible')) {
          $field.val(value).trigger('change.select2');
        } else {
          $field.val(value);
        }
      },

      clearFields(fieldNames) {
        fieldNames.forEach(fieldName => {
          const $field = $form.find(`[name="${fieldName}"], #${fieldName}`);

          if ($field.is(':checkbox') || $field.is(':radio')) {
            $field.prop('checked', false);
          } else if ($field.hasClass('select2-hidden-accessible')) {
            $field.val('').trigger('change.select2');
          } else {
            $field.val('');
          }
        });
      }
    };
  },

  /**
   * Create search manager with debouncing
   */
  createSearchManager(config) {
    const {
      searchFields,
      clearButtonId,
      tableId,
      debounceDelay = 500
    } = config;

    return {
      init() {
        this.bindSearchEvents();
        this.bindClearButton();
      },

      bindSearchEvents() {
        const debouncedReload = Utils.debounce(() => {
          Utils.reloadDataTable(tableId);
        }, debounceDelay);

        searchFields.forEach(selector => {
          $(selector).on('keyup change', debouncedReload);
        });
      },

      bindClearButton() {
        if (clearButtonId) {
          $(clearButtonId).on('click', () => {
            this.clearAllFields();
            Utils.reloadDataTable(tableId);
          });
        }
      },

      clearAllFields() {
        searchFields.forEach(selector => {
          const $field = $(selector);
          if ($field.hasClass('select2-hidden-accessible')) {
            $field.val('').trigger('change.select2');
          } else {
            $field.val('');
          }
        });
      }
    };
  },

  /**
   * Populate view modal with data
   * @param {object} data - Data object to populate
   * @param {object} fieldMapping - Mapping of data keys to element IDs
   * @param {string} defaultValue - Default value if data is empty
   */
  populateViewModal(data, fieldMapping, defaultValue = '--') {
    Object.entries(fieldMapping).forEach(([dataKey, config]) => {
      let elementId, transform;

      if (typeof config === 'string') {
        elementId = config;
        transform = (val) => val;
      } else {
        elementId = config.id;
        transform = config.transform || ((val) => val);
      }

      const value = data[dataKey];
      const displayValue = value !== null && value !== undefined ?
        transform(value) :
        defaultValue;

      const selector = elementId.startsWith('#') ? elementId : `#${elementId}`;
      $(selector).text(displayValue);
    });
  },


  /**
   * Generic Async Task Manager
   * Handles any async operation with progress tracking and status updates
   * 
   * @param {Object} config - Configuration object
   * @returns {Object} Task manager instance
   */
  createAsyncTaskManager(config) {
    console.log('Creating async task manager with config:', config);
    const defaults = {
      // Routes
      startRoute: '',
      checkStatusRoute: '',
      downloadRoute: '',
      cancelRoute: '',

      // Modal IDs
      formModalId: '',
      progressModalId: '',

      // Polling settings
      pollInterval: 3000,
      maxRetries: 3,

      // UI Configuration
      taskName: 'Task',
      showDownloadButton: true,
      showCancelButton: true,

      // Progress display fields (dynamic table rows)
      progressFields: [],
      // Example: [
      //     { key: 'current_step', label: 'Current Step', type: 'text' },
      //     { key: 'items_processed', label: 'Items Processed', type: 'number' }
      // ]

      // Completion display fields (dynamic table rows)
      completionFields: [],
      // Example: [
      //     { key: 'file_name', label: 'File Name', type: 'text' },
      //     { key: 'file_size_mb', label: 'File Size', type: 'size', suffix: ' MB' },
      //     { key: 'total_records', label: 'Total Records', type: 'number' }
      // ]

      // Callbacks
      onBeforeStart: null,      // (formData) => boolean | Promise<boolean>
      onStart: null,            // (response) => void
      onProgress: null,         // (data) => void
      onComplete: null,         // (data) => void
      onFailed: null,           // (data) => void
      onCancel: null,           // () => void

      // Custom validators
      validateForm: null,       // () => { valid: boolean, message?: string }

      // Translations
      translations: {
        processing: 'Processing...',
        taskInitializing: 'Initializing...',
        taskPreparing: 'Preparing...',
        taskCompleted: 'Task completed successfully!',
        taskFailed: 'Task failed',
        taskCancelled: 'Task cancelled',
        downloadFailed: 'Failed to download file',
        statusCheckFailed: 'Failed to check task status',
      }
    };

    const settings = { ...defaults, ...config };
    const { translations } = settings;

    return {
      taskUuid: null,
      retryCount: 0,
      progressInterval: null,
      progressModal: null,
      formModal: null,

      /**
       * Initialize the task manager
       */
      init() {
        console.log('Initializing task manager, progressModalId:', settings.progressModalId);
        if (settings.progressModalId) {
          const modalEl = document.getElementById(settings.progressModalId);
          console.log('Modal element found:', modalEl);
          console.log('Bootstrap defined:', typeof bootstrap !== 'undefined');
          if (modalEl && typeof bootstrap !== 'undefined') {
            this.progressModal = new bootstrap.Modal(modalEl, {
              backdrop: 'static',
              keyboard: false
            });
            console.log('Progress modal initialized:', this.progressModal);
          } else {
            console.log('Failed to initialize progress modal');
          }
        }

        if (settings.formModalId) {
          const formModalEl = document.getElementById(settings.formModalId);
          if (formModalEl && typeof bootstrap !== 'undefined') {
            this.formModal = new bootstrap.Modal(formModalEl);
          }
        }

        this.bindEvents();
      },

      /**
       * Get element ID with modal prefix
       * @param {string} elementName - The base element name (e.g., 'ProgressBar')
       * @returns {string} The full element ID with modal prefix
       */
      getElementId(elementName) {
        return settings.progressModalId + elementName;
      },

      /**
       * Bind modal events
       */
      bindEvents() {
        const progressModalEl = document.getElementById(settings.progressModalId);
        if (progressModalEl) {
          $(progressModalEl).on('hidden.bs.modal', () => {
            this.stopProgressCheck();
          });
        }

        const cancelBtn = document.getElementById(this.getElementId('CancelBtn'));
        if (cancelBtn) {
          cancelBtn.addEventListener('click', () => this.cancel());
        }

        const downloadBtn = document.getElementById(this.getElementId('DownloadBtn'));
        if (downloadBtn) {
          downloadBtn.addEventListener('click', () => this.download());
        }

        const closeBtn = document.getElementById(this.getElementId('CloseBtn'));
        if (closeBtn) {
          closeBtn.addEventListener('click', () => this.close());
        }
      },

      /**
       * Start a new async task
       * @param {FormData|Object} data - Form data or payload object
       * @param {Object} options - Additional options
       */
      async start(data, options = {}) {
        try {
          // Custom validation
          if (settings.validateForm) {
            const validation = settings.validateForm();
            if (!validation.valid) {
              Utils.showError(validation.message);
              return;
            }
          }

          // Before start callback
          if (settings.onBeforeStart) {
            const shouldContinue = await settings.onBeforeStart(data);
            if (shouldContinue === false) return;
          }

          // Show loading state if button provided
          if (options.button) {
            Utils.setLoadingState(options.button, true, {
              loadingText: translations.processing
            });
          }

          // Start the task
          const response = await Utils.post(settings.startRoute, data);

          if (response.success) {
            this.taskUuid = response.data?.uuid || response.uuid;
            this.retryCount = 0;
            console.log(`${settings.taskName} started. UUID: ${this.taskUuid}`);
            // Hide form modal if exists
            if (this.formModal) {
              this.formModal.hide();
            }

            // Show progress modal
            this.showProgressModal();
            this.startProgressCheck();

            // Success callback
            if (settings.onStart) {
              settings.onStart(response);
            }

            if (response.message) {
              Utils.showSuccess(response.message);
            }
          } else {
            Utils.showError(response.message || translations.taskFailed);
          }
        } catch (err) {
          Utils.handleError(err);
        } finally {
          if (options.button) {
            Utils.setLoadingState(options.button, false);
          }
        }
      },

      /**
       * Show progress modal and initialize UI
       */
      showProgressModal() {
        console.log('Attempting to show progress modal, progressModal exists:', !!this.progressModal);
        this.resetModalState();

        const cancelBtn = document.getElementById(this.getElementId('CancelBtn'));
        const downloadBtn = document.getElementById(this.getElementId('DownloadBtn'));
        const closeBtn = document.getElementById(this.getElementId('CloseBtn'));

        if (settings.showCancelButton && cancelBtn) {
          cancelBtn.classList.remove('d-none');
        }
        if (downloadBtn) downloadBtn.classList.add('d-none');
        if (closeBtn) closeBtn.classList.add('d-none');

        if (this.progressModal) {
          console.log('Showing progress modal');
          this.progressModal.show();
        } else {
          console.log('Progress modal not available');
        }
      },

      /**
       * Reset modal to initial state
       */
      resetModalState() {
        // Reset progress bar
        const progressBar = document.getElementById(this.getElementId('ProgressBar'));
        if (progressBar) {
          progressBar.style.width = '0%';
          progressBar.setAttribute('aria-valuenow', '0');
          progressBar.className = 'progress-bar bg-primary progress-bar-animated';
        }

        const progressText = document.getElementById(this.getElementId('ProgressText'));
        if (progressText) progressText.textContent = '0%';

        // Reset status badge
        const statusBadge = document.getElementById(this.getElementId('Status'));
        if (statusBadge) {
          statusBadge.textContent = translations.processing;
          statusBadge.className = 'badge bg-label-info';
        }

        // Reset status message
        const statusMsg = document.getElementById(this.getElementId('StatusMessage'));
        if (statusMsg) {
          statusMsg.textContent = translations.taskPreparing;
        }

        // Hide completion/error sections
        const completedInfo = document.getElementById(this.getElementId('CompletedInfo'));
        const errorInfo = document.getElementById(this.getElementId('ErrorInfo'));
        const progressDetails = document.getElementById(this.getElementId('ProgressDetails'));

        if (completedInfo) completedInfo.classList.add('d-none');
        if (errorInfo) errorInfo.classList.add('d-none');
        if (progressDetails) progressDetails.classList.remove('d-none');

        // Clear dynamic tables
        this.clearDynamicTable(this.getElementId('ProgressTable'));
        this.clearDynamicTable(this.getElementId('CompletionTable'));
      },

      /**
       * Start polling for progress updates
       */
      startProgressCheck() {
        this.progressInterval = setInterval(() => {
          this.checkProgress();
        }, settings.pollInterval);

        this.checkProgress();
      },

      /**
       * Check current progress status
       */
      async checkProgress() {
        if (!this.taskUuid) return;

        try {
          const statusUrl = settings.checkStatusRoute.replace(':uuid', this.taskUuid);
          const response = await Utils.get(statusUrl);

          if (response.success) {
            this.retryCount = 0;
            this.updateProgress(response.data);

            // Handle completion
            if (response.data.status === 'completed') {
              this.stopProgressCheck();
              this.handleComplete(response.data);
            }
            // Handle failure
            else if (response.data.status === 'failed') {
              this.stopProgressCheck();
              this.handleFailed(response.data);
            }
            // Progress callback
            else if (settings.onProgress) {
              settings.onProgress(response.data);
            }
          } else {
            this.handleProgressCheckError(response.message);
          }
        } catch (err) {
          console.error('Progress check error:', err);
          this.handleProgressCheckError();
        }
      },

      /**
       * Handle progress check errors with retry logic
       */
      handleProgressCheckError(message = null) {
        this.retryCount++;

        if (this.retryCount >= settings.maxRetries) {
          this.stopProgressCheck();
          this.handleFailed({
            error_message: message || translations.statusCheckFailed
          });
        }
      },

      /**
       * Stop progress polling
       */
      stopProgressCheck() {
        if (this.progressInterval) {
          clearInterval(this.progressInterval);
          this.progressInterval = null;
        }
      },

      /**
       * Update progress UI
       * @param {Object} data - Progress data from server
       */
      updateProgress(data) {
        const progress = Math.min(Math.round(data.progress || 0), 100);

        // Update progress bar
        const progressBar = document.getElementById(this.getElementId('ProgressBar'));
        if (progressBar) {
          progressBar.style.width = `${progress}%`;
          progressBar.setAttribute('aria-valuenow', progress);
        }

        const progressText = document.getElementById(this.getElementId('ProgressText'));
        if (progressText) {
          progressText.textContent = `${progress}%`;
        }

        // Update status message (prefer explicit message/status_message fields)
        const statusText = data.message || data.status_message;
        if (statusText) {
          const statusMsg = document.getElementById(this.getElementId('StatusMessage'));
          if (statusMsg) statusMsg.textContent = statusText;
        }

        // Update status badge
        this.updateStatusBadge(data.status);

        // Update dynamic progress fields
        if (settings.progressFields.length > 0) {
          this.updateDynamicTable(this.getElementId('ProgressTable'), settings.progressFields, data);
        }
      },

      /**
       * Update status badge based on status
       */
      updateStatusBadge(status) {
        const statusBadge = document.getElementById(this.getElementId('Status'));
        if (!statusBadge) return;

        const statusMap = {
          pending: { text: 'Pending', class: 'bg-label-secondary' },
          queued: { text: 'Queued', class: 'bg-label-secondary' },
          processing: { text: translations.processing, class: 'bg-label-info' },
          finalizing: { text: 'Finalizing', class: 'bg-label-warning' },
          completed: { text: 'Completed', class: 'bg-label-success' },
          failed: { text: 'Failed', class: 'bg-label-danger' }
        };

        const statusConfig = statusMap[status] || statusMap.processing;
        statusBadge.textContent = statusConfig.text;
        statusBadge.className = `badge ${statusConfig.class}`;
      },

      /** * Handle task completion */
      handleComplete(data) {
        // Update progress bar to complete
        const progressBar = document.getElementById(this.getElementId('ProgressBar'));
        if (progressBar) {
          progressBar.style.width = '100%';
          progressBar.setAttribute('aria-valuenow', '100');
          progressBar.className = 'progress-bar bg-success';
        }
        const progressText = document.getElementById(this.getElementId('ProgressText'));
        if (progressText) progressText.textContent = '100%';

        // Hide progress details, show completion info
        const progressDetails = document.getElementById(this.getElementId('ProgressDetails'));
        if (progressDetails) progressDetails.classList.add('d-none');

        const completedInfo = document.getElementById(this.getElementId('CompletedInfo'));
        if (completedInfo) completedInfo.classList.remove('d-none');

        // Update completion message
        const completionMessage = data.message || translations.taskCompleted;
        const statusMsg = document.getElementById(this.getElementId('StatusMessage'));
        if (statusMsg) statusMsg.textContent = completionMessage;

        const completedMsg = document.getElementById(this.getElementId('CompletedMessage'));
        if (completedMsg) completedMsg.textContent = completionMessage;

        // Update dynamic completion fields
        if (settings.completionFields.length > 0) {
          this.updateDynamicTable(this.getElementId('CompletionTable'), settings.completionFields, data.result || data);
        }

        // Update buttons
        const cancelBtn = document.getElementById(this.getElementId('CancelBtn'));
        const downloadBtn = document.getElementById(this.getElementId('DownloadBtn')); // ← FIXED: Changed from this.getElementById
        const closeBtn = document.getElementById(this.getElementId('CloseBtn'));

        if (cancelBtn) cancelBtn.classList.add('d-none');
        if (settings.showDownloadButton && downloadBtn) downloadBtn.classList.remove('d-none');
        if (closeBtn) closeBtn.classList.remove('d-none');

        // Show success message
        Utils.showSuccess(translations.taskCompleted);

        // Completion callback
        if (settings.onComplete) {
          settings.onComplete(data);
        }
      },

      /**
       * Handle task failure
       */
      handleFailed(data) {
        const progressBar = document.getElementById(this.getElementId('ProgressBar'));
        if (progressBar) {
          progressBar.className = 'progress-bar bg-danger';
        }

        const progressDetails = document.getElementById(this.getElementId('ProgressDetails'));
        if (progressDetails) progressDetails.classList.add('d-none');

        const errorInfo = document.getElementById(this.getElementId('ErrorInfo'));
        if (errorInfo) errorInfo.classList.remove('d-none');

        const errorMsg = data.error || data.error_message || data.message || translations.taskFailed;
        const statusMsg = document.getElementById(this.getElementId('StatusMessage'));
        if (statusMsg) statusMsg.textContent = errorMsg;

        const errorMsgEl = document.getElementById(this.getElementId('ErrorMessage'));
        if (errorMsgEl) errorMsgEl.textContent = errorMsg;

        const cancelBtn = document.getElementById(this.getElementId('CancelBtn'));
        const closeBtn = document.getElementById(this.getElementId('CloseBtn'));

        if (cancelBtn) cancelBtn.classList.add('d-none');
        if (closeBtn) closeBtn.classList.remove('d-none');

        Utils.showError(errorMsg);

        if (settings.onFailed) {
          settings.onFailed(data);
        }
      },

      /**
       * Update dynamic table with field values
       * @param {string} tableId - Table body ID
       * @param {Array} fields - Field configuration array
       * @param {Object} data - Data object
       */
      updateDynamicTable(tableId, fields, data) {
        const tableBody = document.getElementById(tableId);
        if (!tableBody) return;

        tableBody.innerHTML = '';

        fields.forEach(field => {
          const value = this.getNestedValue(data, field.key);
          if (value === null || value === undefined) return;

          const row = document.createElement('tr');

          const labelCell = document.createElement('td');
          labelCell.className = 'text-muted';
          labelCell.textContent = field.label;

          const valueCell = document.createElement('td');
          valueCell.className = 'fw-semibold';
          valueCell.textContent = this.formatFieldValue(value, field);

          row.appendChild(labelCell);
          row.appendChild(valueCell);
          tableBody.appendChild(row);
        });
      },

      /**
       * Clear dynamic table
       */
      clearDynamicTable(tableId) {
        const tableBody = document.getElementById(tableId);
        if (tableBody) {
          tableBody.innerHTML = '';
        }
      },

      /**
       * Format field value based on type
       */
      formatFieldValue(value, field) {
        switch (field.type) {
          case 'number':
            return Number(value).toLocaleString();
          case 'size':
            return `${Number(value).toFixed(2)}${field.suffix || ''}`;
          case 'date':
            return new Date(value).toLocaleString();
          case 'boolean':
            return value ? 'Yes' : 'No';
          case 'percentage':
            return `${value}%`;
          default:
            return String(value);
        }
      },

      /**
       * Get nested object value by dot notation
       */
      getNestedValue(obj, path) {
        return path.split('.').reduce((curr, prop) => curr?.[prop], obj);
      },

      /**
       * Download task result
       */
      async download() {
        if (!this.taskUuid) return;

        try {
          const downloadUrl = settings.downloadRoute.replace(':uuid', this.taskUuid);
          const response = await fetch(downloadUrl);

          if (!response.ok) throw new Error('Download failed');

          const blob = await response.blob();
          const url = window.URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;

          // Extract filename from Content-Disposition header
          const contentDisposition = response.headers.get('Content-Disposition');
          let filename = `${settings.taskName.toLowerCase()}_${Date.now()}.zip`;

          if (contentDisposition) {
            const matches = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
            if (matches?.[1]) {
              filename = matches[1].replace(/['"]/g, '');
            }
          }

          a.download = filename;
          document.body.appendChild(a);
          a.click();
          document.body.removeChild(a);
          window.URL.revokeObjectURL(url);

          setTimeout(() => this.close(), 1000);
        } catch (err) {
          Utils.showError(translations.downloadFailed);
          console.error(err);
        }
      },

      /**
       * Cancel current task
       */
      async cancel() {
        // If cancel route provided, call it
        if (settings.cancelRoute && this.taskUuid) {
          try {
            const cancelUrl = settings.cancelRoute.replace(':uuid', this.taskUuid);
            await Utils.post(cancelUrl);
          } catch (err) {
            console.error('Cancel request failed:', err);
          }
        }

        this.stopProgressCheck();
        this.close();

        Utils.showToast('info', translations.taskCancelled);

        if (settings.onCancel) {
          settings.onCancel();
        }
      },

      /**
       * Close progress modal
       */
      close() {
        this.stopProgressCheck();

        if (this.progressModal) {
          this.progressModal.hide();
        }

        this.taskUuid = null;
        this.retryCount = 0;
      },

      /**
       * Get current task UUID
       */
      getTaskUuid() {
        return this.taskUuid;
      },

      /**
       * Check if task is running
       */
      isRunning() {
        return this.progressInterval !== null;
      }
    };
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