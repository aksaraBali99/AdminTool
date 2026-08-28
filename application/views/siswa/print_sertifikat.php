<!DOCTYPE html>
<html>
<head>
    <title>Certificate - <?= $sertifikat->nomor_sertifikat ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,400;0,600;0,700;0,800;1,700;1,800&family=Playfair+Display:ital,wght@0,400;0,700;1,400&family=Pinyon+Script&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        :root {
            --primary-red: #8B1A1A;
            --primary-red-new: #FAF5F5;
            --dark-red: #6B0F0F;
            --accent-red: #A52A2A;
            --light-red: #C41E3A;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            width: 297mm;
            height: 210mm;
            overflow: hidden;
        }
        body {
            font-family: 'Nunito', sans-serif;
            background: #f5f5f5;
        }
        
        /* Action Buttons */
        .action-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }
        .action-btn {
            background: linear-gradient(135deg, var(--primary-red) 0%, var(--dark-red) 100%);
            color: #fff;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Nunito', sans-serif;
            box-shadow: 0 4px 15px rgba(139, 26, 26, 0.35);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .action-btn:hover {
            background: linear-gradient(135deg, var(--dark-red) 0%, #500A0A 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(139, 26, 26, 0.45);
        }
        .action-btn.pdf-btn {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.35);
        }
        .action-btn.pdf-btn:hover {
            background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
            box-shadow: 0 6px 20px rgba(33, 150, 243, 0.45);
        }
        .action-btn svg {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }
        
        .certificate {
            width: 297mm;
            height: 210mm;
            background: #ffffff;
            position: relative;
            overflow: hidden;
        }
        
        /* Red geometric shapes - Top Left - Simple triangular design like reference */
        .shape-tl-1 {
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 0;
            border-left: 290px solid var(--primary-red-new);
            border-bottom: 290px solid transparent;
        }
        .shape-tl-2 {
            position: absolute;
            top: 0;
            left: 0;
            width: 0;
            height: 0;
            border-left: 100px solid var(--primary-red-new);
            border-bottom: 160px solid transparent;
        }
        
        /* Red geometric shapes - Top Right */
        .shape-tr-1 {
            position: absolute;
            top: 0;
            right: 0;
            width: 120px;
            height: 130px;
            background: var(--primary-red);
            clip-path: polygon(30% 0, 100% 0, 100% 100%, 0 70%);
        }
        .shape-tr-2 {
            position: absolute;
            top: 0;
            right: 0;
            width: 80px;
            height: 130px;
            background: var(--dark-red);
            clip-path: polygon(0 0, 100% 0, 100% 100%, 40% 60%);
        }
        
        /* Red geometric shapes - Bottom Left */
        .shape-bl-1 {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 150px;
            height: 170px;
            background: var(--primary-red);
            clip-path: polygon(0 0, 60% 0, 100% 100%, 0 100%);
        }
        .shape-bl-2 {
          position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #fff7f4;
    clip-path: polygon(
        0 0,
        100% 0,
        100% 65%,
        0 100%
    );

    transform: rotate(180deg);
    transform-origin: bottom left;
    z-index: -1;
        }
        
        /* Red geometric shapes - Bottom Right */
        .shape-br-1 {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 150px;
            height: 180px;
            background: var(--primary-red);
            clip-path: polygon(100% 0, 100% 100%, 0 100%, 30% 20%);
        }
        .shape-br-2 {
            position: absolute;
            bottom: 0;
            right: 80px;
            width: 100px;
            height: 120px;
            background: var(--dark-red);
            clip-path: polygon(0 40%, 100% 0, 100% 100%, 0 100%);
        }
        
        /* Logo */
        .logo {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 100;
        }
        .logo img {
            height: 55px;
            width: auto;
        }
        
        /* Content */
        .content {
            position: relative;
            z-index: 5;
            text-align: center;
            padding: 60px 180px 40px;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .title-certificate {
            font-family: 'Nunito', cursive;
            font-size: 90px;
            color: var(--primary-red);
            font-weight: 700;
            margin-bottom: 5px;
            line-height: 1;
        }
        
        .title-completion {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 32px;
            font-weight: 800;
            font-style: italic;
            color: black;
            margin-bottom: 25px;
        }
        
        .presented-to {
            font-family: 'Playfair Display', Georgia, serif;
            font-size: 16px; 
            color: #333;
            margin-bottom: 15px;
        }
        
        .student-name {
            font-family: 'Nunito', sans-serif;
            font-size: 36px;
            font-weight: 800;
            font-style: italic;
            color: var(--primary-red);
            border-bottom: 2px solid var(--primary-red);
            display: inline-block;
            padding: 5px 50px;
            margin-bottom: 20px;
        }
        
        .description {
            font-family: 'Nunito', sans-serif;
            font-size: 14px;
            color: #333;
            line-height: 1.8;
            margin-bottom: 8px;
        }
        .description .underline {
            border-bottom: 1px solid #333;
            padding: 0 5px;
            font-weight: 600;
        }
        
        .presented-by {
            font-family: 'Nunito', sans-serif;
            font-size: 13px;
            font-style: italic;
            color: #333;
            margin-bottom: 20px;
        }
        
        /* Medal/Ribbon - Award Seal Design */
        .medal-container {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin-bottom: 15px;
            position: relative;
            height: 120px;
        }
        
        .seal {
            width: 70px;
            height: 70px;
            background: var(--primary-red);
            border-radius: 50%;
            position: relative;
            z-index: 3;
        }
        
        /* Zigzag/wavy border around the seal */
        .seal::before {
            content: '';
            position: absolute;
            top: -8px;
            left: -8px;
            width: 86px;
            height: 86px;
            background: var(--primary-red);
            border-radius: 50%;
            z-index: -1;
            clip-path: polygon(
                50% 0%, 61% 5%, 70% 2%, 78% 10%, 85% 7%, 
                93% 17%, 98% 15%, 100% 27%, 100% 35%, 
                98% 45%, 100% 50%, 98% 55%, 100% 65%, 
                100% 73%, 98% 85%, 93% 83%, 85% 93%, 
                78% 90%, 70% 98%, 61% 95%, 50% 100%, 
                39% 95%, 30% 98%, 22% 90%, 15% 93%, 
                7% 83%, 2% 85%, 0% 73%, 0% 65%, 
                2% 55%, 0% 50%, 2% 45%, 0% 35%, 
                0% 27%, 2% 15%, 7% 17%, 15% 7%, 
                22% 10%, 30% 2%, 39% 5%
            );
        }
        
        /* Inner circle of the seal */
        .seal::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 50px;
            height: 50px;
            border: 3px solid rgba(255,255,255,0.4);
            border-radius: 50%;
            background: transparent;
        }
        
        /* Ribbons */
        .ribbon {
            position: absolute;
            top: 55px;
            width: 24px;
            height: 55px;
            background: var(--primary-red);
            z-index: 1;
        }
        .ribbon-left {
            left: calc(50% - 22px);
            transform: skewX(15deg);
            transform-origin: top center;
        }
        .ribbon-right {
            right: calc(50% - 22px);
            transform: skewX(-15deg);
            transform-origin: top center;
        }
        /* Ribbon pointed ends */
        .ribbon::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 0;
            width: 100%;
            height: 15px;
            background: var(--primary-red);
            clip-path: polygon(0 0, 50% 100%, 100% 0);
        }
        
        /* Signatures */
        .signatures {
            display: flex;
            justify-content: space-between;
            width: 100%;
            padding: 0 100px;
            margin-top: 10px;
        }
        
        .signature-box {
            text-align: center;
            min-width: 180px;
        }
        
        .signature-name {
            font-family: 'Nunito', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin-bottom: 4px;
        }
        
        .signature-title {
            font-family: 'Nunito', sans-serif;
            font-size: 13px;
            color: var(--primary-red);
            font-weight: 600;
        }
        
        /* Certificate number */
        .cert-number {
            position: absolute;
            bottom: 12px;
            left: 180px;
            font-size: 10px;
            color: #999;
            font-family: 'Nunito', sans-serif;
        }
        
        @media print {
            html, body {
                width: 297mm;
                height: 210mm;
            }
            body { 
                background: white; 
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .certificate {
                box-shadow: none;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            .action-buttons {
                display: none;
            }
        }
    </style>
    <!-- html2pdf.js Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>
<body>
    <div class="action-buttons">
        <button class="action-btn pdf-btn" onclick="savePDF()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M12,19L8,15H10.5V12H13.5V15H16L12,19Z"/></svg>
            Save PDF
        </button>
        <button class="action-btn" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18,3H6V7H18M19,12A1,1 0 0,1 18,11A1,1 0 0,1 19,10A1,1 0 0,1 20,11A1,1 0 0,1 19,12M16,19H8V14H16M19,8H5A3,3 0 0,0 2,11V17H6V21H18V17H22V11A3,3 0 0,0 19,8Z"/></svg>
            Print
        </button>
    </div>

    <div class="certificate" id="certificate">
        <!-- Red geometric shapes - Top Left -->
        <div class="shape-tl-1"></div>
        <div class="shape-tl-2"></div>
        
        <!-- Red geometric shapes - Top Right -->
        <div class="shape-tr-1"></div> 
        
        <!-- Red geometric shapes - Bottom Left -->
        <div class="shape-bl-1"></div> 
        
        <!-- Red geometric shapes - Bottom Right -->
        <div class="shape-br-1"></div> 
        
        <!-- Logo -->
        <div class="logo">
            <img src="<?= base_url('assets/images/english-hub.png') ?>" alt="English Hub Logo">
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="title-certificate">Certificate</div>
            <div class="title-completion">of Completion</div>
            
            <div class="presented-to">Proudly presented to</div>
            
            <div class="student-name"><?= $sertifikat->nama_anak ?></div>
            
            <div class="description">
                Student has successfully advanced to <span class="underline"><?= $sertifikat->nama_level ?></span> level<br>
                after completing the requirements of <span class="underline"><?= $sertifikat->nama_level ?></span> level on <span class="underline"><?= date('jS F Y', strtotime($sertifikat->tanggal_terbit)) ?></span>
            </div>
            
            <div class="presented-by">Presented by English Hub Bali</div>
            
            <!-- Award Seal with ribbon -->
            <div class="medal-container">
                <div class="ribbon ribbon-left"></div>
                <div class="ribbon ribbon-right"></div>
                <div class="seal"></div>
            </div>
            
            <!-- Signatures -->
            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-name">Dewi Suandari</div>
                    <div class="signature-title">Executive Director</div>
                </div>
                <div class="signature-box">
                    <div class="signature-name"><?= $sertifikat->nama ?></div>
                    <div class="signature-title">Teacher</div>
                </div>
            </div>
        </div>
        
        <div class="cert-number">No: <?= $sertifikat->nomor_sertifikat ?></div>
    </div>
    
    <script>
        function savePDF() {
            const element = document.getElementById('certificate');
            const certNumber = '<?= $sertifikat->nomor_sertifikat ?>';
            const studentName = '<?= $sertifikat->nama_anak ?>';
            
            const opt = {
                margin: 0,
                filename: 'Certificate-' + studentName.replace(/\s+/g, '_') + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    useCORS: true,
                    letterRendering: true,
                    width: 1123,
                    height: 794
                },
                jsPDF: { 
                    unit: 'mm', 
                    format: 'a4', 
                    orientation: 'landscape' 
                }
            };
            
            // Hide buttons temporarily for clean PDF
            const buttons = document.querySelector('.action-buttons');
            buttons.style.display = 'none';
            
            html2pdf().set(opt).from(element).save().then(() => {
                buttons.style.display = 'flex';
            });
        }
    </script>
</body>
</html>
