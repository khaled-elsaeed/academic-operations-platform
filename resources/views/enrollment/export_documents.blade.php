@extends('layouts.home')

@section('title', 'Export Enrollment Documents | AcadOps')

@section('page-content')
<div class="container-xxl flex-grow-1 container-p-y">
    <x-ui.page-header
        title="Export Enrollment Documents"
        description="Generate enrollment documents (PDF) for students and download as a single ZIP. Search by academic ID, national ID, program, or level."
        icon="bx bx-file-archive"
    />

    <div class="card">
        <div class="card-body">
            <form id="exportDocumentsForm" method="POST" action="{{ route('enrollments.exportDocuments') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="academic_id" class="form-label">Academic ID</label>
                        <input type="text" id="academic_id" name="academic_id" class="form-control" placeholder="Search by Academic ID">
                    </div>
                    <div class="col-md-4">
                        <label for="national_id" class="form-label">National ID</label>
                        <input type="text" id="national_id" name="national_id" class="form-control" placeholder="Search by National ID">
                    </div>
                    <div class="col-md-4">
                        <label for="term_id" class="form-label">Term (optional)</label>
                        <select id="term_id" name="term_id" class="form-control">
                            <option value="">All Terms</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="program_id" class="form-label">Program (optional)</label>
                        <select id="program_id" name="program_id" class="form-control">
                            <option value="">All Programs</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="level_id" class="form-label">Level (optional)</label>
                        <select id="level_id" name="level_id" class="form-control">
                            <option value="">All Levels</option>
                        </select>
                    </div>

                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="select_all_programs" name="select_all_programs" value="1">
                            <label class="form-check-label" for="select_all_programs">Select all programs</label>
                        </div>
                    </div>

                    <div class="col-12">
                        <button type="submit" id="exportDocsBtn" class="btn btn-primary">
                            <i class="bx bx-download me-1"></i> Generate & Download ZIP
                        </button>
                        <a href="{{ route('enrollments.index') }}" class="btn btn-outline-secondary">Back</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/**
 * Export Documents page script - modular style to match enrollment index page
 */

// ===========================
// ROUTES & SELECTORS
// ===========================
const ROUTES_DOCS = {
  terms: '{{ route('terms.all.with_inactive') }}',
  programs: '{{ route('programs.all') }}',
  levels: '{{ route('levels.all') }}',
  exportDocuments: '{{ route('enrollments.exportDocuments') }}'
};

const SELECTORS_DOCS = {
  form: '#exportDocumentsForm',
  submitBtn: '#exportDocsBtn',
  termSelect: '#term_id',
  programSelect: '#program_id',
  levelSelect: '#level_id',
  academicId: '#academic_id',
  nationalId: '#national_id',
  selectAllPrograms: '#select_all_programs'
};

// ===========================
// UTILITIES
// ===========================
const UtilsDocs = {
  showSuccess(message) {
    Swal.fire({
      toast: true,
      position: 'top-end',
      icon: 'success',
      title: message,
      showConfirmButton: false,
      timer: 2500,
      timerProgressBar: true
    });
  },

  // showError(title, message) — supports both showError(message) and showError(title, message)
  showError(titleOrMessage, maybeMessage) {
    let title = 'Error';
    let message = '';
    if (typeof maybeMessage === 'undefined') {
      message = titleOrMessage || '';
    } else {
      title = titleOrMessage || title;
      message = maybeMessage || '';
    }

    Swal.fire({
      title: title,
      html: message,
      icon: 'error'
    });
  },

  toggleLoadingState(elementId, isLoading) {
    const $value = $(`#${elementId}-value`);
    const $loader = $(`#${elementId}-loader`);
    const $updated = $(`#${elementId}-last-updated`);
    const $updatedLoader = $(`#${elementId}-last-updated-loader`);

    if (isLoading) {
      $value.addClass('d-none');
      $loader.removeClass('d-none');
      $updated.addClass('d-none');
      $updatedLoader.removeClass('d-none');
    } else {
      $value.removeClass('d-none');
      $loader.addClass('d-none');
      $updated.removeClass('d-none');
      $updatedLoader.addClass('d-none');
    }
  },

  replaceRouteId(route, id) {
    return route.replace(':id', id);
  },

  // Hide the page loader overlay.
  hidePageLoader() {
    const loader = document.getElementById('pageLoader');
    if (loader) {
      loader.classList.add('fade-out');
      // Restore scrollbars when loader is hidden
      document.documentElement.style.overflow = '';
      document.body.style.overflow = '';
    }
  },

  disableButton($btn, text) {
    $btn.prop('disabled', true).html(text);
  },

  enableButton($btn, html) {
    $btn.prop('disabled', false).html(html);
  },

  downloadBlob(blob, filename) {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    window.URL.revokeObjectURL(url);
  }
};


