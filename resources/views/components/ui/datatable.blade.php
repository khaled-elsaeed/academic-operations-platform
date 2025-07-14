@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/libs/datatables/dataTables.bootstrap5.min.css') }}?v={{ config('app.version') }}">
<link rel="stylesheet" href="{{ asset('vendor/libs/datatables/responsive.bootstrap5.min.css') }}?v={{ config('app.version') }}">
@endpush

<div>
    <div class="table-responsive bg-white p-3 rounded-3 shadow-sm">
        <table class="table table-bordered table-hover dt-responsive nowrap" id="{{ $tableId }}" style="width:100%">
            <thead>
                <tr>
                    @foreach($headers as $header)
                        <th>{{ $header }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <!-- Data will go here -->
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script src="{{ asset('vendor/libs/datatables/jquery.dataTables.min.js') }}?v={{ config('app.version') }}"></script>
<script src="{{ asset('vendor/libs/datatables/dataTables.bootstrap5.min.js') }}?v={{ config('app.version') }}"></script>
<script src="{{ asset('vendor/libs/datatables/dataTables.responsive.min.js') }}?v={{ config('app.version') }}"></script>
<script src="{{ asset('vendor/libs/datatables/responsive.bootstrap5.min.js') }}?v={{ config('app.version') }}"></script>
<script>
function initializeDataTable() {
    var filterFields = @json($filterFields);
    var table = $('#{{ $tableId }}').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'rtip',
        ajax: {
            url: @json($ajaxUrl),
            data: function (d) {
                filterFields.forEach(function(field) {
                    d[field] = $('#' + field).val();
                });
            }
        },
        columns: @json($columns),
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
</script>
@endpush