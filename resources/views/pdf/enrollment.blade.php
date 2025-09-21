<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('ENROLLMENT') }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8">
    <style media="all">
        /* ===== FONT DEFINITIONS ===== */
        @font-face {
            font-family: 'KFGQPC';
            src: url('{{ public_path('fonts/KFGQPC/ArbFONTS-UthmanTN1-Ver10.otf') }}') format('opentype');
            font-weight: normal;
            font-style: normal;
        }

        /* ===== BASE STYLES ===== */
        body, th, td {
            font-size: 1.1rem;
            font-family: 'KFGQPC', Arial, sans-serif;
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
            font-family: 'KFGQPC', Arial, sans-serif;
            font-size: 12px;
            font-weight: bold;
            color: #333;
            line-height: 1.3;
            text-align: right;
            direction: rtl;
        }
        
        .header-text-english {
            font-family: 'KFGQPC', Arial, sans-serif;
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
            margin: 40pt 15pt 40pt 15pt;
            header: page-header;
            footer: page-footer;
        }

        /* ===== PAGE BREAK STYLES ===== */
        .page-break {
            page-break-before: always;
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
        
        <!-- ===== FIRST PAGE CONTENT ===== -->
        <div style="padding: 80px 15px 20px 15px; margin-top: 80px;">
            <!-- ===== EGYPT FLAG SECTION ===== -->
            <div style="text-align: center; margin-bottom: 20px;">
                <img src="{{ public_path('pdf/egypt_flag.png') }}" alt="Egypt Flag" style="height:40px; width:700px;">
            </div>
            <!-- ===== DOCUMENT TITLE ===== -->
            <div style="text-align: center; margin-bottom: 30px;">
                <h2 style="font-family: 'KFGQPC', Arial, sans-serif; font-size: 18px; font-weight: bold; color: #333; margin: 0;">
                    نموذج ارشاد الطلاب لفصل {{ $semester }} {{ $academic_year }} المستوى {{ $level }}
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
                    <td style="padding: 6px; border: 1px solid #ddd; font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 13px;">{{ $program_name ?? '________' }}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">المعدل التراكمي:</td>
                    <td style="padding: 6px; border: 1px solid #ddd; font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 13px;">{{ $cgpa ?? '________' }}</td>
                </tr>
            </table>
            <!-- ===== NOTICE SECTION ===== -->
            <div style="margin-bottom: 30px; text-align: justify; line-height: 1.6;">
                <p style="margin: 0; font-size: 14px;">
                    نحيطكم علماً بأن المقررات المسجلة في فصل {{ $semester }} من العام الأكاديمي الحالي {{ $academic_year }} الخاصة بك هي على النحو التالي:
                </p>
            </div>
            <!-- ===== COURSES TABLE SECTION (LAST THING ON PAGE 1) ===== -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;">
                <thead>
                    <tr style="background-color: #f5f5f5; border: 1px solid #000;">
                        <th style="padding: 6px; border: 1px solid #000; text-align: center; font-weight: bold; width: 10%;">م</th>
                        <th style="padding: 6px; border: 1px solid #000; text-align: center; font-weight: bold; width: 20%;">كود المقرر</th>
                        <th style="padding: 6px; border: 1px solid #000; text-align: center; font-weight: bold; width: 50%;">اسم المقرر</th>
                        <th style="padding: 6px; border: 1px solid #000; text-align: center; font-weight: bold; width: 20%;">عدد ساعات المقرر</th>
                    </tr>
                </thead>
                <tbody>
                    @php $maxRows = 10; @endphp
                    @for ($i = 1; $i <= $maxRows; $i++)
                        <tr>
                            <td style="padding: 6px; border: 1px solid #000; text-align: center; font-family: 'DejaVu Sans', Arial, sans-serif;">{{ $i }}</td>
                            <td style="padding: 6px; border: 1px solid #000; text-align: center; font-family: 'DejaVu Sans', Arial, sans-serif;">
                                {{ ${'course_code_' . $i} ?? '________' }}
                            </td>
                            <td style="padding: 6px; border: 1px solid #000; text-align: center; font-family: 'DejaVu Sans', Arial, sans-serif;">
                                {{ ${'course_title_' . $i} ?? '________' }}
                            </td>
                            <td style="padding: 6px; border: 1px solid #000; text-align: center; font-family: 'DejaVu Sans', Arial, sans-serif;">
                                {{ ${'course_hours_' . $i} ?? '________' }}
                            </td>
                        </tr>
                    @endfor
                    <tr style="border-top: 2px solid #000; border-bottom: 1px solid #000;">
                        <td colspan="3" style="padding: 6px; border: 1px solid #000; border-top: 2px solid #000; border-bottom: 2px solid #000; font-weight: bold; text-align: right; background-color: #f5f5f5;">
                            إجمالي عدد الساعات المسجلة
                        </td>
                        <td style="padding: 6px; border: 1px solid #000; border-top: 2px solid #000; border-bottom: 2px solid #000; font-weight: bold; text-align: center; background-color: #f5f5f5; font-family: 'DejaVu Sans', Arial, sans-serif;">
                            {{ $total_hours ?? '________' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ===== PAGE BREAK - START SECOND PAGE ===== -->
        <div class="page-break">
            <!-- ===== SECOND PAGE CONTENT ===== -->
            <div style="padding: 80px 15px 20px 15px; margin-top: 80px;">
                <!-- ===== NOTES SECTION ===== -->
                <div style="margin: 20px 0 20px 0;">
                    <strong>ملاحظات:</strong>
                    <ul style="font-size: 14px; margin-top: 8px; margin-bottom: 0; padding-right: 20px;">
                        <li>يتعهد الطالب بتسجيل المقررات علي نظام معلومات الطالب <span style="font-family: 'DejaVu Sans', Arial, sans-serif;">SIS</span> مطابق تماما للتسجيل الورقي الذي قام بالتوقيع عليه.</li>
                        <li>في حالة رغبة الطالب في اجراء أي تعديل في تسجيل المقررات خلال فترة التسجيل الممكنة أن يتم ذلك بموافقة وتوقيع المرشد الأكاديمي أولاً.</li>
                        <li>أيضا إتاحة المقرر في الفصل {{ $semester }} يعتمد علي اكتمال الحد الأدنى لفتح المقرر طبقا للأعداد المقررة بلائحة الجامعة.</li>
                        <li>لن يُعتمد تسجيل أي طالب لم يقم بسداد رسوم الفصل الدراسي على النظام <span style="font-family: 'DejaVu Sans', Arial, sans-serif;">SIS</span> فقط، وسيتم إلغاء تسجيله نهائيًا.</li>
                    </ul>
                </div>
                
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
                
                <!-- ===== ADDITIONAL INFORMATION SECTION ===== -->
                <div style="text-align: center; margin-top: 20px;">
                    <span style="font-size: 18px; color: #333; font-family: 'KFGQPC', Arial, sans-serif; font-weight: bold;">
                        مع تمنياتنا بالتوفيق والنجاح
                    </span>
                </div>
            </div>
        </div>

        <!-- ===== PAGE FOOTER ===== -->
        <htmlpagefooter name="page-footer">
            <div style="width: 100%; text-align: right; font-size: 12px; color: #888; font-family: 'DejaVu Sans', Arial, sans-serif;">
                تاريخ استخراج النموذج: {{ $enrollment_date ?? '---' }}
            </div>
        </htmlpagefooter>
    </div>
</body>
</html>