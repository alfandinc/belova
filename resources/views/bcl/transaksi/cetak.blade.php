@extends('layouts.bcl.app')

@section('content')
<!-- Page-Title -->
<?php

use Carbon\Carbon;

$transaksi = $transaksi;
$total_masuk = 0;
foreach ($transaksi->jurnal as $jurnal) {
    $total_masuk += $jurnal->kredit;
}
// generate QR code for the signed user name (used in TTD section)
$__signedUserName = Auth::user()->name ?? '';
// Prefer embedding the QR image as a data URI so client-side canvas captures include it.
$__signedUserQrRemote = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . urlencode($__signedUserName);
$__signedUserQr = $__signedUserQrRemote;
try {
    // attempt server-side fetch and base64 encode
    $qr_contents = @file_get_contents($__signedUserQrRemote);
    if ($qr_contents !== false) {
        $mime = 'image/png';
        $__signedUserQr = 'data:' . $mime . ';base64,' . base64_encode($qr_contents);
    }
} catch (\Exception $e) {
    // ignore, keep remote URL
}
?>
@section('page_css')
<link href="{{asset('plugins/datatables/responsive.bootstrap4.min.css')}}" rel="stylesheet" type="text/css" />
<style>
    .table td {
        vertical-align: top !important;
    }

    /* .watermark {
        background: url("assets/images/watermark.png") !important;
        transform: rotate(30deg);
    } */

    .invoice-head {
        border-bottom: 0px white !important;
    }
</style>
@endsection
<div class="row">
    <div class="col-sm-12">
        <div class="page-title-box">
            <div class="row">
                <div class="col">
                    <h4 class="page-title">Cetak Transaksi Sewa</h4>
                    <span>{{config('app.name')}}</span>
                </div><!--end col-->
                <div class="col-auto align-self-center">
                </div><!--end col-->
            </div><!--end row-->
        </div><!--end page-title-box-->
    </div><!--end col-->
</div><!--end row-->

