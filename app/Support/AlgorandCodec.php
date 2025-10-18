<?php
namespace App\Support;

use MessagePack\MessagePack;

class AlgorandCodec
{
    public static function encodeLsigSignedTxn(array $txnFields, string $programB64, array $args = []): string
    {
        $logicSig = [
            'l'   => base64_decode($programB64), // program bytes
            'arg' => $args,                      // list of byte strings
            'sig' => '',                         // empty = pure LogicSig
        ];

        $signed = [
            'txn'  => $txnFields,
            'lsig' => $logicSig,
        ];

        return MessagePack::pack($signed); // RAW BYTES
    }
}