// ===========================
// API SERVICE
// ===========================
const ApiDocs = {
  request(options) { return $.ajax(options); },

  fetchTerms() { return this.request({ url: ROUTES_DOCS.terms, method: 'GET' }); },
  fetchPrograms() { return this.request({ url: ROUTES_DOCS.programs, method: 'GET' }); },
  fetchLevels() { return this.request({ url: ROUTES_DOCS.levels, method: 'GET' }); },

  exportDocuments(formData) {
    return fetch(ROUTES_DOCS.exportDocuments, {
      method: 'POST',
      headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
      body: formData
    });
  }
};

// ===========================
// DROPDOWN MANAGER
// ===========================
const DropdownManagerDocs = {
  loadTerms(selector = SELECTORS_DOCS.termSelect) {
    return ApiDocs.fetchTerms()
      .done(response => {
        const terms = response.data || [];
        const $s = $(selector);
        $s.empty().append('<option value="">All Terms</option>');
        terms.forEach(t => $s.append($('<option>', { value: t.id, text: t.name })));
      })
      .fail(() => UtilsDocs.showError('Error', 'Failed to load terms'));
  },

  loadPrograms(selector = SELECTORS_DOCS.programSelect) {
    return ApiDocs.fetchPrograms()
      .done(response => {
        const programs = response.data || [];
        const $s = $(selector);
        $s.empty().append('<option value="">All Programs</option>');
        programs.forEach(p => $s.append($('<option>', { value: p.id, text: p.name })));
      })
      .fail(() => UtilsDocs.showError('Error', 'Failed to load programs'));
  },

  loadLevels(selector = SELECTORS_DOCS.levelSelect) {
    return ApiDocs.fetchLevels()
      .done(response => {
        const levels = response.data || [];
        const $s = $(selector);
        $s.empty().append('<option value="">All Levels</option>');
        levels.forEach(l => $s.append($('<option>', { value: l.id, text: l.name })));
      })
      .fail(() => UtilsDocs.showError('Error', 'Failed to load levels'));
  }
};

// ===========================
// EXPORT DOCS MANAGER
// ===========================
const ExportDocsManager = {
  init() {
    this.bindEvents();
    $.when(
      DropdownManagerDocs.loadTerms(),
      DropdownManagerDocs.loadPrograms(),
      DropdownManagerDocs.loadLevels()
    ).done(() => {
        UtilsDocs.hidePageLoader();
    });
  },

  bindEvents() {
    $(SELECTORS_DOCS.form).on('submit', this.handleSubmit.bind(this));
  },

  async handleSubmit(e) {
    e.preventDefault();
    const $btn = $(SELECTORS_DOCS.submitBtn);
    UtilsDocs.disableButton($btn, '<i class="bx bx-loader-alt bx-spin me-1"></i>Generating...');

    const formEl = document.querySelector(SELECTORS_DOCS.form);
    const formData = new FormData(formEl);

    try {
      const res = await ApiDocs.exportDocuments(formData);

      if (!res.ok) {
        const json = await res.json().catch(() => ({}));
        const message = json.message || 'Export failed. Please check your input.';
        UtilsDocs.showError('Error', message);
        UtilsDocs.enableButton($btn, '<i class="bx bx-download me-1"></i> Generate & Download ZIP');
        return;
      }

      const blob = await res.blob();
      const disposition = res.headers.get('Content-Disposition') || '';
      let filename = 'enrollment_documents.zip';
      const match = /filename="?([^";]+)"?/.exec(disposition);
      if (match) filename = match[1];

      UtilsDocs.downloadBlob(blob, filename);
      UtilsDocs.showSuccess('Download started');
    } catch (err) {
      console.error(err);
      UtilsDocs.showError('Error', 'Failed to export documents');
    } finally {
      UtilsDocs.enableButton($btn, '<i class="bx bx-download me-1"></i> Generate & Download ZIP');
    }
  }
};

// ===========================
// INIT
// ===========================
$(document).ready(() => {
  ExportDocsManager.init();
});
</script>
@endpush
