<?php

namespace ProDevelopement\GermanBankVerification\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use ProDevelopement\GermanBankVerification\Models\blz;
use ProDevelopement\GermanBankVerification\Classes\GBV as GermanBankVerification;

class AutoPopulateController extends Controller
{

    public function index(){
        return view('GBV::autopopulate.index');
    }

    public function autopopulate(Request $request){
        
        $res = file_get_contents($request->input('url'));
        if(is_file(public_path('kto.txt'))) {
            unlink(public_path('kto.txt'));
        }
        blz::truncate();
        file_put_contents(public_path('kto.txt'), $res);

        $delimiter = "\t";
        $fp = fopen(public_path('kto.txt'), 'r');
        while ( !feof($fp) )
        {
            $line = fgets($fp, 2048);
            $arrgs['blz'] = substr($line, 0, 8);
            $arrgs['namelong'] = utf8_encode(rtrim(substr($line, 9, 58)));
            $arrgs['nameshort'] = utf8_encode(rtrim(substr($line, 107, 27)));
            $arrgs['zipcode'] = substr($line, 67, 5);
            $arrgs['town'] = utf8_encode(rtrim(substr($line, 72, 35)));
            $arrgs['own'] = substr($line, 8, 1);
            $arrgs['bbk'] = 0;
            $arrgs['followid'] = substr($line, 160, 8);
            $arrgs['bic'] = rtrim(substr($line, 139, 11));
            $arrgs['pzc'] = rtrim(substr($line, 150, 2));
            $arrgs['btxname'] = '';
            if(strlen($arrgs['blz']) > 7){
                $blz = blz::create($arrgs);
            }
        }                              
        fclose($fp);
        return 'Database was Updated!';
    }

    public function test($blz = NULL, $kto = NULL){
        //return $blz;
        $t = new GermanBankVerification();
        $blzCount = $t->checkKtoBlz($kto, $blz);
        return response()->json($blzCount);
    }

    /**
     * API Functions depending on Method Called
     * Methods: getBanks, suggestBanks, checkBankInfo
     */
    public function apiClient(Request $request){
        $t = new GermanBankVerification();
        if($request->has('methode')){
            $methode = $request->input('methode');
            switch ($methode) {
                case 'getBanks':
                    /**
                     * Returns object with all banks associated with the Bankleitzahl
                     */
                    if ( $request->input('blz') != null ){
                        $data = $t->getBanksByBLZ($request->input('blz'));
                        return response()->json($data);
                    }
                    else{
                        return response()->json(['msg' => 'Bankleitzahl not submited, for this methode, please submit Bankleitzahl as [blz]']);
                    }
                    break;
                case 'suggestBanks':
                    /**
                     * Returns object with all the banks like the Bankleitzahl
                     * It should have 4 or more characters
                     */
                    if ( $request->input('blz') != null and strlen($request->input('blz')) > 3){
                        $data = $t->suggestBanks($request->input('blz'));
                        return response()->json($data);
                    }
                    else{
                        return response()->json(['msg' => 'Bankleitzahl not submited, for this methode, please submit a partial or full Bankleitzahl as [blz]']);
                    }
                    break;
                case 'checkBankInfo':
                    /**
                     * Depending on wether IBAN or KTO/BLZ was submited, will return object with
                     * the response that includes all related information and verification info
                    */
                    if($request->input('kto') != null and $request->input('blz') != null){
                        $data = $t->checkKtoBlz($request->input('kto'), $request->input('blz'));
                        return response()->json($data);
                    }
                    elseif ($request->input('iban') != null){
                        $data = $t->checkIban($request->input('iban'));
                        return response()->json($data);
                    }
                    else{
                        return response()->json(['msg' => 'Neither [iban] or Combination [kto, blz] were submited, please submit [iban], or [kto, blz]']);
                    }
                    break;
                case null:
                    $data = array(
                        'availableMethods' => [ 'getBanks', 'suggestBanks', 'checkBankInfo' ]
                    );
                    return response()->json($data);
                    break;
                default:
                    /**
                     * This will return a generic response since non of the methodes were submited
                     */
                    $data = array(
                        'availableMethods' => [ 'getBanks', 'suggestBanks', 'checkBankInfo' ]
                    );
                    return response()->json($data);
                    break;
            }
        }
    }
}
