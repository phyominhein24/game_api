<?php

namespace App\Http\Controllers;

use App\Http\Requests\MemberStoreRequest;
use App\Http\Requests\MemberUpdateRequest;
use App\Http\Requests\getBalanceRequest;
use App\Http\Requests\gameLaunchRequest;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        DB::beginTransaction();
        try {
    
            $members = Member::sortingQuery()
                ->searchQuery()
                ->filterQuery()
                ->filterDateQuery()
                ->paginationQuery();
    
            $members->transform(function ($member) {
                $member->created_by = $member->created_by ? User::find($member->created_by)->name : "Unknown";
                $member->updated_by = $member->updated_by ? User::find($member->updated_by)->name : "Unknown";
                $member->deleted_by = $member->deleted_by ? User::find($member->deleted_by)->name : "Unknown";
                return $member;
            });
            DB::commit();
            return $this->success('members retrived successfully', $members);
        } catch (Exception $e) {
            DB::rollback();
            return $this->internalServerError();
        }
    }

    // Log::info($apiUrl . '?' . http_build_query($queryParams));
    public function store(MemberStoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $name = $request->input('name');

            $operatorCode = env('OPERATOR_CODE');
            $secretKey    = env('SECRET_KEY');

            $apiUrl = env('API_URL') . '/getBalance.aspx';

            $signatureString = $operatorCode . $name . $secretKey;
            $signature = strtoupper(md5($signatureString));

            $queryParams = [
                'operatorcode' => $operatorCode,
                'username'     => $name,
                'signature'    => $signature,
            ];

            $response = Http::get($apiUrl, $queryParams);
            $apiResult = $response->json();

            if (isset($apiResult['errCode']) && $apiResult['errCode'] === '0') {
                $payload = collect($request->validated());

                // if ($payload->has('password')) {
                //     $payload['password'] = bcrypt($payload['password']);
                // }

                $member = Member::create($payload->toArray());
                DB::commit();

                return $this->success('Member created successfully', $member);
            } else {
                DB::rollBack();
                return response()->json([
                    'error'   => 'Failed to create member in remote system',
                    'details' => $apiResult
                ], 400);
            }
        } catch (ConnectionException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Unable to connect to API: ' . $e->getMessage()], 500);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Unexpected error: ' . $e->getMessage()], 500);
        }
    }

    public function getBalance(getBalanceRequest $request)
    {
        $operatorCode = env('OPERATOR_CODE');
        $secretKey = env('SECRET_KEY');
        $providerCode = $request->input('providercode');
        $username = $request->input('username');
        $password = $request->input('password');

        $signatureString = $operatorCode . $password . $providerCode . $username . $secretKey;

        $signature = strtoupper(md5($signatureString));

        $apiUrl = env('API_URL') . '/getBalance.aspx';
        
        $queryParams = [
            'operatorcode' => $operatorCode,
            'providercode' => $providerCode,
            'username' => $username,
            'password' => $password,
            'signature' => $signature
        ];

        $response = Http::get($apiUrl, $queryParams);
        $data = $response->json();

        if (isset($data['errCode']) && $data['errCode'] === '0') {
            return response()->json([
                'balance' => $data['balance']
            ]);
        }

        return response()->json([
            'error' => $data['errMsg'] ?? 'Unknown error',
            'code' => $data['errCode'] ?? 'UNKNOWN'
        ], 400);
    }

    public function getGameList(Request $request)
    {   
        $operatorCode = env('OPERATOR_CODE');    
        $secretKey    = env('SECRET_KEY'); 
        $providerCode = $request->input('providercode', 'PG');

        $signatureString = strtolower($operatorCode) . strtoupper($providerCode) . $secretKey;
        $signature = strtoupper(md5($signatureString));

        $apiUrl = env('API_URL') . '/getGameList.aspx';

        $queryParams = [
            'operatorcode'  => $operatorCode,
            'providercode'  => $providerCode,
            'lang'          => 'en',
            'html5'         => '0',
            'reformatjson'  => 'yes',
            'signature'     => $signature,
        ];

        $response = Http::get($apiUrl, $queryParams);
        Log::info($apiUrl . '?' . http_build_query($queryParams));
        $data = $response->json();

        if (isset($data['gamelist'])) {
            $games = json_decode($data['gamelist'], true);
    
            return response()->json([
                "message" => "Game List retrieved successfully",
                "data" => $games,
                "total" => count($games)
            ]);
        }

        return response()->json([
            'error'   => $data['errMsg'] ?? 'Unknown error',
            'errCode' => $data['errCode'] ?? 'UNKNOWN',
        ], 400);
    }

    public function launchGame(gameLaunchRequest $request)
    {
        $apiUrl       = env('API_URL');
        $operatorCode = env('OPERATOR_CODE');
        $secretKey    = env('SECRET_KEY');

        $username      = strtolower($request->input('username')); 
        $password      = $request->input('password');
        $providerCode  = $request->input('providercode', 'PG');
        $type          = $request->input('typef', 'SL'); 
        $gameId        = $request->input('gameid', '0'); 
        $lang          = $request->input('lang', 'en-US'); 
        $html5         = $request->input('html5', '1'); 
        $blimit        = $request->input('blimit', 'AG'); 

    
        $signatureString = $operatorCode . $password . $providerCode . $type . $username . $secretKey;
        $signature = strtoupper(md5($signatureString));

        $queryParams = [
            'operatorcode' => $operatorCode,
            'providercode' => $providerCode,
            'username'     => $username,
            'password'     => $password,
            'type'         => $type,
            'gameid'       => $gameId,
            'lang'         => $lang,
            'html5'        => $html5,
            'signature'    => $signature,
        ];

        if ($blimit) {
            $queryParams['blimit'] = $blimit;
        }

        $fullUrl = $apiUrl . '/launchGames.aspx';

        $response = Http::get($fullUrl, $queryParams);
        $data = $response->json();

        if (isset($data['errCode']) && $data['errCode'] === "0") {
            return response()->json([
                "status" => 200,
                "message" => "Game Launch successfully",
                "data" => $data['gameUrl']
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $data['errMsg'] ?? 'Unknown error',
        ], 400);
    }


    public function show($id)
    {
        DB::beginTransaction();
        try {
            $member = Member::findOrFail($id);
            DB::commit();
            return $this->success('member retrived successfully by id', $member);
        } catch (Exception $e) {
            DB::rollback();
            return $this->internalServerError();
        }
    }

    public function update(MemberUpdateRequest $request, $id)
    {
        DB::beginTransaction();
        $payload = collect($request->validated());
        try {
            $member = Member::findOrFail($id);
            $member->update($payload->toArray());
            DB::commit();
            return $this->success('member updated successfully by id', $member);
        } catch (Exception $e) {
            DB::rollback();
            return $this->internalServerError();
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $member = Member::findOrFail($id);
            $member->forceDelete();
            DB::commit();
            return $this->success('member deleted successfully by id', []);
        } catch (Exception $e) {
            DB::rollback();
            return $this->internalServerError();
        }
    }
}