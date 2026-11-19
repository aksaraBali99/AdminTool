<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


function tgl_indo($tanggal)
{
    $bulan = array(
        1 =>   'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );

    $day = date('D', strtotime($tanggal));
    $dayList = array(
        'Sun' => 'Minggu',
        'Mon' => 'Senin',
        'Tue' => 'Selasa',
        'Wed' => 'Rabu',
        'Thu' => 'Kamis',
        'Fri' => 'Jumat',
        'Sat' => 'Sabtu'
    );
    $pecahkan = explode('-', $tanggal);
    return $dayList[$day] . ', ' . $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}
function getAge($tanggal_lahir)
{
    $birthDate = new DateTime($tanggal_lahir);
    $today = new DateTime("today");

    return $birthDate->diff($today)->y;
}
function tgl_indo_full($tanggal, $all)
{
    $bulan = array(
        1 =>   'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );

    $day = date('D', strtotime($tanggal));
    $dayList = array(
        'Sun' => 'Minggu',
        'Mon' => 'Senin',
        'Tue' => 'Selasa',
        'Wed' => 'Rabu',
        'Thu' => 'Kamis',
        'Fri' => 'Jumat',
        'Sat' => 'Sabtu'
    );
    $pecahkan = explode('-', $tanggal);
    return $dayList[$day] . ', ' . $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0] . '  ' . substr($all, 11, 5);
}

function rupiah($angka)
{
    $hasil_rupiah = "Rp " . number_format($angka, 0, ',', '.');
    return $hasil_rupiah;
}

function tgl($tanggal)
{
    $pecahkan = explode('-', substr($tanggal, 0, 10));
    return $pecahkan[2] . '-' . $pecahkan[1] . '-' . $pecahkan[0];
}

/*
 * Sends a WhatsApp message through Fonnte, but only in production
 * (outbound_is_live(), application/helpers/outbound_helper.php). Anywhere
 * else it makes no request at all and returns a Fonnte-shaped answer with
 * status false and a "NOT SENT (<environment>): ..." reason, which callers
 * log into reminder_tagihan like any other failed send.
 */
function sendWa($no_hp, $isi_pesan, $token)
{
    $fonnte_url = outbound_fonnte_url(ENVIRONMENT);

    if ( ! outbound_is_live(ENVIRONMENT)) {
        log_message('info', 'WhatsApp NOT SENT (' . ENVIRONMENT . '): blocked outside production; target='
            . $no_hp . ', message length=' . strlen((string) $isi_pesan));
        return json_encode(array(
            'status' => false,
            'reason' => 'NOT SENT (' . ENVIRONMENT . '): WhatsApp is blocked outside production',
            'target' => $no_hp,
        ));
    }

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $fonnte_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'target' => $no_hp,
            'message' => $isi_pesan,
            'filename' => 'filename',
            'schedule' => 0,
            'typing' => true,
            'delay' => '2',
            'countryCode' => '62',
            'followup' => 0,
        ),
        CURLOPT_HTTPHEADER => array(
            'Authorization: ' . $token . ''
        ),
    ));

    $response = curl_exec($curl);
    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
    }
    curl_close($curl);

    if (isset($error_msg)) {
        echo $error_msg;
    }
    return $response;
}
