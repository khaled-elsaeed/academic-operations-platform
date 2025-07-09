@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/libs/datatables/dataTables.bootstrap5.min.css') }}?v={{ config('app.version') }}">
<link rel="stylesheet" href="{{ asset('vendor/libs/datatables/responsive.bootstrap5.min.css') }}?v={{ config('app.version') }}">
@endpush

<div>
    @if(!empty($filters))
        <div class="row mb-3">
            @foreach($filters as $filter)
                <div class="col-md-3">
                    {!! $filter['html'] ?? '' !!}
                </div>
            @endforeach
        </div>
    @endif

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
/**
 * Initializes the DataTable with server-side processing and filter support.
 * @function initializeDataTable
 * @returns {void}
 */
function initializeDataTable() {
    var table = $('#{{ $tableId }}').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        dom: 'rtip',
        ajax: {
            url: @json($ajaxUrl),
            data: function (d) {
                @foreach($filters as $filter)
                    d['{{ $filter['name'] ?? '' }}'] = $('#{{ $filter['id'] ?? '' }}').val();
                @endforeach
            }
        },
        columns: @json($columns),
        language: {
            search: "",
            searchPlaceholder: "",
            lengthMenu: "Show _MENU_ entries"
        }
    });

    @foreach($filters as $filter)
        $('#{{ $filter['id'] ?? '' }}').on('change keyup', function() {
            table.ajax.reload();
        });
    @endforeach
}

// Main entry point
$(document).ready(function() {
    initializeDataTable();
});
</script>
@endpush