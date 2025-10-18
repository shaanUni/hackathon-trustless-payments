<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Escrow;
use App\Services\AlgorandService;
use Illuminate\Http\Request;

class EscrowController extends Controller
{
    public function create(Request $req, AlgorandService $algo)
    {
        $v = $req->validate([
            'employer_address' => ['required', 'regex:/^[A-Z2-7]{58}$/'],
            'freelancer_address' => ['required', 'regex:/^[A-Z2-7]{58}$/'],
            'amount_algo' => 'required|numeric|min:0.000001',
            'deadline_round' => 'required|integer|min:1',
            'sha256_release_hex' => 'nullable|regex:/^[0-9a-fA-F]{64}$/',
            'sha256_cancel_hex' => 'nullable|regex:/^[0-9a-fA-F]{64}$/',
        ]);

        $amountMicro = (int) round($v['amount_algo'] * 1_000_000);

        $tpl = file_get_contents(base_path('resources/teal/escrow.teal'));
        $filled = str_replace(
            ['<EMPLOYER_ADDR>', '<FREELANCER_ADDR>', '<DEADLINE_ROUND>', '<SHA256_RELEASE_HEX>', '<SHA256_CANCEL_HEX>'],
            [
                $v['employer_address'],
                $v['freelancer_address'],
                (string) $v['deadline_round'],
                $v['sha256_release_hex'] ?? str_repeat('0', 64),
                $v['sha256_cancel_hex'] ?? str_repeat('0', 64),
            ],
            $tpl
        );

        // Compile via SDK (correct signature)
        $params = [
            'body' => $filled,
            'headers' => ['Content-Type' => 'text/plain'],
        ];
        $resp = $algo->algod->post('v2', 'teal', 'compile', $params);
        $payload = $resp['response'] ?? $resp;
        if (is_string($payload))
            $payload = json_decode($payload, true);
        if (is_object($payload))
            $payload = json_decode(json_encode($payload), true);

        if (!is_array($payload) || !isset($payload['hash'], $payload['result'])) {
            $msg = is_array($payload) && isset($payload['message']) ? $payload['message'] : 'Unknown node response';
            throw new \RuntimeException("TEAL compile failed: " . json_encode(['message' => $msg]));
        }

        $escrow = Escrow::create([
            'employer_address' => $v['employer_address'],
            'freelancer_address' => $v['freelancer_address'],
            'amount_microalgo' => $amountMicro,
            'deadline_round' => (int) $v['deadline_round'],
            'sha256_release_hash' => $v['sha256_release_hex'] ?? null,
            'sha256_cancel_hash' => $v['sha256_cancel_hex'] ?? null,
            'escrow_address' => $payload['hash'],
            'teal_source' => $filled,
            'program_b64' => $payload['result'],
            'status' => 'AWAIT_FUNDING',
        ]);

        return response()->json([
            'escrow_id' => $escrow->id,
            'escrow_address' => $escrow->escrow_address,
            'amount_micro' => $escrow->amount_microalgo,
            'deadline_round' => $escrow->deadline_round,
        ], 201);
    }

    public function release(int $id, \Illuminate\Http\Request $req, \App\Services\AlgorandService $algo)
{
    $e = \App\Models\Escrow::findOrFail($id);

    // Always provide arg0 (empty string OK for deadline path)
    $preimage = (string) $req->input('preimage', "");

    /**
     * 1) Get canonical suggested params (transactions/params)
     *    This endpoint MUST have: last-round, genesis-hash, genesis-id, min-fee
     */
    $raw = $algo->algod->get('v2', 'transactions/params'); // or: get('v2','transactions','params')
    $params = $raw['response'] ?? $raw; // some clients nest under 'response'
    if (is_string($params)) $params = json_decode($params, true);
    if (!is_array($params)) $params = [];

    $fv  = (int)($params['last-round']   ?? $params['lastRound']   ?? 0);
    $ghS =        $params['genesis-hash']?? $params['genesisHash'] ?? null;
    $gen =        $params['genesis-id']  ?? $params['genesisId']   ?? 'testnet-v1.0';
    $fee = (int) ($params['min-fee']     ?? $params['minFee']      ?? $params['fee'] ?? 1000);

    // Fallback for last-round if provider omits it for some reason
    if ($fv <= 0) {
        $status = $algo->algod->get('v2', 'status');
        $fv = (int)($status['last-round'] ?? $status['lastRound'] ?? 0);
    }

    if ($fv <= 0 || empty($ghS)) {
        // Fail early with details so you can see what the node returned
        throw new \RuntimeException("Algorand node missing required fields in /v2/transactions/params: ".json_encode($params));
    }

    $lv  = $fv + 1000;              // ~10–15 minutes validity window
    $ghB = base64_decode($ghS);     // MUST be raw 32-byte hash
    $fee = max(1000, $fee);         // ensure min fee

    /**
     * 2) Convert addresses to raw 32-byte public key bytes
     */
    $sndB = \App\Support\AlgoBin::addrToBytes($e->escrow_address);
    $rcvB = \App\Support\AlgoBin::addrToBytes($e->freelancer_address);

    /**
     * 3) Build inner transaction map with CORRECT types
     */
    $txnFields = [
        'type' => 'pay',
        'snd'  => $sndB,                      // bytes (32)
        'rcv'  => $rcvB,                      // bytes (32)
        'amt'  => (int)$e->amount_microalgo,  // uint64
        'fee'  => (int)$fee,                  // uint64
        'fv'   => (int)$fv,                   // uint64
        'lv'   => (int)$lv,                   // uint64
        'gh'  => $ghB,                        // bytes (32)
        'gen' => $gen,                        // string
        'note'=> 'release',                   // bytes (string)
        // no 'close' for release path
    ];

    /**
     * 4) Encode (txn + lsig) as raw msgpack bytes
     *    lsig: pure LogicSig (program bytes + args, empty 'sig')
     */
    $msgpack = \App\Support\AlgorandCodec::encodeLsigSignedTxn(
        $txnFields,
        $e->program_b64,
        [$preimage] // arg0 ALWAYS present (empty string OK)
    );

    /**
     * 5) Broadcast raw binary to algod
     */
    $sent = $algo->sendRawTxn($msgpack); // MUST use Content-Type: application/x-binary

    // Persist status
    $e->status = 'RELEASED';
    $e->save();

    return response()->json([
        'txId'               => $sent['txId'] ?? null,
        'escrow_address'     => $e->escrow_address,
        'freelancer_address' => $e->freelancer_address,
        'amount'             => $e->amount_microalgo,
        'first_valid'        => $fv,
        'last_valid'         => $lv,
    ]);
}


}