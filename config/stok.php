<?php

return [
    /*
     * Penyesuaian Stok (Mutasi Stok) approval.
     * A document whose value (sum of qty x HPP) reaches the threshold waits for approval before the
     * stok changes. Penyesuaian masuk (stock without a purchase document) always needs approval.
     * With boleh_approve_sendiri an approver may also approve documents they created themselves
     * (Admin/Farmasi keep full control); set it to false to require a second person.
     */
    'penyesuaian' => [
        'approval_threshold' => (float) env('STOK_PENYESUAIAN_APPROVAL_THRESHOLD', 1000000),
        'masuk_selalu_approval' => true,
        'approver_roles' => ['Admin', 'Farmasi', 'Marketing', 'Beautician'],
        'boleh_approve_sendiri' => (bool) env('STOK_PENYESUAIAN_BOLEH_APPROVE_SENDIRI', true),
    ],

    /*
     * "Data Perlu Dicek" in Manajemen Stok (StokAuditService).
     * ed_placeholder: ED values known to be defaults (the old-system migration used 2025-12-05).
     * ed_massal_min_obat: one ED shared by at least this many different obat is treated as a default.
     * batch_sementara_pattern: batch names made by the system / test data rather than the manufacturer.
     */
    'audit' => [
        'ed_placeholder' => ['2025-12-05'],
        'ed_massal_min_obat' => 10,
        'ed_min' => '2015-01-01',
        'ed_max_tahun' => 10,
        'batch_sementara_pattern' => '/^(MIGRATE|INITIAL|BULK)\b|TEST|DUMMY|CONTOH|SAMPLE/i',
    ],
];
