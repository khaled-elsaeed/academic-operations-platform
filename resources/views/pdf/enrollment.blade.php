<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('INVOICE') }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <style media="all">
        /* ===== FONT DEFINITIONS ===== */
        @font-face {
            font-family: 'Cairo';
            src: url('{{ public_path('fonts/Cairo-Regular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        /* ===== BASE STYLES ===== */
        body, th, td {
            font-size: 0.875rem;
            font-family: 'Cairo', Arial, sans-serif;
            font-weight: normal;
            direction: rtl;
            text-align: right;
            padding: 0;
            margin: 0;
        }

        /* ===== TEXT ALIGNMENT ===== */
        .text-left {
            text-align: right;
        }
        
        .text-right {
            text-align: left;
        }

        /* ===== TABLE STYLES ===== */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            font-weight: normal;
        }

        table.padding th {
            padding: .25rem .7rem;
        }

        table.padding td {
            padding: .25rem .7rem;
        }

        table.sm-padding td {
            padding: .1rem .7rem;
        }

        .border-bottom td,
        .border-bottom th {
            border-bottom: 1px solid #eceff4;
        }

        th td, tr td, table, tr {
            border: 0;
        }

        /* ===== HEADER STYLES ===== */
        .header-logo-section {
            display: table-cell;
            vertical-align: middle;
            width: 60px;
            padding: 5px;
        }
        
        .header-text-section {
            display: table-cell;
            vertical-align: middle;
            padding: 5px 10px;
        }
        
        .header-logo {
            height: 50px;
            width: auto;
            max-width: 60px;
        }
        
        .header-text-arabic {
            font-family: 'Cairo', Arial, sans-serif;
            font-size: 12px;
            font-weight: bold;
            color: #333;
            line-height: 1.3;
            text-align: right;
            direction: rtl;
        }
        
        .header-text-english {
            font-family: 'Cairo', Arial, sans-serif;
            font-size: 12px;
            font-weight: bold;
            color: #333;
            line-height: 1.3;
            text-align: left;
            direction: ltr;
        }

        /* ===== CONTENT STYLES ===== */
        .gry-color *,
        .gry-color {
            color: #000;
            padding: 5px;
            border-color: transparent;
        }

        /* ===== PAGE SETTINGS ===== */
        @page {
            margin: 50pt 15pt 40pt 15pt;
            header: page-header;
            footer: page-footer;
        }
    </style>
</head>
<body>
<div>
    <!-- ===== PAGE HEADER ===== -->
    <htmlpageheader name="page-header">
        <table width="100%" style="border-collapse:collapse; margin-bottom: 10px;">
            <tr>
                <!-- Right side: Arabic content -->
                <td width="45%" align="right" valign="middle" style="padding: 40px;">
                    <table style="border-collapse:collapse; width: 100%;">
                        <tr>
                            <td align="right" valign="middle" style="padding: 0 10px 0 0;">
                                <div style="font-family: 'Cairo', Arial, sans-serif; font-size: 12px; font-weight: bold; color: #333; line-height: 1.3; text-align: left; direction: rtl;">
                                    جامعة المنصورة الجديدة<br>
                                    كلية علوم وهندسة الحاسب
                                </div>
                            </td>
                            <td align="right" valign="middle" style="width: 100px; padding: 0;">
                                <img src="{{ public_path('pdf/nmu_left.png') }}" style="height:50px; width:auto; max-width:60px;">
                            </td>
                        </tr>
                    </table>
                </td>
                
                <!-- Center spacing -->
                <td width="10%"></td>
                
                <!-- Left side: English content -->
                <td width="45%" align="left" valign="middle" style="padding: 40px;">
                    <table style="border-collapse:collapse; width: 100%;">
                        <tr>
                            <td align="left" valign="middle" style="padding: 0 0 0 0;">
                                <div style="font-family: 'Cairo', Arial, sans-serif; font-size: 12px; font-weight: bold; color: #333; line-height: 1.3; text-align: left; direction: ltr;">
                                    NEW MANSOURA UNIVERSITY<br>
                                    Faculty of Computer Science<br>
                                    and Engineering
                                </div>
                            </td>
                            <td align="left" valign="middle" style="width: 70px; padding: 0;">
                                <img src="{{ public_path('pdf/nmu_right.png') }}" style="height:50px; width:auto; max-width:60px;">
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </htmlpageheader>

    <!-- Alternative English-only header -->
    <htmlpageheader name="page-header-en">
        <table width="100%" style="border-collapse:collapse; margin-bottom: 10px;">
            <tr>
                <td width="70px" align="left" valign="middle" style="padding: 5px;">
                    <img src="{{ public_path('pdf/nmu_right.png') }}" style="height:50px; width:auto; max-width:60px;">
                </td>
                <td align="left" valign="middle" style="padding: 0 0 0 15px;">
                    <div style="font-family: 'Cairo', Arial, sans-serif; font-size: 14px; font-weight: bold; color: #333; line-height: 1.3; text-align: left; direction: ltr;">
                        NEW MANSOURA UNIVERSITY<br>
                        Faculty of Computer Science<br>
                        and Engineering
                    </div>
                </td>
            </tr>
        </table>
    </htmlpageheader>
    
    <!-- ===== MAIN CONTENT ===== -->
    <div style="padding: 80px 15px 20px 15px; margin-top: 80px;">
        
        <!-- ===== DOCUMENT TITLE ===== -->
        <div style="text-align: center; margin-bottom: 30px;">
            <h2 style="font-family: 'Cairo', Arial, sans-serif; font-size: 18px; font-weight: bold; color: #333; margin: 0;">
                نموذج ارشاد الطلاب لفصل {{ $semester ?? 'الصيف' }} {{ $academic_year ?? '2024/2025' }} المستوى الأول
            </h2>
        </div>

        <!-- ===== STUDENT INFORMATION SECTION ===== -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; width: 30%; font-weight: bold;">الرقم الأكاديمي:</td>
                <td style="padding: 8px; border: 1px solid #ddd; width: 70%;">{{ $academic_number ?? '________' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">اسم الطالب:</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $student_name ?? '________' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">الرقم القومي:</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $national_id ?? '________' }}</td>
            </tr>
            <tr>
                <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">اسم البرنامج:</td>
                <td style="padding: 8px; border: 1px solid #ddd;">{{ $program_name ?? '________' }}</td>
            </tr>
        </table>

        <!-- ===== NOTICE SECTION ===== -->
        <div style="margin-bottom: 30px; text-align: justify; line-height: 1.6;">
            <p style="margin: 0; font-size: 14px;">
                نحيطكم علماً بأن المقررات المسجلة في فصل الصيف من العام الأكاديمي الحالي 2024/2025 الخاصة بك هي على النحو التالي:
            </p>
        </div>

        <!-- ===== COURSES TABLE SECTION ===== -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
            <thead>
                <tr style="background-color: #f5f5f5;">
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: bold; width: 10%;">م</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: bold; width: 20%;">كود المقرر</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: bold; width: 50%;">اسم المقرر</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: center; font-weight: bold; width: 20%;">عدد ساعات المقرر</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= 10; $i++)
                <tr>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">{{ $i }}</td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">{{ ${"course_code_$i"} ?? '________' }}</td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">{{ ${"course_name_$i"} ?? '________' }}</td>
                    <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">{{ ${"course_hours_$i"} ?? '________' }}</td>
                </tr>
                @endfor
                <tr>
                    <td colspan="3" style="padding: 12px; border: 1px solid #ddd; font-weight: bold; text-align: right; background-color: #f5f5f5;">
                        إجمالي عدد الساعات المسجلة
                    </td>
                    <td style="padding: 12px; border: 1px solid #ddd; font-weight: bold; text-align: center; background-color: #f5f5f5;">
                        {{ $total_hours ?? '________' }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- ===== SIGNATURES SECTION ===== -->
        <div style="margin-top: 60px;">
            <table class="text-right sm-padding small strong">
                <thead>
                    <tr>
                        <th width="2%"></th>
                        <th width="48%"></th>
                        <th width="2%"></th>
                        <th width="48%"></th>
                        <th width="2%"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td width=""></td>
                        <td class="text-left border-bottom">
                        توقيع الطالب : ...................................................
                        </td>
                        <td width=""></td>
                        <td class="text-left border-bottom">
                        توقيع فريق الارشاد : ...................................................
                        </td>
                        <td width=""></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 20px;margin-bottom:16px">
        <table class="text-right sm-padding small strong">
                <thead>
                    <tr>
                        <th width="2%"></th>
                        <th width="48%"></th>
                        <th width="2%"></th>
                        <th width="48%"></th>
                        <th width="2%"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td width=""></td>
                        <td class="text-left border-bottom">
                        رقم تليفون الطالب : ...................................................
                        </td>
                        <td width=""></td>
                        <td class="text-left border-bottom">
                        توقيع المرشد الأكاديمي : ...................................................
                        </td>
                        
                        <td width=""></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- ===== ADDITIONAL INFORMATION SECTION ===== -->
    <div style="padding: 15px 15px;">
        <table class="text-right sm-padding small strong">
            <thead>
                <tr>
                    <th width="2%"></th>
                    <th width="46%"></th>
                    <th width="4%"></th>
                    <th width="46%"></th>
                    <th width="2%"></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td></td>
                    <td style="padding: 10px 0;"></td>
                    <td></td>
                    <td class="text-left" style="padding: 10px 0;">
                        <span style="font-size: 15px;color: #333">
                            {{ "مع تمنياتنا بالتوفيق والنجاح" }}
                        </span>
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>