<?php

namespace App\Http\Controllers;

use App\Models\Escrow;

class EscrowPageController extends Controller
{
    public function show(int $id)
    {
        $escrow = Escrow::findOrFail($id);
        return view('escrows.show', ['escrow' => $escrow]);
    }
}
