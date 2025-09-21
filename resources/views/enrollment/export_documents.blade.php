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
const ROUTES_DOCS = {
  terms: '{{ route('terms.all.with_inactive') }}',
  programs: '{{ route('programs.all') }}',
  levels: '{{ route('levels.all') }}',
};

function loadSelectOptions() {
  $.get(ROUTES_DOCS.terms).done(res => {
    const $t = $('#term_id');
    $t.empty().append('<option value="">All Terms</option>');
    (res.data || []).forEach(term => $t.append($('<option>', { value: term.id, text: term.name })));
  });

  $.get(ROUTES_DOCS.programs).done(res => {
    const $p = $('#program_id');
    $p.empty().append('<option value="">All Programs</option>');
    (res.data || []).forEach(program => $p.append($('<option>', { value: program.id, text: program.name })));
  });

  $.get(ROUTES_DOCS.levels).done(res => {
    const $l = $('#level_id');
    $l.empty().append('<option value="">All Levels</option>');
    (res.data || []).forEach(level => $l.append($('<option>', { value: level.id, text: level.name })));
  });
}

$(function(){
  loadSelectOptions();

  $('#exportDocumentsForm').on('submit', function(e){
    e.preventDefault();
    const $btn = $('#exportDocsBtn');
    const form = this;
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Generating...');

    const formData = new FormData(form);

    fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}'
      },
      body: formData
    }).then(async (res) => {
      if (!res.ok) {
        const json = await res.json().catch(()=> ({}));
        const message = json.message || 'Export failed';
        Swal.fire('Error', message, 'error');
        $btn.prop('disabled', false).html('<i class="bx bx-download me-1"></i> Generate & Download ZIP');
        return;
      }

      // Streamed response: convert to blob and download
      const blob = await res.blob();
      const disposition = res.headers.get('Content-Disposition') || '';
      let filename = 'enrollment_documents.zip';
      const match = /filename="?([^";]+)"?/.exec(disposition);
      if (match) filename = match[1];

      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      document.body.appendChild(a);
      a.click();
      a.remove();
      window.URL.revokeObjectURL(url);

      Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Download started', showConfirmButton: false, timer: 2000 });
      $btn.prop('disabled', false).html('<i class="bx bx-download me-1"></i> Generate & Download ZIP');
    }).catch(err => {
      console.error(err);
      Swal.fire('Error', 'Failed to export documents', 'error');
      $btn.prop('disabled', false).html('<i class="bx bx-download me-1"></i> Generate & Download ZIP');
    });
  });
});
</script>
@endpush