<div class="row">
    <div class="col-lg-12">
        <div class="row">
            <div class="col-lg-12 mx-auto">
                <div class="card " id="print_this" style="border-bottom: #F1F5FA !important;">
                    <div class="card-body invoice-head top-color">
                        <div class="row align-items-center mb-2" style="border-bottom: 4px double #b6c2e4;">
                            <div class="col-sm-4 align-self-center">
                                <img src="{{asset('assets/images/')}}/logo_full.png" alt="logo-small" class="logo-sm mr-1" height="84">
                                <p class="mt-0 mb-1 text-muted">{{config('app.name')}} {{config('app.tagline')}}</p>
                                <!-- <p class="m-0 p-0 text-muted font-weight-light"></p> -->
                            </div>
                            <div class="col-sm-7">
                                <ul class="list-inline mb-0 contact-detail float-right">
                                    <li class="list-inline-item">
                                        <div class="pl-3">
                                            <i class="mdi mdi-contact-mail"></i>
                                            <p class="text-muted mb-0">+62 813-2615-0009</p>
                                            <p class="text-muted mb-0">+62 813-2615-0009</p>
                                        </div>
                                    </li>
                                    <li class="list-inline-item">
                                        <div class="pl-3">
                                            <i class="mdi mdi-earth"></i>
                                            <p class="text-muted mb-0">Jl. Melon Raya No.1, Karangasem, Kec Laweyan,</p>
                                            <p class="text-muted mb-0">Kota Surakarta, Jawa Tengah 57145</p>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <!-- </div>
                            <div class="card-body"> -->
                        <div class="row mt-2">
                            <div class="col-sm-4">
                                <div class="float-left">
                                    <address class="font-13">
                                        <strong class="font-15">Ditujukan Kepada: </strong><br>
                                        <b>{{$transaksi->renter->nama}}</b> (Hp: {{$transaksi->renter->phone}})<br>
                                        <!-- <b></b><br> -->
                                        {{$transaksi->renter->alamat}}

                                    </address>
                                </div>
                            </div>
                            <div class="col-sm-5 text-right align-items-center">

                            </div>
                            <div class="col-sm-3">
                                <div class="text-right">
                                    <h4 class="mb-2 mt-0 p-0"><b>INVOICE</b></h4>
                                    <h6 style="margin-top: 0px; padding-top: 0px;"><b>Nomor: </b><span class="inv_number">{{$transaksi->trans_id}}</span></h6>
                                    <h6 class="mb-0"><b>Tanggal: </b> {{$transaksi->tanggal}}</h6>
                                    <!-- <h6 class="mb-2"><b>Jatuh Tempo: </b></h6> -->
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12 ">
                                <div class="table-responsive project-invoice">
                                    <table class="table mb-0">
                                        <thead style="background-color: #F1F5FA !important;">
                                            <tr>
                                                <th style="background-color: #F1F5FA;">No</th>
                                                <th>Deskripsi</th>
                                                <th class="text-left">Tipe Kamar</th>
                                                <th class="text-left">Paket</th>
                                                <th class="text-center">Check-in</th>
                                                <th class="text-center">Check-out</th>
                                                <th class="text-right text-nowrap">Nominal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $no = 1;
                                            ?>
                                            <tr>
                                                <td>{{$no}}</td>
                                                <td>
                                                    @if(strpos($transaksi->identity, 'Pindah Kamar') !== false)
                                                        @if($transaksi->catatan && strpos($transaksi->catatan, 'Pindah kamar:') !== false)
                                                            {{ $transaksi->catatan }}
                                                        @else
                                                            Upgrade Kamar No {{$transaksi->room->room_name??'Kamar dihapus'}}
                                                        @endif
                                                    @else
                                                        Sewa Kamar No {{$transaksi->room->room_name??'Kamar dihapus'}}
                                                    @endif
                                                </td>
                                                <td>{{$transaksi->room->category->category_name??'Dihapus'}}</td>
                                                <td>{{$transaksi->lama_sewa.' '.$transaksi->jangka_sewa}}</td>
                                                <td class="text-center">{{$transaksi->tgl_mulai}}</td>
                                                <td class="text-center">{{$transaksi->tgl_selesai}}</td>
                                                <td class="text-right">Rp {{number_format($transaksi->harga,2)}}</td>
                                            </tr>
                                            <?php
                                            $no++;
                                            $total_tbh = 0;
                                            $total_dibayar = 0;
                                            ?>

                                            @foreach($transaksi->tambahan as $tbh)
                                            <?php
                                            $total_tbh += $tbh->harga*$tbh->qty*$tbh->lama_sewa;

                                            foreach ($tbh->jurnal as $jurnal) {
                                                $total_dibayar += $jurnal->kredit;
                                            }
                                            ?>
                                            <tr>
                                                <td>{{$no}}</td>
                                                <td>Tambahan {{$tbh->nama}}</td>
                                                <td>{{$transaksi->room->category->category_name}}</td>
                                                <td>{{$tbh->lama_sewa.' '.$tbh->jangka_sewa}}</td>
                                                <td class="text-center">{{$tbh->tgl_mulai}}</td>
                                                <td class="text-center">{{$tbh->tgl_selesai}}</td>
                                                <td class="text-right">Rp {{number_format($tbh->harga*$tbh->qty*$tbh->lama_sewa,2)}}</td>
                                            </tr>
                                            @endforeach
                                            <tr>
                                                <td height="100"></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                            </tr>

                                            <?php
                                            $no++
                                            ?>

                                            <tr>
                                                <td colspan="5" class="border-0"></td>
                                                <td class="border-0 font-14 text-dark text-right"><b>Sub Total</b></td>
                                                <td class="border-0 font-14 text-dark text-right"><b>Rp {{number_format($transaksi->harga+$total_tbh,2)}}</b></td>
                                            </tr>

                                                <tr>
                                                    <th colspan="5" class="border-0"></th>
                                                    <td class="border-0 font-14 text-dark text-right text-nowrap"><b>DP/Pembayaran</b></td>
                                                    <td class="border-0 font-14 text-dark text-right"><b>Rp {{number_format($total_masuk+$total_dibayar,2)}}</b></td>
                                                </tr>
                                                <tr style="background-color: #000000 !important; color: white;">
                                                    <th colspan="5" class="border-0"></th>
                                                    <td class="border-0 font-14 text-right"><b>Sisa/Kurang</b></td>
                                                    <td class="border-0 font-14 text-right text-nowrap"><b>Rp {{number_format($transaksi->harga+$total_tbh-$total_dibayar-$total_masuk,2)}}</b></td>
                                                </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 mt-2">
                                <h5 class="">Pembayaran: </h5>
                                <div class="col-sm-8">
                                    <div class="card">
                                        <div class="card-body pl-0 pt-0 pb-0">
                                            <div class="media align-items-center">
                                                <img style="height: 50px;" src="{{asset('assets/images/bank/')}}/BNI.png" alt="" class="rounded">
                                                <div class="media-body align-items-center ml-3 text-truncate">
                                                    <h5 class="my-0 mt-1 font-weight-bold">CV BELOVA GRHA</h5>
                                                    <h6 class="font-13 mt-1">2013131515</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <h6 class="">Syarat & Ketentuan:</h6>
                                <ul class="pl-3">
                                    <li><small class="font-12">Simpan bukti pembayaran ini sebagai bukti pembayaran yang sah</small></li>
                                    <li><small class="font-12">Mohon periksa kembali barang barang anda sebelum Check-out,
                                            apabila terdapat kehilangan barang yang berada di dalam kamar maka sepenuhnya menjadi tanggung jawab penyewa</small></li>
                                </ul>
                            </div>
                            <!-- <div class="col-sm-2"></div> -->
                            <div class="col-sm-6 align-self-end">
                                <div class="float-right text-center" style="width: 30%;">
                                    <div style="display:flex;flex-direction:column;align-items:center;">
                                        <img src="<?= $__signedUserQr ?>" alt="QR" style="height:80px;margin-bottom:6px;" />
                                        <p class="border-top" style="margin-top:8px;font-size:12px;"><?= htmlspecialchars($__signedUserName) ?></p>
                                    </div>
                                </div>
                            </div>
                            <!--end col-->
                        </div>
                        <!--end row-->
                        <hr>
                        <div class="row d-flex justify-content-center bottom-color">
                            <div class="col-lg-12 col-xl-4 ml-auto align-self-center">
                                <div class="text-center"><small class="font-12">Terimakasih atas kepercayaan anda</small></div>
                            </div>
                            <!--end col-->
                            <div class="col-lg-12 col-xl-4">
                                <div class="row float-right d-print-none">
                                    <a class="btn btn-danger btn-sm mr-2" href="{{route('bcl.transaksi.index')}}"><i class="mdi mdi-chevron-left"></i> Kembali</a>
                                    <button type="button" onclick="download_receipt()" class="btn btn-success btn-sm"><i class="mdi mdi-download"></i> Download</button>
                                </div>

                            </div>
                            <!--end col-->
                        </div>
                        <!--end row-->
                    </div>
                    <!--end card-body-->
                </div>
                <!--end card-->
            </div>

        </div>
    </div>
