<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

<style>
    #calendar {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
    }
    .fc-event {
        cursor: pointer;
        font-size: 11px;
    }
    .fc-daygrid-event {
        padding: 2px 5px;
    }
    .legend-item {
        display: inline-block;
        margin-right: 15px;
        padding: 3px 10px;
        border-radius: 4px;
        font-size: 12px;
        color: #fff;
    }
</style>

<div class="page-inner">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <h4 class="card-title">Kalender Jadwal</h4>
                    <button class="btn btn-success btn-round ml-auto" id="btn-export-excel">
                        <i class="fa fa-file-excel mr-2"></i> Export Excel Mingguan
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filter -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Filter Guru</label>
                        <select id="filter_guru" class="form-control">
                            <option value="">-- Semua Guru --</option>
                            <?php foreach($pengajar as $p): ?>
                            <option value="<?= $p->id_pengajar ?>"><?= $p->nama ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" id="filter_start" class="form-control" value="<?= date('Y-m-d', strtotime('monday this week')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tanggal Selesai</label>
                        <input type="date" id="filter_end" class="form-control" value="<?= date('Y-m-d', strtotime('sunday this week')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-info btn-block" id="btn-filter">
                            <i class="fa fa-refresh"></i> Refresh
                        </button>
                    </div>
                </div>
                
                <div class="mb-3">
                    <span class="legend-item ml-4" style="background: #3498db;">Kelas Anak</span>
                    <span class="legend-item" style="background: #8e44ad;">Kelas Dewasa</span>
                    <span class="legend-item" style="background: #f39c12;">Trial Class</span>
                    <span class="legend-item" style="background: #27ae60;">Placement Test</span>
                    <span class="legend-item" style="background: #e91e63;">Reschedule</span>
                    <span class="legend-item" style="background: #e74c3c;">Hari Libur</span>
                </div>
                
                <div id="calendar"></div>
            </div>
        </div>
        
        <!-- Event Detail Modal -->
        <div class="modal fade" id="modal-detail" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Jadwal</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-borderless">
                            <tr>
                                <td width="30%"><strong>Kelas</strong></td>
                                <td id="detail-kelas">-</td>
                            </tr>
                            <tr>
                                <td><strong>Guru</strong></td>
                                <td id="detail-guru">-</td>
                            </tr>
                            <tr>
                                <td><strong>Ruangan</strong></td>
                                <td id="detail-ruangan">-</td>
                            </tr>
                            <tr>
                                <td><strong>Tipe</strong></td>
                                <td id="detail-tipe">-</td>
                            </tr>
                            <tr>
                                <td><strong>Jenis</strong></td>
                                <td id="detail-jenis">-</td>
                            </tr>
                            <tr>
                                <td><strong>Waktu</strong></td>
                                <td id="detail-waktu">-</td>
                            </tr>
                            <tr>
                                <td><strong>Keterangan</strong></td>
                                <td id="detail-keterangan">-</td>
                            </tr>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div> 
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        locale: 'id',
        firstDay: 1,
        slotMinTime: '07:00:00',
        slotMaxTime: '21:00:00',
        allDaySlot: false,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: false,
            hour12: false
        },
        events: function(info, successCallback, failureCallback) {
            var id_guru = $('#filter_guru').val();
            
            $.ajax({
                url: '<?= site_url("Penjadwalan/get_events") ?>',
                data: {
                    start: info.startStr,
                    end: info.endStr,
                    id_guru: id_guru
                },
                success: function(res) {
                    var events = JSON.parse(res);
                    successCallback(events);
                },
                error: function() {
                    failureCallback();
                }
            });
        },
        eventClick: function(info) {
            if (info.event.extendedProps.kelas) {
                $('#detail-kelas').text(info.event.extendedProps.kelas);
                $('#detail-guru').text(info.event.extendedProps.guru);
                $('#detail-ruangan').text(info.event.extendedProps.ruangan || '-');
                $('#detail-tipe').text(info.event.extendedProps.tipe == 'dewasa' ? 'Kelas Dewasa' : 'Kelas Anak');
                $('#detail-jenis').text(info.event.extendedProps.jenis_jadwal || 'Regular');
                $('#detail-keterangan').text(info.event.extendedProps.keterangan || '-');
                
                var start = info.event.start;
                var end = info.event.end;
                var timeStr = start.toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'}) + 
                              ' - ' + end.toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});
                $('#detail-waktu').text(timeStr);
                
                $('#modal-detail').modal('show');
            }
        }
    });
    
    calendar.render();
    
    $('#btn-filter').click(function() {
        calendar.refetchEvents();
    });
    
    // Export Excel
    $('#btn-export-excel').click(function() {
        var start = $('#filter_start').val();
        var end = $('#filter_end').val();
        var id_guru = $('#filter_guru').val();
        
        if (!start || !end) {
            Swal.fire('Error', 'Pilih tanggal mulai dan selesai', 'error');
            return;
        }
        
        window.location.href = '<?= site_url("Penjadwalan/export_weekly_schedule") ?>?start=' + start + '&end=' + end + '&id_guru=' + id_guru;
    });
});
</script>

