@push('styles')
<link rel="stylesheet" href="{{ asset('vendor/libs/datatables/dataTables.bootstrap5.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/libs/datatables/responsive.bootstrap5.min.css') }}">
<style>

        /* LTR styles for DataTables control icon */
        table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control::before,
        table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control::before {
            top: 50% !important;
            left: 5px !important;
            right: auto !important;
            height: 1em !important;
            width: 1em !important;
            margin-top: -9px !important;
            display: inline-block !important;
            color: white !important;
            border: .15em solid white !important;
            border-radius: 1em !important;
            box-shadow: 0 0 .2em #444 !important;
            box-sizing: content-box !important;
            text-align: center !important;
            text-indent: 0 !important;
            line-height: 1em !important;
            content: "+" !important;
            background-color: #931a23 !important;
        }

        table.dataTable.dtr-inline.collapsed>tbody>tr.parent>td.dtr-control:before,
        table.dataTable.dtr-inline.collapsed>tbody>tr.parent>th.dtr-control:before {
            top: 50% !important;
            left: 5px !important;
            right: auto !important;
            height: 1em !important;
            width: 1em !important;
            margin-top: -9px !important;
            display: inline-block !important;
            color: white !important;
            border: .15em solid white !important;
            border-radius: 1em !important;
            box-shadow: 0 0 .2em #444 !important;
            box-sizing: content-box !important;
            text-align: center !important;
            text-indent: 0 !important;
            line-height: 1em !important;
            content: "-" !important;
            background-color: #8592a3 !important;
        }
</style>
@endpush

<div>
    <div class="table-responsive bg-white p-3 rounded-3 shadow-sm">
        <table 
            class="table table-bordered table-hover dt-responsive nowrap" 
            id="{{ $tableId }}" 
            style="width:100%"
            dir="ltr"
        >
            <thead>
                <tr>
                    <th>#</th>
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
<script src="{{ asset('vendor/libs/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('vendor/libs/datatables/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('vendor/libs/datatables/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('vendor/libs/datatables/responsive.bootstrap5.min.js') }}"></script>
<script>
$(document).ready(function() {
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
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false }
        ].concat(@json($columns)),
        language: {
                search: "",
                searchPlaceholder: "",
                lengthMenu: "Show _MENU_ entries"
        }
    });

});
</script>
@endpush