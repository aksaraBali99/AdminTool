<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Suara Realcount Pilkada</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-image: url('<?php echo base_url('assets/img/pe.jpg'); ?>');
            /* Background image from assets */
            background-color: #f4f4f4;
            background-size: 100% auto;
            background-repeat: no-repeat;
            margin: 0;
            padding: 20px;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        /* Photo by Johannes Plenio: https: //www.pexels.com/photo/gray-and-white-wallpaper-1103970/ */

        .paslon-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }

        .paslon-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 210px;
            text-align: center;
            padding: 20px;
            transition: transform 0.3s ease;
        }

        .paslon-card:hover {
            transform: translateY(-5px);
        }

        img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
        }

        .nama-paslon {
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .partai-pengusung {
            font-size: 0.9em;
            color: #888;
            margin-bottom: 10px;
        }

        .vote-count {
            font-size: 1.1em;
            font-weight: bold;
            color: #555;
            margin-bottom: 5px;
        }

        .percentage {
            font-size: 1em;
            color: #4CAF50;
            margin-bottom: 15px;
        }

        @media screen and (max-width: 600px) {
            .paslon-card {
                width: 100%;
                margin-bottom: 20px;
            }

            img {
                width: 100px;
                height: 100px;
            }
        }

        .clock {
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>

<body>
    <div>
        <center>
            <h3 style="color:white">Hasil Hitung Cepat Suara Pilkada</h3>
            <hr>
            <?php
            $persentase = @((int)$jml_tps_terisi / $jml_tps) * 100;
            ?>
            <span style="color:white">
                Persentase Input TPS :<h3><?php echo round($persentase); ?> %</h3>
                Tps Sudah Input : <?= $jml_tps_terisi . ' / ' . $jml_tps; ?> <br>
                Total Suara : <?= $suara_real; ?>
            </span><br><br>


            <?php
            // Set zona waktu ke Indonesia (WIB)
            date_default_timezone_set('Asia/Jakarta');

            // Ambil tanggal sekarang
            $hariInggris = date('l');
            $tanggal = date('d');
            $bulanInggris = date('F');
            $tahun = date('Y');

            // Array untuk konversi hari dan bulan ke bahasa Indonesia
            $hariIndo = [
                'Sunday' => 'Minggu',
                'Monday' => 'Senin',
                'Tuesday' => 'Selasa',
                'Wednesday' => 'Rabu',
                'Thursday' => 'Kamis',
                'Friday' => 'Jumat',
                'Saturday' => 'Sabtu'
            ];

            $bulanIndo = [
                'January' => 'Januari',
                'February' => 'Februari',
                'March' => 'Maret',
                'April' => 'April',
                'May' => 'Mei',
                'June' => 'Juni',
                'July' => 'Juli',
                'August' => 'Agustus',
                'September' => 'September',
                'October' => 'Oktober',
                'November' => 'November',
                'December' => 'Desember'
            ];

            // Konversi ke bahasa Indonesia
            $hari = $hariIndo[$hariInggris];
            $bulan = $bulanIndo[$bulanInggris];

            // Hasil akhir
            $tanggalIndo = "$hari, $tanggal $bulan $tahun";
            echo '<span style="color:white">' . $tanggalIndo . '</span>';
            ?>
            <div class="clock mt-1" style="color:white" id="clock"></div>
        </center>
    </div>
    <br>
    <br>

    <div class="paslon-container">
        <?php
        // Total suara untuk menghitung persentase
        $total_suara = 0;
        foreach ($paslon as $p) {
            $total_suara += $p->total;
        }

        foreach ($paslon as $p) :
            $persentase = ($p->total / $total_suara) * 100;
        ?>
            <div class="paslon-card">
                <img src="<?php echo base_url('uploads/foto/' . $p->photo); ?>" alt="<?php echo $p->nama; ?>">
                <div class="nama-paslon"><?php echo $p->nama; ?></div>
                <div class="partai-pengusung"><?php echo number_format($p->total); ?> Suara</div>
                <div class="vote-count"></div>
                <div class="percentage"><?php echo number_format($persentase, 2); ?>%</div>
            </div>
        <?php endforeach; ?>
    </div>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <br>
    <center style="font-size:xx-small">
        <a style="color:gray">Digitalind.id</a><br>
        <a href='https://pngtree.com/freebackground/elegant-background_1911359.html' style="color:gray;text-decoration:none;">free background photos from pngtree.com</a>
    </center>

</body>
<script>
    setTimeout(function() {
        window.location.reload(1);
    }, 5000);

    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');

        const timeString = `${hours}:${minutes}:${seconds}`;
        document.getElementById('clock').textContent = timeString;
    }

    // Update the clock every second
    setInterval(updateClock, 10000);

    // Initialize clock immediately
    updateClock();
</script>

</html>