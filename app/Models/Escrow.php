<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Escrow extends Model {
    protected $fillable = [
      'employer_address','freelancer_address','amount_microalgo','deadline_round',
      'sha256_release_hash','sha256_cancel_hash',
      'escrow_address','teal_source','program_b64','status'
    ];
}
