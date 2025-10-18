<?php
namespace App\Services;

use App\Algorand\algorand as AlgodClient;   // provided by the SDK
use Illuminate\Support\Facades\Http;

class AlgorandService {
    public $algod;
    public $indexer;
    public string $algonet;

    public function __construct() {
        $this->algonet = config('algorand.network', env('ALGONET_NAME','testnet-v1.0'));

        // Algod (SDK thin wrapper)
        $this->algod = new \Algorand(
            "algod",
            env('ALGOD_API_KEY',''),
            parse_url(env('ALGOD_URL'))['host'] ?? 'testnet-api.algonode.cloud',
            (int) env('ALGOD_PORT', 0),
            filter_var(env('ALGOD_IS_EXTERNAL', true), FILTER_VALIDATE_BOOLEAN)
        );

        // Indexer (SDK)
        $this->indexer = new \Algorand(
            "indexer",
            env('INDEXER_API_KEY',''),
            parse_url(env('INDEXER_URL'))['host'] ?? 'testnet-idx.algonode.cloud',
            (int) env('INDEXER_PORT', 0),
            filter_var(env('INDEXER_IS_EXTERNAL', true), FILTER_VALIDATE_BOOLEAN)
        );
    }

    public function suggestedParams(): array {
        return $this->algod->get("v2","transactions/params"); // fv, lv, gh, fee, etc.
    }

    public function compileTeal(string $tealSrc): array {
        // SDK post() accepts text; node must have EnableDeveloperAPI
        return $this->algod->post("v2","teal/compile", $tealSrc, ['Content-Type: text/plain']);
    }

    public function sendRawTxn(string $raw)
{
    $url = rtrim(env('ALGOD_URL', 'https://testnet-api.algonode.cloud'), '/').'/v2/transactions';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $raw,  // raw binary
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/x-binary',
        ],
    ]);

    $res  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($err) {
        throw new \RuntimeException("Curl error: ".$err);
    }

    if ($code < 200 || $code >= 300) {
        throw new \RuntimeException("Algod error HTTP {$code}: ".$res);
    }

    $json = json_decode($res, true);
    if (!is_array($json)) {
        throw new \RuntimeException("Invalid response from Algod: ".$res);
    }

    return $json;
}

    
    public function accountInfo(string $addr): array {
        return $this->algod->get("v2","accounts/{$addr}");
    }

    public function lookupTxnsByAddress(string $addr): array {
        return $this->indexer->get("v2","accounts/{$addr}/transactions");
    }
}
