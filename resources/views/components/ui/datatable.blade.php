{{-- resources/views/components/ui/datatable.blade.php --}}

@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/libs/datatables/dataTables.bootstrap5.min.css') }}?v={{ config('app.version') }}">
<link rel="stylesheet" href="{{ asset('vendor/libs/datatables/responsive.bootstrap5.min.css') }}?v={{ config('app.version') }}">
<style>
  table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control::before,
  table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control::before {
    top: 50%;
    left: 5px;
    height: 1em;
    width: 1em;
    margin-top: -9px;
    display: inline-block;
    color: white;
    border: .15em solid white;
    border-radius: 1em;
    box-shadow: 0 0 .2em #444;
    box-sizing: content-box;
    text-align: center;
    text-indent: 0;
    line-height: 1em;
    content: "+";
    background-color: #931a23;
  }

  table.dataTable.dtr-inline.collapsed > tbody > tr.parent > td.dtr-control:before,
  table.dataTable.dtr-inline.collapsed > tbody > tr.parent > th.dtr-control:before {
    content: "-";
    background-color: #8592a3;
  }
</style>
@endpush

<div>
  <div class="table-responsive bg-white p-3 rounded-3 shadow-sm">
    <table class="table table-bordered table-hover dt-responsive nowrap" id="{{ $tableId }}" style="width:100%">
      <thead>
        <tr>
          <th>#</th>
          @foreach($headers as $header)
            <th>{{ $header }}</th>
          @endforeach
        </tr>
      </thead>
      <tbody>
      </tbody>
    </table>
  </div>
</div>

@once
@push('scripts')
<script src="{{ asset('vendor/libs/datatables/jquery.dataTables.min.js') }}?v={{ config('app.version') }}"></script>
<script src="{{ asset('vendor/libs/datatables/dataTables.bootstrap5.min.js') }}?v={{ config('app.version') }}"></script>
<script src="{{ asset('vendor/libs/datatables/dataTables.responsive.min.js') }}?v={{ config('app.version') }}"></script>
<script src="{{ asset('vendor/libs/datatables/responsive.bootstrap5.min.js') }}?v={{ config('app.version') }}"></script>
@endpush
@endonce

@push('scripts')
<script>
(function() {
  const tableId = '{{ $tableId }}';
  const filterFields = @json($filterFields);
  const columns = [
    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
    ...@json($columns)
  ];
  const ajaxUrl = @json($ajaxUrl);

  function initializeDataTable() {
    $('#' + tableId).DataTable({
      processing: true,
      serverSide: true,
      responsive: true,
      dom: 'rtip',
      ajax: {
        url: ajaxUrl,
        data: function (d) {
          filterFields.forEach(function(field) {
            d[field] = $('#' + field).val();
          });
        }
      },
      columns: columns,
      language: {
        search: "",
        searchPlaceholder: "",
        lengthMenu: "Show _MENU_ entries"
      }
    });
  }

  $(document).ready(function() {
    initializeDataTable();
  });
})();
</script>
@endpush