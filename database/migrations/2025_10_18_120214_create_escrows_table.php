<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('escrows', function (Blueprint $t) {
            $t->id();
            $t->string('client_email')->nullable();
            $t->string('freelancer_address');
            $t->unsignedBigInteger('amount_microalgo'); // in microalgos
            $t->unsignedBigInteger('deadline_round');   // block/round for auto-release
            $t->string('sha256_hash')->nullable();      // optional secret-hash for early release
            $t->string('escrow_address');               // LogicSig address (program hash)
            $t->text('teal_source');                    // stored for transparency
            $t->text('program_b64');                    // compiled program (base64)
            $t->string('status')->default('AWAIT_FUNDING'); // AWAIT_FUNDING | FUNDED | PAID | REFUNDED | EXPIRED
            $t->timestamps();
          });
          
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('escrows');
    }
};