</div>
<div id="previewImage" style="display: none;"></div>
@endsection
@section('pagescript')
<!-- Attempt to load local html2canvas/FileSaver; blade references may 404 if files aren't present. Our code below handles missing libraries gracefully. -->
<script src="{{asset('plugins/html2canvas/html2canvas.min.js')}}" type="text/javascript"></script>
<script src="{{asset('plugins/html2canvas/FileSaver.min.js')}}" type="text/javascript"></script>
<script>
    // Simple script loader returning a Promise
    function loadScript(url) {
        return new Promise(function(resolve, reject) {
            var s = document.createElement('script');
            s.src = url;
            s.async = true;
            s.onload = function() { resolve(); };
            s.onerror = function(e) { reject(e); };
            document.head.appendChild(s);
        });
    }

    // Ensure html2canvas and FileSaver are available. Load from CDN if missing.
    function ensureLibraries() {
        var promises = [];
        if (typeof html2canvas === 'undefined') {
            // html2canvas CDN
            promises.push(loadScript('https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'));
        }
        if (typeof window.saveAs === 'undefined') {
            // FileSaver CDN (provides window.saveAs)
            promises.push(loadScript('https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js'));
        }
        return Promise.all(promises);
    }

    function download_receipt() {
        $('.d-print-none').addClass('hidden');
        $("#previewImage").html('');
        var element = $("#print_this")[0];

        ensureLibraries().catch(function(err){
            // If CDN load failed, we'll still try to proceed — fallback to print if capture fails
            console.warn('Could not load helper libraries from CDN', err);
        }).then(function(){
            // If html2canvas still missing, fallback to print
            if (typeof html2canvas === 'undefined') {
                alert('Preview library not available. Opening print dialog instead.');
                window.print();
                $('.d-print-none').removeClass('hidden');
                return;
            }

            html2canvas(element).then(function(canvas) {
            $("#previewImage").append(canvas);
            // Prefer toBlob (binary), then FileSaver.saveAs if available, else fallback to anchor download
            function fallbackDownload(dataUrl, filename) {
                try {
                    var link = document.createElement('a');
                    link.href = dataUrl;
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } catch (e) {
                    // Last resort: open in new tab
                    window.open(dataUrl, '_blank');
                }
            }

            var filename = 'Receipt_{{$transaksi->renter->nama}}.png';

            if (canvas.toBlob) {
                canvas.toBlob(function(blob) {
                    // If FileSaver's saveAs is available, use it; otherwise create object URL
                    if (typeof window.saveAs === 'function') {
                        try {
                            window.saveAs(blob, filename);
                        } catch (e) {
                            // Fallback to object URL
                            var url = URL.createObjectURL(blob);
                            var a = document.createElement('a');
                            a.href = url;
                            a.download = filename;
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                            URL.revokeObjectURL(url);
                        }
                    } else {
                        var url = URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                        URL.revokeObjectURL(url);
                    }
                }, 'image/png');
            } else {
                // toBlob not available: use toDataURL and fallback download
                try {
                    var dataUrl = canvas.toDataURL('image/png');
                    fallbackDownload(dataUrl, filename);
                } catch (e) {
                    // If dataURL fails, open print
                    alert('Unable to generate image. Opening print dialog instead.');
                    window.print();
                }
            }

            $('.d-print-none').removeClass('hidden');
            }).catch(function(err){
                console.error('html2canvas error', err);
                alert('Gagal membuat preview. Silakan gunakan fungsi Print sebagai alternatif.');
                $('.d-print-none').removeClass('hidden');
            });
        });
    }
</script>
@stop