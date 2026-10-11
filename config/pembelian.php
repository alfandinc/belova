<?php

return [
    /*
     * PPN rate (percent) used to estimate the value of a permintaan and in the faktur item export.
     * A faktur itself carries the tax typed in from the supplier invoice.
     */
    'ppn_persen' => (float) env('PEMBELIAN_PPN_PERSEN', 11),

    /*
     * Approve faktur: warn when the price differs from Master Pembelian by more than this percent.
     */
    'toleransi_harga_persen' => (float) env('PEMBELIAN_TOLERANSI_HARGA_PERSEN', 10),

    /*
     * Harga jual: target margin over HNA (HPP + PPN), and the saran harga is rounded up to this many rupiah.
     */
    'target_margin_persen' => (float) env('HARGA_JUAL_TARGET_MARGIN_PERSEN', 30),
    'pembulatan_harga_jual' => (int) env('HARGA_JUAL_PEMBULATAN', 100),
];
