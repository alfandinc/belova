<?php

namespace App\Models\Satusehat;

use Illuminate\Database\Eloquent\Model;

class PatientGet extends Model
{
    protected $table = 'satusehat_patients';
    protected $fillable = ['visitation_id','pasien_id','pasien_name','satusehat_patient_id','raw_response'];
}
