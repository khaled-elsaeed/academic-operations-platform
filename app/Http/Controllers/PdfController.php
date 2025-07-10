<?php

namespace App\Http\Controllers;

use App\Models\User;
use PDF;

class PdfController extends Controller
{
    public function invoice()
    {
        $pdf = PDF::loadView('pdf.invoice');

        return $pdf->stream('document.pdf');
    }

}