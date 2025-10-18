<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('escrows', function (Blueprint $t) {
            // add the columns your controller inserts
            $t->string('employer_address', 58)->after('client_email'); // Algorand address is 58 chars
            $t->string('sha256_release_hash', 64)->nullable()->after('deadline_round');
            $t->string('sha256_cancel_hash', 64)->nullable()->after('sha256_release_hash');
        });
    }

    public function down(): void
    {
        Schema::table('escrows', function (Blueprint $t) {
            $t->dropColumn(['employer_address', 'sha256_release_hash', 'sha256_cancel_hash']);
        });
    }
};
