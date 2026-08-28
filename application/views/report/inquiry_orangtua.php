<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Laporan Inquiry Orangtua</h4>
                    <div class="ml-auto">
                        <span class="badge badge-warning" style="font-size: 1.2em;" id="total-count">0</span>
                        <span class="ml-1">Total Inquiry</span>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="data-table" class="display table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Orangtua</th>
                                <th>No HP</th>
                                <th>Nama Anak</th>
                                <th>Tanggal Hubungi</th>
                                <th>Sumber</th>
                                <th>Kelas Diminati</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#data-table').DataTable({
        "ajax": {
            url: '<?= site_url("Report/get_data_inquiry") ?>',
            type: 'GET',
            dataSrc: function(json) {
                $('#total-count').text(json.data.length);
                return json.data;
            }
        },
        "order": [[4, 'desc']]
    });
});
</script>
